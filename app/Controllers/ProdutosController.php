<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Models\Produto;
use App\Models\ProdutoComponente;
use App\Models\ProdutoComissao;
use App\Models\Fornecedor;
use App\Models\Colaborador;

class ProdutosController extends Controller
{
    private const BASE_ROUTE = '/estoque/produtos';

    private Produto           $model;
    private ProdutoComponente $compModel;
    private ProdutoComissao   $comissaoModel;
    private Logger            $logger;

    public function __construct()
    {
        $this->model         = new Produto();
        $this->compModel     = new ProdutoComponente();
        $this->comissaoModel = new ProdutoComissao();
        $this->logger        = new Logger();
    }

    // ─── Listagem ────────────────────────────────────────────────────────────
    public function index(): void
    {
        try {
            $uid     = Auth::user()->id;
            $filtros = [
                'q'           => $_GET['q']          ?? '',
                'tipo'        => $_GET['tipo']        ?? '',
                'categoria'   => $_GET['categoria']   ?? '',
                'status'      => $_GET['status']      ?? 'ativo',
                'fabricante_id' => $_GET['fabricante_id'] ?? '',
                'estoque_baixo' => !empty($_GET['estoque_baixo']),
            ];
            $produtos     = $this->model->findByUsuarioId($uid, $filtros);
            $kpis         = $this->model->kpis($uid);
            $fornecedores = (new Fornecedor())->findByUsuarioId($uid);

            View::render('estoque/produtos/index', [
                '_layout'     => 'erp',
                'title'       => 'Produtos e Serviços',
                'breadcrumb'  => ['Estoque' => '#', 0 => 'Produtos'],
                'produtos'    => $produtos,
                'kpis'        => $kpis,
                'filtros'     => $filtros,
                'fornecedores'=> $fornecedores,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ProdutosController::index] ' . $e->getMessage());
            header('Location: /dashboard?error=1');
            exit();
        }
    }

    // ─── Formulário de criação ───────────────────────────────────────────────
    public function create(): void
    {
        try {
            $uid          = Auth::user()->id;
            $fornecedores = (new Fornecedor())->findByUsuarioId($uid);
            $colaboradores = method_exists(new Colaborador(), 'findByUsuarioId')
                             ? (new Colaborador())->findByUsuarioId($uid)
                             : [];
            $proximoCodigo = $this->model->gerarCodigo($uid);

            View::render('estoque/produtos/form', [
                '_layout'      => 'erp',
                'title'        => 'Novo Produto',
                'breadcrumb'   => ['Estoque' => '#', 'Produtos' => self::BASE_ROUTE, 0 => 'Novo'],
                'produto'      => null,
                'componentes'  => [],
                'comissoes'    => [],
                'historico_precos' => [],
                'fornecedores' => $fornecedores,
                'colaboradores'=> $colaboradores,
                'proximo_codigo' => $proximoCodigo,
                'tab'          => $_GET['tab'] ?? 'dados',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ProdutosController::create] ' . $e->getMessage());
            header('Location: ' . self::BASE_ROUTE . '?error=1');
            exit();
        }
    }

    // ─── Salvar novo produto ─────────────────────────────────────────────────
    public function store(): void
    {
        try {
            $uid = Auth::user()->id;
            $nome = trim($_POST['nome'] ?? '');
            if ($nome === '') {
                header('Location: ' . self::BASE_ROUTE . '/create?error=nome_obrigatorio');
                exit();
            }
            // Garante código único
            $codigo = trim($_POST['codigo'] ?? '');
            if ($codigo === '') {
                $codigo = $this->model->gerarCodigo($uid);
            }
            $dados = array_merge($_POST, [
                'usuario_id' => $uid,
                'codigo'     => $codigo,
            ]);
            $id = $this->model->create($dados);
            if (!$id) {
                header('Location: ' . self::BASE_ROUTE . '/create?error=save_failed');
                exit();
            }
            // Upload de imagem principal
            if (!empty($_FILES['imagem_principal']['tmp_name'])) {
                $this->_uploadImagem($id, $uid);
            }
            header('Location: ' . self::BASE_ROUTE . '/' . $id . '?success=created');
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[ProdutosController::store] ' . $e->getMessage());
            header('Location: ' . self::BASE_ROUTE . '/create?error=exception');
            exit();
        }
    }

    // ─── Formulário de edição ────────────────────────────────────────────────
    public function edit(int $id): void
    {
        try {
            $uid     = Auth::user()->id;
            $produto = $this->model->findById($id);
            if (!$produto || (int)$produto->usuario_id !== $uid) {
                header('Location: ' . self::BASE_ROUTE . '?error=not_found');
                exit();
            }
            $fornecedores      = (new Fornecedor())->findByUsuarioId($uid);
            $colaboradores     = method_exists(new Colaborador(), 'findByUsuarioId')
                                 ? (new Colaborador())->findByUsuarioId($uid)
                                 : [];
            $componentes       = $this->compModel->findByProdutoId($id);
            $comissoes         = $this->comissaoModel->findByProdutoId($id);
            $historico_precos  = $this->model->getHistoricoPrecos($id);

            View::render('estoque/produtos/form', [
                '_layout'         => 'erp',
                'title'           => 'Editar Produto',
                'breadcrumb'      => ['Estoque' => '#', 'Produtos' => self::BASE_ROUTE, 0 => htmlspecialchars($produto->nome)],
                'produto'         => $produto,
                'componentes'     => $componentes,
                'comissoes'       => $comissoes,
                'historico_precos'=> $historico_precos,
                'fornecedores'    => $fornecedores,
                'colaboradores'   => $colaboradores,
                'proximo_codigo'  => $produto->codigo,
                'tab'             => $_GET['tab'] ?? 'dados',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ProdutosController::edit] ' . $e->getMessage());
            header('Location: ' . self::BASE_ROUTE . '?error=1');
            exit();
        }
    }

    // ─── Atualizar produto ───────────────────────────────────────────────────
    public function update(int $id): void
    {
        try {
            $uid     = Auth::user()->id;
            $produto = $this->model->findById($id);
            if (!$produto || (int)$produto->usuario_id !== $uid) {
                header('Location: ' . self::BASE_ROUTE . '?error=not_found');
                exit();
            }
            $dados = array_merge($_POST, ['usuario_id' => $uid]);
            $ok    = $this->model->update($id, $dados);
            if (!empty($_FILES['imagem_principal']['tmp_name'])) {
                $this->_uploadImagem($id, $uid);
            }
            header('Location: ' . self::BASE_ROUTE . '/' . $id . ($ok ? '?success=updated' : '?error=save_failed'));
            exit();
        } catch (\Exception $e) {
            $this->logger->error('[ProdutosController::update] ' . $e->getMessage());
            header('Location: ' . self::BASE_ROUTE . '/' . $id . '/edit?error=exception');
            exit();
        }
    }

    // ─── Visualizar produto ──────────────────────────────────────────────────
    public function show(int $id): void
    {
        try {
            $uid     = Auth::user()->id;
            $produto = $this->model->findById($id);
            if (!$produto || (int)$produto->usuario_id !== $uid) {
                header('Location: ' . self::BASE_ROUTE . '?error=not_found');
                exit();
            }
            $componentes      = $this->compModel->findByProdutoId($id);
            $comissoes        = $this->comissaoModel->findByProdutoId($id);
            $historico_precos = $this->model->getHistoricoPrecos($id);

            View::render('estoque/produtos/show', [
                '_layout'         => 'erp',
                'title'           => $produto->nome,
                'breadcrumb'      => ['Estoque' => '#', 'Produtos' => self::BASE_ROUTE, 0 => htmlspecialchars($produto->nome)],
                'produto'         => $produto,
                'componentes'     => $componentes,
                'comissoes'       => $comissoes,
                'historico_precos'=> $historico_precos,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ProdutosController::show] ' . $e->getMessage());
            header('Location: ' . self::BASE_ROUTE . '?error=1');
            exit();
        }
    }

    // ─── Excluir produto ─────────────────────────────────────────────────────
    public function delete(int $id): void
    {
        $uid = Auth::user()->id;
        $ok  = $this->model->delete($id, $uid);
        header('Location: ' . self::BASE_ROUTE . ($ok ? '?success=deleted' : '?error=delete_failed'));
        exit();
    }

    // ─── AJAX: Buscar produtos (para propostas e componentes) ────────────────
    public function buscar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $uid = Auth::user()->id;
        $q   = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode([]);
            exit();
        }
        $produtos = $this->model->buscar($uid, $q);
        echo json_encode($produtos);
        exit();
    }

    // ─── AJAX: Adicionar componente ──────────────────────────────────────────
    public function addComponente(int $produtoId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $uid     = Auth::user()->id;
            $produto = $this->model->findById($produtoId);
            if (!$produto || (int)$produto->usuario_id !== $uid) {
                echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
                exit();
            }
            $compId = (int)($_POST['componente_id'] ?? 0);
            if ($compId === $produtoId) {
                echo json_encode(['success' => false, 'message' => 'Um produto não pode ser componente de si mesmo']);
                exit();
            }
            $dados = array_merge($_POST, [
                'produto_id'  => $produtoId,
                'usuario_id'  => $uid,
            ]);
            $id = $this->compModel->create($dados);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Erro ao salvar componente']);
                exit();
            }
            $componentes = $this->compModel->findByProdutoId($produtoId);
            echo json_encode(['success' => true, 'componentes' => $componentes]);
        } catch (\Exception $e) {
            error_log('[ProdutosController::addComponente] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno']);
        }
        exit();
    }

    // ─── AJAX: Remover componente ────────────────────────────────────────────
    public function deleteComponente(int $compId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $uid = Auth::user()->id;
        $ok  = $this->compModel->delete($compId, $uid);
        echo json_encode(['success' => $ok]);
        exit();
    }

    // ─── AJAX: Adicionar regra de comissão ───────────────────────────────────
    public function addComissao(int $produtoId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $uid     = Auth::user()->id;
            $produto = $this->model->findById($produtoId);
            if (!$produto || (int)$produto->usuario_id !== $uid) {
                echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
                exit();
            }
            $dados = array_merge($_POST, [
                'produto_id' => $produtoId,
                'usuario_id' => $uid,
            ]);
            $id = $this->comissaoModel->create($dados);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Erro ao salvar regra']);
                exit();
            }
            $comissoes = $this->comissaoModel->findByProdutoId($produtoId);
            echo json_encode(['success' => true, 'comissoes' => $comissoes]);
        } catch (\Exception $e) {
            error_log('[ProdutosController::addComissao] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno']);
        }
        exit();
    }

    // ─── AJAX: Remover regra de comissão ─────────────────────────────────────
    public function deleteComissao(int $comissaoId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $uid = Auth::user()->id;
        $ok  = $this->comissaoModel->delete($comissaoId, $uid);
        echo json_encode(['success' => $ok]);
        exit();
    }

    // ─── AJAX: Upload de imagem ──────────────────────────────────────────────
    public function uploadImagem(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $uid     = Auth::user()->id;
        $produto = $this->model->findById($id);
        if (!$produto || (int)$produto->usuario_id !== $uid) {
            echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
            exit();
        }
        $path = $this->_uploadImagem($id, $uid);
        if ($path) {
            echo json_encode(['success' => true, 'path' => $path]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro no upload']);
        }
        exit();
    }

    // ─── Helper: upload de imagem ────────────────────────────────────────────
    private function _uploadImagem(int $id, int $uid): string|false
    {
        if (empty($_FILES['imagem_principal']['tmp_name'])) return false;
        $file    = $_FILES['imagem_principal'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime    = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed)) return false;
        $ext     = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'gif',
        };
        $dir  = BASE_PATH . '/public/uploads/produtos/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $name = 'produto_' . $id . '_' . time() . '.' . $ext;
        $dest = $dir . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) return false;
        $path = '/uploads/produtos/' . $name;
        $this->model->updateImagem($id, $uid, $path);
        return $path;
    }
}
