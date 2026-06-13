<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\ContaReceber;
use App\Models\ContaReceberAnexo;
use App\Models\PlanoConta;
use App\Models\Cliente;
use App\Services\ContaReceberRecorrenciaService;
use App\Services\AsaasService;
use App\Models\ConfiguracaoFinanceira;
use App\Services\MailService;

class ContasReceberController extends Controller
{
    private ContaReceber $model;
    private ContaReceberAnexo $anexoModel;
    private PlanoConta $planoContaModel;
    private Cliente $clienteModel;
    private Logger $logger;
    // AsaasService e MailService são instanciados sob demanda (lazy)
    // para não causar erro fatal quando ASAAS_API_KEY não está configurado.
    private ?AsaasService $asaasService = null;
    private ConfiguracaoFinanceira $configFinanceiroModel;
    private ?MailService $mailService = null;

    public function __construct()
    {
        $this->model           = new ContaReceber();
        $this->anexoModel      = new ContaReceberAnexo();
        $this->planoContaModel = new PlanoConta();
        $this->clienteModel    = new Cliente();
        $this->logger                = new Logger();
        $this->configFinanceiroModel = new ConfiguracaoFinanceira();
    }

    /**
     * Retorna a instância do AsaasService, criando-a apenas quando necessário.
     * Prioriza a API Key salva no banco (Integrações); fallback para env vars.
     */
    private function getAsaasService(): AsaasService
    {
        if ($this->asaasService === null) {
            $usuarioId = (int) (Auth::user()->id ?? 0);
            $apiKey    = null;
            $env       = null;
            if ($usuarioId > 0) {
                $integracaoModel = new \App\Models\Integracao();
                $config = $integracaoModel->findByProvider('asaas', $usuarioId);
                if ($config && !empty($config->api_key)) {
                    $apiKey = $config->api_key;
                    $env    = $config->environment ?? 'sandbox';
                }
            }
            $this->asaasService = new AsaasService($apiKey, $env);
        }
        return $this->asaasService;
    }

