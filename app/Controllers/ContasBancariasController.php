<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\ContaBancaria;
use App\Models\ContaMovimentacao;
use App\Models\PlanoConta;
use App\Models\Integracao;
use App\Services\OFXImportService;
use App\Services\OpenFinanceService;

class ContasBancariasController extends Controller
{
    private ContaBancaria    $model;
    private ContaMovimentacao $movModel;
    private PlanoConta       $planoModel;
    private Logger           $logger;

    public function __construct()
    {
        $this->model      = new ContaBancaria();
        $this->movModel   = new ContaMovimentacao();
        $this->planoModel = new PlanoConta();
        $this->logger     = new Logger();
    }

    // ================================================================
    // LISTAGEM DE CONTAS
    // ================================================================

    public function index(): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $filtros   = [
                'pesquisa' => $_GET['q'] ?? '',
                'ativa'    => $_GET['ativa'] ?? '',
            ];

            $contas      = $this->model->findByUsuarioId($usuarioId, $filtros);
            $saldoTotal  = $this->model->getSaldoTotal($usuarioId);

            View::render('contas_bancarias/index', [
                '_layout'    => 'erp',
                'title'      => 'Contas',
                'breadcrumb' => ['Financeiro' => '/financeiro/pagar', 0 => 'Contas'],
                'contas'     => $contas,
                'filtros'    => $filtros,
                'saldoTotal' => $saldoTotal,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao listar: ' . $e->getMessage());
            header('Location: /dashboard?error=1');
            exit();
        }
    }

    // ================================================================
    // CRIAR CONTA
    // ================================================================

    public function create(): void
    {
        $bancos = ContaBancaria::getBancosComuns();
        View::render('contas_bancarias/form', [
            '_layout'    => 'erp',
            'title'      => 'Nova Conta',
            'breadcrumb' => [
                'Financeiro' => '/financeiro/pagar',
                'Contas'     => '/financeiro/contas',
                0            => 'Nova Conta',
            ],
            'conta'  => null,
            'bancos' => $bancos,
        ]);
    }

    public function store(): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $data      = $_POST;
            $data['usuario_id'] = $usuarioId;

            // Validação básica
            if (empty(trim($data['nome'] ?? ''))) {
                header('Location: /financeiro/contas/create?error=nome_obrigatorio');
                exit();
            }

            $id = $this->model->create($data);

            AuditLogger::log('conta_bancaria_criada', [
                'conta_id' => $id,
                'nome'     => $data['nome'],
                'banco'    => $data['banco_nome'] ?? '',
            ]);

