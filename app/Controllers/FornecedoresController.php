<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\Fornecedor;

class FornecedoresController extends Controller
{
    private const BASE_ROUTE = '/fornecedores';

    private Fornecedor $model;
    private Logger $logger;

    public function __construct()
    {
        $this->model = new Fornecedor();
        $this->logger = new Logger();
    }

    public function index(): void
    {
        try {
            $usuarioId = Auth::user()->id;

            $filtros = [
                'status' => $_GET['status'] ?? 'ativo',
                'pesquisa' => $_GET['q'] ?? '',
            ];

            $fornecedores = $this->model->findByUsuarioId($usuarioId, $filtros);

            View::render('fornecedores/index', [
                '_layout' => 'erp',
                'title' => 'Fornecedores',
                'breadcrumb' => [
                    'Cadastros' => '#',
                    0 => 'Fornecedores',
                ],
                'fornecedores' => $fornecedores,
                'filtros' => $filtros,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao listar fornecedores: ' . $e->getMessage());
            header('Location: /dashboard?error=1');
            exit();
        }
    }

    public function create(): void
    {
        View::render('fornecedores/form-enterprise', [
            '_layout' => 'erp',
            'title' => 'Novo Fornecedor',
            'breadcrumb' => [
                'Cadastros' => '#',
                'Fornecedores' => self::BASE_ROUTE,
                0 => 'Novo Fornecedor',
            ],
            'fornecedor' => null,
            'tab' => 'geral',
        ]);
    }

    public function store(): void
    {
        try {
            $usuarioId = Auth::user()->id;

            $nome = trim($_POST['nome'] ?? '');
            if ($nome === '') {
                header('Location: ' . self::BASE_ROUTE . '/create?error=missing_fields');
                exit();
            }

            $dados = [
                'usuario_id' => $usuarioId,
                'nome' => $nome,
                'documento' => trim($_POST['documento'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'telefone' => trim($_POST['telefone'] ?? ''),
                'status' => $_POST['status'] ?? 'ativo',
            ];

            if ($dados['documento'] === '') $dados['documento'] = null;
            if ($dados['email'] === '') $dados['email'] = null;
            if ($dados['telefone'] === '') $dados['telefone'] = null;

            $id = $this->model->create($dados);
            if ($id) {
                AuditLogger::log('create_fornecedor', ['id' => $id, 'nome' => $nome]);
                header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?success=created");
            } else {
                header('Location: ' . self::BASE_ROUTE . '/create?error=db_failure');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar fornecedor: ' . $e->getMessage());
            header('Location: ' . self::BASE_ROUTE . '/create?error=fatal');
        }
        exit();
    }

    public function edit($id): void
    {
        $usuarioId = Auth::user()->id;
        $fornecedor = $this->model->findById((int)$id);

        if (!$fornecedor || (int)$fornecedor->usuario_id !== (int)$usuarioId) {
            header('Location: ' . self::BASE_ROUTE . '?error=not_found');
            exit();
        }

        View::render('fornecedores/form-enterprise', [
            '_layout' => 'erp',
            'title' => 'Editar Fornecedor',
            'breadcrumb' => [
                'Cadastros' => '#',
                'Fornecedores' => self::BASE_ROUTE,
                0 => 'Editar Fornecedor',
            ],
            'fornecedor' => $fornecedor,
            'tab' => $_GET['tab'] ?? 'geral',
        ]);
    }

    public function update($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $fornecedor = $this->model->findById((int)$id);

            if (!$fornecedor || (int)$fornecedor->usuario_id !== (int)$usuarioId) {
                header('Location: ' . self::BASE_ROUTE . '?error=unauthorized');
                exit();
            }

            $nome = trim($_POST['nome'] ?? '');
            if ($nome === '') {
                header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?error=missing_fields");
                exit();
            }

            $dados = [
                'nome' => $nome,
                'documento' => trim($_POST['documento'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'telefone' => trim($_POST['telefone'] ?? ''),
                'status' => $_POST['status'] ?? 'ativo',
            ];

            if ($dados['documento'] === '') $dados['documento'] = null;
            if ($dados['email'] === '') $dados['email'] = null;
            if ($dados['telefone'] === '') $dados['telefone'] = null;

            if ($this->model->update((int)$id, $dados)) {
                AuditLogger::log('update_fornecedor', ['id' => (int)$id, 'nome' => $nome]);
                header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?success=updated");
            } else {
                header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?error=db_failure");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar fornecedor: ' . $e->getMessage());
            header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?error=fatal");
        }
        exit();
    }

    public function delete($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $fornecedor = $this->model->findById((int)$id);

            if (!$fornecedor || (int)$fornecedor->usuario_id !== (int)$usuarioId) {
                header('Location: ' . self::BASE_ROUTE . '?error=unauthorized');
                exit();
            }

            if ($this->model->delete((int)$id)) {
                AuditLogger::log('delete_fornecedor', ['id' => (int)$id, 'nome' => $fornecedor->nome ?? null]);
                header('Location: ' . self::BASE_ROUTE . '?success=deleted');
            } else {
                header('Location: ' . self::BASE_ROUTE . '?error=db_failure');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar fornecedor: ' . $e->getMessage());
            header('Location: ' . self::BASE_ROUTE . '?error=fatal');
        }
        exit();
    }
}
