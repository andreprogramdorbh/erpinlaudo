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

/**
 * Controller de Contas a Pagar do Portal do Cliente.
 *
 * Fluxo de pagamento por meio:
 *  - PIX      → exibe QR Code + código copia-e-cola na tela
 *  - Boleto   → redireciona para URL do boleto no Asaas
 *  - Checkout → redireciona para invoiceUrl (cliente escolhe o meio no Asaas)
 *  - Manual   → exibe mensagem de contato
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

    /**
     * Retorna a instância do AsaasService configurada para o tenant (vendedor).
     */
    private function getAsaasService(int $tenantId): ?AsaasService
    {
        $integracaoModel = new Integracao();
        $config = $integracaoModel->findByProvider('asaas', $tenantId);

        if (!$config || $config->status !== 'active' || empty($config->api_key)) {
            return null;
        }

        return new AsaasService($config->api_key, $config->environment ?? 'sandbox');
    }

    // ---------------------------------------------------------------
    // GET /portal/contas-a-pagar/status/{id}  — polling para PIX
    // ---------------------------------------------------------------
    public function statusCheck(int $id): void
    {
        header('Content-Type: application/json');

        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;

        $conta = $this->contaModel->findById($id);

        if (!$conta || (int) $conta->cliente_id !== $clienteId) {
            echo json_encode(['status' => 'error', 'message' => 'nao_autorizado']);
            exit();
        }

        echo json_encode([
            'status'  => $conta->status,
            'conta_id'=> $id,
        ]);
        exit();
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

        // Filtro de status via GET
        $statusFiltro  = $_GET['status'] ?? '';
        $statusValidos = ['', 'aberta', 'recebida', 'cancelada'];
        if (!in_array($statusFiltro, $statusValidos, true)) {
            $statusFiltro = '';
        }

        // Busca todas as contas do cliente neste tenant
        $todasContas = $this->contaModel->findByClienteIdAndTenantId($clienteId, $tenantId);

        // Contadores para o resumo
        $totalAbertas   = count(array_filter($todasContas, fn($c) => $c->status === 'aberta'));
        $totalRecebidas = count(array_filter($todasContas, fn($c) => $c->status === 'recebida'));
        $totalValorAberto = array_sum(array_map(
            fn($c) => $c->status === 'aberta' ? (float)$c->valor : 0,
            $todasContas
        ));

        // Aplica filtro de status se solicitado
        if ($statusFiltro !== '') {
            $contas = array_values(array_filter($todasContas, fn($c) => $c->status === $statusFiltro));
        } else {
            $contas = array_values($todasContas);
        }

        // Carrega anexos de cada conta
        foreach ($contas as $conta) {
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

        View::render('portal/contas-a-pagar/index', [
            'title'           => 'Minhas Contas',
            '_layout'         => 'portal',
            'portal'          => $portal,
            'contas'          => $contas,
            'totalAbertas'    => $totalAbertas,
            'totalRecebidas'  => $totalRecebidas,
            'totalValorAberto'=> $totalValorAberto,
            'statusFiltro'    => $statusFiltro,
            'asaasEnabled'    => $asaasEnabled,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /portal/contas-a-pagar/pagar/{id}
    // Redireciona ou exibe QR Code conforme o meio de pagamento
    // ---------------------------------------------------------------
    public function pagar(int $id): void
    {
        $portal    = $this->getPortalCliente();
        $clienteId = (int) $portal->cliente_id;
        $tenantId  = (int) $portal->tenant_id;

        $conta = $this->contaModel->findById($id);

        // Segurança: a conta deve pertencer ao cliente logado
        if (!$conta || (int) $conta->cliente_id !== $clienteId) {
            $this->logger->warning('[Portal] Tentativa de acesso a conta de outro cliente', [
                'portal_id'  => $portal->id,
                'conta_id'   => $id,
                'cliente_id' => $clienteId,
            ]);
            header('Location: /portal/contas-a-pagar?error=nao_autorizado');
            exit();
        }

        if ($conta->status === 'recebida') {
            header('Location: /portal/contas-a-pagar?info=ja_pago');
            exit();
        }

        if ($conta->status === 'cancelada') {
            header('Location: /portal/contas-a-pagar?error=cancelada');
            exit();
        }

        $meio = $conta->meio_pagamento ?? '';

        // Meios manuais — sem integração Asaas
        $meiosManuais = ['dinheiro', 'transferencia', 'cartao', 'outro', ''];
        if (in_array($meio, $meiosManuais, true)) {
            View::render('portal/contas-a-pagar/pagamento-manual', [
                'title'   => 'Pagamento',
                '_layout' => 'portal',
                'portal'  => $portal,
                'conta'   => $conta,
            ]);
            return;
        }

        // Meios Asaas — requer payment_id
        $asaas = $this->getAsaasService($tenantId);

        if (!$asaas) {
            $this->logger->error('[Portal] AsaasService não configurado ou inativo', ['tenant_id' => $tenantId]);
            header('Location: /portal/contas-a-pagar?error=pagamento_indisponivel');
            exit();
        }

        // Se não tem payment_id, tenta gerar um agora (Checkout UNDEFINED)
        if (empty($conta->asaas_payment_id)) {
            try {
                $conta = $this->gerarPagamentoAsaas($conta, $portal, $asaas);
            } catch (\Exception $e) {
                $this->logger->error('[Portal] Falha ao gerar pagamento Asaas on-the-fly', [
                    'conta_id' => $id,
                    'error'    => $e->getMessage()
                ]);
                header('Location: /portal/contas-a-pagar?error=link_indisponivel');
                exit();
            }
        }

        try {
            // $asaas já foi instanciado acima via getAsaasService

            AuditLogger::log('portal_pagamento_iniciado', [
                'portal_id'        => $portal->id,
                'cliente_id'       => $clienteId,
                'conta_id'         => $id,
                'asaas_payment_id' => $conta->asaas_payment_id,
                'meio_pagamento'   => $meio,
            ]);

            switch ($meio) {

                // PIX — exibe QR Code na tela
                case 'pix':
                    $pixData = $asaas->getPixQrCode((string) $conta->asaas_payment_id);

                    if (empty($pixData['encodedImage'])) {
                        $this->logger->error('[Portal] QR Code PIX não disponível', [
                            'conta_id'         => $id,
                            'asaas_payment_id' => $conta->asaas_payment_id,
                        ]);
                        header('Location: /portal/contas-a-pagar?error=pix_indisponivel');
                        exit();
                    }

                    $this->logger->info('[Portal] QR Code PIX gerado', ['conta_id' => $id]);

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

                // Boleto — redireciona para URL do boleto
                case 'boleto':
                    $boletoUrl = $asaas->getBoletoUrl((string) $conta->asaas_payment_id);

                    if (empty($boletoUrl)) {
                        $this->logger->error('[Portal] URL do boleto não disponível', [
                            'conta_id'         => $id,
                            'asaas_payment_id' => $conta->asaas_payment_id,
                        ]);
                        header('Location: /portal/contas-a-pagar?error=boleto_indisponivel');
                        exit();
                    }

                    $this->logger->info('[Portal] Redirecionando para boleto', [
                        'conta_id' => $id,
                        'url'      => $boletoUrl,
                    ]);

                    header("Location: {$boletoUrl}");
                    exit();

                // Checkout — redireciona para invoiceUrl (cliente escolhe o meio no Asaas)
                case 'checkout':
                default:
                    $link = $asaas->getLinkPagamento((string) $conta->asaas_payment_id);

                    if (empty($link)) {
                        $this->logger->error('[Portal] Link de checkout não disponível', [
                            'conta_id'         => $id,
                            'asaas_payment_id' => $conta->asaas_payment_id,
                        ]);
                        header('Location: /portal/contas-a-pagar?error=link_indisponivel');
                        exit();
                    }

                    $this->logger->info('[Portal] Redirecionando para checkout Asaas', [
                        'conta_id' => $id,
                        'link'     => $link,
                    ]);

                    header("Location: {$link}");
                    exit();
            }

        } catch (\Throwable $e) {
            $this->logger->error('[Portal] Erro ao processar pagamento: ' . $e->getMessage(), [
                'conta_id' => $id,
                'trace'    => $e->getTraceAsString(),
            ]);
            header('Location: /portal/contas-a-pagar?error=erro_pagamento');
            exit();
        }
    }

    public function downloadAnexo(int $id): void
    {
        try {
            $portal    = $this->getPortalCliente();
            $clienteId = (int) $portal->cliente_id;
            $tenantId  = (int) $portal->tenant_id;

            $anexo = $this->anexoModel->findById($id);
            if (!$anexo || (int) $anexo->usuario_id !== $tenantId) {
                http_response_code(403);
                echo '403 - Acesso Negado';
                exit();
            }

            // Verifica se a conta vinculada a este anexo realmente pertence ao cliente
            $conta = $this->contaModel->findById((int) $anexo->conta_receber_id);
            if (!$conta || (int) $conta->cliente_id !== $clienteId) {
                http_response_code(403);
                echo '403 - Acesso Negado (Conta Inválida)';
                exit();
            }

            $fileRel = (string)($anexo->file_path ?? '');
            $fileAbs = BASE_PATH . '/' . ltrim($fileRel, '/');
            if (!is_file($fileAbs)) {
                http_response_code(404);
                echo '404 - Arquivo não encontrado';
                exit();
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
            http_response_code(500);
            echo 'Erro ao baixar arquivo';
            exit();
        }
    }

    /**
     * Gera uma cobrança no Asaas on-the-fly para contas que ainda não possuem.
     */
    private function gerarPagamentoAsaas(object $conta, object $portal, AsaasService $asaas): object
    {
        // 1. Buscar ou Criar Cliente no Asaas
        $documento = AsaasService::formatarDocumento($portal->cpf_cnpj ?? '');
        $asaasCliente = $asaas->buscarCliente($documento, $portal->email_principal ?? null);

        if (!$asaasCliente) {
            $asaasCliente = $asaas->criarCliente([
                'name'    => $portal->razao_social ?? '',
                'email'   => $portal->email_principal ?? '',
                'phone'   => $portal->telefone ?? $portal->celular ?? '',
                'cpfCnpj' => $documento,
            ]);
        }

        // 2. Criar Checkout (UNDEFINED)
        $dadosBase = [
            'customer'          => $asaasCliente['id'],
            'value'             => (float) $conta->valor,
            'dueDate'           => date('Y-m-d', strtotime($conta->data_vencimento)),
            'description'       => $conta->descricao,
            'externalReference' => "u:{$portal->tenant_id}|cr:{$conta->id}",
            'postalService'     => false,
        ];

        $cobranca = $asaas->criarCheckout($dadosBase);

        // 3. Atualizar no Banco
        $this->contaModel->update((int) $conta->id, [
            'asaas_payment_id'   => $cobranca['id'],
            'external_reference' => $dadosBase['externalReference'],
            'meio_pagamento'     => 'checkout', // Força checkout para permitir escolha
            'status'             => AsaasService::mapearStatus($cobranca['status'] ?? 'PENDING'),
        ]);

        // Retorna a conta atualizada
        return $this->contaModel->findById((int) $conta->id);
    }
}
