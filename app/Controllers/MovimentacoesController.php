<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Models\MovimentacaoEstoque;
use App\Models\PedidoCompra;
use App\Models\PedidoVenda;
use App\Models\Produto;
use App\Models\User;

class MovimentacoesController extends Controller
{
    private MovimentacaoEstoque $movModel;
    private PedidoCompra        $pcModel;
    private PedidoVenda         $pvModel;
    private Produto             $produtoModel;
    private User                $userModel;
    private Logger              $logger;

    public function __construct()
    {
        $this->movModel     = new MovimentacaoEstoque();
        $this->pcModel      = new PedidoCompra();
        $this->pvModel      = new PedidoVenda();
        $this->produtoModel = new Produto();
        $this->userModel    = new User();
        $this->logger       = new Logger();
    }

    private function uid(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    private function isAdmin(): bool
    {
        return in_array(strtolower($_SESSION['user_role'] ?? ''), ['admin', 'superadmin'], true);
    }

    private function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function jsonError(string $msg, int $code = 400): void
    {
        $this->json(['success' => false, 'error' => $msg], $code);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MOVIMENTAÇÕES — Listagem geral
    // GET /estoque/movimentacoes
    // ═══════════════════════════════════════════════════════════════════════
    public function index(): void
    {
        $uid     = $this->uid();
        $filtros = [
            'tipo'        => $_GET['tipo']        ?? '',
            'origem'      => $_GET['origem']      ?? '',
            'produto_id'  => $_GET['produto_id']  ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim'    => $_GET['data_fim']    ?? date('Y-m-d'),
            'q'           => $_GET['q']           ?? '',
            'limit'       => 200,
        ];
        if (empty($filtros['data_inicio'])) {
            $filtros['data_inicio'] = date('Y-m-01');
        }

        $movimentacoes = $this->movModel->findByUsuarioId($uid, $filtros);
        $kpis          = $this->movModel->kpis($uid, $filtros['data_inicio'], $filtros['data_fim']);

        View::render('estoque/movimentacoes/index', [
            '_layout'       => 'erp',
            'title'         => 'Movimentações de Estoque',
            'breadcrumb'    => [
                ['label' => 'Estoque', 'url' => '/estoque/produtos'],
                ['label' => 'Movimentações'],
            ],
            'movimentacoes' => $movimentacoes,
            'kpis'          => $kpis,
            'filtros'       => $filtros,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MOVIMENTAÇÃO MANUAL — Formulário
    // GET /estoque/movimentacoes/create
    // ═══════════════════════════════════════════════════════════════════════
    public function create(): void
    {
        $uid      = $this->uid();
        $tipo     = $_GET['tipo'] ?? 'entrada'; // entrada | saida | ajuste
        $produtos = $this->produtoModel->findByUsuarioId($uid);

        View::render('estoque/movimentacoes/form_manual', [
            '_layout'   => 'erp',
            'title'     => $tipo === 'saida' ? 'Saída Manual de Estoque' : 'Entrada Manual de Estoque',
            'breadcrumb'=> [
                ['label' => 'Estoque', 'url' => '/estoque/produtos'],
                ['label' => 'Movimentações', 'url' => '/estoque/movimentacoes'],
                ['label' => $tipo === 'saida' ? 'Nova Saída' : 'Nova Entrada'],
            ],
            'tipo'      => $tipo,
            'produtos'  => $produtos,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MOVIMENTAÇÃO MANUAL — Salvar
    // POST /estoque/movimentacoes
    // ═══════════════════════════════════════════════════════════════════════
    public function store(): void
    {
        $uid  = $this->uid();
        $data = $_POST;

        if (empty($data['produto_id']) || empty($data['quantidade'])) {
            header('Location: /estoque/movimentacoes/create?error=campos_obrigatorios');
            exit;
        }

        $movId = $this->movModel->registrar([
            'usuario_id'  => $uid,
            'produto_id'  => (int)$data['produto_id'],
            'tipo'        => $data['tipo'] ?? 'entrada',
            'origem'      => 'manual',
            'quantidade'  => (float)str_replace(',', '.', $data['quantidade']),
            'unidade'     => $data['unidade'] ?? 'UN',
            'preco_unitario' => (float)str_replace(['.', ','], ['', '.'], $data['preco_unitario'] ?? '0'),
            'custo_unitario' => (float)str_replace(['.', ','], ['', '.'], $data['custo_unitario'] ?? '0'),
            'lote'           => $data['lote'] ?? null,
            'data_fabricacao'=> !empty($data['data_fabricacao']) ? $data['data_fabricacao'] : null,
            'data_validade'  => !empty($data['data_validade']) ? $data['data_validade'] : null,
            'localizacao'    => $data['localizacao'] ?? null,
            'motivo'         => $data['motivo'] ?? null,
            'observacoes'    => $data['observacoes'] ?? null,
        ]);

        if ($movId) {
            header('Location: /estoque/movimentacoes/' . $movId . '?success=registrado');
        } else {
            header('Location: /estoque/movimentacoes/create?error=save_failed&tipo=' . ($data['tipo'] ?? 'entrada'));
        }
        exit;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MOVIMENTAÇÃO — Visualizar
    // GET /estoque/movimentacoes/{id}
    // ═══════════════════════════════════════════════════════════════════════
    public function show(int $id): void
    {
        $uid = $this->uid();
        $mov = $this->movModel->findById($id);

        if (!$mov || ($mov->usuario_id != $uid && !$this->isAdmin())) {
            header('Location: /estoque/movimentacoes?error=not_found');
            exit;
        }

        View::render('estoque/movimentacoes/show', [
            '_layout'    => 'erp',
            'title'      => 'Movimentação #' . $id,
            'breadcrumb' => [
                ['label' => 'Estoque', 'url' => '/estoque/produtos'],
                ['label' => 'Movimentações', 'url' => '/estoque/movimentacoes'],
                ['label' => '#' . $id],
            ],
            'mov'        => $mov,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // IMPORTAÇÃO XML NF-e — Formulário
    // GET /estoque/movimentacoes/importar-xml
    // ═══════════════════════════════════════════════════════════════════════
    public function importarXmlForm(): void
    {
        View::render('estoque/movimentacoes/importar_xml', [
            '_layout'    => 'erp',
            'title'      => 'Importar NF-e (XML)',
            'breadcrumb' => [
                ['label' => 'Estoque', 'url' => '/estoque/produtos'],
                ['label' => 'Movimentações', 'url' => '/estoque/movimentacoes'],
                ['label' => 'Importar XML NF-e'],
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // IMPORTAÇÃO XML NF-e — Processar
    // POST /estoque/movimentacoes/importar-xml
    // ═══════════════════════════════════════════════════════════════════════
    public function importarXml(): void
    {
        $uid = $this->uid();

        if (empty($_FILES['xml_nfe']) || $_FILES['xml_nfe']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'error' => 'Arquivo XML não enviado ou inválido.'], 400);
        }

        $xmlContent = file_get_contents($_FILES['xml_nfe']['tmp_name']);
        if (empty($xmlContent)) {
            $this->json(['success' => false, 'error' => 'Arquivo XML vazio.'], 400);
        }

        $dados = $this->pcModel->parseNfeXml($xmlContent);
        if (!$dados) {
            $this->json(['success' => false, 'error' => 'Não foi possível interpretar o XML da NF-e. Verifique se é um arquivo de NF-e válido.'], 422);
        }

        // Salva o XML no servidor
        $xmlDir  = defined('BASE_PATH') ? BASE_PATH . '/storage/nfe_xml' : sys_get_temp_dir();
        if (!is_dir($xmlDir)) @mkdir($xmlDir, 0755, true);
        $xmlPath = $xmlDir . '/' . ($dados['nfe_chave'] ?: uniqid('nfe_')) . '.xml';
        file_put_contents($xmlPath, $xmlContent);
        $dados['nfe_xml_path'] = 'storage/nfe_xml/' . basename($xmlPath);

        // Tenta casar produtos pelo código
        foreach ($dados['itens'] as &$item) {
            if (!empty($item['codigo_produto'])) {
                $prod = $this->produtoModel->findByCodigo($item['codigo_produto'], $uid);
                if ($prod) {
                    $item['produto_id']   = $prod->id;
                    $item['produto_nome'] = $prod->nome;
                    $item['preco_custo']  = $prod->preco_custo;
                }
            }
        }
        unset($item);

        $this->json(['success' => true, 'dados' => $dados]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // IMPORTAÇÃO XML — Confirmar e criar pedido de compra
    // POST /estoque/movimentacoes/importar-xml/confirmar
    // ═══════════════════════════════════════════════════════════════════════
    public function importarXmlConfirmar(): void
    {
        $uid  = $this->uid();
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $itens = $data['itens'] ?? [];
        if (empty($itens)) {
            $this->jsonError('Nenhum item para importar.');
        }

        $numero   = $this->pcModel->gerarNumero($uid);
        $pedidoId = $this->pcModel->create(array_merge($data, [
            'usuario_id' => $uid,
            'numero'     => $numero,
            'status'     => 'recebido',
            'data_pedido'=> $data['nfe_data_emissao'] ?? date('Y-m-d'),
        ]), $itens);

        if (!$pedidoId) {
            $this->jsonError('Erro ao criar pedido de compra. Verifique os logs.');
        }

        // Registra movimentação de entrada para cada item
        foreach ($itens as $item) {
            if (empty($item['produto_id']) || empty($item['quantidade'])) continue;
            $this->movModel->registrar([
                'usuario_id'        => $uid,
                'produto_id'        => (int)$item['produto_id'],
                'tipo'              => 'entrada',
                'origem'            => 'xml_nfe',
                'pedido_compra_id'  => $pedidoId,
                'nfe_chave'         => $data['nfe_chave'] ?? null,
                'nfe_numero'        => $data['nfe_numero'] ?? null,
                'nfe_serie'         => $data['nfe_serie'] ?? null,
                'nfe_emitente_cnpj' => $data['fornecedor_cnpj'] ?? null,
                'nfe_emitente_nome' => $data['fornecedor_nome'] ?? null,
                'nfe_data_emissao'  => $data['nfe_data_emissao'] ?? null,
                'quantidade'        => (float)$item['quantidade'],
                'unidade'           => $item['unidade'] ?? 'UN',
                'preco_unitario'    => (float)($item['preco_unitario'] ?? 0),
                'custo_unitario'    => (float)($item['preco_unitario'] ?? 0),
                'lote'              => $item['lote'] ?? null,
                'data_validade'     => $item['data_validade'] ?? null,
                'motivo'            => 'Entrada via importação NF-e ' . ($data['nfe_numero'] ?? ''),
            ]);
        }

        $this->json([
            'success'   => true,
            'pedido_id' => $pedidoId,
            'redirect'  => '/estoque/compras/' . $pedidoId,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDOS DE COMPRA — Listagem
    // GET /estoque/compras
    // ═══════════════════════════════════════════════════════════════════════
    public function compras(): void
    {
        $uid     = $this->uid();
        $filtros = [
            'status'      => $_GET['status']      ?? '',
            'q'           => $_GET['q']           ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? date('Y-m-01'),
            'data_fim'    => $_GET['data_fim']    ?? date('Y-m-d'),
        ];
        $pedidos = $this->pcModel->findByUsuarioId($uid, $filtros);
        $kpis    = $this->pcModel->kpis($uid);

        View::render('estoque/movimentacoes/compras_index', [
            '_layout'    => 'erp',
            'title'      => 'Pedidos de Compra',
            'breadcrumb' => [
                ['label' => 'Estoque', 'url' => '/estoque/produtos'],
                ['label' => 'Pedidos de Compra'],
            ],
            'pedidos'    => $pedidos,
            'kpis'       => $kpis,
            'filtros'    => $filtros,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE COMPRA — Novo formulário
    // GET /estoque/compras/create
    // ═══════════════════════════════════════════════════════════════════════
    public function compraCreate(): void
    {
        $uid      = $this->uid();
        $produtos = $this->produtoModel->findByUsuarioId($uid);
        $numero   = $this->pcModel->gerarNumero($uid);

        View::render('estoque/movimentacoes/compra_form', [
            '_layout'    => 'erp',
            'title'      => 'Novo Pedido de Compra',
            'breadcrumb' => [
                ['label' => 'Estoque', 'url' => '/estoque/produtos'],
                ['label' => 'Pedidos de Compra', 'url' => '/estoque/compras'],
                ['label' => 'Novo'],
            ],
            'pedido'     => null,
            'produtos'   => $produtos,
            'numero'     => $numero,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE COMPRA — Salvar
    // POST /estoque/compras
    // ═══════════════════════════════════════════════════════════════════════
    public function compraStore(): void
    {
        $uid   = $this->uid();
        $data  = $_POST;
        $itens = $this->_parseItens('item_produto_id', 'item_descricao', 'item_quantidade', 'item_preco_unitario', 'item_unidade', 'item_desconto_perc', 'item_lote', 'item_data_validade');

        if (empty($data['fornecedor_nome']) || empty($itens)) {
            header('Location: /estoque/compras/create?error=campos_obrigatorios');
            exit;
        }

        $numero   = $this->pcModel->gerarNumero($uid);
        $pedidoId = $this->pcModel->create(array_merge($data, [
            'usuario_id' => $uid,
            'numero'     => $numero,
        ]), $itens);

        if ($pedidoId) {
            // Se status = recebido, registra entrada no estoque
            if (($data['status'] ?? '') === 'recebido') {
                $this->_registrarEntradaCompra($pedidoId, $uid, $itens, $data);
            }
            header('Location: /estoque/compras/' . $pedidoId . '?success=criado');
        } else {
            header('Location: /estoque/compras/create?error=save_failed');
        }
        exit;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE COMPRA — Editar
    // GET /estoque/compras/{id}/edit
    // ═══════════════════════════════════════════════════════════════════════
    public function compraEdit(int $id): void
    {
        $uid    = $this->uid();
        $pedido = $this->pcModel->findById($id);

        if (!$pedido || ($pedido->usuario_id != $uid && !$this->isAdmin())) {
            header('Location: /estoque/compras?error=not_found');
            exit;
        }

        $produtos = $this->produtoModel->findByUsuarioId($uid);

        View::render('estoque/movimentacoes/compra_form', [
            '_layout'    => 'erp',
            'title'      => 'Editar Pedido de Compra #' . $pedido->numero,
            'breadcrumb' => [
                ['label' => 'Estoque', 'url' => '/estoque/produtos'],
                ['label' => 'Pedidos de Compra', 'url' => '/estoque/compras'],
                ['label' => '#' . $pedido->numero],
            ],
            'pedido'     => $pedido,
            'produtos'   => $produtos,
            'numero'     => $pedido->numero,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE COMPRA — Atualizar
    // POST /estoque/compras/{id}/update
    // ═══════════════════════════════════════════════════════════════════════
    public function compraUpdate(int $id): void
    {
        $uid    = $this->uid();
        $pedido = $this->pcModel->findById($id);

        if (!$pedido || ($pedido->usuario_id != $uid && !$this->isAdmin())) {
            header('Location: /estoque/compras?error=not_found');
            exit;
        }

        $data  = $_POST;
        $itens = $this->_parseItens('item_produto_id', 'item_descricao', 'item_quantidade', 'item_preco_unitario', 'item_unidade', 'item_desconto_perc', 'item_lote', 'item_data_validade');

        $ok = $this->pcModel->update($id, array_merge($data, ['usuario_id' => $uid]), $itens);

        // Se mudou para recebido e ainda não tinha movimentação de entrada
        if ($ok && ($data['status'] ?? '') === 'recebido' && $pedido->status !== 'recebido') {
            $this->_registrarEntradaCompra($id, $uid, $itens, $data);
        }

        header('Location: /estoque/compras/' . $id . ($ok ? '?success=atualizado' : '?error=save_failed'));
        exit;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE COMPRA — Visualizar
    // GET /estoque/compras/{id}
    // ═══════════════════════════════════════════════════════════════════════
    public function compraShow(int $id): void
    {
        $uid    = $this->uid();
        $pedido = $this->pcModel->findById($id);

        if (!$pedido || ($pedido->usuario_id != $uid && !$this->isAdmin())) {
            header('Location: /estoque/compras?error=not_found');
            exit;
        }

        $movs = $this->movModel->findByUsuarioId($uid, ['pedido_compra_id' => $id]);

        View::render('estoque/movimentacoes/compra_show', [
            '_layout'    => 'erp',
            'title'      => 'Pedido de Compra ' . $pedido->numero,
            'breadcrumb' => [
                ['label' => 'Estoque', 'url' => '/estoque/produtos'],
                ['label' => 'Pedidos de Compra', 'url' => '/estoque/compras'],
                ['label' => $pedido->numero],
            ],
            'pedido'     => $pedido,
            'movs'       => $movs,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE COMPRA — Excluir
    // POST /estoque/compras/{id}/delete
    // ═══════════════════════════════════════════════════════════════════════
    public function compraDelete(int $id): void
    {
        $uid    = $this->uid();
        $pedido = $this->pcModel->findById($id);

        if (!$pedido || ($pedido->usuario_id != $uid && !$this->isAdmin())) {
            header('Location: /estoque/compras?error=not_found');
            exit;
        }

        $ok = $this->pcModel->delete($id, $uid);
        header('Location: /estoque/compras' . ($ok ? '?success=excluido' : '?error=delete_failed'));
        exit;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDOS DE VENDA — Listagem
    // GET /estoque/vendas
    // ═══════════════════════════════════════════════════════════════════════
    public function vendas(): void
    {
        $uid     = $this->uid();
        $filtros = [
            'status'      => $_GET['status']      ?? '',
            'q'           => $_GET['q']           ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? date('Y-m-01'),
            'data_fim'    => $_GET['data_fim']    ?? date('Y-m-d'),
        ];
        $pedidos = $this->pvModel->findByUsuarioId($uid, $filtros);
        $kpis    = $this->pvModel->kpis($uid);

        View::render('estoque/movimentacoes/vendas_index', [
            '_layout'    => 'erp',
            'title'      => 'Pedidos de Venda',
            'breadcrumb' => [
                ['label' => 'Estoque', 'url' => '/estoque/produtos'],
                ['label' => 'Pedidos de Venda'],
            ],
            'pedidos'    => $pedidos,
            'kpis'       => $kpis,
            'filtros'    => $filtros,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE VENDA — Novo formulário
    // GET /estoque/vendas/create
    // ═══════════════════════════════════════════════════════════════════════
    public function vendaCreate(): void
    {
        $uid      = $this->uid();
        $produtos = $this->produtoModel->findByUsuarioId($uid);
        $numero   = $this->pvModel->gerarNumero($uid);

        View::render('estoque/movimentacoes/venda_form', [
            '_layout'    => 'erp',
            'title'      => 'Novo Pedido de Venda',
            'breadcrumb' => [
                ['label' => 'Estoque', 'url' => '/estoque/produtos'],
                ['label' => 'Pedidos de Venda', 'url' => '/estoque/vendas'],
                ['label' => 'Novo'],
            ],
            'pedido'     => null,
            'produtos'   => $produtos,
            'numero'     => $numero,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE VENDA — Salvar
    // POST /estoque/vendas
    // ═══════════════════════════════════════════════════════════════════════
    public function vendaStore(): void
    {
        $uid   = $this->uid();
        $data  = $_POST;
        $itens = $this->_parseItens('item_produto_id', 'item_descricao', 'item_quantidade', 'item_preco_unitario', 'item_unidade', 'item_desconto_perc', 'item_lote', 'item_data_validade', 'item_preco_custo');

        if (empty($data['cliente_nome']) || empty($itens)) {
            header('Location: /estoque/vendas/create?error=campos_obrigatorios');
            exit;
        }

        $numero   = $this->pvModel->gerarNumero($uid);
        $pedidoId = $this->pvModel->create(array_merge($data, [
            'usuario_id' => $uid,
            'numero'     => $numero,
        ]), $itens);

        if ($pedidoId) {
            // Se status = entregue, registra saída no estoque
            if (($data['status'] ?? '') === 'entregue') {
                $this->_registrarSaidaVenda($pedidoId, $uid, $itens, $data);
            }
            header('Location: /estoque/vendas/' . $pedidoId . '?success=criado');
        } else {
            header('Location: /estoque/vendas/create?error=save_failed');
        }
        exit;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE VENDA — Editar
    // GET /estoque/vendas/{id}/edit
    // ═══════════════════════════════════════════════════════════════════════
    public function vendaEdit(int $id): void
    {
        $uid    = $this->uid();
        $pedido = $this->pvModel->findById($id);

        if (!$pedido || ($pedido->usuario_id != $uid && !$this->isAdmin())) {
            header('Location: /estoque/vendas?error=not_found');
            exit;
        }

        $produtos = $this->produtoModel->findByUsuarioId($uid);

        View::render('estoque/movimentacoes/venda_form', [
            '_layout'    => 'erp',
            'title'      => 'Editar Pedido de Venda #' . $pedido->numero,
            'breadcrumb' => [
                ['label' => 'Estoque', 'url' => '/estoque/produtos'],
                ['label' => 'Pedidos de Venda', 'url' => '/estoque/vendas'],
                ['label' => '#' . $pedido->numero],
            ],
            'pedido'     => $pedido,
            'produtos'   => $produtos,
            'numero'     => $pedido->numero,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE VENDA — Atualizar
    // POST /estoque/vendas/{id}/update
    // ═══════════════════════════════════════════════════════════════════════
    public function vendaUpdate(int $id): void
    {
        $uid    = $this->uid();
        $pedido = $this->pvModel->findById($id);

        if (!$pedido || ($pedido->usuario_id != $uid && !$this->isAdmin())) {
            header('Location: /estoque/vendas?error=not_found');
            exit;
        }

        $data  = $_POST;
        $itens = $this->_parseItens('item_produto_id', 'item_descricao', 'item_quantidade', 'item_preco_unitario', 'item_unidade', 'item_desconto_perc', 'item_lote', 'item_data_validade', 'item_preco_custo');

        $ok = $this->pvModel->update($id, array_merge($data, ['usuario_id' => $uid]), $itens);

        if ($ok && ($data['status'] ?? '') === 'entregue' && $pedido->status !== 'entregue') {
            $this->_registrarSaidaVenda($id, $uid, $itens, $data);
        }

        header('Location: /estoque/vendas/' . $id . ($ok ? '?success=atualizado' : '?error=save_failed'));
        exit;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE VENDA — Visualizar
    // GET /estoque/vendas/{id}
    // ═══════════════════════════════════════════════════════════════════════
    public function vendaShow(int $id): void
    {
        $uid    = $this->uid();
        $pedido = $this->pvModel->findById($id);

        if (!$pedido || ($pedido->usuario_id != $uid && !$this->isAdmin())) {
            header('Location: /estoque/vendas?error=not_found');
            exit;
        }

        View::render('estoque/movimentacoes/venda_show', [
            '_layout'    => 'erp',
            'title'      => 'Pedido de Venda ' . $pedido->numero,
            'breadcrumb' => [
                ['label' => 'Estoque', 'url' => '/estoque/produtos'],
                ['label' => 'Pedidos de Venda', 'url' => '/estoque/vendas'],
                ['label' => $pedido->numero],
            ],
            'pedido'     => $pedido,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE VENDA — Excluir
    // POST /estoque/vendas/{id}/delete
    // ═══════════════════════════════════════════════════════════════════════
    public function vendaDelete(int $id): void
    {
        $uid    = $this->uid();
        $pedido = $this->pvModel->findById($id);

        if (!$pedido || ($pedido->usuario_id != $uid && !$this->isAdmin())) {
            header('Location: /estoque/vendas?error=not_found');
            exit;
        }

        $ok = $this->pvModel->delete($id, $uid);
        header('Location: /estoque/vendas' . ($ok ? '?success=excluido' : '?error=delete_failed'));
        exit;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AJAX — Buscar produto por código ou nome
    // GET /estoque/movimentacoes/buscar-produto
    // ═══════════════════════════════════════════════════════════════════════
    public function buscarProduto(): void
    {
        $uid = $this->uid();
        $q   = trim($_GET['q'] ?? '');
        // Permite busca com 1 ou mais caracteres para maior flexibilidade
        if (strlen($q) < 1) {
            $this->json([]);
            return;
        }
        // Usa o metodo buscar() do Model que ja trata status=ativo e busca por nome/codigo/marca/modelo
        // Adiciona busca por nome_tecnico via query direta usando getPdo() exposto
        try {
            $like   = '%' . $q . '%';
            $stmt   = $this->produtoModel->getPdo()->prepare(
                "SELECT id, codigo, nome, preco_venda, preco_custo, estoque_atual, unidade_medida
                 FROM produtos
                 WHERE usuario_id = ?
                   AND status = 'ativo'
                   AND (nome LIKE ? OR codigo LIKE ?
                        OR (nome_tecnico IS NOT NULL AND nome_tecnico LIKE ?)
                        OR (marca IS NOT NULL AND marca LIKE ?)
                        OR (modelo IS NOT NULL AND modelo LIKE ?))
                 ORDER BY nome ASC
                 LIMIT 30"
            );
            $stmt->execute([$uid, $like, $like, $like, $like, $like]);
            $this->json($stmt->fetchAll(\PDO::FETCH_OBJ));
        } catch (\Throwable $e) {
            // Fallback: usa o metodo do model sem nome_tecnico
            $resultados = $this->produtoModel->buscar($uid, $q, 30);
            $this->json($resultados);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE COMPRA — Index (alias para compras())
    // GET /estoque/compras  — rota registrada como comprasIndex
    // ═══════════════════════════════════════════════════════════════════════
    public function comprasIndex(): void
    {
        $this->compras();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE COMPRA — Criar (alias para compraCreate)
    // ═══════════════════════════════════════════════════════════════════════
    public function comprasCreate(): void
    {
        $this->compraCreate();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE COMPRA — Salvar (alias para compraStore)
    // ═══════════════════════════════════════════════════════════════════════
    public function comprasStore(): void
    {
        $this->compraStore();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE COMPRA — Show (alias para compraShow)
    // ═══════════════════════════════════════════════════════════════════════
    public function comprasShow(int $id): void
    {
        $this->compraShow($id);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE COMPRA — Receber (alias para compraReceber)
    // ═══════════════════════════════════════════════════════════════════════
    public function comprasReceber(int $id): void
    {
        $this->compraReceber($id);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PEDIDO DE COMPRA — Cancelar (alias para compraCancelar)
    // ═══════════════════════════════════════════════════════════════════════
    public function comprasCancelar(int $id): void
    {
        $this->compraCancelar($id);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ═══════════════════════════════════════════════════════════════════════

    private function _parseItens(
        string $kProdId,
        string $kDesc,
        string $kQty,
        string $kPreco,
        string $kUnid,
        string $kDesc_perc,
        string $kLote,
        string $kValidade,
        string $kCusto = ''
    ): array {
        $itens   = [];
        $prodIds = $_POST[$kProdId] ?? [];
        $descs   = $_POST[$kDesc]   ?? [];
        $qtys    = $_POST[$kQty]    ?? [];
        $precos  = $_POST[$kPreco]  ?? [];
        $unids   = $_POST[$kUnid]   ?? [];
        $descp   = $_POST[$kDesc_perc] ?? [];
        $lotes   = $_POST[$kLote]   ?? [];
        $vals    = $_POST[$kValidade] ?? [];
        $custos  = $kCusto ? ($_POST[$kCusto] ?? []) : [];

        foreach ($qtys as $i => $qty) {
            if (empty($qty) || (float)str_replace(',', '.', $qty) <= 0) continue;
            $itens[] = [
                'produto_id'     => !empty($prodIds[$i]) ? (int)$prodIds[$i] : null,
                'descricao'      => $descs[$i] ?? '',
                'quantidade'     => (float)str_replace(',', '.', $qty),
                'preco_unitario' => (float)str_replace(['.', ','], ['', '.'], $precos[$i] ?? '0'),
                'preco_custo'    => $kCusto ? (float)str_replace(['.', ','], ['', '.'], $custos[$i] ?? '0') : 0,
                'unidade'        => $unids[$i] ?? 'UN',
                'desconto_perc'  => (float)($descp[$i] ?? 0),
                'lote'           => $lotes[$i] ?? null,
                'data_validade'  => !empty($vals[$i]) ? $vals[$i] : null,
            ];
        }
        return $itens;
    }

    private function _registrarEntradaCompra(int $pedidoId, int $uid, array $itens, array $data): void
    {
        foreach ($itens as $item) {
            if (empty($item['produto_id']) || empty($item['quantidade'])) continue;
            $this->movModel->registrar([
                'usuario_id'       => $uid,
                'produto_id'       => (int)$item['produto_id'],
                'tipo'             => 'entrada',
                'origem'           => 'pedido_compra',
                'pedido_compra_id' => $pedidoId,
                'quantidade'       => (float)$item['quantidade'],
                'unidade'          => $item['unidade'] ?? 'UN',
                'preco_unitario'   => (float)($item['preco_unitario'] ?? 0),
                'custo_unitario'   => (float)($item['preco_unitario'] ?? 0),
                'lote'             => $item['lote'] ?? null,
                'data_validade'    => $item['data_validade'] ?? null,
                'motivo'           => 'Recebimento pedido de compra',
            ]);
        }
    }

    private function _registrarSaidaVenda(int $pedidoId, int $uid, array $itens, array $data): void
    {
        foreach ($itens as $item) {
            if (empty($item['produto_id']) || empty($item['quantidade'])) continue;
            $this->movModel->registrar([
                'usuario_id'      => $uid,
                'produto_id'      => (int)$item['produto_id'],
                'tipo'            => 'saida',
                'origem'          => 'pedido_venda',
                'pedido_venda_id' => $pedidoId,
                'quantidade'      => (float)$item['quantidade'],
                'unidade'         => $item['unidade'] ?? 'UN',
                'preco_unitario'  => (float)($item['preco_unitario'] ?? 0),
                'custo_unitario'  => (float)($item['preco_custo'] ?? 0),
                'lote'            => $item['lote'] ?? null,
                'motivo'          => 'Saída pedido de venda',
            ]);
        }
    }
}
