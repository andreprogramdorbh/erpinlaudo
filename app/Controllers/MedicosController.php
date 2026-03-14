<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\View;
use App\Core\Audit\AuditLogger;
use App\Models\Especialidade;
use App\Models\Medico;

class MedicosController extends Controller
{
    private const BASE_ROUTE = '/medicos';

    private Medico $model;
    private Especialidade $especialidadeModel;
    private Logger $logger;

    public function __construct()
    {
        $this->model = new Medico();
        $this->especialidadeModel = new Especialidade();
        $this->logger = new Logger();
    }

    public function index(): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $filtros = [
                'status' => trim((string) ($_GET['status'] ?? 'ativo')),
                'pesquisa' => trim((string) ($_GET['q'] ?? '')),
            ];

            $medicos = $this->model->findByUsuarioId($usuarioId, $filtros);

            View::render('medicos/index', [
                '_layout' => 'erp',
                'title' => 'Médicos',
                'breadcrumb' => [
                    'Cadastros' => '#',
                    'Corpo Clínico' => '#',
                    0 => 'Médicos',
                ],
                'medicos' => $medicos,
                'filtros' => $filtros,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao listar médicos: ' . $e->getMessage());
            header('Location: /dashboard?error=1');
            exit();
        }
    }

    public function create(): void
    {
        $usuarioId = Auth::user()->id;

        View::render('medicos/create', [
            '_layout' => 'erp',
            'title' => 'Novo Médico',
            'breadcrumb' => [
                'Cadastros' => '#',
                'Corpo Clínico' => '#',
                'Médicos' => self::BASE_ROUTE,
                0 => 'Novo Médico',
            ],
            'medico' => null,
            'especialidades' => $this->especialidadeModel->listForSelect($usuarioId),
        ]);
    }

    public function store(): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $dados = $this->validarDadosFormulario($usuarioId);

            $id = $this->model->create($dados);
            if ($id) {
                AuditLogger::log('create_medico', [
                    'id' => $id,
                    'nome' => $dados['nome'],
                    'crm' => $dados['crm'],
                ]);
                header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?success=created");
            } else {
                header('Location: ' . self::BASE_ROUTE . '/create?error=db_failure');
            }
        } catch (\InvalidArgumentException $e) {
            header('Location: ' . self::BASE_ROUTE . '/create?error=' . urlencode($e->getMessage()));
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao criar médico: ' . $e->getMessage());
            header('Location: ' . self::BASE_ROUTE . '/create?error=fatal');
        }
        exit();
    }

    public function edit($id): void
    {
        $usuarioId = Auth::user()->id;
        $medico = $this->model->findById((int) $id);

        if (!$medico || (int) ($medico->usuario_id ?? 0) !== (int) $usuarioId) {
            header('Location: ' . self::BASE_ROUTE . '?error=not_found');
            exit();
        }

        View::render('medicos/edit', [
            '_layout' => 'erp',
            'title' => 'Editar Médico',
            'breadcrumb' => [
                'Cadastros' => '#',
                'Corpo Clínico' => '#',
                'Médicos' => self::BASE_ROUTE,
                0 => 'Editar Médico',
            ],
            'medico' => $medico,
            'especialidades' => $this->especialidadeModel->listForSelect($usuarioId),
        ]);
    }

    public function update($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $medico = $this->model->findById((int) $id);

            if (!$medico || (int) ($medico->usuario_id ?? 0) !== (int) $usuarioId) {
                header('Location: ' . self::BASE_ROUTE . '?error=unauthorized');
                exit();
            }

            $dados = $this->validarDadosFormulario($usuarioId, $medico);

            if ($this->model->update((int) $id, $dados)) {
                AuditLogger::log('update_medico', [
                    'id' => (int) $id,
                    'nome' => $dados['nome'],
                    'crm' => $dados['crm'],
                ]);
                header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?success=updated");
            } else {
                header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?error=db_failure");
            }
        } catch (\InvalidArgumentException $e) {
            header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?error=" . urlencode($e->getMessage()));
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao atualizar médico: ' . $e->getMessage());
            header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?error=fatal");
        }
        exit();
    }

    private function validarDadosFormulario(int $usuarioId, ?object $medicoAtual = null): array
    {
        $nome = trim(strip_tags((string) ($_POST['nome'] ?? '')));
        $crm = trim(strip_tags((string) ($_POST['crm'] ?? '')));
        $ufCrm = strtoupper(trim(strip_tags((string) ($_POST['uf_crm'] ?? ''))));
        $cpf = preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? ''));
        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
        $telefone = preg_replace('/\D/', '', (string) ($_POST['telefone'] ?? ''));
        $especialidadeId = (int) ($_POST['especialidade_id'] ?? 0);
        $subespecialidade = trim(strip_tags((string) ($_POST['subespecialidade'] ?? '')));
        $rqe = trim(strip_tags((string) ($_POST['rqe'] ?? '')));
        $status = ($_POST['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';

        if ($nome === '' || $crm === '' || $ufCrm === '' || $cpf === '' || $email === '' || $telefone === '' || $especialidadeId <= 0) {
            throw new \InvalidArgumentException('missing_fields');
        }

        if (strlen($ufCrm) !== 2) {
            throw new \InvalidArgumentException('invalid_uf');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('invalid_email');
        }

        $especialidade = $this->especialidadeModel->findById($especialidadeId);
        if (!$especialidade || (int) ($especialidade->usuario_id ?? 0) !== (int) $usuarioId) {
            throw new \InvalidArgumentException('invalid_especialidade');
        }

        return [
            'usuario_id' => $usuarioId,
            'nome' => $nome,
            'crm' => $crm,
            'uf_crm' => $ufCrm,
            'cpf' => $cpf,
            'email' => $email,
            'telefone' => $telefone,
            'especialidade_id' => $especialidadeId,
            'subespecialidade' => $subespecialidade !== '' ? $subespecialidade : null,
            'rqe' => $rqe !== '' ? $rqe : null,
            'assinatura_digital' => $this->processarAssinaturaDigital($usuarioId, $medicoAtual),
            'status' => $status,
        ];
    }

    private function processarAssinaturaDigital(int $usuarioId, ?object $medicoAtual = null): ?string
    {
        $upload = $_FILES['assinatura_digital'] ?? null;
        $assinaturaAtual = $medicoAtual->assinatura_digital ?? null;

        if (!$upload || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $assinaturaAtual;
        }

        if ((int) ($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('invalid_upload');
        }

        $tmpPath = (string) ($upload['tmp_name'] ?? '');
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath) ?: '';

        $allowed = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'application/pdf' => 'pdf',
        ];

        if (!isset($allowed[$mime])) {
            throw new \InvalidArgumentException('invalid_file_type');
        }

        if (((int) ($upload['size'] ?? 0)) > (5 * 1024 * 1024)) {
            throw new \InvalidArgumentException('file_too_large');
        }

        $baseDir = BASE_PATH . '/storage/uploads/medicos_assinaturas/' . $usuarioId;
        if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
            throw new \RuntimeException('Falha ao preparar diretório de assinatura digital.');
        }

        $safeName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $destPath = $baseDir . '/' . $safeName;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            throw new \RuntimeException('Falha ao salvar assinatura digital.');
        }

        if (!empty($assinaturaAtual)) {
            $oldPath = BASE_PATH . '/' . ltrim((string) $assinaturaAtual, '/');
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        return 'storage/uploads/medicos_assinaturas/' . $usuarioId . '/' . $safeName;
    }
}
