<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\ContaReceber;
use App\Models\PlanoConta;
use App\Models\Cliente;
use App\Services\ContaReceberRecorrenciaService;
use App\Services\AsaasService;
use App\Services\MailService;

class ContasReceberController extends Controller
{
    private ContaReceber $model;
    private PlanoConta $planoContaModel;
    private Cliente $clienteModel;
    private Logger $logger;
    private AsaasService $asaasService;
    private MailService $mailService;

    public function __construct()
    {
        $this->model = new ContaReceber();
        $this->planoContaModel = new PlanoConta();
        $this->clienteModel = new Cliente();
        $this->logger = new Logger();
        $this->asaasService = new AsaasService();
        $this->mailService = new MailService();
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
                'status' => $_GET['status'] ?? 'aberta',
                'pesquisa' => $_GET['q'] ?? '',
            ];

            $contas = $this->model->findByUsuarioId($usuarioId, $filtros);

            View::render('contas_receber/index', [
                '_layout' => 'erp',
                'title' => 'Contas a Receber',
                'breadcrumb' => [
                    'Financeiro' => '/financeiro/receber',
                    0 => 'Contas a Receber',
                ],
                'contas' => $contas,
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

            $planos = $this->planoContaModel->findByUsuarioId($usuarioId, ['status' => 'ativo']);
            $clientes = $this->clienteModel->findByUsuarioId($usuarioId, ['status' => 'ativo', 'pesquisa' => '', 'uf' => '']);

            View::render('contas_receber/form-enterprise', [
                '_layout' => 'erp',
                'title' => 'Nova Conta a Receber',
                'conta' => null,
                'planos' => $planos,
                'clientes' => $clientes,
                'tab' => 'geral',
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
            
            $usuarioId = $user->id;

            $clienteId = (int)($_POST['cliente_id'] ?? 0);
            $planoContaId = (int)($_POST['plano_conta_id'] ?? 0);
            $descricao = trim($_POST['descricao'] ?? '');
            $valor = trim($_POST['valor'] ?? '');
            $dataVencimento = $_POST['data_vencimento'] ?? '';

            if ($clienteId <= 0 || $planoContaId <= 0 || $descricao === '' || $valor === '' || $dataVencimento === '') {
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

            $dados = [
                'usuario_id' => $usuarioId,
                'cliente_id' => $clienteId,
                'plano_conta_id' => $planoContaId,
                'descricao' => $descricao,
                'valor' => $valor,
                'data_vencimento' => $dataVencimento,
                'data_recebimento' => $_POST['data_recebimento'] ?? null,
                'status' => $_POST['status'] ?? 'aberta',
                'observacoes' => trim($_POST['observacoes'] ?? ''),
                'meio_pagamento' => trim($_POST['meio_pagamento'] ?? ''),
                'recorrente' => isset($_POST['recorrente']) ? 1 : 0,
                'recorrencia_tipo' => $_POST['recorrencia_tipo'] ?? null,
                'recorrencia_intervalo' => $_POST['recorrencia_intervalo'] ?? null,
            ];

            if ($dados['observacoes'] === '') $dados['observacoes'] = null;
            if ($dados['meio_pagamento'] === '') $dados['meio_pagamento'] = null;

            $id = $this->model->create($dados);
            if ($id) {
                $this->model->update((int)$id, ['external_reference' => 'cr:' . (int)$id . '|u:' . (int)$usuarioId]);

                // Integrar com Asaas se meio de pagamento for digital
                $meioPagamento = $dados['meio_pagamento'] ?? null;
                if (in_array($meioPagamento, ['boleto', 'cartao', 'pix']) && AsaasService::isConfigured()) {
                    $this->integrarComAsaas((int)$id, $dados, $cliente);
                }

                AuditLogger::log('conta_receber_created', [
                    'conta_id' => $id,
                    'usuario_id' => $usuarioId,
                    'cliente_id' => $clienteId,
                    'valor' => $valor,
                    'meio_pagamento' => $meioPagamento
                ]);

                header('Location: /financeiro/contas-a-receber?success=created');
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
     * Integra conta a receber com o Asaas
     */
    private function integrarComAsaas(int $contaId, array $dados, object $cliente): void
    {
        try {
            // 1. Buscar ou criar cliente no Asaas
            $asaasCliente = $this->buscarOuCriarClienteAsaas($cliente);
            
            // 2. Criar cobrança no Asaas
            $dadosCobranca = [
                'customer' => $asaasCliente['id'],
                'value' => (float)str_replace(['R$', ' ', '.'], ['', '', ''], str_replace(',', '.', $dados['valor'])),
                'dueDate' => date('Y-m-d', strtotime($dados['data_vencimento'])),
                'description' => $dados['descricao'],
                'billingType' => $this->mapearMeioPagamentoAsaas($dados['meio_pagamento']),
                'externalReference' => 'cr:' . $contaId,
                'postalService' => false
            ];

            // Adicionar dados do cliente para PIX
            if ($dados['meio_pagamento'] === 'pix') {
                $dadosCobranca['pix'] = [
                    'expirationDate' => date('Y-m-d', strtotime($dados['data_vencimento'] . ' +30 days')),
                    'addressKey' => $cliente->pix_key ?? null
                ];
            }

            $cobrancaAsaas = $this->asaasService->criarCobranca($dadosCobranca);

            // 3. Atualizar conta com IDs do Asaas
            $this->model->update($contaId, [
                'asaas_payment_id' => $cobrancaAsaas['id'],
                'status' => AsaasService::mapearStatus($cobrancaAsaas['status'])
            ]);

            // 4. Enviar e-mail com link de pagamento
            $this->enviarEmailPagamento($cliente, $cobrancaAsaas, $dados);

        } catch (\Exception $e) {
            $this->logger->error('Erro na integração com Asaas: ' . $e->getMessage());
            AuditLogger::log('asaas_integration_failed', [
                'conta_id' => $contaId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Busca cliente no Asaas ou cria um novo
     */
    private function buscarOuCriarClienteAsaas(object $cliente): array
    {
        // Buscar por CPF/CNPJ
        $documento = AsaasService::formatarDocumento($cliente->cpf_cnpj ?? '');
        $asaasCliente = $this->asaasService->buscarCliente($documento, $cliente->email ?? null);

        if (!$asaasCliente) {
            // Criar novo cliente
            $dadosCliente = [
                'name' => $cliente->razao_social ?? $cliente->nome,
                'email' => $cliente->email ?? '',
                'phone' => $cliente->telefone ?? '',
                'cpfCnpj' => $documento,
                'address' => $cliente->endereco ?? '',
                'addressNumber' => $cliente->numero ?? '',
                'complement' => $cliente->complemento ?? '',
                'province' => $cliente->bairro ?? '',
                'postalCode' => $cliente->cep ?? '',
                'city' => $cliente->cidade ?? '',
                'state' => $cliente->estado ?? '',
                'notificationDisabled' => false
            ];

            $asaasCliente = $this->asaasService->criarCliente($dadosCliente);
        }

        return $asaasCliente;
    }

    /**
     * Mapeia meio de pagamento para formato Asaas
     */
    private function mapearMeioPagamentoAsaas(string $meioPagamento): string
    {
        $mapa = [
            'boleto' => 'BOLETO',
            'cartao' => 'CREDIT_CARD',
            'pix' => 'PIX',
            'outro' => 'UNDEFINED'
        ];

        return $mapa[$meioPagamento] ?? 'UNDEFINED';
    }

    /**
     * Envia e-mail com link de pagamento
     */
    private function enviarEmailPagamento(object $cliente, array $cobrancaAsaas, array $dados): void
    {
        try {
            $linkPagamento = $this->asaasService->getLinkPagamento($cobrancaAsaas['id']);
            
            if ($linkPagamento && MailService::isConfigured()) {
                $subject = 'Link de Pagamento - ' . $dados['descricao'];
                $body = "Olá, " . ($cliente->razao_social ?? $cliente->nome) . "!\n\n";
                $body .= "Geramos um link de pagamento para a sua cobrança:\n\n";
                $body .= "Descrição: " . $dados['descricao'] . "\n";
                $body .= "Valor: R$ " . number_format((float)str_replace(['R$', ' ', '.'], ['', '', ''], str_replace(',', '.', $dados['valor'])), 2, ',', '.') . "\n";
                $body .= "Vencimento: " . date('d/m/Y', strtotime($dados['data_vencimento'])) . "\n\n";
                $body .= "Clique no link abaixo para efetuar o pagamento:\n";
                $body .= $linkPagamento . "\n\n";
                
                if ($dados['meio_pagamento'] === 'boleto') {
                    $body .= "O boleto também será enviado por e-mail e estará disponível no link acima.\n\n";
                } elseif ($dados['meio_pagamento'] === 'pix') {
                    $body .= "O código PIX estará disponível no link acima.\n\n";
                }
                
                $body .= "Dúvidas? Entre em contato conosco.\n\n";
                $body .= "Atenciosamente,\n";
                $body .= "Equipe ERP InLaudo";

                $this->mailService->send($cliente->email, $subject, $body);
                
                AuditLogger::log('payment_email_sent', [
                    'cliente_id' => $cliente->id,
                    'conta_id' => $dados['id'] ?? null,
                    'payment_id' => $cobrancaAsaas['id'],
                    'meio_pagamento' => $dados['meio_pagamento']
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao enviar e-mail de pagamento: ' . $e->getMessage());
        }
    }

    /**
     * Sincroniza status de pagamentos com Asaas
     */
    public function sincronizarStatus(): void
    {
        if (!Auth::can('manage_financial')) {
            header('Location: /dashboard?error=unauthorized');
            exit();
        }

        try {
            $usuarioId = Auth::user()->id;
            $contas = $this->model->findByUsuarioId($usuarioId, ['asaas_payment_id_not_null' => true]);
            
            $atualizadas = 0;
            foreach ($contas as $conta) {
                if (!empty($conta->asaas_payment_id)) {
                    $statusAsaas = $this->asaasService->getStatusPagamento($conta->asaas_payment_id);
                    
                    if ($statusAsaas && $statusAsaas !== $conta->status) {
                        $novoStatus = AsaasService::mapearStatus($statusAsaas);
                        $this->model->update($conta->id, ['status' => $novoStatus]);
                        
                        AuditLogger::log('payment_status_synced', [
                            'conta_id' => $conta->id,
                            'payment_id' => $conta->asaas_payment_id,
                            'old_status' => $conta->status,
                            'new_status' => $novoStatus,
                            'asaas_status' => $statusAsaas
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
            $conta = $this->model->findById((int)$id);

            if (!$conta || (int)$conta->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/contas-a-receber?error=not_found');
                exit();
            }

            $planos = $this->planoContaModel->findByUsuarioId($usuarioId, ['status' => 'ativo']);
            $clientes = $this->clienteModel->findByUsuarioId($usuarioId, ['status' => 'ativo', 'pesquisa' => '', 'uf' => '']);

            View::render('contas_receber/form-enterprise', [
                '_layout' => 'erp',
                'title' => 'Editar Conta a Receber',
                'conta' => $conta,
                'planos' => $planos,
                'clientes' => $clientes,
                'tab' => $_GET['tab'] ?? 'geral',
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
            $conta = $this->model->findById((int)$id);

            if (!$conta || (int)$conta->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/contas-a-receber?error=unauthorized');
                exit();
            }

            $clienteId = (int)($_POST['cliente_id'] ?? 0);
            $planoContaId = (int)($_POST['plano_conta_id'] ?? 0);
            $descricao = trim($_POST['descricao'] ?? '');
            $valor = trim($_POST['valor'] ?? '');
            $dataVencimento = $_POST['data_vencimento'] ?? '';

            if ($clienteId <= 0 || $planoContaId <= 0 || $descricao === '' || $valor === '' || $dataVencimento === '') {
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
                'cliente_id' => $clienteId,
                'plano_conta_id' => $planoContaId,
                'descricao' => $descricao,
                'valor' => $valor,
                'data_vencimento' => $dataVencimento,
                'data_recebimento' => $_POST['data_recebimento'] ?? null,
                'status' => $_POST['status'] ?? 'aberta',
                'observacoes' => trim($_POST['observacoes'] ?? ''),
                'meio_pagamento' => trim($_POST['meio_pagamento'] ?? ''),
                'recorrente' => isset($_POST['recorrente']) ? 1 : 0,
                'recorrencia_tipo' => $_POST['recorrencia_tipo'] ?? null,
                'recorrencia_intervalo' => $_POST['recorrencia_intervalo'] ?? null,
            ];

            if ($dados['observacoes'] === '') $dados['observacoes'] = null;
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
            $conta = $this->model->findById((int)$id);

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
}
