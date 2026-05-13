<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Audit\AuditLogger;
use App\Models\PortalCliente;
use App\Models\ContaReceber;
use App\Models\ContaReceberAnexo;
use App\Models\Integracao;
use App\Models\Cliente;
use App\Services\AsaasService;
use App\Services\CoraService;

/**
 * Controller de Contas a Pagar do Portal do Cliente.
 *
 * Fluxo de pagamento:
 *  - PIX      → exibe QR Code + código copia-e-cola na tela
 *  - Boleto   → redireciona para URL do boleto no Asaas
 *  - Checkout → JSON com link para o JS abrir em nova aba (target=_blank)
 *  - Manual   → exibe mensagem de contato
 *
 * Polling de status:
 *  - GET /portal/contas-a-pagar/status/{id}  → JSON leve (só lê BD)
 *  - GET /portal/contas-a-pagar/sync/{id}    → JSON completo (consulta Asaas + atualiza BD)
 *  - GET /portal/contas-a-pagar/link/{id}    → JSON com URL de pagamento para abrir em nova aba
 */
class PortalContasPagarController extends Controller
{
    private PortalCliente      $portalModel;
    private ContaReceber       $contaModel;
    private ContaReceberAnexo  $anexoModel;
    private Cliente            $clienteModel;
    private Logger             $logger;