    // -----------------------------------------------------------------------
    // POST /financeiro/contas-a-receber/sync-asaas
    // Sincroniza o status de todas as contas abertas vinculadas ao Asaas,
    // consultando a API do Asaas e atualizando o banco quando houver mudança.
    // -----------------------------------------------------------------------
    public function syncAsaas(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = Auth::user();
        if (!$user || !Auth::can('edit_contas_receber')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Não autorizado']);
            return;
        }

        $usuarioId = (int) $user->id;

        try {
            $asaas = $this->getAsaasService();
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => 'Asaas não configurado: ' . $e->getMessage()]);
            return;
        }

        $contas    = $this->model->findAbertasComAsaasId($usuarioId);
        $atualizadas = 0;
        $erros       = 0;
        $detalhes    = [];

        foreach ($contas as $conta) {
            try {
                $response = $asaas->makeRequestPublic('GET', '/payments/' . $conta->asaas_payment_id);
                if (empty($response['status'])) {
                    continue;
                }

                $novoStatus = AsaasService::mapearStatus($response['status']);
                if ($novoStatus === $conta->status) {
                    continue;
                }

                $dataRec = null;
                foreach (['paymentDate', 'confirmedDate', 'clientPaymentDate'] as $field) {
                    if (!empty($response[$field])) {
                        $dataRec = substr((string) $response[$field], 0, 10);
                        break;
                    }
                }

                $this->model->update((int) $conta->id, [
                    'status'           => $novoStatus,
                    'data_recebimento' => $dataRec ?: ($novoStatus === 'recebida' ? date('Y-m-d') : null),
                    'meio_pagamento'   => AsaasService::mapearMeioPagamento($response['billingType'] ?? 'UNDEFINED'),
                ]);

                AuditLogger::log('sync_asaas_status_atualizado', [
                    'usuario_id'       => $usuarioId,
                    'conta_id'         => (int) $conta->id,
                    'status_anterior'  => $conta->status,
                    'status_novo'      => $novoStatus,
                    'asaas_payment_id' => $conta->asaas_payment_id,
                    'asaas_status_raw' => $response['status'],
                ]);

                if ($novoStatus === 'recebida') {
                    $svc = new \App\Services\ContaReceberRecorrenciaService();
                    $svc->gerarProximaSeRecorrente($usuarioId, (int) $conta->id);
                }

                $atualizadas++;
                $detalhes[] = [
                    'conta_id'   => (int) $conta->id,
                    'descricao'  => $conta->descricao ?? '',
                    'status_novo'=> $novoStatus,
                ];
            } catch (\Throwable $e) {
                $erros++;
                $this->logger->warning('syncAsaas: erro ao consultar pagamento', [
                    'conta_id'         => (int) $conta->id,
                    'asaas_payment_id' => $conta->asaas_payment_id,
                    'error'            => $e->getMessage(),
                ]);
            }
        }

        echo json_encode([
            'success'     => true,
            'verificadas' => count($contas),
            'atualizadas' => $atualizadas,
            'erros'       => $erros,
            'detalhes'    => $detalhes,
        ]);
    }

    /**
     * Retorna a instância do MailService carregando credenciais SMTP do banco.
     */
    private function getMailService(): MailService
    {
        if ($this->mailService === null) {
            try {
                $integracaoModel = new \App\Models\Integracao();
                $usuarioId       = (int) Auth::user()->id;
                $row             = $integracaoModel->findByNomeAndUsuarioId('email', $usuarioId);
                if ($row && ($row->status ?? 'ativo') === 'ativo') {
                    $config   = $integracaoModel->getDecodedConfig($row);
                    $password = '';
                    if (!empty($config['password_enc'])) {
                        $crypto   = new \App\Services\CryptoService();
                        $password = $crypto->decryptString((string) $config['password_enc']);
                    }
                    $this->mailService = new MailService([
                        'host'       => $config['host']       ?? '',
                        'port'       => $config['port']       ?? 587,
                        'username'   => $config['username']   ?? '',
                        'password'   => $password,
                        'protocol'   => $config['protocol']   ?? 'tls',
                        'from_email' => $config['from_email'] ?? ($config['username'] ?? ''),
                        'from_name'  => $config['from_name']  ?? 'ERP InLaudo',
                    ]);
                } else {
                    $this->mailService = new MailService(); // fallback .env
                }
            } catch (\Exception $e) {
                $this->mailService = new MailService(); // fallback .env
            }
        }
        return $this->mailService;
    }

    public function index(): void
    {
        try {
            // VERIFICAÇÃO CRÍTICA: Usuário autenticado
            $user = Auth::user();
            if (!$user) {
                header('Location: /login?error=session_expired');
                exit();
            }

            $usuarioId = $user->id;

            $filtros = [
                'status'   => $_GET['status'] ?? 'aberta',
                'pesquisa' => $_GET['q'] ?? '',
            ];

            $contas = $this->model->findByUsuarioId($usuarioId, $filtros);

            View::render('contas_receber/index', [
                '_layout'    => 'erp',
                'title'      => 'Contas a Receber',
                'breadcrumb' => [
                    'Financeiro' => '/financeiro/receber',
                    0            => 'Contas a Receber',
                ],
                'contas'  => $contas,
                'filtros' => $filtros,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao listar contas a receber: ' . $e->getMessage());
            header('Location: /dashboard?error=1');
            exit();
        }
    }

    public function create(): void
    {
        try {
            // VERIFICAÇÃO CRÍTICA: Usuário autenticado
            $user = Auth::user();
            if (!$user) {
                header('Location: /login?error=session_expired');
                exit();
            }

            $usuarioId = $user->id;

            $planos   = $this->planoContaModel->findByUsuarioId($usuarioId, ['status' => 'ativo']);
            $clientes = $this->clienteModel->findByUsuarioId($usuarioId, ['status' => 'ativo', 'pesquisa' => '', 'uf' => '']);

            View::render('contas_receber/form-enterprise', [
                '_layout'  => 'erp',
                'title'    => 'Nova Conta a Receber',
                'conta'    => null,
                'planos'   => $planos,
                'clientes' => $clientes,
                'anexos'   => [],
                'tab'      => 'geral',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao carregar formulário de conta a receber: ' . $e->getMessage());
            header('Location: /dashboard?error=1');
            exit();
        }
    }

    public function store(): void
    {
        try {
            // VERIFICAÇÃO CRÍTICA: Usuário autenticado
            $user = Auth::user();
            if (!$user) {
                header('Location: /login?error=session_expired');
                exit();
            }

            $usuarioId    = $user->id;
            $clienteId    = (int)($_POST['cliente_id'] ?? 0);
            $planoContaId = (int)($_POST['plano_conta_id'] ?? 0);
            $descricao    = trim($_POST['descricao'] ?? '');
            // Sanitiza o valor: aceita float puro (do campo hidden) ou string monetaria (fallback)
            $valorRaw     = trim($_POST['valor'] ?? '');
            $valorClean   = preg_replace('/[^\d,.]/', '', $valorRaw);
            // Suporte a "1.234,56" (pt-BR) e "1234.56" (float puro)
            if (substr_count($valorClean, ',') === 1 && substr_count($valorClean, '.') >= 1) {
                $valorClean = str_replace('.', '', $valorClean);
                $valorClean = str_replace(',', '.', $valorClean);
            } elseif (substr_count($valorClean, ',') === 1) {
                $valorClean = str_replace(',', '.', $valorClean);
            }
            $valor = (float) $valorClean;
            $dataVencimento = trim($_POST['data_vencimento'] ?? '');
            // Valida formato da data (YYYY-MM-DD vindo do input type="date")
            if ($dataVencimento !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataVencimento)) {
                $dataVencimento = '';
            }

            if ($clienteId <= 0 || $planoContaId <= 0 || $descricao === '' || $valor <= 0 || $dataVencimento === '') {
                header('Location: /financeiro/contas-a-receber/create?error=missing_fields');
                exit();
            }

            $cliente = $this->clienteModel->findById($clienteId);
            if (!$cliente || (int)$cliente->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/contas-a-receber/create?error=invalid_cliente');
                exit();
            }

            $plano = $this->planoContaModel->findById($planoContaId);
            if (!$plano || (int)$plano->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/contas-a-receber/create?error=invalid_plano');
                exit();
            }

            // Campos de recorrência e parcelas
            $recorrente         = isset($_POST['recorrente']) ? 1 : 0;
            $recorrenciaTipo    = trim($_POST['recorrencia_tipo'] ?? '');
            $recorrenciaIntervalo = (int)($_POST['recorrencia_intervalo'] ?? 0);
            $totalParcelas      = (int)($_POST['total_parcelas'] ?? 0);
            $recorrenciaModo    = ($recorrente && $recorrenciaTipo !== '' && $totalParcelas > 1) ? 'antecipado' : 'rolling';

            $dados = [
                'usuario_id'            => $usuarioId,
                'cliente_id'            => $clienteId,
                'plano_conta_id'        => $planoContaId,
                'descricao'             => $descricao,
                'valor'                 => $valor,
                'data_vencimento'       => $dataVencimento,
                'data_recebimento'      => $_POST['data_recebimento'] ?? null,
                'status'                => $_POST['status'] ?? 'aberta',
                'observacoes'           => trim($_POST['observacoes'] ?? ''),
                'meio_pagamento'        => trim($_POST['meio_pagamento'] ?? ''),
                'recorrente'            => $recorrente,
                'recorrencia_tipo'      => $recorrenciaTipo ?: null,
                'recorrencia_intervalo' => $recorrenciaIntervalo > 0 ? $recorrenciaIntervalo : null,
                'recorrencia_modo'      => $recorrenciaModo,
                'numero_parcela'        => ($recorrenciaModo === 'antecipado' && $totalParcelas > 1) ? 1 : null,
                'total_parcelas'        => ($recorrenciaModo === 'antecipado' && $totalParcelas > 1) ? $totalParcelas : null,
            ];

            if ($dados['observacoes'] === '')   $dados['observacoes']  = null;
            if ($dados['meio_pagamento'] === '') $dados['meio_pagamento'] = null;

            $id = $this->model->create($dados);
            if ($id) {
                $this->model->update((int)$id, ['external_reference' => 'cr:' . (int)$id . '|u:' . (int)$usuarioId]);

                // Gerar parcelas antecipadas se solicitado
                $parcelasGeradas = 0;
                if ($recorrenciaModo === 'antecipado' && $totalParcelas > 1 && $recorrenciaTipo !== '') {
                    $recorrenciaService = new ContaReceberRecorrenciaService();
                    $resultadoParcelas  = $recorrenciaService->gerarTodasParcelas(
                        (int)$usuarioId,
                        (int)$id,
                        $totalParcelas,
                        $recorrenciaTipo,
                        max(1, $recorrenciaIntervalo)
                    );
                    $parcelasGeradas = $resultadoParcelas['geradas'];
                    if (!empty($resultadoParcelas['erros'])) {
                        $this->logger->error('Erros ao gerar parcelas antecipadas', [
                            'conta_id' => $id,
                            'erros'    => $resultadoParcelas['erros'],
                        ]);
                    }
                }

                // Integrar com Asaas se meio de pagamento for digital E a chave estiver configurada
                $meioPagamento = $dados['meio_pagamento'] ?? null;
                // Usar meio padrão das configurações financeiras se não definido
                if (empty($meioPagamento) || $meioPagamento === 'outro') {
                    $cfin = $this->configFinanceiroModel->findByUsuarioId((int)$usuarioId);
                    $meioPadrao = $cfin->meio_pagamento_padrao ?? 'checkout';
                    if (in_array($meioPadrao, ['boleto', 'cartao', 'pix', 'checkout'])) {
                        $meioPagamento = $meioPadrao;
                        $dados['meio_pagamento'] = $meioPagamento;
                    }
                }
                if (in_array($meioPagamento, ['boleto', 'cartao', 'pix', 'checkout']) && AsaasService::isConfigured()) {
                    $this->integrarComAsaas((int)$id, $dados, $cliente);
                }

                AuditLogger::log('conta_receber_created', [
                    'conta_id'        => $id,
                    'usuario_id'      => $usuarioId,
                    'cliente_id'      => $clienteId,
                    'valor'           => $valor,
                    'meio_pagamento'  => $meioPagamento,
                    'parcelas_geradas'=> $parcelasGeradas,
                    'total_parcelas'  => $totalParcelas,
                    'recorrencia_modo'=> $recorrenciaModo,
                ]);

                $successParam = $parcelasGeradas > 0 ? 'created_parcelas&parcelas=' . ($parcelasGeradas + 1) : 'created';
                header("Location: /financeiro/contas-a-receber/edit/{$id}?success={$successParam}&tab=parcelas");
            } else {
                header('Location: /financeiro/contas-a-receber/create?error=create_failed');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar conta a receber: ' . $e->getMessage());
            header('Location: /financeiro/contas-a-receber/create?error=exception');
        }
        exit();
    }

    /**
     * Integra conta a receber com o Asaas.
     * Chamado apenas quando AsaasService::isConfigured() === true.
     */
    /**
     * Integra conta a receber com o Asaas.
     * Suporta: PIX (QR Code), Boleto, Checkout (cliente escolhe o meio).
     * O externalReference usa o formato u:{usuario_id}|cr:{conta_id} para o webhook.
     */
    private function integrarComAsaas(int $contaId, array $dados, object $cliente): void
    {
        try {
            $asaasCliente = $this->buscarOuCriarClienteAsaas($cliente);
            $usuarioId    = $_SESSION['usuario_id'] ?? 0;
            $meio         = $dados['meio_pagamento'] ?? 'checkout';

            // Carregar configurações financeiras do tenant para juros/multa/desconto
            $cfin = $this->configFinanceiroModel->findByUsuarioId((int)$usuarioId);

            // Se meio não definido, usar padrão das configurações
            if (empty($meio) || $meio === 'outro') {
                $meio = $cfin->meio_pagamento_padrao ?? 'checkout';
            }

            $dadosBase = [
                'customer'          => $asaasCliente['id'],
                'value'             => (float) $dados['valor'],
                'dueDate'           => date('Y-m-d', strtotime($dados['data_vencimento'])),
                'description'       => $dados['descricao'],
                'externalReference' => "u:{$usuarioId}|cr:{$contaId}",
                'postalService'     => false,
            ];

            // Adicionar juros, multa e desconto para boleto via ConfiguracaoFinanceira
            if ($meio === 'boleto') {
                $fine     = $this->configFinanceiroModel->montarFine($cfin);
                $interest = $this->configFinanceiroModel->montarInterest($cfin);
                $discount = $this->configFinanceiroModel->montarDiscount($cfin);
                if (!empty($fine))     $dadosBase['fine']     = $fine;
                if (!empty($interest)) $dadosBase['interest'] = $interest;
                if (!empty($discount)) $dadosBase['discount'] = $discount;
                if (!empty($cfin->boleto_instrucoes)) {
                    $dadosBase['bankSlipInstructions'] = $cfin->boleto_instrucoes;
                }
            }

            // PIX — expiração do QR Code
            if ($meio === 'pix') {
                $expSeg = (int)($cfin->pix_expiracao_segundos ?? 86400);
                if ($expSeg > 0) {
                    $dadosBase['pixExpirationDate'] = date('Y-m-d\\TH:i:s', time() + $expSeg);
                }
            }

            $asaas = $this->getAsaasService();

            switch ($meio) {
                case 'pix':
                    $cobranca = $asaas->criarPix($dadosBase);
                    break;

                case 'boleto':
                    $cobranca = $asaas->criarBoleto($dadosBase);
                    break;

                case 'cartao':
                    $cobranca = $asaas->criarCheckout($dadosBase); // Cartão via checkout Asaas
                    break;

                case 'checkout':
                default:
                    $cobranca = $asaas->criarCheckout($dadosBase);
                    break;
            }

            // Salva o payment_id e atualiza o status
            $this->model->update($contaId, [
                'asaas_payment_id'   => $cobranca['id'],
                'external_reference' => $dadosBase['externalReference'],
                'status'             => AsaasService::mapearStatus($cobranca['status'] ?? 'PENDING'),
            ]);

            $this->logger->info('Cobrança Asaas criada', [
                'conta_id'         => $contaId,
                'asaas_payment_id' => $cobranca['id'],
                'billing_type'     => $cobranca['billingType'] ?? $meio,
                'status'           => $cobranca['status'] ?? '',
            ]);

            $this->enviarEmailPagamento($cliente, $cobranca, $dados);

        } catch (\Exception $e) {
            $this->logger->error('Erro na integração com Asaas: ' . $e->getMessage(), [
                'conta_id' => $contaId,
                'trace'    => $e->getTraceAsString(),
            ]);
            AuditLogger::log('asaas_integration_failed', [
                'conta_id' => $contaId,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private function buscarOuCriarClienteAsaas(object $cliente): array
    {
        $documento    = AsaasService::formatarDocumento($cliente->cpf_cnpj ?? '');
        $asaasCliente = $this->getAsaasService()->buscarCliente($documento, $cliente->email ?? null);

        if (!$asaasCliente) {
            $dadosCliente = [
                'name'               => $cliente->razao_social ?? $cliente->nome ?? '',
                'email'              => $cliente->email ?? '',
                'phone'              => $cliente->telefone ?? '',
                'cpfCnpj'            => $documento,
                'address'            => $cliente->endereco ?? '',
                'addressNumber'      => $cliente->numero ?? '',
                'complement'         => $cliente->complemento ?? '',
                'province'           => $cliente->bairro ?? '',
                'postalCode'         => $cliente->cep ?? '',
                'city'               => $cliente->cidade ?? '',
                'state'              => $cliente->estado ?? '',
                'notificationDisabled' => false,
            ];
            $asaasCliente = $this->getAsaasService()->criarCliente($dadosCliente);
        }

        return $asaasCliente;
    }

    /**
     * Mapeia meio de pagamento interno para billingType Asaas.
     * Nota: checkout usa UNDEFINED (cliente escolhe no Asaas).
     */
    private function mapearMeioPagamentoAsaas(string $meioPagamento): string
    {
        return match ($meioPagamento) {
            'boleto'   => 'BOLETO',
            'cartao'   => 'CREDIT_CARD',
            'pix'      => 'PIX',
            'checkout' => 'UNDEFINED',
            default    => 'UNDEFINED',
        };
    }

    /**
     * Envia e-mail com link de pagamento.
     */
    private function enviarEmailPagamento(object $cliente, array $cobrancaAsaas, array $dados): void
    {
        try {
            $linkPagamento = $this->getAsaasService()->getLinkPagamento($cobrancaAsaas['id']);

            if ($linkPagamento && MailService::isConfigured()) {
                $nomeCliente  = htmlspecialchars($cliente->razao_social ?? $cliente->nome ?? 'Cliente', ENT_QUOTES);
                $descricao    = htmlspecialchars($dados['descricao'] ?? '', ENT_QUOTES);
                $valor        = 'R$ ' . number_format((float)str_replace(['R$', ' ', '.'], ['', '', ''], str_replace(',', '.', $dados['valor'] ?? '0')), 2, ',', '.');
                $vencimento   = date('d/m/Y', strtotime($dados['data_vencimento'] ?? 'now'));
                $meioPgto     = ucfirst($dados['meio_pagamento'] ?? 'link');

                $infoMeio = '';
                if ($dados['meio_pagamento'] === 'boleto') {
                    $infoMeio = "<p style='color:#374151;'>O boleto também será enviado por e-mail e estará disponível no link acima.</p>";
                } elseif ($dados['meio_pagamento'] === 'pix') {
                    $infoMeio = "<p style='color:#374151;'>O código PIX estará disponível no link acima.</p>";
                }

                $subject = 'Link de Pagamento — ' . $descricao;

                $body = MailService::buildEmailHtml(
                    'Link de Pagamento',
                    "<p>Olá, <strong>{$nomeCliente}</strong>!</p>"
                    . "<p>Geramos um link de pagamento para a sua cobrança:</p>"
                    . "<table cellpadding='0' cellspacing='0' border='0' style='border-collapse:collapse;width:100%;max-width:480px;margin:16px 0;'>"
                    . "<tr><td style='padding:8px 12px;background:#f9fafb;border:1px solid #e5e7eb;'><strong>Descrição</strong></td><td style='padding:8px 12px;border:1px solid #e5e7eb;'>{$descricao}</td></tr>"
                    . "<tr><td style='padding:8px 12px;background:#f9fafb;border:1px solid #e5e7eb;'><strong>Valor</strong></td><td style='padding:8px 12px;border:1px solid #e5e7eb;'>{$valor}</td></tr>"
                    . "<tr><td style='padding:8px 12px;background:#f9fafb;border:1px solid #e5e7eb;'><strong>Vencimento</strong></td><td style='padding:8px 12px;border:1px solid #e5e7eb;'>{$vencimento}</td></tr>"
                    . "<tr><td style='padding:8px 12px;background:#f9fafb;border:1px solid #e5e7eb;'><strong>Meio de Pagamento</strong></td><td style='padding:8px 12px;border:1px solid #e5e7eb;'>{$meioPgto}</td></tr>"
                    . "</table>"
                    . "<p style='text-align:center;margin:24px 0;'>"
                    . "<a href='{$linkPagamento}' style='background:#1a56db;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:700;font-size:15px;display:inline-block;'>Acessar Link de Pagamento</a>"
                    . "</p>"
                    . $infoMeio
                    . "<p style='color:#6b7280;font-size:13px;'>Dúvidas? Entre em contato conosco.</p>"
                );

                $this->getMailService()->send($cliente->email, $subject, $body, true);

                AuditLogger::log('payment_email_sent', [
                    'cliente_id'     => $cliente->id,
                    'conta_id'       => $dados['id'] ?? null,
                    'payment_id'     => $cobrancaAsaas['id'],
                    'meio_pagamento' => $dados['meio_pagamento'],
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao enviar e-mail de pagamento: ' . $e->getMessage());
        }
    }

    /**
     * Sincroniza status de pagamentos com Asaas.
     */
    public function sincronizarStatus(): void
    {
        if (!Auth::can('manage_financial')) {
            header('Location: /dashboard?error=unauthorized');
            exit();
        }

        try {
            $usuarioId = Auth::user()->id;
            $contas    = $this->model->findByUsuarioId($usuarioId, ['asaas_payment_id_not_null' => true]);

            $atualizadas = 0;
            foreach ($contas as $conta) {
                if (!empty($conta->asaas_payment_id)) {
                    $statusAsaas = $this->getAsaasService()->getStatusPagamento($conta->asaas_payment_id);

                    if ($statusAsaas && $statusAsaas !== $conta->status) {
                        $novoStatus = AsaasService::mapearStatus($statusAsaas);
                        $this->model->update($conta->id, ['status' => $novoStatus]);

                        AuditLogger::log('payment_status_synced', [
                            'conta_id'    => $conta->id,
                            'payment_id'  => $conta->asaas_payment_id,
                            'old_status'  => $conta->status,
                            'new_status'  => $novoStatus,
                            'asaas_status' => $statusAsaas,
                        ]);

                        $atualizadas++;
                    }
                }
            }

            header("Location: /financeiro/contas-a-receber?success=synced&count={$atualizadas}");
        } catch (\Exception $e) {
            $this->logger->error('Erro ao sincronizar status: ' . $e->getMessage());
            header('Location: /financeiro/contas-a-receber?error=sync_failed');
        }
        exit();
    }

    public function edit($id): void
    {
        try {
            // VERIFICAÇÃO CRÍTICA: Usuário autenticado
            $user = Auth::user();
            if (!$user) {
                header('Location: /login?error=session_expired');
                exit();
            }

            $usuarioId = $user->id;
            $conta     = $this->model->findById((int)$id);

            if (!$conta || (int)$conta->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/contas-a-receber?error=not_found');
                exit();
            }

            $planos   = $this->planoContaModel->findByUsuarioId($usuarioId, ['status' => 'ativo']);
            $clientes = $this->clienteModel->findByUsuarioId($usuarioId, ['status' => 'ativo', 'pesquisa' => '', 'uf' => '']);

            $anexos   = $this->anexoModel->findByContaId((int)$conta->id, $usuarioId);

            // Buscar parcelas do grupo (se existir)
            $parcelas     = [];
            $resumoParcelas = null;
            if (!empty($conta->grupo_parcelas)) {
                $parcelas       = $this->model->findByGrupoParcelas($usuarioId, (string)$conta->grupo_parcelas);
                $resumoParcelas = $this->model->getResumoParcelas($usuarioId, (string)$conta->grupo_parcelas);
            }

            View::render('contas_receber/form-enterprise', [
                '_layout'       => 'erp',
                'title'         => 'Editar Conta a Receber',
                'conta'         => $conta,
                'planos'        => $planos,
                'clientes'      => $clientes,
                'anexos'        => $anexos,
                'parcelas'      => $parcelas,
                'resumoParcelas'=> $resumoParcelas,
                'tab'           => $_GET['tab'] ?? 'geral',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao editar conta a receber: ' . $e->getMessage());
            header('Location: /dashboard?error=1');
            exit();
        }
    }

    public function update($id): void
    {
        try {
            // VERIFICAÇÃO CRÍTICA: Usuário autenticado
            $user = Auth::user();
            if (!$user) {
                header('Location: /login?error=session_expired');
                exit();
            }

            $usuarioId = $user->id;
            $conta     = $this->model->findById((int)$id);

            if (!$conta || (int)$conta->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/contas-a-receber?error=unauthorized');
                exit();
            }

            $clienteId    = (int)($_POST['cliente_id'] ?? 0);
            $planoContaId = (int)($_POST['plano_conta_id'] ?? 0);
            $descricao    = trim($_POST['descricao'] ?? '');
            // Sanitiza o valor: aceita float puro (do campo hidden) ou string monetaria (fallback)
            $valorRaw     = trim($_POST['valor'] ?? '');
            $valorClean   = preg_replace('/[^\d,.]/', '', $valorRaw);
            if (substr_count($valorClean, ',') === 1 && substr_count($valorClean, '.') >= 1) {
                $valorClean = str_replace('.', '', $valorClean);
                $valorClean = str_replace(',', '.', $valorClean);
            } elseif (substr_count($valorClean, ',') === 1) {
                $valorClean = str_replace(',', '.', $valorClean);
            }
            $valor = (float) $valorClean;
            $dataVencimento = trim($_POST['data_vencimento'] ?? '');
            if ($dataVencimento !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataVencimento)) {
                $dataVencimento = '';
            }

            if ($clienteId <= 0 || $planoContaId <= 0 || $descricao === '' || $valor <= 0 || $dataVencimento === '') {
                header("Location: /financeiro/contas-a-receber/edit/{$id}?error=missing_fields");
                exit();
            }

            $cliente = $this->clienteModel->findById($clienteId);
            if (!$cliente || (int)$cliente->usuario_id !== (int)$usuarioId) {
                header("Location: /financeiro/contas-a-receber/edit/{$id}?error=invalid_cliente");
                exit();
            }

            $plano = $this->planoContaModel->findById($planoContaId);
            if (!$plano || (int)$plano->usuario_id !== (int)$usuarioId) {
                header("Location: /financeiro/contas-a-receber/edit/{$id}?error=invalid_plano");
                exit();
            }

            $dados = [
                'cliente_id'          => $clienteId,
                'plano_conta_id'      => $planoContaId,
                'descricao'           => $descricao,
                'valor'               => $valor,
                'data_vencimento'     => $dataVencimento,
                'data_recebimento'    => $_POST['data_recebimento'] ?? null,
                'status'              => $_POST['status'] ?? 'aberta',
                'observacoes'         => trim($_POST['observacoes'] ?? ''),
                'meio_pagamento'      => trim($_POST['meio_pagamento'] ?? ''),
                'recorrente'          => isset($_POST['recorrente']) ? 1 : 0,
                'recorrencia_tipo'    => $_POST['recorrencia_tipo'] ?? null,
                'recorrencia_intervalo' => $_POST['recorrencia_intervalo'] ?? null,
            ];

            if ($dados['observacoes'] === '')   $dados['observacoes']  = null;
            if ($dados['meio_pagamento'] === '') $dados['meio_pagamento'] = null;

            $oldStatus = (string)($conta->status ?? '');
            $newStatus = (string)($dados['status'] ?? '');

            if ($this->model->update((int)$id, $dados)) {
                AuditLogger::log('update_conta_receber', ['id' => (int)$id, 'descricao' => $descricao, 'valor' => $valor]);

                if ($newStatus === 'recebida' && $oldStatus !== 'recebida') {
                    $svc = new ContaReceberRecorrenciaService();
                    $svc->gerarProximaSeRecorrente((int)$usuarioId, (int)$id);
                }

                header("Location: /financeiro/contas-a-receber/edit/{$id}?success=updated");
            } else {
                header("Location: /financeiro/contas-a-receber/edit/{$id}?error=db_failure");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar conta a receber: ' . $e->getMessage());
            header("Location: /financeiro/contas-a-receber/edit/{$id}?error=fatal");
        }
        exit();
    }

    public function delete($id): void
    {
        try {
            // VERIFICAÇÃO CRÍTICA: Usuário autenticado
            $user = Auth::user();
            if (!$user) {
                header('Location: /login?error=session_expired');
                exit();
            }

            $usuarioId = $user->id;
            $conta     = $this->model->findById((int)$id);

            if (!$conta || (int)$conta->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/contas-a-receber?error=unauthorized');
                exit();
            }

            if ($this->model->cancel((int)$id)) {
                AuditLogger::log('delete_conta_receber', ['id' => (int)$id, 'descricao' => $conta->descricao ?? null]);
                header('Location: /financeiro/contas-a-receber?success=deleted');
            } else {
                header('Location: /financeiro/contas-a-receber?error=db_failure');
            }
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao cancelar conta a receber: ' . $e->getMessage());
            header('Location: /financeiro/contas-a-receber?error=fatal');
        }
        exit();
    }

    public function uploadAnexo(): void
    {
        try {
            $user = Auth::user();
            if (!$user) {
                header('Location: /login?error=session_expired');
                exit();
            }
            $usuarioId = $user->id;
            $contaId = (int)($_POST['conta_receber_id'] ?? 0);

            if ($contaId <= 0) {
                header('Location: /financeiro/contas-a-receber?error=invalid_request');
                exit();
            }

            $conta = $this->model->findById($contaId);
            if (!$conta || (int)$conta->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/contas-a-receber?error=unauthorized');
                exit();
            }

            if (!isset($_FILES['anexo'])) {
                header("Location: /financeiro/contas-a-receber/edit/{$contaId}?error=upload_failed&tab=anexos");
                exit();
            }

            $files = $_FILES['anexo'];
            $maxSize = 5 * 1024 * 1024;
            $finfo = new \finfo(FILEINFO_MIME_TYPE);

            $allowed = [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                // Excel (legacy + OpenXML)
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'application/vnd.ms-excel.sheet.macroEnabled.12' => 'xlsm',
                'application/vnd.ms-excel.sheet.binary.macroEnabled.12' => 'xlsb',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.template' => 'xltx',
                'application/vnd.ms-excel.template.macroEnabled.12' => 'xltm',
            ];

            $excelExts = ['xls', 'xlsx', 'xlsm', 'xlsb', 'xlt', 'xltx', 'xltm'];
            $excelFallbackMimes = [
                'application/zip',
                'application/octet-stream',
                'application/vnd.ms-office',
                'application/x-ole-storage',
                'application/cdfv2',
            ];

            $baseDir = BASE_PATH . '/storage/uploads/contas_receber/' . $usuarioId . '/' . $contaId;
            if (!is_dir($baseDir)) {
                if (!mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
                    $this->logger->error('Falha ao criar diretório de upload (contas_receber): ' . $baseDir . ' | BASE_PATH=' . BASE_PATH);
                    header("Location: /financeiro/contas-a-receber/edit/{$contaId}?error=upload_failed&tab=anexos");
                    exit();
                }
            }

            $isMulti = is_array($files['name'] ?? null);
            $count = $isMulti ? count((array) $files['name']) : 1;

            for ($i = 0; $i < $count; $i++) {
                $error = $isMulti ? ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) : ($files['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($error !== UPLOAD_ERR_OK) {
                    header("Location: /financeiro/contas-a-receber/edit/{$contaId}?error=upload_failed&tab=anexos");
                    exit();
                }

                $name = $isMulti ? ($files['name'][$i] ?? '') : ($files['name'] ?? '');
                $size = $isMulti ? ($files['size'][$i] ?? 0) : ($files['size'] ?? 0);
                $tmpPath = $isMulti ? ($files['tmp_name'][$i] ?? '') : ($files['tmp_name'] ?? '');

                if ($size > $maxSize) {
                    header("Location: /financeiro/contas-a-receber/edit/{$contaId}?error=file_too_large&tab=anexos");
                    exit();
                }

                $mime = $tmpPath !== '' ? ($finfo->file($tmpPath) ?: '') : '';
                $origExt = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));

                $ext = $allowed[$mime] ?? null;
                if ($ext === null && in_array($origExt, $excelExts, true)) {
                    if (in_array($mime, $excelFallbackMimes, true) ||
                        str_starts_with($mime, 'application/vnd.ms-excel') ||
                        str_starts_with($mime, 'application/vnd.openxmlformats')) {
                        $ext = $origExt;
                    }
                }

                if ($ext === null) {
                    $this->logger->warning('Upload anexo (contas_receber): tipo de arquivo inválido', [
                        'conta_receber_id' => $contaId,
                        'mime' => $mime,
                        'original_name' => $name,
                        'original_ext' => $origExt,
                    ]);
                    header("Location: /financeiro/contas-a-receber/edit/{$contaId}?error=invalid_file_type&tab=anexos");
                    exit();
                }

                $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
                $destPath = $baseDir . '/' . $safeName;

                if (!move_uploaded_file($tmpPath, $destPath)) {
                    header("Location: /financeiro/contas-a-receber/edit/{$contaId}?error=upload_failed&tab=anexos");
                    exit();
                }

                $relativePath = 'storage/uploads/contas_receber/' . $usuarioId . '/' . $contaId . '/' . $safeName;

                $anexoId = $this->anexoModel->create([
                    'usuario_id' => $usuarioId,
                    'conta_receber_id' => $contaId,
                    'file_path' => $relativePath,
                    'original_name' => $name !== '' ? $name : 'anexo',
                    'mime_type' => $mime,
                    'file_size' => $size ?: null,
                ]);

                if ($anexoId) {
                    AuditLogger::log('upload_conta_receber_anexo', ['id' => $anexoId, 'conta_receber_id' => $contaId]);
                } else {
                    @unlink($destPath);
                    header("Location: /financeiro/contas-a-receber/edit/{$contaId}?error=db_failure&tab=anexos");
                    exit();
                }
            }
            header("Location: /financeiro/contas-a-receber/edit/{$contaId}?success=upload&tab=anexos");
        } catch (\Exception $e) {
            $this->logger->error('Erro ao enviar anexo (contas a receber): ' . $e->getMessage());
            $contaId = (int)($_POST['conta_receber_id'] ?? 0);
            if ($contaId > 0) {
                header("Location: /financeiro/contas-a-receber/edit/{$contaId}?error=fatal&tab=anexos");
            } else {
                header('Location: /financeiro/contas-a-receber?error=fatal');
            }
        }
        exit();
    }

    public function deleteAnexo($id): void
    {
        try {
            $user = Auth::user();
            if (!$user) {
                header('Location: /login?error=session_expired');
                exit();
            }
            $usuarioId = $user->id;
            $anexo = $this->anexoModel->findById((int)$id);

            if (!$anexo || (int)$anexo->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/contas-a-receber?error=unauthorized');
                exit();
            }

            $contaId = (int)($anexo->conta_receber_id ?? 0);
            $filePath = BASE_PATH . '/' . ltrim((string)($anexo->file_path ?? ''), '/');

            if ($this->anexoModel->delete((int)$id)) {
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
                AuditLogger::log('delete_conta_receber_anexo', ['id' => (int)$id, 'conta_receber_id' => $contaId]);
                header("Location: /financeiro/contas-a-receber/edit/{$contaId}?success=deleted_anexo&tab=anexos");
            } else {
                header("Location: /financeiro/contas-a-receber/edit/{$contaId}?error=db_failure&tab=anexos");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao remover anexo (contas a receber): ' . $e->getMessage());
            header('Location: /financeiro/contas-a-receber?error=fatal');
        }
        exit();
    }

    public function downloadAnexo($id): void
    {
        try {
            $user = Auth::user();
            if (!$user) {
                http_response_code(401);
                exit();
            }
            $usuarioId = $user->id;
            $anexo = $this->anexoModel->findById((int)$id);

            if (!$anexo || (int)$anexo->usuario_id !== (int)$usuarioId) {
                http_response_code(403);
                echo '403 - Acesso Negado';
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
            $this->logger->error('Erro ao baixar anexo (contas a receber): ' . $e->getMessage());
            http_response_code(500);
            echo 'Erro ao baixar arquivo';
            exit();
        }
    }

    // -----------------------------------------------------------------------
    // POST /financeiro/contas-a-receber/receber-manual/{id}
    // Executa o recebimento manual de um título em aberto:
    //   1. Valida propriedade e status
    //   2. Atualiza status para 'recebida' + data_recebimento = hoje
    //   3. Libera NF vinculada no portal (se existir)
    //   4. Envia e-mail de confirmação ao cliente (se e-mail configurado)
    //   5. Gera próxima parcela (se conta recorrente)
    //   6. Registra auditoria
    // -----------------------------------------------------------------------
    public function receberManual($id): void
    {
        ob_start();
        $log = [];

        try {
            $user = Auth::user();
            if (!$user) {
                ob_end_clean();
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
                exit();
            }

            if (!Auth::can('edit_contas_receber')) {
                ob_end_clean();
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Sem permissão para executar esta operação.']);
                exit();
            }

            $usuarioId = (int) $user->id;
            $conta     = $this->model->findById((int) $id);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                ob_end_clean();
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Conta não encontrada ou sem permissão.']);
                exit();
            }

            if (($conta->status ?? '') === 'recebida') {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Esta conta já foi recebida anteriormente.']);
                exit();
            }

            if (($conta->status ?? '') === 'cancelada') {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Não é possível receber uma conta cancelada.']);
                exit();
            }

            // Ler body JSON
            $body          = json_decode(file_get_contents('php://input'), true) ?? [];
            $meioPagamento = trim($body['meio_pagamento'] ?? 'outro');
            $observacoes   = trim($body['observacoes']    ?? '');
            $dataRecebimento = date('Y-m-d');

            // ── 1. Atualizar status da conta ──────────────────────────────────
            $dadosUpdate = [
                'status'           => 'recebida',
                'data_recebimento' => $dataRecebimento,
                'meio_pagamento'   => $meioPagamento ?: null,
                'observacoes'      => $observacoes   ?: ($conta->observacoes ?? null),
            ];

            if (!$this->model->update((int) $id, $dadosUpdate)) {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Falha ao atualizar o status no banco de dados.']);
                exit();
            }

            $log[] = 'status_atualizado';

            // ── 2. Liberar NF vinculada no portal (ou criar registro pendente) ────
            $nfLiberada = false;
            try {
                $nfModel = new \App\Models\NotaFiscal();
                $nf      = $nfModel->findByContaReceberId((int) $id, $usuarioId);

                if ($nf) {
                    // NF já existe — libera para o portal independente do status
                    $nfModel->update((int) $nf->id, [
                        'portal_liberada'    => 1,
                        'portal_liberada_em' => date('Y-m-d H:i:s'),
                    ]);
                    $nfLiberada = true;
                    $log[]      = 'nf_liberada_id_' . $nf->id;
                } else {
                    // Não existe NF — cria registro pendente para que o portal
                    // exiba o botão "Emitir NF-s" (ou "Ver NF-s" após emissão)
                    $clienteNf = $this->clienteModel->findById((int)($conta->cliente_id ?? 0));
                    if ($clienteNf) {
                        $nfId = $nfModel->create([
                            'usuario_id'        => $usuarioId,
                            'cliente_id'        => (int) $conta->cliente_id,
                            'numero_nf'         => '',
                            'serie'             => '1',
                            'valor_total'       => (float) ($conta->valor ?? 0),
                            'data_emissao'      => $dataRecebimento,
                            'status'            => 'pendente',
                            'xml_path'          => null,
                            'asaas_invoice_id'  => null,
                            'origem_emissao'    => 'manual',
                            'conta_receber_id'  => (int) $id,
                            'asaas_pdf_url'     => null,
                            'asaas_status'      => null,
                            'servico_descricao' => $conta->descricao ?? 'Serviços Prestados',
                            'observacoes_nf'    => 'Gerada automaticamente no recebimento manual',
                        ]);
                        if ($nfId) {
                            $nfLiberada = true;
                            $log[]      = 'nf_pendente_criada_id_' . $nfId;
                        } else {
                            $log[] = 'nf_pendente_falha_ao_criar';
                        }
                    } else {
                        $log[] = 'nf_pendente_sem_cliente';
                    }
                }
            } catch (\Throwable $eNf) {
                $this->logger->warning('[receberManual] Falha ao liberar/criar NF: ' . $eNf->getMessage(), [
                    'conta_id' => (int) $id,
                ]);
                $log[] = 'nf_erro: ' . $eNf->getMessage();
            }

            // ── 3. Enviar e-mail de confirmação ao cliente ──────────────────────
            $emailEnviado = false;
            try {
                $cliente = $this->clienteModel->findById((int)($conta->cliente_id ?? 0));
                $emailCliente = $cliente->email ?? '';
                if ($emailCliente !== '') {
                    $mail = $this->getMailService();
                    $nomeCliente = $cliente->nome_fantasia ?: ($cliente->razao_social ?? 'Cliente');
                    $valorFmt    = 'R$ ' . number_format((float)($conta->valor ?? 0), 2, ',', '.');
                    $descFmt     = htmlspecialchars($conta->descricao ?? '');
                    $dataFmt     = date('d/m/Y', strtotime($dataRecebimento));

                    $meioPagFmt = ucfirst($meioPagamento ?? 'pix');
                    $nfBloco    = $nfLiberada
                        ? "<p style='margin-top:16px;color:#059669;font-size:14px;'>✅ Sua <strong>Nota Fiscal</strong> já está disponível no <a href='https://erp.inlaudo.com.br/portal' style='color:#1a56db;'>Portal do Cliente</a>.</p>"
                        : '';

                    $assunto = "Pagamento Confirmado \u2014 {$descFmt}";
                    $corpo   = MailService::buildEmailHtml(
                        'Confirmação de Pagamento',
                        "<p style='margin:0 0 8px;font-size:15px;'>Olá, <strong>{$nomeCliente}</strong>!</p>"
                        . "<p style='margin:0 0 20px;font-size:15px;'>Confirmamos o recebimento do seguinte título:</p>"
                        . "<table style='border-collapse:collapse;width:100%;font-size:14px;'>"
                        . "<tr><td style='padding:8px 12px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:700;width:42%'>Descrição</td><td style='padding:8px 12px;border:1px solid #e5e7eb;'>{$descFmt}</td></tr>"
                        . "<tr><td style='padding:8px 12px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:700;'>Valor</td><td style='padding:8px 12px;border:1px solid #e5e7eb;color:#059669;font-weight:700;'>{$valorFmt}</td></tr>"
                        . "<tr><td style='padding:8px 12px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:700;'>Data de Recebimento</td><td style='padding:8px 12px;border:1px solid #e5e7eb;'>{$dataFmt}</td></tr>"
                        . "<tr><td style='padding:8px 12px;background:#f9fafb;border:1px solid #e5e7eb;font-weight:700;'>Meio de Pagamento</td><td style='padding:8px 12px;border:1px solid #e5e7eb;'>{$meioPagFmt}</td></tr>"
                        . "</table>"
                        . $nfBloco
                    );

                    $mail->send($emailCliente, $assunto, $corpo, true);
                    $emailEnviado = true;
                    $log[]        = 'email_enviado_para_' . $emailCliente;
                }
            } catch (\Throwable $eMail) {
                $this->logger->warning('[receberManual] Falha ao enviar e-mail: ' . $eMail->getMessage(), [
                    'conta_id' => (int) $id,
                ]);
                $log[] = 'email_erro: ' . $eMail->getMessage();
            }

            // ── 4. Gerar próxima parcela (se recorrente) ─────────────────────
            $proximaGerada = false;
            try {
                if (!empty($conta->recorrente) && (int)$conta->recorrente === 1) {
                    $svc = new ContaReceberRecorrenciaService();
                    $svc->gerarProximaSeRecorrente($usuarioId, (int) $id);
                    $proximaGerada = true;
                    $log[]         = 'proxima_parcela_gerada';
                }
            } catch (\Throwable $eRec) {
                $this->logger->warning('[receberManual] Falha ao gerar próxima parcela: ' . $eRec->getMessage(), [
                    'conta_id' => (int) $id,
                ]);
                $log[] = 'recorrencia_erro: ' . $eRec->getMessage();
            }

            // ── 5. Auditoria ──────────────────────────────────────────────────
            AuditLogger::log('receber_manual', [
                'conta_id'       => (int) $id,
                'usuario_id'     => $usuarioId,
                'descricao'      => $conta->descricao ?? null,
                'valor'          => $conta->valor ?? null,
                'meio_pagamento' => $meioPagamento,
                'nf_liberada'    => $nfLiberada,
                'email_enviado'  => $emailEnviado,
                'proxima_gerada' => $proximaGerada,
                'log'            => $log,
            ]);

            $this->logger->info('[receberManual] Recebimento manual executado com sucesso', [
                'conta_id' => (int) $id,
                'log'      => $log,
            ]);

            // ── 6. Resposta JSON ──────────────────────────────────────────────
            $extras = [];
            if ($nfLiberada)    $extras[] = 'NF liberada no portal';
            if ($emailEnviado)  $extras[] = 'e-mail enviado ao cliente';
            if ($proximaGerada) $extras[] = 'próxima parcela gerada';

            $mensagem = 'Recebimento registrado com sucesso.';
            if (!empty($extras)) {
                $mensagem .= ' (' . implode(', ', $extras) . ')';
            }

            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success'        => true,
                'message'        => $mensagem,
                'nf_liberada'    => $nfLiberada,
                'email_enviado'  => $emailEnviado,
                'proxima_gerada' => $proximaGerada,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('[receberManual] Erro fatal: ' . $e->getMessage(), [
                'conta_id' => (int) $id,
                'trace'    => $e->getTraceAsString(),
            ]);
            ob_end_clean();
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erro interno ao processar o recebimento. Tente novamente.',
            ]);
        }
        exit();
    }
}
