<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Models\OrdemServico;
use App\Models\EquipamentoCliente;
use App\Models\CrmProposta;
use App\Models\PedidoVenda;
use App\Models\Cliente;
use App\Models\Produto;
use App\Models\User;
use App\Models\EmpresaConfig;
use App\Services\MailService;

class ManutencaoController extends Controller
{
    private OrdemServico      $osModel;
    private EquipamentoCliente $equipModel;
    private CrmProposta       $propostaModel;
    private PedidoVenda       $pedidoVendaModel;
    private Cliente           $clienteModel;
    private Produto           $produtoModel;
    private User              $userModel;
    private Logger            $logger;

    public function __construct()
    {
        $this->osModel          = new OrdemServico();
        $this->equipModel       = new EquipamentoCliente();
        $this->propostaModel    = new CrmProposta();
        $this->pedidoVendaModel = new PedidoVenda();
        $this->clienteModel     = new Cliente();
        $this->produtoModel     = new Produto();
        $this->userModel        = new User();
        $this->logger           = new Logger();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────
    private function usuarioId(): int  { return (int) ($_SESSION['user_id']   ?? 0); }
    private function isAdmin():   bool
    {
        return in_array(strtolower($_SESSION['user_role'] ?? ''), ['admin','superadmin'], true);
    }
    private function jsonError(string $msg): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $msg]);
        exit();
    }
    private function jsonSuccess(array $data = []): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => true], $data));
        exit();
    }

    // =========================================================================
    // GET /manutencao/ordens
    // =========================================================================
    public function index(): void
    {
        $uid     = $this->usuarioId();
        $filtros = [
            'status'   => $_GET['status']   ?? '',
            'tipo'     => $_GET['tipo']      ?? '',
            'q'        => $_GET['q']         ?? '',
            'data_de'  => $_GET['data_de']   ?? '',
            'data_ate' => $_GET['data_ate']  ?? '',
        ];
        $ordens = $this->osModel->findByUsuarioId($uid, $filtros);
        $kpis   = $this->osModel->kpis($uid);

        View::render('manutencao/ordens/index', [
            '_layout'   => 'erp',
            'title'     => 'Ordens de Serviço',
            'breadcrumb'=> ['Manutenção' => '/manutencao/ordens', 'Ordens de Serviço'],
            'ordens'    => $ordens,
            'kpis'      => $kpis,
            'filtros'   => $filtros,
            'isAdmin'   => $this->isAdmin(),
        ]);
    }

    // =========================================================================
    // GET /manutencao/ordens/create
    // =========================================================================
    public function create(): void
    {
        $uid      = $this->usuarioId();
        $clientes = $this->clienteModel->findByUsuarioId($uid);
        $produtos = $this->produtoModel->findByUsuarioId($uid, ['ativo' => 1]);

        View::render('manutencao/ordens/form', [
            '_layout'   => 'erp',
            'title'     => 'Nova Ordem de Serviço',
            'breadcrumb'=> ['Manutenção' => '/manutencao/ordens', 'Ordens de Serviço' => '/manutencao/ordens', 'Nova O.S'],
            'os'        => null,
            'clientes'  => $clientes,
            'produtos'  => $produtos,
            'trocas'    => [],
            'isAdmin'   => $this->isAdmin(),
        ]);
    }

    // =========================================================================
    // POST /manutencao/ordens/store
    // =========================================================================
    public function store(): void
    {
        $uid  = $this->usuarioId();
        $data = $_POST;

        try {
            // Validações
            if (empty($data['cliente_nome'])) {
                $this->jsonError('O nome do cliente é obrigatório.');
            }
            if (empty($data['motivo_chamado'])) {
                $this->jsonError('O motivo do chamado é obrigatório.');
            }
            if (empty($data['tipo'])) {
                $this->jsonError('O tipo de manutenção é obrigatório.');
            }

            $numero = $this->osModel->gerarNumero($uid);
            $token  = bin2hex(random_bytes(16));

            // Criar ou localizar equipamento vinculado ao cliente
            $equipamentoId = null;
            if (!empty($data['numero_serie'])) {
                $equipamentoId = $this->equipModel->findOrCreate([
                    'usuario_id'        => $uid,
                    'cliente_id'        => !empty($data['cliente_id']) ? (int)$data['cliente_id'] : null,
                    'cliente_nome'      => $data['cliente_nome'] ?? '',
                    'produto_id'        => !empty($data['produto_id']) ? (int)$data['produto_id'] : null,
                    'produto_nome'      => $data['produto_nome'] ?? '',
                    'produto_codigo'    => $data['produto_codigo'] ?? null,
                    'numero_serie'      => $data['numero_serie'],
                    'modelo'            => $data['modelo'] ?? null,
                    'marca'             => $data['marca']  ?? null,
                    'vida_util_meses'   => !empty($data['vida_util_meses']) ? (int)$data['vida_util_meses'] : null,
                    'depreciacao_mensal'=> !empty($data['depreciacao_mensal']) ? (float)$data['depreciacao_mensal'] : null,
                ]);
            }

            // Criar a O.S
            $osId = $this->osModel->create([
                'usuario_id'          => $uid,
                'numero'              => $numero,
                'tipo'                => $data['tipo'],
                'status'              => 'aberta',
                'cliente_id'          => !empty($data['cliente_id']) ? (int)$data['cliente_id'] : null,
                'cliente_nome'        => $data['cliente_nome'] ?? '',
                'cliente_cpf_cnpj'    => $data['cliente_cpf_cnpj'] ?? null,
                'cliente_email'       => $data['cliente_email']    ?? null,
                'cliente_telefone'    => $data['cliente_telefone'] ?? null,
                'cliente_endereco'    => $data['cliente_endereco'] ?? null,
                'cliente_cidade'      => $data['cliente_cidade']   ?? null,
                'cliente_estado'      => $data['cliente_estado']   ?? null,
                'equipamento_id'      => $equipamentoId,
                'produto_id'          => !empty($data['produto_id']) ? (int)$data['produto_id'] : null,
                'produto_nome'        => $data['produto_nome']   ?? null,
                'produto_codigo'      => $data['produto_codigo'] ?? null,
                'numero_serie'        => $data['numero_serie']   ?? null,
                'motivo_chamado'      => $data['motivo_chamado'],
                'descricao_servico'   => $data['descricao_servico'] ?? null,
                'data_abertura'       => $data['data_abertura'] ?? date('Y-m-d'),
                'data_previsao'       => !empty($data['data_previsao']) ? $data['data_previsao'] : null,
                'tecnico_responsavel' => $data['tecnico_responsavel'] ?? null,
                'prioridade'          => $data['prioridade'] ?? 'normal',
                'valor_servico'       => $this->toFloat($data['valor_servico'] ?? '0'),
                'valor_pecas'         => 0,
                'valor_total'         => $this->toFloat($data['valor_servico'] ?? '0'),
                'observacoes'         => $data['observacoes'] ?? null,
                'token_impressao'     => $token,
            ]);

            if (!$osId) {
                $this->jsonError('Falha ao criar Ordem de Serviço.');
            }

            // Registrar histórico
            $user = $this->userModel->findById($uid);
            $this->osModel->registrarHistorico(
                $osId, '', 'aberta', $uid,
                'Ordem de Serviço criada.',
                $user->name ?? ''
            );

            // ── Criar Proposta CRM automaticamente ───────────────────────────
            $propostaId = $this->_criarPropostaDaOS($osId, $uid);
            if ($propostaId) {
                $this->osModel->vincularProposta($osId, $propostaId);
                $this->osModel->registrarHistorico(
                    $osId, 'aberta', 'aberta', $uid,
                    "Proposta CRM gerada automaticamente: PROP-{$propostaId}.",
                    $user->name ?? ''
                );
            }

            $this->logger->info('[Manutencao] OS criada', ['id' => $osId, 'numero' => $numero]);

            $this->jsonSuccess([
                'os_id'       => $osId,
                'numero'      => $numero,
                'proposta_id' => $propostaId,
                'redirect'    => "/manutencao/ordens/{$osId}",
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('[Manutencao::store] ' . $e->getMessage());
            $this->jsonError('Erro interno: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // GET /manutencao/ordens/{id}
    // =========================================================================
    public function show(int $id): void
    {
        $uid = $this->usuarioId();
        $os  = $this->osModel->findById($id);
        if (!$os || ((int)$os->usuario_id !== $uid && !$this->isAdmin())) {
            http_response_code(404);
            echo '404 - Ordem de Serviço não encontrada.';
            return;
        }
        $historico = $this->osModel->getHistorico($id);
        $trocas    = $this->osModel->getTrocas($id);
        $produtos  = $this->produtoModel->findByUsuarioId($uid, ['ativo' => 1]);
        $proposta  = !empty($os->proposta_id)
                     ? $this->propostaModel->findById((int)$os->proposta_id)
                     : null;

        View::render('manutencao/ordens/show', [
            '_layout'   => 'erp',
            'title'     => 'O.S ' . $os->numero,
            'breadcrumb'=> ['Manutenção' => '/manutencao/ordens', 'Ordens de Serviço' => '/manutencao/ordens', $os->numero],
            'os'        => $os,
            'historico' => $historico,
            'trocas'    => $trocas,
            'produtos'  => $produtos,
            'proposta'  => $proposta,
            'isAdmin'   => $this->isAdmin(),
        ]);
    }

    // =========================================================================
    // GET /manutencao/ordens/{id}/edit
    // =========================================================================
    public function edit(int $id): void
    {
        $uid = $this->usuarioId();
        $os  = $this->osModel->findById($id);
        if (!$os || ((int)$os->usuario_id !== $uid && !$this->isAdmin())) {
            http_response_code(404);
            echo '404 - Ordem de Serviço não encontrada.';
            return;
        }
        // OS faturada não pode ser editada
        if ($os->status === 'faturada') {
            header('Location: /manutencao/ordens/' . $id);
            exit();
        }
        $clientes = $this->clienteModel->findByUsuarioId($uid);
        $produtos = $this->produtoModel->findByUsuarioId($uid, ['ativo' => 1]);
        $trocas   = $this->osModel->getTrocas($id);

        View::render('manutencao/ordens/form', [
            '_layout'   => 'erp',
            'title'     => 'Editar O.S ' . $os->numero,
            'breadcrumb'=> ['Manutenção' => '/manutencao/ordens', 'Ordens de Serviço' => '/manutencao/ordens', 'Editar ' . $os->numero],
            'os'        => $os,
            'clientes'  => $clientes,
            'produtos'  => $produtos,
            'trocas'    => $trocas,
            'isAdmin'   => $this->isAdmin(),
        ]);
    }

    // =========================================================================
    // POST /manutencao/ordens/{id}/update
    // =========================================================================
    public function update(int $id): void
    {
        $uid  = $this->usuarioId();
        $os   = $this->osModel->findById($id);
        $data = $_POST;

        if (!$os || ((int)$os->usuario_id !== $uid && !$this->isAdmin())) {
            $this->jsonError('Ordem de Serviço não encontrada ou sem permissão.');
        }
        if ($os->status === 'faturada') {
            $this->jsonError('Uma O.S faturada não pode ser editada.');
        }

        try {
            $this->osModel->update($id, [
                'tipo'                => $data['tipo']                ?? $os->tipo,
                'cliente_id'          => !empty($data['cliente_id']) ? (int)$data['cliente_id'] : $os->cliente_id,
                'cliente_nome'        => $data['cliente_nome']        ?? $os->cliente_nome,
                'cliente_cpf_cnpj'    => $data['cliente_cpf_cnpj']   ?? $os->cliente_cpf_cnpj,
                'cliente_email'       => $data['cliente_email']       ?? $os->cliente_email,
                'cliente_telefone'    => $data['cliente_telefone']    ?? $os->cliente_telefone,
                'cliente_endereco'    => $data['cliente_endereco']    ?? $os->cliente_endereco,
                'produto_id'          => !empty($data['produto_id']) ? (int)$data['produto_id'] : $os->produto_id,
                'produto_nome'        => $data['produto_nome']        ?? $os->produto_nome,
                'numero_serie'        => $data['numero_serie']        ?? $os->numero_serie,
                'motivo_chamado'      => $data['motivo_chamado']      ?? $os->motivo_chamado,
                'descricao_servico'   => $data['descricao_servico']   ?? $os->descricao_servico,
                'evolucao'            => $data['evolucao']            ?? $os->evolucao,
                'data_previsao'       => !empty($data['data_previsao']) ? $data['data_previsao'] : $os->data_previsao,
                'tecnico_responsavel' => $data['tecnico_responsavel'] ?? $os->tecnico_responsavel,
                'prioridade'          => $data['prioridade']          ?? $os->prioridade,
                'valor_servico'       => $this->toFloat($data['valor_servico'] ?? (string)$os->valor_servico),
                'observacoes'         => $data['observacoes']         ?? $os->observacoes,
            ]);

            $user = $this->userModel->findById($uid);
            $this->osModel->registrarHistorico(
                $id, $os->status, $os->status, $uid,
                'Dados da O.S atualizados.',
                $user->name ?? ''
            );

            $this->jsonSuccess(['redirect' => "/manutencao/ordens/{$id}"]);

        } catch (\Throwable $e) {
            $this->logger->error('[Manutencao::update] ' . $e->getMessage());
            $this->jsonError('Erro interno: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // POST /manutencao/ordens/{id}/status  (AJAX — alterar status)
    // =========================================================================
    public function alterarStatus(int $id): void
    {
        $uid    = $this->usuarioId();
        $os     = $this->osModel->findById($id);
        $novoStatus = $_POST['status'] ?? '';

        if (!$os || ((int)$os->usuario_id !== $uid && !$this->isAdmin())) {
            $this->jsonError('Sem permissão.');
        }
        if ($os->status === 'faturada') {
            $this->jsonError('Uma O.S faturada não pode ter seu status alterado.');
        }

        $statusValidos = ['aberta','em_andamento','aguardando_peca','concluida','cancelada'];
        if (!in_array($novoStatus, $statusValidos, true)) {
            $this->jsonError('Status inválido.');
        }

        $this->osModel->update($id, ['status' => $novoStatus]);
        $user = $this->userModel->findById($uid);
        $this->osModel->registrarHistorico(
            $id, $os->status, $novoStatus, $uid,
            ($_POST['obs'] ?? 'Status alterado para ' . $novoStatus . '.'),
            $user->name ?? ''
        );

        $this->jsonSuccess(['status' => $novoStatus]);
    }

    // =========================================================================
    // POST /manutencao/ordens/{id}/troca/add  (AJAX — adicionar troca/peça)
    // =========================================================================
    public function addTroca(int $id): void
    {
        $uid = $this->usuarioId();
        $os  = $this->osModel->findById($id);

        if (!$os || ((int)$os->usuario_id !== $uid && !$this->isAdmin())) {
            $this->jsonError('Sem permissão.');
        }
        if ($os->status === 'faturada') {
            $this->jsonError('Não é possível adicionar trocas a uma O.S faturada.');
        }

        $d = $_POST;
        if (empty($d['descricao'])) {
            $this->jsonError('A descrição do item é obrigatória.');
        }

        // Calcular data_proxima_troca a partir de vida_util_meses
        $dataProxTroca = null;
        if (!empty($d['vida_util_meses'])) {
            $dataBase      = !empty($os->data_conclusao) ? $os->data_conclusao : date('Y-m-d');
            $dataProxTroca = date('Y-m-d', strtotime("+{$d['vida_util_meses']} months", strtotime($dataBase)));
        }

        $qty   = (float)($d['quantidade']    ?? 1);
        $preco = $this->toFloat($d['preco_unitario'] ?? '0');

        $trocaId = $this->osModel->addTroca($id, [
            'produto_id'         => !empty($d['produto_id']) ? (int)$d['produto_id'] : null,
            'produto_codigo'     => $d['produto_codigo']  ?? null,
            'descricao'          => $d['descricao'],
            'unidade'            => $d['unidade']         ?? 'UN',
            'quantidade'         => $qty,
            'preco_unitario'     => $preco,
            'preco_total'        => round($qty * $preco, 4),
            'vida_util_meses'    => !empty($d['vida_util_meses']) ? (int)$d['vida_util_meses'] : null,
            'data_proxima_troca' => $dataProxTroca,
            'observacoes'        => $d['observacoes'] ?? null,
        ]);

        if (!$trocaId) {
            $this->jsonError('Falha ao adicionar item de troca.');
        }

        // Recarregar OS para pegar totais atualizados
        $osAtualizada = $this->osModel->findById($id);
        $this->jsonSuccess([
            'troca_id'    => $trocaId,
            'valor_pecas' => number_format((float)$osAtualizada->valor_pecas, 2, ',', '.'),
            'valor_total' => number_format((float)$osAtualizada->valor_total, 2, ',', '.'),
        ]);
    }

    // =========================================================================
    // POST /manutencao/ordens/{id}/troca/{trocaId}/delete  (AJAX)
    // =========================================================================
    public function deleteTroca(int $id, int $trocaId): void
    {
        $uid = $this->usuarioId();
        $os  = $this->osModel->findById($id);
        if (!$os || ((int)$os->usuario_id !== $uid && !$this->isAdmin())) {
            $this->jsonError('Sem permissão.');
        }
        $this->osModel->deleteTroca($trocaId);
        $osAtualizada = $this->osModel->findById($id);
        $this->jsonSuccess([
            'valor_pecas' => number_format((float)$osAtualizada->valor_pecas, 2, ',', '.'),
            'valor_total' => number_format((float)$osAtualizada->valor_total, 2, ',', '.'),
        ]);
    }

    // =========================================================================
    // GET /manutencao/ordens/{id}/imprimir  — view de impressão
    // =========================================================================
    public function imprimir(int $id): void
    {
        $uid = $this->usuarioId();
        $os  = $this->osModel->findById($id);
        if (!$os || ((int)$os->usuario_id !== $uid && !$this->isAdmin())) {
            http_response_code(403);
            echo 'Sem permissão.';
            return;
        }
        $trocas    = $this->osModel->getTrocas($id);
        $historico = $this->osModel->getHistorico($id);
        $empresa   = (new EmpresaConfig())->findByUsuarioId($uid);

        View::render('manutencao/ordens/print', [
            '_layout'   => 'default',
            'title'     => 'Impressão O.S ' . $os->numero,
            'os'        => $os,
            'trocas'    => $trocas,
            'historico' => $historico,
            'empresa'   => $empresa,
        ]);
    }

    // =========================================================================
    // GET /manutencao/ordens/print/{token}  — impressão pública por token
    // =========================================================================
    public function imprimirPublico(string $token): void
    {
        $os = $this->osModel->findByToken($token);
        if (!$os) {
            http_response_code(404);
            echo '404 - Ordem de Serviço não encontrada.';
            return;
        }
        $trocas    = $this->osModel->getTrocas((int)$os->id);
        $historico = $this->osModel->getHistorico((int)$os->id);
        $empresa   = (new EmpresaConfig())->findByUsuarioId((int)$os->usuario_id);

        View::render('manutencao/ordens/print', [
            '_layout'   => 'default',
            'title'     => 'O.S ' . $os->numero,
            'os'        => $os,
            'trocas'    => $trocas,
            'historico' => $historico,
            'empresa'   => $empresa,
        ]);
    }

    // =========================================================================
    // POST /manutencao/ordens/{id}/enviar  (AJAX — enviar por e-mail)
    // =========================================================================
    public function enviar(int $id): void
    {
        $uid = $this->usuarioId();
        $os  = $this->osModel->findById($id);

        if (!$os || ((int)$os->usuario_id !== $uid && !$this->isAdmin())) {
            $this->jsonError('Sem permissão.');
        }
        if (empty($os->cliente_email)) {
            $this->jsonError('O cliente não possui e-mail cadastrado. Edite a O.S e adicione o e-mail.');
        }

        try {
            $trocas    = $this->osModel->getTrocas($id);
            $historico = $this->osModel->getHistorico($id);
            $empresa   = (new EmpresaConfig())->findByUsuarioId($uid);
            $user      = $this->userModel->findById($uid);

            $baseUrl   = defined('BASE_URL') ? rtrim(BASE_URL, '/') : ('https://' . ($_SERVER['HTTP_HOST'] ?? 'erp.inlaudo.com.br'));
            $linkOS    = $baseUrl . '/manutencao/print/' . $os->token_impressao;

            $body = $this->_montarEmailOS($os, $trocas, $empresa, $linkOS);

            $mail = new MailService();
            $mail->send(
                $os->cliente_email,
                "Ordem de Serviço {$os->numero} — " . ($empresa->nome_fantasia ?? 'ERP InLaudo'),
                $body,
                $os->cliente_nome
            );

            $user2 = $this->userModel->findById($uid);
            $this->osModel->registrarHistorico(
                $id, $os->status, $os->status, $uid,
                "O.S enviada por e-mail para {$os->cliente_email}.",
                $user2->name ?? ''
            );

            $this->jsonSuccess(['message' => 'O.S enviada com sucesso para ' . $os->cliente_email]);

        } catch (\Throwable $e) {
            $this->logger->error('[Manutencao::enviar] ' . $e->getMessage());
            $this->jsonError('Erro ao enviar e-mail: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // POST /manutencao/ordens/{id}/cancelar
    // =========================================================================
    public function cancelar(int $id): void
    {
        $uid = $this->usuarioId();
        $os  = $this->osModel->findById($id);
        if (!$os || ((int)$os->usuario_id !== $uid && !$this->isAdmin())) {
            $this->jsonError('Sem permissão.');
        }
        if ($os->status === 'faturada') {
            $this->jsonError('Uma O.S faturada não pode ser cancelada.');
        }
        $this->osModel->update($id, ['status' => 'cancelada']);
        $user = $this->userModel->findById($uid);
        $this->osModel->registrarHistorico($id, $os->status, 'cancelada', $uid, 'O.S cancelada.', $user->name ?? '');
        $this->jsonSuccess(['redirect' => '/manutencao/ordens']);
    }

    // =========================================================================
    // AJAX GET /manutencao/produto-info?id=X  — dados do produto para o form
    // =========================================================================
    public function produtoInfo(): void
    {
        $uid = $this->usuarioId();
        $pid = (int)($_GET['id'] ?? 0);
        if (!$pid) $this->jsonError('ID inválido.');
        $produto = $this->produtoModel->findById($pid);
        if (!$produto || (int)$produto->usuario_id !== $uid) {
            $this->jsonError('Produto não encontrado.');
        }
        $this->jsonSuccess([
            'id'                 => $produto->id,
            'nome'               => $produto->nome,
            'codigo'             => $produto->codigo ?? '',
            'unidade'            => $produto->unidade_medida ?? 'UN',
            'preco_venda'        => (float)($produto->preco_venda ?? 0),
            'preco_custo'        => (float)($produto->preco_custo ?? 0),
            'vida_util_meses'    => (int)($produto->vida_util_meses ?? 0),
            'depreciacao_mensal' => (float)($produto->depreciacao_mensal ?? 0),
            'controla_deprec'    => (int)($produto->controla_depreciacao ?? 0),
        ]);
    }

    // =========================================================================
    // AJAX GET /manutencao/cliente-equipamentos?cliente_id=X
    // =========================================================================
    public function clienteEquipamentos(): void
    {
        $uid = $this->usuarioId();
        $cid = (int)($_GET['cliente_id'] ?? 0);
        if (!$cid) $this->jsonSuccess(['equipamentos' => []]);
        $equips = $this->equipModel->findByCliente($uid, $cid);
        $this->jsonSuccess(['equipamentos' => $equips]);
    }

    // =========================================================================
    // Criar Proposta CRM automaticamente a partir da O.S
    // =========================================================================
    private function _criarPropostaDaOS(int $osId, int $uid): int|false
    {
        try {
            $os = $this->osModel->findById($osId);
            if (!$os) return false;

            $numero    = $this->propostaModel->gerarNumero($uid);
            $token     = bin2hex(random_bytes(32));
            $validade  = date('Y-m-d', strtotime('+30 days'));
            $titulo    = "O.S {$os->numero} — " . ($os->produto_nome ?? 'Manutenção') . ' — ' . $os->cliente_nome;

            $propostaId = $this->propostaModel->create([
                'usuario_id'          => $uid,
                'numero'              => $numero,
                'cliente_id'          => $os->cliente_id,
                'cliente_nome'        => $os->cliente_nome,
                'cliente_cnpj_cpf'    => $os->cliente_cpf_cnpj,
                'cliente_email'       => $os->cliente_email,
                'cliente_telefone'    => $os->cliente_telefone,
                'cliente_endereco'    => $os->cliente_endereco,
                'cliente_cidade'      => $os->cliente_cidade,
                'cliente_estado'      => $os->cliente_estado,
                'titulo'              => $titulo,
                'descricao'           => "Proposta gerada automaticamente a partir da Ordem de Serviço {$os->numero}.\n\nMotivo: {$os->motivo_chamado}",
                'validade_proposta'   => $validade,
                'status'              => 'gerada',
                'prazo_entrega'       => '5 DIAS ÚTEIS',
                'condicao_pagamento'  => 'A VISTA',
                'frete_tipo'          => 'a_calcular',
                'frete_valor'         => 0,
                'local_entrega'       => 'ENDEREÇO DO CLIENTE',
                'observacoes'         => "Ref. O.S {$os->numero} — Tipo: " . strtoupper($os->tipo),
                'notas_internas'      => "OS ID: {$osId}",
                'token_acesso'        => $token,
            ]);

            if (!$propostaId) return false;

            // Adicionar item de serviço se houver valor
            if ((float)$os->valor_servico > 0) {
                $this->propostaModel->salvarItens((int)$propostaId, [[
                    'produto_id'     => $os->produto_id,
                    'codigo'         => $os->produto_codigo ?? '',
                    'descricao'      => 'Serviço de Manutenção ' . ucfirst($os->tipo) . ' — ' . ($os->produto_nome ?? ''),
                    'unidade'        => 'SV',
                    'quantidade'     => 1,
                    'preco_custo'    => 0,
                    'margem_lucro'   => 0,
                    'preco_unitario' => (float)$os->valor_servico,
                    'desconto_item'  => 0,
                ]]);
            }

            $this->propostaModel->recalcularTotais((int)$propostaId);
            $this->propostaModel->updateStatus((int)$propostaId, 'gerada', $uid, "Proposta gerada automaticamente pela O.S {$os->numero}.");

            $this->logger->info('[Manutencao] Proposta CRM criada', ['os_id' => $osId, 'proposta_id' => $propostaId]);
            return (int)$propostaId;

        } catch (\Throwable $e) {
            $this->logger->error('[Manutencao::_criarPropostaDaOS] ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // Montar corpo do e-mail da O.S
    // =========================================================================
    private function _montarEmailOS(object $os, array $trocas, ?object $empresa, string $linkOS): string
    {
        $empresaNome = $empresa->nome_fantasia ?? $empresa->razao_social ?? 'ERP InLaudo';
        $trocasHtml  = '';
        foreach ($trocas as $t) {
            $trocasHtml .= "<tr>
                <td style='padding:6px 8px;border-bottom:1px solid #f0f0f0'>" . htmlspecialchars($t->descricao) . "</td>
                <td style='padding:6px 8px;border-bottom:1px solid #f0f0f0;text-align:center'>" . number_format((float)$t->quantidade, 3, ',', '.') . "</td>
                <td style='padding:6px 8px;border-bottom:1px solid #f0f0f0;text-align:right'>R$ " . number_format((float)$t->preco_total, 2, ',', '.') . "</td>
                <td style='padding:6px 8px;border-bottom:1px solid #f0f0f0;text-align:center'>" . (!empty($t->data_proxima_troca) ? date('d/m/Y', strtotime($t->data_proxima_troca)) : '-') . "</td>
            </tr>";
        }

        $statusLabel = [
            'aberta'         => 'Aberta',
            'em_andamento'   => 'Em Andamento',
            'aguardando_peca'=> 'Aguardando Peça',
            'concluida'      => 'Concluída',
            'faturada'       => 'Faturada',
            'cancelada'      => 'Cancelada',
        ];

        return <<<HTML
<div style="font-family:Arial,sans-serif;max-width:680px;margin:0 auto;background:#f9fafb;padding:24px">
  <div style="background:#1a56db;color:#fff;padding:20px 28px;border-radius:8px 8px 0 0">
    <h2 style="margin:0;font-size:18px">Ordem de Serviço — {$os->numero}</h2>
    <p style="margin:4px 0 0;font-size:13px;opacity:.85">{$empresaNome}</p>
  </div>
  <div style="background:#fff;padding:24px 28px;border:1px solid #e5e7eb;border-top:none">
    <table style="width:100%;font-size:13px;margin-bottom:20px">
      <tr><td style="color:#6b7280;width:40%">Número:</td><td><strong>{$os->numero}</strong></td></tr>
      <tr><td style="color:#6b7280">Tipo:</td><td><strong>" . ucfirst($os->tipo) . "</strong></td></tr>
      <tr><td style="color:#6b7280">Status:</td><td><strong>" . ($statusLabel[$os->status] ?? $os->status) . "</strong></td></tr>
      <tr><td style="color:#6b7280">Data de Abertura:</td><td>" . date('d/m/Y', strtotime($os->data_abertura)) . "</td></tr>
      <tr><td style="color:#6b7280">Equipamento:</td><td>" . htmlspecialchars($os->produto_nome ?? '-') . "</td></tr>
      <tr><td style="color:#6b7280">Número de Série:</td><td>" . htmlspecialchars($os->numero_serie ?? '-') . "</td></tr>
      <tr><td style="color:#6b7280">Motivo:</td><td>" . htmlspecialchars($os->motivo_chamado) . "</td></tr>
    </table>
    " . (!empty($trocasHtml) ? "
    <h3 style='font-size:14px;color:#1e293b;margin:0 0 12px'>Itens Trocados / Serviços Realizados</h3>
    <table style='width:100%;font-size:12px;border-collapse:collapse'>
      <thead><tr style='background:#f1f5f9'>
        <th style='padding:6px 8px;text-align:left'>Descrição</th>
        <th style='padding:6px 8px;text-align:center'>Qtd</th>
        <th style='padding:6px 8px;text-align:right'>Total</th>
        <th style='padding:6px 8px;text-align:center'>Próx. Troca</th>
      </tr></thead>
      <tbody>{$trocasHtml}</tbody>
    </table>" : '') . "
    <div style="margin-top:20px;text-align:center">
      <a href="{$linkOS}" style="background:#1a56db;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-size:13px">
        Visualizar Ordem de Serviço Completa
      </a>
    </div>
  </div>
  <div style="background:#f9fafb;padding:12px 28px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 8px 8px;font-size:11px;color:#9ca3af;text-align:center">
    Este e-mail foi enviado automaticamente pelo ERP InLaudo.
  </div>
</div>
HTML;
    }

    // =========================================================================
    // Helper: converter string monetária para float
    // =========================================================================
    private function toFloat(string $v): float
    {
        $s = trim($v);
        if (preg_match('/^-?[\d.]+,[\d]{1,2}$/', $s)) {
            return (float) str_replace(['.', ','], ['', '.'], $s);
        }
        return (float) str_replace(',', '', $s);
    }
}