    public function __construct()
    {
        $this->portalModel  = new PortalCliente();
        $this->contaModel   = new ContaReceber();
        $this->anexoModel   = new ContaReceberAnexo();
        $this->clienteModel = new Cliente();
        $this->logger       = new Logger();
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------
    private function getPortalCliente(): object
    {
        $id     = (int) ($_SESSION['portal_cliente_id'] ?? 0);
        $portal = $this->portalModel->findById($id);
        if (!$portal) {
            session_unset();
            header('Location: /login?error=sessao_expirada');
            exit();
        }
        return $portal;
    }

    private function getAsaasService(int $tenantId): ?AsaasService
    {
        $integracaoModel = new Integracao();

        // 1º tenta buscar a integração pelo tenantId específico do cliente
        $config = $integracaoModel->findByProvider('asaas', $tenantId);

        // 2º fallback: sistema single-tenant — busca qualquer integração Asaas ativa.
        // Ocorre quando o cliente foi cadastrado por um sub-usuário diferente do admin
        // que configurou a integração Asaas, ou quando tenantId = 0.
        if (!$config || empty($config->api_key)) {
            $rowFallback = $integracaoModel->findByProviderAtivo('asaas');
            if ($rowFallback) {
                $config = $integracaoModel->findByProvider('asaas', (int) $rowFallback->usuario_id);
            }
        }

        if (!$config || empty($config->api_key)) {
            $this->logger->error('[Portal] Integração Asaas não encontrada ou inativa', [
                'tenant_id' => $tenantId,
            ]);
            return null;
        }

        // Normaliza status: findByProvider() retorna 'active'/'inactive',
        // mas findByProviderAtivo() pode retornar 'ativo'/'inativo' (valor bruto do banco).
        $statusNorm = strtolower($config->status ?? 'ativo');
        if (in_array($statusNorm, ['inactive', 'inativo'], true)) {
            $this->logger->error('[Portal] Integração Asaas está inativa', [
                'tenant_id' => $tenantId,
                'status'    => $config->status,
            ]);
            return null;
        }

        return new AsaasService($config->api_key, $config->environment ?? 'sandbox');
    }

    // ---------------------------------------------------------------
    // GET /portal/contas-a-pagar/status/{id}
    // Polling leve: apenas lê o banco (sem chamar Asaas)
    // ---------------------------------------------------------------
    public function statusCheck(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;
        $conta = $this->contaModel->findById($id);
        if (!$conta || (int) $conta->cliente_id !== $clienteId) {
            echo json_encode(['status' => 'error', 'message' => 'nao_autorizado']);
            exit();
        }
        $pago = ($conta->status === 'recebida');
        echo json_encode([
            'status'           => $conta->status,
            'conta_id'         => $id,
            'pago'             => $pago,
            'data_recebimento' => $conta->data_recebimento ?? null,
        ]);
        exit();
    }

    // ---------------------------------------------------------------
    // GET /portal/contas-a-pagar/sync/{id}
    // Polling completo: consulta Asaas e atualiza BD antes de responder
    // ---------------------------------------------------------------
    public function syncStatus(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;
        $tenantId  = (int) $portal->tenant_id;
        $conta = $this->contaModel->findById($id);
        if (!$conta || (int) $conta->cliente_id !== $clienteId) {
            echo json_encode(['status' => 'error', 'message' => 'nao_autorizado']);
            exit();
        }
        // Só sincroniza se tiver payment_id e ainda não estiver pago
        if (!empty($conta->asaas_payment_id) && $conta->status === 'aberta') {
            try {
                $asaas = $this->getAsaasService($tenantId);
                if ($asaas) {
                    $response = $asaas->makeRequestPublic('GET', "/payments/{$conta->asaas_payment_id}");
                    if (!empty($response['status'])) {
                        $novoStatus = AsaasService::mapearStatus($response['status']);
                        if ($novoStatus !== $conta->status) {
                            $dataRec = null;
                            foreach (['paymentDate', 'confirmedDate', 'clientPaymentDate'] as $field) {
                                if (!empty($response[$field])) {
                                    $dataRec = substr((string)$response[$field], 0, 10);
                                    break;
                                }
                            }
                            $this->contaModel->update((int) $conta->id, [
                                'status'           => $novoStatus,
                                'data_recebimento' => $dataRec ?: ($novoStatus === 'recebida' ? date('Y-m-d') : null),
                                'meio_pagamento'   => AsaasService::mapearMeioPagamento($response['billingType'] ?? 'UNDEFINED'),
                            ]);
                            AuditLogger::log('portal_sync_status_atualizado', [
                                'conta_id'         => $id,
                                'status_anterior'  => $conta->status,
                                'status_novo'      => $novoStatus,
                                'asaas_payment_id' => $conta->asaas_payment_id,
                            ]);
                            $this->logger->info('[Portal] Sync: status atualizado via polling', [
                                'conta_id'   => $id,
                                'status_old' => $conta->status,
                                'status_new' => $novoStatus,
                            ]);
                            $conta = $this->contaModel->findById($id);
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->warning('[Portal] Sync: erro ao consultar Asaas', [
                    'conta_id' => $id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }
        $pago = ($conta->status === 'recebida');
        echo json_encode([
            'status'           => $conta->status,
            'conta_id'         => $id,
            'pago'             => $pago,
            'data_recebimento' => $conta->data_recebimento ?? null,
        ]);
        exit();
    }

    // ---------------------------------------------------------------
    // GET /portal/contas-a-pagar/link/{id}
    // Retorna JSON com URL de pagamento para abrir em nova aba via JS
    // ---------------------------------------------------------------
    public function getLink(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;
        $tenantId  = (int) $portal->tenant_id;
        $conta = $this->contaModel->findById($id);
        if (!$conta || (int) $conta->cliente_id !== $clienteId) {
            echo json_encode(['success' => false, 'error' => 'nao_autorizado']);
            exit();
        }
        if ($conta->status === 'recebida') {
            echo json_encode(['success' => false, 'error' => 'ja_pago', 'status' => $conta->status]);
            exit();
        }
        if ($conta->status === 'cancelada') {
            echo json_encode(['success' => false, 'error' => 'cancelada']);
            exit();
        }
        $asaas = $this->getAsaasService($tenantId);
        if (!$asaas) {
            echo json_encode(['success' => false, 'error' => 'pagamento_indisponivel']);
            exit();
        }
        try {
            if (empty($conta->asaas_payment_id)) {
                $conta = $this->gerarPagamentoAsaas($conta, $portal, $asaas);
            }
            $meio = strtolower((string)($conta->meio_pagamento ?? 'checkout'));
            if ($meio === 'pix') {
                echo json_encode([
                    'success'  => true,
                    'tipo'     => 'redirect',
                    'url'      => "/portal/contas-a-pagar/pagar/{$id}",
                    'conta_id' => $id,
                ]);
                exit();
            }
            if ($meio === 'boleto') {
                $url = $asaas->getBoletoUrl((string) $conta->asaas_payment_id);
                if (empty($url)) {
                    echo json_encode(['success' => false, 'error' => 'boleto_indisponivel']);
                    exit();
                }
                echo json_encode(['success' => true, 'tipo' => 'nova_aba', 'url' => $url, 'conta_id' => $id]);
                exit();
            }
            // Checkout (padrão)
            $link = $asaas->getLinkPagamento((string) $conta->asaas_payment_id);
            if (empty($link)) {
                echo json_encode(['success' => false, 'error' => 'link_indisponivel']);
                exit();
            }
            echo json_encode(['success' => true, 'tipo' => 'nova_aba', 'url' => $link, 'conta_id' => $id]);
            exit();
        } catch (\InvalidArgumentException $e) {
            echo json_encode(['success' => false, 'error' => 'valor_minimo', 'mensagem' => $e->getMessage()]);
            exit();
        } catch (\Throwable $e) {
            $this->logger->error('[Portal] getLink: erro', ['conta_id' => $id, 'error' => $e->getMessage()]);
            echo json_encode(['success' => false, 'error' => 'erro_pagamento']);
            exit();
        }
    }

    // ---------------------------------------------------------------
    // GET /portal/contas-a-pagar
    // ---------------------------------------------------------------
    public function index(): void
    {
        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;
        $tenantId  = (int) $portal->tenant_id;
        $this->logger->info('[Portal] Minhas Contas acessado', [
            'portal_id'  => $portal->id,
            'cliente_id' => $clienteId,
        ]);
        // Busca TODAS as contas do cliente neste tenant
        $todasContas = $this->contaModel->findByClienteIdAndTenantId($clienteId, $tenantId);

        // Correcao de consistencia: se data_recebimento esta preenchida mas status ainda e 'aberta',
        // corrigir no banco. Isso ocorre quando o webhook falhou (ex.: retornava 403) mas o
        // pagamento foi confirmado por outro caminho (polling manual, Asaas sandbox, etc.).
        foreach ($todasContas as $conta) {
            if ($conta->status === 'aberta' && !empty($conta->data_recebimento)) {
                try {
                    $this->contaModel->update((int)$conta->id, ['status' => 'recebida']);
                    $conta->status = 'recebida';
                    $this->logger->info('[Portal] Correcao de consistencia: status corrigido para recebida', [
                        'conta_id'         => (int)$conta->id,
                        'data_recebimento' => $conta->data_recebimento,
                    ]);
                } catch (\Throwable $e) {
                    $this->logger->error('[Portal] Falha ao corrigir status de consistencia', [
                        'conta_id' => (int)$conta->id,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }
        }

        // Separa em grupos
        $contasAbertas    = array_values(array_filter($todasContas, fn($c) => $c->status === 'aberta'));
        $contasRecebidas  = array_values(array_filter($todasContas, fn($c) => $c->status === 'recebida'));
        $contasCanceladas = array_values(array_filter($todasContas, fn($c) => $c->status === 'cancelada'));
        $hoje             = date('Y-m-d');
        $contasVencidas   = array_values(array_filter($contasAbertas, fn($c) => $c->data_vencimento < $hoje));
        // Totalizadores
        $totalAbertas       = count($contasAbertas);
        $totalRecebidas     = count($contasRecebidas);
        $totalCanceladas    = count($contasCanceladas);
        $totalVencidas      = count($contasVencidas);
        $totalValorAberto   = array_sum(array_map(fn($c) => (float)$c->valor, $contasAbertas));
        $totalValorRecebido = array_sum(array_map(fn($c) => (float)$c->valor, $contasRecebidas));
        $totalValorTotal    = $totalValorAberto + $totalValorRecebido;
        // Aba ativa via GET
        $abaAtiva = $_GET['aba'] ?? 'abertas';
        if (!in_array($abaAtiva, ['abertas', 'pagas', 'canceladas'], true)) {
            $abaAtiva = 'abertas';
        }
        // Seleciona contas a exibir conforme aba
        $contasExibir = match ($abaAtiva) {
            'pagas'     => $contasRecebidas,
            'canceladas'=> $contasCanceladas,
            default     => $contasAbertas,
        };
        // Carrega anexos de cada conta exibida
        foreach ($contasExibir as $conta) {
            try {
                $conta->anexos = $this->anexoModel->findByContaId((int)$conta->id, $tenantId);
            } catch (\Throwable $e) {
                $conta->anexos = [];
            }
        }
        // Verifica se o Asaas está configurado para este tenant
        $asaasEnabled = false;
        try {
            $asaas = $this->getAsaasService($tenantId);
            $asaasEnabled = ($asaas !== null);
        } catch (\Throwable $e) {
            $asaasEnabled = false;
        }
        // Monta mapa de NFs emitidas por conta_receber_id (para botão Emitir NF-s)
        $nfsPorConta = [];
        if ($abaAtiva === 'pagas' && !empty($contasRecebidas)) {
            try {
                $notaFiscalModel = new \App\Models\NotaFiscal();
                foreach ($contasRecebidas as $cr) {
                    $nf = $notaFiscalModel->findByContaReceberId((int)$cr->id, $tenantId);
                    if ($nf) {
                        $nfsPorConta[(int)$cr->id] = $nf;
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->warning('[Portal] Erro ao carregar NFs por conta: ' . $e->getMessage());
            }
        }
        View::render('portal/contas-a-pagar/index', [
            'title'              => 'Minhas Contas',
            '_layout'            => 'portal',
            'portal'             => $portal,
            'contas'             => $contasExibir,
            'contasAbertas'      => $contasAbertas,
            'contasRecebidas'    => $contasRecebidas,
            'contasCanceladas'   => $contasCanceladas,
            'totalAbertas'       => $totalAbertas,
            'totalRecebidas'     => $totalRecebidas,
            'totalCanceladas'    => $totalCanceladas,
            'totalVencidas'      => $totalVencidas,
            'totalValorAberto'   => $totalValorAberto,
            'totalValorRecebido' => $totalValorRecebido,
            'totalValorTotal'    => $totalValorTotal,
            'abaAtiva'           => $abaAtiva,
            'asaasEnabled'       => $asaasEnabled,
            'nfsPorConta'        => $nfsPorConta,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /portal/contas-a-pagar/pagar/{id}
    // Mantido para compatibilidade (PIX usa redirect direto)
    // ---------------------------------------------------------------
    public function pagar(int $id): void
    {
        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;
        $tenantId  = (int) $portal->tenant_id;
        $conta = $this->contaModel->findById($id);
        if (!$conta || (int) $conta->cliente_id !== $clienteId) {
            header('Location: /portal/contas-a-pagar?error=nao_autorizado');
            exit();
        }
        if ($conta->status === 'recebida') {
            header('Location: /portal/contas-a-pagar?aba=pagas&info=ja_pago');
            exit();
        }
        if ($conta->status === 'cancelada') {
            header('Location: /portal/contas-a-pagar?error=cancelada');
            exit();
        }
        $meio = strtolower((string)($conta->meio_pagamento ?? ''));
        $meiosManuais = ['dinheiro', 'transferencia', 'outro', ''];
        if (in_array($meio, $meiosManuais, true)) {
            View::render('portal/contas-a-pagar/pagamento-manual', [
                'title'   => 'Pagamento',
                '_layout' => 'portal',
                'portal'  => $portal,
                'conta'   => $conta,
            ]);
            return;
        }

        // ── Fluxo Cora (boleto_cora) ──────────────────────────────────
        if ($meio === 'boleto_cora') {
            $cora = $this->getCoraService($tenantId);
            if (!$cora) {
                header('Location: /portal/contas-a-pagar?error=pagamento_indisponivel');
                exit();
            }
            try {
                // Gera boleto Cora se ainda não existir
                if (empty($conta->cora_invoice_id)) {
                    $conta = $this->gerarBoletoCora($conta, $portal, $cora);
                }
                // Redireciona para URL do boleto
                $boletoUrl = $conta->cora_boleto_url ?? '';
                if (empty($boletoUrl)) {
                    // Tenta buscar novamente via API
                    $invoice = $cora->consultarFatura((string) $conta->cora_invoice_id);
                    $boletoUrl = $invoice['bank_slip']['url_slip'] ?? $invoice['bank_slip']['url_slip_pdf'] ?? '';
                }
                if (empty($boletoUrl)) {
                    header('Location: /portal/contas-a-pagar?error=boleto_indisponivel');
                    exit();
                }
                AuditLogger::log('portal_pagamento_cora_iniciado', [
                    'portal_id'       => $portal->id,
                    'cliente_id'      => $clienteId,
                    'conta_id'        => $id,
                    'cora_invoice_id' => $conta->cora_invoice_id,
                ]);
                header("Location: {$boletoUrl}");
                exit();
            } catch (\Throwable $e) {
                $this->logger->error('[Portal] Erro ao processar boleto Cora: ' . $e->getMessage(), [
                    'conta_id' => $id, 'trace' => $e->getTraceAsString(),
                ]);
                header('Location: /portal/contas-a-pagar?error=erro_pagamento');
                exit();
            }
        }

        // ── Fluxo Asaas (padrão) ──────────────────────────────────────
        $asaas = $this->getAsaasService($tenantId);
        if (!$asaas) {
            header('Location: /portal/contas-a-pagar?error=pagamento_indisponivel');
            exit();
        }
        if (empty($conta->asaas_payment_id)) {
            try {
                $conta = $this->gerarPagamentoAsaas($conta, $portal, $asaas);
            } catch (\InvalidArgumentException $e) {
                $asaas->logAsaas('error', '[Portal] Valor abaixo do mínimo Asaas', [
                    'conta_id' => $id, 'valor' => $conta->valor, 'error' => $e->getMessage(),
                ]);
                $errorMsg = urlencode($e->getMessage());
                header("Location: /portal/contas-a-pagar?error=valor_minimo&msg={$errorMsg}");
                exit();
            } catch (\Exception $e) {
                $this->logger->error('[Portal] Falha ao gerar pagamento on-the-fly', [
                    'conta_id' => $id, 'error' => $e->getMessage(),
                ]);
                header('Location: /portal/contas-a-pagar?error=link_indisponivel');
                exit();
            }
        }
        try {
            AuditLogger::log('portal_pagamento_iniciado', [
                'portal_id' => $portal->id, 'cliente_id' => $clienteId,
                'conta_id'  => $id, 'asaas_payment_id' => $conta->asaas_payment_id,
                'meio'      => $meio,
            ]);
            switch ($meio) {
                case 'pix':
                    $pixData = $asaas->getPixQrCode((string) $conta->asaas_payment_id);
                    if (empty($pixData['encodedImage'])) {
                        header('Location: /portal/contas-a-pagar?error=pix_indisponivel');
                        exit();
                    }
                    View::render('portal/contas-a-pagar/pagar-pix', [
                        'title'          => 'Pagar com PIX',
                        '_layout'        => 'portal',
                        'portal'         => $portal,
                        'conta'          => $conta,
                        'pixEncodedImage'=> $pixData['encodedImage'],
                        'pixPayload'     => $pixData['payload'] ?? '',
                        'pixExpiracao'   => $pixData['expirationDate'] ?? '',
                    ]);
                    return;
                case 'boleto':
                    $boletoUrl = $asaas->getBoletoUrl((string) $conta->asaas_payment_id);
                    if (empty($boletoUrl)) {
                        header('Location: /portal/contas-a-pagar?error=boleto_indisponivel');
                        exit();
                    }
                    header("Location: {$boletoUrl}");
                    exit();
                case 'checkout':
                default:
                    $link = $asaas->getLinkPagamento((string) $conta->asaas_payment_id);
                    if (empty($link)) {
                        header('Location: /portal/contas-a-pagar?error=link_indisponivel');
                        exit();
                    }
                    header("Location: {$link}");
                    exit();
            }
        } catch (\Throwable $e) {
            $this->logger->error('[Portal] Erro ao processar pagamento: ' . $e->getMessage(), [
                'conta_id' => $id, 'trace' => $e->getTraceAsString(),
            ]);
            header('Location: /portal/contas-a-pagar?error=erro_pagamento');
            exit();
        }
    }

    /**
     * Gera um boleto Cora on-the-fly para contas que ainda não possuem.
     */
    private function gerarBoletoCora(object $conta, object $portal, CoraService $cora): object
    {
        $cpfCnpj = preg_replace('/\D/', '', $portal->cpf_cnpj ?? '');
        $nome    = $portal->razao_social ?? $portal->nome_fantasia ?? '';
        $email   = $portal->email_principal ?? '';

        $dadosFatura = [
            'code'            => 'CR-' . $conta->id . '-' . time(),
            'customer'        => [
                'name'     => $nome,
                'email'    => $email,
                'document' => [
                    'identity' => $cpfCnpj,
                    'type'     => strlen($cpfCnpj) === 14 ? 'CNPJ' : 'CPF',
                ],
                'address'  => [
                    'street'       => $portal->endereco ?? '',
                    'number'       => $portal->numero ?? 'S/N',
                    'complement'   => $portal->complemento ?? '',
                    'district'     => $portal->bairro ?? '',
                    'city'         => $portal->cidade ?? '',
                    'state'        => $portal->estado ?? '',
                    'zip_code'     => preg_replace('/\D/', '', $portal->cep ?? ''),
                    'country'      => 'BR',
                ],
            ],
            'payment_terms'   => [
                'due_date' => date('Y-m-d', strtotime($conta->data_vencimento)),
            ],
            'payment_forms'   => [['type' => 'BANK_SLIP']],
            'services'        => [
                [
                    'name'     => $conta->descricao,
                    'amount'   => 1,
                    'unit_value' => (float) $conta->valor,
                ],
            ],
        ];

        $fatura = $cora->emitirFatura($dadosFatura);

        $invoiceId = $fatura['id'] ?? '';
        $boletoUrl = $fatura['bank_slip']['url_slip']     ??
                     $fatura['bank_slip']['url_slip_pdf'] ?? '';
        $pixQrcode = $fatura['pix']['qr_code']            ?? '';

        $this->contaModel->update((int) $conta->id, [
            'cora_invoice_id' => $invoiceId,
            'cora_boleto_url' => $boletoUrl,
            'cora_pix_qrcode' => $pixQrcode,
            'external_reference' => 'cora:' . $invoiceId,
        ]);

        return $this->contaModel->findById((int) $conta->id);
    }

    // ---------------------------------------------------------------
    // Download de anexo
    // ---------------------------------------------------------------
    public function downloadAnexo(int $id): void
    {
        try {
            $portal    = $this->getPortalCliente();
            $clienteId = (int) $portal->cliente_id;
            $tenantId  = (int) $portal->tenant_id;
            $anexo = $this->anexoModel->findById($id);
            if (!$anexo || (int) $anexo->usuario_id !== $tenantId) {
                http_response_code(403); echo '403 - Acesso Negado'; exit();
            }
            $conta = $this->contaModel->findById((int) $anexo->conta_receber_id);
            if (!$conta || (int) $conta->cliente_id !== $clienteId) {
                http_response_code(403); echo '403 - Acesso Negado (Conta Inválida)'; exit();
            }
            $fileRel = (string)($anexo->file_path ?? '');
            $fileAbs = BASE_PATH . '/' . ltrim($fileRel, '/');
            if (!is_file($fileAbs)) {
                http_response_code(404); echo '404 - Arquivo não encontrado'; exit();
            }
            $mime = $anexo->mime_type ?? 'application/octet-stream';
            $name = $anexo->original_name ?? basename($fileAbs);
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($fileAbs));
            header('Content-Disposition: attachment; filename="' . addslashes($name) . '"');
            readfile($fileAbs);
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[Portal] Erro ao baixar anexo: ' . $e->getMessage());
            http_response_code(500); echo 'Erro ao baixar arquivo'; exit();
        }
    }

    // ---------------------------------------------------------------
    // Helper: obtém CoraService configurado
    // ---------------------------------------------------------------
    private function getCoraService(int $tenantId): ?CoraService
    {
        $integracaoModel = new Integracao();

        // 1º tenta buscar a integração Cora pelo tenantId específico
        $config = $integracaoModel->findByProvider('cora', $tenantId);

        // 2º fallback: busca qualquer integração Cora ativa (single-tenant)
        if (!$config || empty($config->api_key)) {
            $rowFallback = $integracaoModel->findByProviderAtivo('cora');
            if ($rowFallback) {
                $config = $integracaoModel->findByProvider('cora', (int) $rowFallback->usuario_id);
            }
        }

        if (!$config || $config->status !== 'active' || empty($config->api_key)) {
            return null;
        }

        // Monta caminhos dos certificados
        $certDir    = BASE_PATH . '/storage/cora/certs/';
        $configData = json_decode($config->config_json ?? '{}', true) ?? [];
        $certPath   = $certDir . ($configData['cert_file']    ?? '');
        $keyPath    = $certDir . ($configData['key_file']     ?? '');
        $chainPath  = !empty($configData['chain_file']) ? $certDir . $configData['chain_file'] : null;

        try {
            return new CoraService(
                $config->api_key,
                $certPath,
                $keyPath,
                $chainPath,
                $config->environment ?? 'production'
            );
        } catch (\Throwable $e) {
            $this->logger->error('[Portal] Falha ao instanciar CoraService: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Gera uma cobrança no Asaas on-the-fly para contas que ainda não possuem.
     */
    private function gerarPagamentoAsaas(object $conta, object $portal, AsaasService $asaas): object
    {
        $documento    = AsaasService::formatarDocumento($portal->cpf_cnpj ?? '');
        $asaasCliente = $asaas->buscarCliente($documento, $portal->email_principal ?? null);

        // Montar dados completos do cliente (com endereço para NFS-e)
        $dadosCliente = [
            'name'    => $portal->razao_social ?? $portal->nome_fantasia ?? '',
            'email'   => $portal->email_principal ?? '',
            'phone'   => $portal->telefone ?? $portal->celular ?? '',
            'cpfCnpj' => $documento,
        ];
        if (!empty($portal->cep)) {
            $cepLimpo = preg_replace('/\D/', '', $portal->cep);
            $dadosCliente['postalCode']    = $cepLimpo;
            $dadosCliente['address']       = $portal->endereco ?? '';
            $dadosCliente['addressNumber'] = $portal->numero ?? 'S/N';
            $dadosCliente['complement']    = $portal->complemento ?? '';
            $dadosCliente['province']      = $portal->bairro ?? '';
            $dadosCliente['city']          = $portal->cidade ?? '';
            $dadosCliente['state']         = $portal->estado ?? '';
        }

        if (!$asaasCliente) {
            $asaasCliente = $asaas->criarCliente($dadosCliente);
        } else {
            // Atualizar endereço do cliente existente (necessário para NFS-e)
            try {
                $asaas->atualizarCliente($asaasCliente['id'], $dadosCliente);
            } catch (\Exception $e) {
                // Não bloquear o pagamento se a atualização falhar
            }
        }
        $dadosBase = [
            'customer'          => $asaasCliente['id'],
            'value'             => (float) $conta->valor,
            'dueDate'           => date('Y-m-d', strtotime($conta->data_vencimento)),
            'description'       => $conta->descricao,
            'externalReference' => "u:{$portal->tenant_id}|cr:{$conta->id}",
            'postalService'     => false,
        ];
        $cobranca = $asaas->criarCheckout($dadosBase);
        $this->contaModel->update((int) $conta->id, [
            'asaas_payment_id'   => $cobranca['id'],
            'external_reference' => $dadosBase['externalReference'],
            'meio_pagamento'     => 'checkout',
            'status'             => AsaasService::mapearStatus($cobranca['status'] ?? 'PENDING'),
        ]);
        return $this->contaModel->findById((int) $conta->id);
    }
}
