<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Auth;
use App\Core\Audit\AuditLogger;
use App\Models\PlanoConta;

class PlanoContasController extends Controller
{
    private PlanoConta $model;
    private Logger $logger;

    public function __construct()
    {
        $this->model = new PlanoConta();
        $this->logger = new Logger();
    }

    public function index(): void
    {
        try {
            $usuarioId = Auth::user()->id;

            $filtros = [
                'status' => $_GET['status'] ?? 'ativo',
                'tipo' => $_GET['tipo'] ?? '',
                'pesquisa' => $_GET['q'] ?? '',
            ];

            $contas = $this->model->findByUsuarioId($usuarioId, $filtros);

            View::render('plano_contas/index', [
                '_layout' => 'erp',
                'title' => 'Plano de Contas',
                'breadcrumb' => [
                    'Financeiro' => '/financeiro/pagar',
                    0 => 'Plano de Contas',
                ],
                'contas' => $contas,
                'filtros' => $filtros,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erro ao listar plano de contas: ' . $e->getMessage());
            header('Location: /dashboard?error=1');
            exit();
        }
    }

    public function create(): void
    {
        $usuarioId = Auth::user()->id;
        $contasPai = $this->model->listAtivasParaPai($usuarioId);

        View::render('plano_contas/form-enterprise', [
            '_layout' => 'erp',
            'title' => 'Novo Plano de Contas',
            'conta' => null,
            'contasPai' => $contasPai,
            'tab' => 'geral',
        ]);
    }

    public function store(): void
    {
        try {
            $usuarioId = Auth::user()->id;

            $codigo = trim($_POST['codigo'] ?? '');
            $nome = trim($_POST['nome'] ?? '');
            $tipo = $_POST['tipo'] ?? '';

            if ($codigo === '' || $nome === '' || ($tipo !== 'Receita' && $tipo !== 'Despesa')) {
                header('Location: /financeiro/plano-contas/create?error=missing_fields');
                exit();
            }

            $dados = [
                'usuario_id' => $usuarioId,
                'codigo' => $codigo,
                'nome' => $nome,
                'tipo' => $tipo,
                'conta_pai_id' => ($_POST['conta_pai_id'] ?? '') !== '' ? (int)$_POST['conta_pai_id'] : null,
                'status' => $_POST['status'] ?? 'ativo',
            ];

            $id = $this->model->create($dados);
            if ($id) {
                AuditLogger::log('create_plano_conta', ['id' => $id, 'codigo' => $codigo, 'nome' => $nome]);
                header("Location: /financeiro/plano-contas/edit/{$id}?success=created");
            } else {
                header('Location: /financeiro/plano-contas/create?error=db_failure');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao criar plano de conta: ' . $e->getMessage());
            header('Location: /financeiro/plano-contas/create?error=fatal');
        }
        exit();
    }

    public function edit($id): void
    {
        $usuarioId = Auth::user()->id;
        $conta = $this->model->findById((int)$id);

        if (!$conta || (int)$conta->usuario_id !== (int)$usuarioId) {
            header('Location: /financeiro/plano-contas?error=not_found');
            exit();
        }

        $contasPai = $this->model->listAtivasParaPai($usuarioId, (int)$conta->id);

        View::render('plano_contas/form-enterprise', [
            '_layout' => 'erp',
            'title' => 'Editar Plano de Contas',
            'conta' => $conta,
            'contasPai' => $contasPai,
            'tab' => $_GET['tab'] ?? 'geral',
        ]);
    }

    public function update($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta = $this->model->findById((int)$id);

            if (!$conta || (int)$conta->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/plano-contas?error=unauthorized');
                exit();
            }

            $codigo = trim($_POST['codigo'] ?? '');
            $nome = trim($_POST['nome'] ?? '');
            $tipo = $_POST['tipo'] ?? '';

            if ($codigo === '' || $nome === '' || ($tipo !== 'Receita' && $tipo !== 'Despesa')) {
                header("Location: /financeiro/plano-contas/edit/{$id}?error=missing_fields");
                exit();
            }

            $dados = [
                'codigo' => $codigo,
                'nome' => $nome,
                'tipo' => $tipo,
                'conta_pai_id' => $_POST['conta_pai_id'] ?? null,
                'status' => $_POST['status'] ?? 'ativo',
            ];

            if ($this->model->update((int)$id, $dados)) {
                AuditLogger::log('update_plano_conta', ['id' => (int)$id, 'codigo' => $codigo, 'nome' => $nome]);
                header("Location: /financeiro/plano-contas/edit/{$id}?success=updated");
            } else {
                header("Location: /financeiro/plano-contas/edit/{$id}?error=db_failure");
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao atualizar plano de conta: ' . $e->getMessage());
            header("Location: /financeiro/plano-contas/edit/{$id}?error=fatal");
        }
        exit();
    }

    public function delete($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $conta = $this->model->findById((int)$id);

            if (!$conta || (int)$conta->usuario_id !== (int)$usuarioId) {
                header('Location: /financeiro/plano-contas?error=unauthorized');
                exit();
            }

            if ($this->model->delete((int)$id)) {
                AuditLogger::log('delete_plano_conta', ['id' => (int)$id, 'codigo' => $conta->codigo ?? null]);
                header('Location: /financeiro/plano-contas?success=deleted');
            } else {
                header('Location: /financeiro/plano-contas?error=db_failure');
            }
        } catch (\Exception $e) {
            $this->logger->error('Erro ao deletar plano de conta: ' . $e->getMessage());
            header('Location: /financeiro/plano-contas?error=fatal');
        }
        exit();
    }
}