            header("Location: /financeiro/contas?success=conta_criada");
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao criar: ' . $e->getMessage());
            header('Location: /financeiro/contas/create?error=erro_criar');
            exit();
        }
    }

    // ================================================================
    // EDITAR CONTA
    // ================================================================

    public function edit(int $id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                header('Location: /financeiro/contas?error=nao_encontrada');
                exit();
            }

            $bancos = ContaBancaria::getBancosComuns();
            View::render('contas_bancarias/form', [
                '_layout'    => 'erp',
                'title'      => 'Editar Conta',
                'breadcrumb' => [
                    'Financeiro' => '/financeiro/pagar',
                    'Contas'     => '/financeiro/contas',
                    0            => 'Editar Conta',
                ],
                'conta'  => $conta,
                'bancos' => $bancos,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao editar: ' . $e->getMessage());
            header('Location: /financeiro/contas?error=1');
            exit();
        }
    }

    public function update(int $id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                header('Location: /financeiro/contas?error=nao_encontrada');
                exit();
            }

            $data = $_POST;
            if (empty(trim($data['nome'] ?? ''))) {
                header("Location: /financeiro/contas/edit/{$id}?error=nome_obrigatorio");
                exit();
            }

            $this->model->update($id, $data);

            AuditLogger::log('conta_bancaria_atualizada', [
                'conta_id' => $id,
                'nome'     => $data['nome'],
            ]);

            header("Location: /financeiro/contas?success=conta_atualizada");
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao atualizar: ' . $e->getMessage());
            header("Location: /financeiro/contas/edit/{$id}?error=erro_atualizar");
            exit();
        }
    }

    // ================================================================
    // EXCLUIR CONTA
    // ================================================================

    public function delete(int $id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                header('Location: /financeiro/contas?error=nao_encontrada');
                exit();
            }

            $this->model->delete($id);

            AuditLogger::log('conta_bancaria_excluida', ['conta_id' => $id, 'nome' => $conta->nome]);

            header('Location: /financeiro/contas?success=conta_excluida');
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao excluir: ' . $e->getMessage());
            header('Location: /financeiro/contas?error=erro_excluir');
            exit();
        }
    }

    // ================================================================
    // MOVIMENTAÇÕES (EXTRATO)
    // ================================================================

    public function movimentacoes(int $id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                header('Location: /financeiro/contas?error=nao_encontrada');
                exit();
            }

            // Filtros padrão: mês atual
            $filtros = [
                'data_inicio' => $_GET['data_inicio'] ?? date('Y-m-01'),
                'data_fim'    => $_GET['data_fim']    ?? date('Y-m-t'),
                'tipo'        => $_GET['tipo']        ?? '',
                'origem'      => $_GET['origem']      ?? '',
                'conciliada'  => $_GET['conciliada']  ?? '',
                'pesquisa'    => $_GET['q']            ?? '',
            ];

            $pagina    = max(1, (int)($_GET['pagina'] ?? 1));
            $porPagina = 50;

            $resultado = $this->movModel->findByContaId($id, $filtros, $pagina, $porPagina);
            $resumo    = $this->movModel->getResumo($id, $filtros);

            // Dados para gráfico de evolução de saldo (últimos 30 dias)
            $evolucao = $this->movModel->getEvolucaoSaldo(
                $id,
                $filtros['data_inicio'],
                $filtros['data_fim']
            );

            // Dados para gráfico de categorias
            $categorias = $this->movModel->getTotaisPorCategoria($id, $filtros);

            // Planos de conta para categorização
            $planos = $this->planoModel->findByUsuarioId($usuarioId);

            // Verifica se há integração Open Finance configurada
            $integracaoModel = new Integracao();
            $openfinanceAtivo = false;
            if (!empty($conta->openfinance_account_id)) {
                $openfinanceAtivo = true;
            }

            View::render('contas_bancarias/movimentacoes', [
                '_layout'          => 'erp',
                'title'            => 'Movimentações — ' . $conta->nome,
                'breadcrumb'       => [
                    'Financeiro'   => '/financeiro/pagar',
                    'Contas'       => '/financeiro/contas',
                    0              => 'Movimentações',
                ],
                'conta'            => $conta,
                'movimentacoes'    => $resultado['itens'],
                'total'            => $resultado['total'],
                'pagina'           => $resultado['pagina'],
                'paginas'          => $resultado['paginas'],
                'por_pagina'       => $resultado['por_pagina'],
                'filtros'          => $filtros,
                'resumo'           => $resumo,
                'evolucao'         => $evolucao,
                'categorias'       => $categorias,
                'planos'           => $planos,
                'openfinanceAtivo' => $openfinanceAtivo,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao listar movimentações: ' . $e->getMessage());
            header('Location: /financeiro/contas?error=1');
            exit();
        }
    }

    // ================================================================
    // NOVA MOVIMENTAÇÃO MANUAL
    // ================================================================

    public function novaMovimentacao(int $id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                header('Location: /financeiro/contas?error=nao_encontrada');
                exit();
            }

            $planos = $this->planoModel->findByUsuarioId($usuarioId);

            View::render('contas_bancarias/form-movimentacao', [
                '_layout'    => 'erp',
                'title'      => 'Nova Movimentação',
                'breadcrumb' => [
                    'Financeiro'      => '/financeiro/pagar',
                    'Contas'          => '/financeiro/contas',
                    'Movimentações'   => "/financeiro/contas/{$id}/movimentacoes",
                    0                 => 'Nova Movimentação',
                ],
                'conta'  => $conta,
                'planos' => $planos,
                'mov'    => null,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao abrir form movimentação: ' . $e->getMessage());
            header("Location: /financeiro/contas/{$id}/movimentacoes?error=1");
            exit();
        }
    }

    public function salvarMovimentacao(int $id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                header('Location: /financeiro/contas?error=nao_encontrada');
                exit();
            }

            $data = $_POST;
            $data['conta_bancaria_id'] = $id;
            $data['usuario_id']        = $usuarioId;
            $data['origem']            = 'manual';

            if (empty($data['descricao']) || empty($data['data_movimentacao']) || empty($data['valor'])) {
                header("Location: /financeiro/contas/{$id}/movimentacoes/nova?error=campos_obrigatorios");
                exit();
            }

            // Normaliza valor: débito deve ser negativo
            $valor = (float) str_replace(['.', ','], ['', '.'], $data['valor']);
            if ($data['tipo'] === 'debito' && $valor > 0) {
                $valor = -$valor;
            }
            $data['valor'] = $valor;

            // Gera hash para deduplicação
            $data['origem_hash'] = md5($id . $data['data_movimentacao'] . $data['descricao'] . $valor);

            $movId = $this->movModel->create($data);

            // Atualiza saldo atual da conta
            $this->recalcularSaldo($id);

            AuditLogger::log('movimentacao_criada', [
                'conta_id' => $id,
                'mov_id'   => $movId,
                'valor'    => $valor,
                'tipo'     => $data['tipo'],
            ]);

            header("Location: /financeiro/contas/{$id}/movimentacoes?success=movimentacao_criada");
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao salvar movimentação: ' . $e->getMessage());
            header("Location: /financeiro/contas/{$id}/movimentacoes/nova?error=erro_salvar");
            exit();
        }
    }

    // ================================================================
    // EDITAR MOVIMENTAÇÃO
    // ================================================================

    public function editarMovimentacao(int $contaId, int $movId): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($contaId);
            $mov       = $this->movModel->findById($movId);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId || !$mov) {
                header('Location: /financeiro/contas?error=nao_encontrada');
                exit();
            }

            $planos = $this->planoModel->findByUsuarioId($usuarioId);

            View::render('contas_bancarias/form-movimentacao', [
                '_layout'    => 'erp',
                'title'      => 'Editar Movimentação',
                'breadcrumb' => [
                    'Financeiro'    => '/financeiro/pagar',
                    'Contas'        => '/financeiro/contas',
                    'Movimentações' => "/financeiro/contas/{$contaId}/movimentacoes",
                    0               => 'Editar Movimentação',
                ],
                'conta'  => $conta,
                'planos' => $planos,
                'mov'    => $mov,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao abrir edição de movimentação: ' . $e->getMessage());
            header("Location: /financeiro/contas/{$contaId}/movimentacoes?error=1");
            exit();
        }
    }

    public function atualizarMovimentacao(int $contaId, int $movId): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($contaId);
            $mov       = $this->movModel->findById($movId);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId || !$mov) {
                header('Location: /financeiro/contas?error=nao_encontrada');
                exit();
            }

            $data  = $_POST;
            $valor = (float) str_replace(['.', ','], ['', '.'], $data['valor'] ?? '0');
            if (($data['tipo'] ?? '') === 'debito' && $valor > 0) {
                $valor = -$valor;
            }
            $data['valor'] = $valor;

            $this->movModel->update($movId, $data);
            $this->recalcularSaldo($contaId);

            AuditLogger::log('movimentacao_atualizada', ['conta_id' => $contaId, 'mov_id' => $movId]);

            header("Location: /financeiro/contas/{$contaId}/movimentacoes?success=movimentacao_atualizada");
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao atualizar movimentação: ' . $e->getMessage());
            header("Location: /financeiro/contas/{$contaId}/movimentacoes?error=1");
            exit();
        }
    }

    // ================================================================
    // EXCLUIR MOVIMENTAÇÃO
    // ================================================================

    public function excluirMovimentacao(int $contaId, int $movId): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($contaId);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                header('Location: /financeiro/contas?error=nao_encontrada');
                exit();
            }

            $this->movModel->delete($movId);
            $this->recalcularSaldo($contaId);

            AuditLogger::log('movimentacao_excluida', ['conta_id' => $contaId, 'mov_id' => $movId]);

            header("Location: /financeiro/contas/{$contaId}/movimentacoes?success=movimentacao_excluida");
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao excluir movimentação: ' . $e->getMessage());
            header("Location: /financeiro/contas/{$contaId}/movimentacoes?error=1");
            exit();
        }
    }

    // ================================================================
    // CONCILIAÇÃO
    // ================================================================

    public function conciliar(int $contaId, int $movId): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($contaId);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                http_response_code(403);
                echo json_encode(['error' => 'Acesso negado']);
                exit();
            }

            $mov = $this->movModel->findById($movId);
            if (!$mov) {
                http_response_code(404);
                echo json_encode(['error' => 'Movimentação não encontrada']);
                exit();
            }

            if ((int) $mov->conciliada === 1) {
                $this->movModel->desconciliar($movId);
                $status = 0;
            } else {
                $this->movModel->conciliar($movId);
                $status = 1;
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'conciliada' => $status]);
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao conciliar: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno']);
            exit();
        }
    }

    // ================================================================
    // IMPORTAÇÃO OFX / OFC
    // ================================================================

    public function importarOfx(int $id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                header('Location: /financeiro/contas?error=nao_encontrada');
                exit();
            }

            if (empty($_FILES['arquivo']['tmp_name'])) {
                header("Location: /financeiro/contas/{$id}/movimentacoes?error=arquivo_obrigatorio");
                exit();
            }

            $arquivo   = $_FILES['arquivo'];
            $extensao  = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

            if (!in_array($extensao, ['ofx', 'ofc', 'qfx'])) {
                header("Location: /financeiro/contas/{$id}/movimentacoes?error=formato_invalido");
                exit();
            }

            $conteudo = file_get_contents($arquivo['tmp_name']);
            if ($conteudo === false) {
                header("Location: /financeiro/contas/{$id}/movimentacoes?error=erro_leitura");
                exit();
            }

            $service   = new OFXImportService();
            $resultado = $service->importar($conteudo, $id, $usuarioId, $this->movModel);

            AuditLogger::log('ofx_importado', [
                'conta_id'   => $id,
                'arquivo'    => $arquivo['name'],
                'importadas' => $resultado['importadas'],
                'duplicadas' => $resultado['duplicadas'],
            ]);

            $msg = urlencode("Importação concluída: {$resultado['importadas']} transações importadas, {$resultado['duplicadas']} duplicadas ignoradas.");
            $this->recalcularSaldo($id);

            header("Location: /financeiro/contas/{$id}/movimentacoes?success=ofx_importado&msg={$msg}");
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao importar OFX: ' . $e->getMessage());
            header("Location: /financeiro/contas/{$id}/movimentacoes?error=erro_importacao");
            exit();
        }
    }

    // ================================================================
    // OPEN FINANCE — Sincronização
    // ================================================================

    public function sincronizarOpenFinance(int $id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                http_response_code(403);
                echo json_encode(['error' => 'Acesso negado']);
                exit();
            }

            if (empty($conta->openfinance_account_id)) {
                http_response_code(400);
                echo json_encode(['error' => 'Conta não vinculada ao Open Finance']);
                exit();
            }

            // Busca configuração do Open Finance (Pluggy)
            $integracaoModel = new Integracao();
            $config = $integracaoModel->findByProviderAtivo('pluggy');

            if (!$config || empty($config->api_key)) {
                http_response_code(400);
                echo json_encode(['error' => 'Integração Open Finance não configurada']);
                exit();
            }

            $service   = new OpenFinanceService($config->api_key, $config->api_secret ?? '');
            $resultado = $service->sincronizarTransacoes(
                $conta->openfinance_item_id,
                $conta->openfinance_account_id,
                $id,
                $usuarioId,
                $this->movModel
            );

            // Atualiza data da última sincronização
            $this->model->update($id, [
                'openfinance_last_sync' => date('Y-m-d H:i:s'),
                'openfinance_status'    => 'connected',
            ]);

            $this->recalcularSaldo($id);

            AuditLogger::log('openfinance_sincronizado', [
                'conta_id'   => $id,
                'importadas' => $resultado['importadas'],
                'duplicadas' => $resultado['duplicadas'],
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'success'    => true,
                'importadas' => $resultado['importadas'],
                'duplicadas' => $resultado['duplicadas'],
                'msg'        => "{$resultado['importadas']} transações sincronizadas.",
            ]);
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao sincronizar Open Finance: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao sincronizar: ' . $e->getMessage()]);
            exit();
        }
    }

    // ================================================================
    // OPEN FINANCE — Conectar conta via Pluggy Connect Widget
    // ================================================================

    public function conectarOpenFinance(int $id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                header('Location: /financeiro/contas?error=nao_encontrada');
                exit();
            }

            // Busca configuração do Open Finance (Pluggy)
            $integracaoModel = new Integracao();
            $config = $integracaoModel->findByProviderAtivo('pluggy');

            $pluggyClientId     = '';
            $pluggyConnectToken = '';

            if ($config && !empty($config->api_key)) {
                try {
                    $service            = new OpenFinanceService($config->api_key, $config->api_secret ?? '');
                    $pluggyConnectToken = $service->gerarConnectToken();
                    $pluggyClientId     = $config->api_key;
                } catch (\Exception $e) {
                    $this->logger->error('[ContasBancarias] Erro ao gerar connect token: ' . $e->getMessage());
                }
            }

            View::render('contas_bancarias/open-finance', [
                '_layout'            => 'erp',
                'title'              => 'Conectar Open Finance',
                'breadcrumb'         => [
                    'Financeiro'     => '/financeiro/pagar',
                    'Contas'         => '/financeiro/contas',
                    0                => 'Open Finance',
                ],
                'conta'              => $conta,
                'pluggyConnectToken' => $pluggyConnectToken,
                'pluggyClientId'     => $pluggyClientId,
                'openfinanceAtivo'   => !empty($config),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao abrir Open Finance: ' . $e->getMessage());
            header('Location: /financeiro/contas?error=1');
            exit();
        }
    }

    // ================================================================
    // OPEN FINANCE — Salvar vinculação após Pluggy Connect
    // ================================================================

    public function salvarVinculacaoOpenFinance(int $id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                http_response_code(403);
                echo json_encode(['error' => 'Acesso negado']);
                exit();
            }

            $input     = json_decode(file_get_contents('php://input'), true) ?? [];
            $itemId    = $input['item_id']    ?? '';
            $accountId = $input['account_id'] ?? '';
            $provider  = $input['provider']   ?? 'pluggy';

            if (empty($itemId) || empty($accountId)) {
                http_response_code(400);
                echo json_encode(['error' => 'item_id e account_id são obrigatórios']);
                exit();
            }

            $this->model->update($id, [
                'openfinance_item_id'    => $itemId,
                'openfinance_account_id' => $accountId,
                'openfinance_provider'   => $provider,
                'openfinance_status'     => 'connected',
            ]);

            AuditLogger::log('openfinance_vinculado', [
                'conta_id'   => $id,
                'item_id'    => $itemId,
                'account_id' => $accountId,
                'provider'   => $provider,
            ]);

            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao salvar vinculação: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno']);
            exit();
        }
    }

    // ================================================================
    // API: Dados do gráfico de evolução de saldo (AJAX)
    // ================================================================

    public function apiEvolucao(int $id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);

            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                http_response_code(403);
                echo json_encode(['error' => 'Acesso negado']);
                exit();
            }

            $dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
            $dataFim    = $_GET['data_fim']    ?? date('Y-m-t');

            $evolucao = $this->movModel->getEvolucaoSaldo($id, $dataInicio, $dataFim);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $evolucao]);
            exit();
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno']);
            exit();
        }
    }

    // ================================================================
    // OPEN FINANCE — Métodos com nomes corretos das rotas
    // ================================================================

    /**
     * GET /financeiro/contas/{id}/openfinance
     * Exibe a página de gerenciamento do Open Finance para a conta.
     */
    public function openfinance(int $id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);
            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                header('Location: /financeiro/contas?error=nao_encontrada');
                exit();
            }

            $integracaoModel  = new Integracao();
            $config           = $integracaoModel->findByProvider('pluggy', $usuarioId)
                             ?: $integracaoModel->findByProviderAtivo('pluggy');
            $openfinanceAtivo = ($config && !empty($config->api_key) && !empty($config->api_secret));

            View::render('contas_bancarias/openfinance', [
                '_layout'          => 'erp',
                'title'            => 'Open Finance — ' . ($conta->nome ?? ''),
                'breadcrumb'       => [
                    'Financeiro'   => '/financeiro/contas',
                    'Contas'       => '/financeiro/contas',
                    0              => 'Open Finance',
                ],
                'conta'            => $conta,
                'openfinanceAtivo' => $openfinanceAtivo,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[OpenFinance] Erro ao abrir página: ' . $e->getMessage());
            header('Location: /financeiro/contas?error=1');
            exit();
        }
    }

    /**
     * POST /financeiro/contas/{id}/openfinance/connect-token
     * Retorna um connect token Pluggy para o widget frontend (AJAX).
     */
    public function connectToken(int $id): void
    {
        header('Content-Type: application/json');
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);
            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                http_response_code(403);
                echo json_encode(['error' => 'Acesso negado']);
                exit();
            }

            $integracaoModel = new Integracao();
            $config = $integracaoModel->findByProvider('pluggy', $usuarioId)
                   ?: $integracaoModel->findByProviderAtivo('pluggy');

            if (!$config || empty($config->api_key) || empty($config->api_secret)) {
                http_response_code(400);
                echo json_encode(['error' => 'Integração Pluggy não configurada. Acesse Configurações → Integrações → Pluggy.']);
                exit();
            }

            $service = new OpenFinanceService($config->api_key, $config->api_secret);
            $itemId  = !empty($conta->openfinance_item_id) ? $conta->openfinance_item_id : null;
            $token   = $service->gerarConnectToken($itemId);

            if (empty($token)) {
                http_response_code(500);
                echo json_encode(['error' => 'Não foi possível gerar o connect token. Verifique as credenciais Pluggy.']);
                exit();
            }

            echo json_encode(['connect_token' => $token]);
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[OpenFinance] Erro ao gerar connect token: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit();
        }
    }

    /**
     * POST /financeiro/contas/{id}/openfinance/salvar
     * Salva o item_id retornado pelo widget Pluggy Connect após conexão bem-sucedida.
     */
    public function salvarConexao(int $id): void
    {
        header('Content-Type: application/json');
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);
            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                http_response_code(403);
                echo json_encode(['error' => 'Acesso negado']);
                exit();
            }

            $input  = json_decode(file_get_contents('php://input'), true) ?? [];
            $itemId = trim($input['item_id'] ?? '');

            if (empty($itemId)) {
                http_response_code(400);
                echo json_encode(['error' => 'item_id é obrigatório']);
                exit();
            }

            $integracaoModel = new Integracao();
            $config = $integracaoModel->findByProvider('pluggy', $usuarioId)
                   ?: $integracaoModel->findByProviderAtivo('pluggy');

            $accountId = $input['account_id'] ?? '';
            $connector = '';

            if ($config && !empty($config->api_key)) {
                try {
                    $service   = new OpenFinanceService($config->api_key, $config->api_secret ?? '');
                    $item      = $service->getItem($itemId);
                    $connector = $item['connector']['name'] ?? '';
                    if (empty($accountId)) {
                        $contas = $service->listarContas($itemId);
                        if (!empty($contas[0]['id'])) {
                            $accountId = $contas[0]['id'];
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->error('[OpenFinance] Erro ao buscar item/contas: ' . $e->getMessage());
                }
            }

            $this->model->update($id, [
                'openfinance_item_id'    => $itemId,
                'openfinance_account_id' => $accountId,
                'openfinance_provider'   => 'pluggy',
                'openfinance_status'     => 'connected',
                'openfinance_connector'  => $connector,
            ]);

            AuditLogger::log('openfinance_conectado', [
                'conta_id'   => $id,
                'item_id'    => $itemId,
                'account_id' => $accountId,
                'connector'  => $connector,
            ]);

            echo json_encode(['success' => true, 'account_id' => $accountId]);
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[OpenFinance] Erro ao salvar conexão: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit();
        }
    }

    /**
     * POST /financeiro/contas/{id}/openfinance/sincronizar
     * Importa as transações da conta via Pluggy (AJAX).
     */
    public function sincronizar(int $id): void
    {
        header('Content-Type: application/json');
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);
            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                http_response_code(403);
                echo json_encode(['error' => 'Acesso negado']);
                exit();
            }

            if (empty($conta->openfinance_account_id)) {
                http_response_code(400);
                echo json_encode(['error' => 'Conta não vinculada ao Open Finance. Conecte primeiro.']);
                exit();
            }

            $integracaoModel = new Integracao();
            $config = $integracaoModel->findByProvider('pluggy', $usuarioId)
                   ?: $integracaoModel->findByProviderAtivo('pluggy');

            if (!$config || empty($config->api_key)) {
                http_response_code(400);
                echo json_encode(['error' => 'Integração Pluggy não configurada.']);
                exit();
            }

            $service   = new OpenFinanceService($config->api_key, $config->api_secret ?? '');
            $resultado = $service->sincronizarTransacoes(
                $conta->openfinance_item_id,
                $conta->openfinance_account_id,
                $id,
                $usuarioId,
                $this->movModel
            );

            $this->model->update($id, [
                'openfinance_last_sync' => date('Y-m-d H:i:s'),
                'openfinance_status'    => 'connected',
            ]);

            $this->recalcularSaldo($id);

            AuditLogger::log('openfinance_sincronizado', [
                'conta_id'   => $id,
                'importadas' => $resultado['importadas'],
                'duplicadas' => $resultado['duplicadas'],
            ]);

            echo json_encode([
                'success'    => true,
                'importadas' => $resultado['importadas'],
                'duplicadas' => $resultado['duplicadas'],
                'erros'      => $resultado['erros'] ?? 0,
                'msg'        => "{$resultado['importadas']} transações importadas, {$resultado['duplicadas']} duplicadas ignoradas.",
            ]);
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[OpenFinance] Erro ao sincronizar: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit();
        }
    }

    /**
     * GET /financeiro/contas/{id}/openfinance/desconectar
     * Desconecta a conta do Open Finance.
     */
    public function desconectar(int $id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta     = $this->model->findById($id);
            if (!$conta || (int) $conta->usuario_id !== $usuarioId) {
                header('Location: /financeiro/contas?error=nao_encontrada');
                exit();
            }

            if (!empty($conta->openfinance_item_id)) {
                $integracaoModel = new Integracao();
                $config = $integracaoModel->findByProvider('pluggy', $usuarioId)
                       ?: $integracaoModel->findByProviderAtivo('pluggy');
                if ($config && !empty($config->api_key)) {
                    try {
                        $service = new OpenFinanceService($config->api_key, $config->api_secret ?? '');
                        $service->desconectarItem($conta->openfinance_item_id);
                    } catch (\Exception $e) {
                        $this->logger->error('[OpenFinance] Erro ao remover item Pluggy: ' . $e->getMessage());
                    }
                }
            }

            $this->model->update($id, [
                'openfinance_item_id'    => null,
                'openfinance_account_id' => null,
                'openfinance_provider'   => null,
                'openfinance_status'     => 'disconnected',
                'openfinance_connector'  => null,
            ]);

            AuditLogger::log('openfinance_desconectado', ['conta_id' => $id]);

            header('Location: /financeiro/contas/' . $id . '/openfinance?success=desconectado');
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[OpenFinance] Erro ao desconectar: ' . $e->getMessage());
            header('Location: /financeiro/contas/' . $id . '/openfinance?error=desconectar');
            exit();
        }
    }

    // ================================================================
    // HELPER PRIVADO: Recalcular saldo atual da conta
    // ================================================================

    private function recalcularSaldo(int $contaId): void
    {
        try {
            $conta = $this->model->findById($contaId);
            if (!$conta) {
                return;
            }

            $pdo  = $this->model->getPdo();
            $stmt = $pdo->prepare(
                "SELECT COALESCE(SUM(valor), 0) FROM contas_movimentacoes WHERE conta_bancaria_id = ?"
            );
            $stmt->execute([$contaId]);
            $totalMovs = (float) $stmt->fetchColumn();

            $novoSaldo = (float) $conta->saldo_inicial + $totalMovs;
            $this->model->updateSaldo($contaId, $novoSaldo);
        } catch (\Exception $e) {
            $this->logger->error('[ContasBancarias] Erro ao recalcular saldo: ' . $e->getMessage());
        }
    }
}
