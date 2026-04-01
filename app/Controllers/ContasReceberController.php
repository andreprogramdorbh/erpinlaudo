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
    private ?MailService $mailService = null;

    public function __construct()
    {
        $this->model           = new ContaReceber();
        $this->anexoModel      = new ContaReceberAnexo();
        $this->planoContaModel = new PlanoConta();
        $this->clienteModel    = new Cliente();
        $this->logger          = new Logger();
    }

    /**
     * Retorna a instância do AsaasService, criando-a apenas quando necessário.
     * Lança exceção se a chave não estiver configurada.
     */
    private function getAsaasService(): AsaasService
    {
        if ($this->asaasService === null) {
            $this->asaasService = new AsaasService();
        }
        return $this->asaasService;
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

                // Integrar com Asaas apenas se meio de pagamento for digital E a chave estiver configurada
                $meioPagamento = $dados['meio_pagamento'] ?? null;
                if (in_array($meioPagamento, ['boleto', 'cartao', 'pix']) && AsaasService::isConfigured()) {
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
            $meio         = $dados['meio_pagamento'] ?? '';

            $dadosBase = [
                'customer'          => $asaasCliente['id'],
                'value'             => (float) $dados['valor'],
                'dueDate'           => date('Y-m-d', strtotime($dados['data_vencimento'])),
                'description'       => $dados['descricao'],
                'externalReference' => "u:{$usuarioId}|cr:{$contaId}",
                'postalService'     => false,
            ];

            $asaas = $this->getAsaasService();

            switch ($meio) {
                case 'pix':
                    $cobranca = $asaas->criarPix($dadosBase);
                    break;

                case 'boleto':
                    $cobranca = $asaas->criarBoleto($dadosBase);
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
                $subject = 'Link de Pagamento - ' . $dados['descricao'];
                $body    = "Olá, " . ($cliente->razao_social ?? $cliente->nome ?? '') . "!\n\n";
                $body   .= "Geramos um link de pagamento para a sua cobrança:\n\n";
                $body   .= "Descrição: " . $dados['descricao'] . "\n";
                $body   .= "Valor: R$ " . number_format((float)str_replace(['R$', ' ', '.'], ['', '', ''], str_replace(',', '.', $dados['valor'])), 2, ',', '.') . "\n";
                $body   .= "Vencimento: " . date('d/m/Y', strtotime($dados['data_vencimento'])) . "\n\n";
                $body   .= "Clique no link abaixo para efetuar o pagamento:\n";
                $body   .= $linkPagamento . "\n\n";

                if ($dados['meio_pagamento'] === 'boleto') {
                    $body .= "O boleto também será enviado por e-mail e estará disponível no link acima.\n\n";
                } elseif ($dados['meio_pagamento'] === 'pix') {
                    $body .= "O código PIX estará disponível no link acima.\n\n";
                }

                $body .= "Dúvidas? Entre em contato conosco.\n\n";
                $body .= "Atenciosamente,\nEquipe ERP InLaudo";

                $this->getMailService()->send($cliente->email, $subject, $body);

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
}
