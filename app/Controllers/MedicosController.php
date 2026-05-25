<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\View;
use App\Core\Audit\AuditLogger;
use App\Models\Especialidade;
use App\Models\Medico;
use App\Models\MedicoExame;
use App\Models\TabelaExame;

class MedicosController extends Controller
{
    private const BASE_ROUTE = '/medicos';

    private Medico $model;
    private Especialidade $especialidadeModel;
    private MedicoExame $medicoExameModel;
    private TabelaExame $tabelaExameModel;
    private Logger $logger;

    public function __construct()
    {
        $this->model              = new Medico();
        $this->especialidadeModel = new Especialidade();
        $this->medicoExameModel   = new MedicoExame();
        $this->tabelaExameModel   = new TabelaExame();
        $this->logger             = new Logger();
    }

    // =========================================================
    // LISTAGEM
    // =========================================================

    public function index(): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $filtros = [
                'status'   => trim((string) ($_GET['status'] ?? 'ativo')),
                'pesquisa' => trim((string) ($_GET['q'] ?? '')),
            ];

            $medicos = $this->model->findByUsuarioId($usuarioId, $filtros);

            View::render('medicos/index', [
                '_layout'   => 'erp',
                'title'     => 'Médicos',
                'breadcrumb' => [
                    'Cadastros'    => '#',
                    'Corpo Clínico' => '#',
                    0              => 'Médicos',
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

    // =========================================================
    // CRIAR
    // =========================================================

    public function create(): void
    {
        $usuarioId = Auth::user()->id;

        View::render('medicos/create', [
            '_layout'   => 'erp',
            'title'     => 'Novo Médico',
            'breadcrumb' => [
                'Cadastros'    => '#',
                'Corpo Clínico' => '#',
                'Médicos'      => self::BASE_ROUTE,
                0              => 'Novo Médico',
            ],
            'medico'        => null,
            'especialidades' => $this->especialidadeModel->listForSelect($usuarioId),
            'medicoCrms'    => [],
        ]);
    }

    public function store(): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $dados = $this->validarDadosFormulario($usuarioId);

            // --- PROTECAO CONTRA DUPLICATAS (camada de aplicacao) ---
            $crm   = preg_replace('/\D/', '', $dados['crm']   ?? '');
            $ufCrm = strtoupper(trim($dados['uf_crm'] ?? ''));
            $cpf   = preg_replace('/\D/', '', $dados['cpf']   ?? '');
            if ($crm !== '' && $ufCrm !== '' && $this->model->crmExists($crm, $ufCrm, $usuarioId)) {
                $this->logger->warning('[Medicos] store bloqueado: CRM duplicado', [
                    'usuario_id' => $usuarioId,
                    'crm'        => $crm,
                    'uf_crm'     => $ufCrm,
                ]);
                AuditLogger::log('medico_duplicado_bloqueado', [
                    'usuario_id' => $usuarioId,
                    'crm'        => $crm,
                    'uf_crm'     => $ufCrm,
                ]);
                header('Location: ' . self::BASE_ROUTE . '/create?error=crm_duplicado');
                exit();
            }
            if ($cpf !== '' && $this->model->cpfExists($cpf, $usuarioId)) {
                $this->logger->warning('[Medicos] store bloqueado: CPF duplicado', [
                    'usuario_id' => $usuarioId,
                    'cpf'        => $cpf,
                ]);
                AuditLogger::log('medico_cpf_duplicado_bloqueado', [
                    'usuario_id' => $usuarioId,
                    'cpf'        => $cpf,
                ]);
                header('Location: ' . self::BASE_ROUTE . '/create?error=cpf_cnpj_exists');
                exit();
            }
            // --------------------------------------------------------

            $id = $this->model->create($dados);
            if ($id) {
                // Salvar CRMs adicionais (o principal já foi inserido pelo model->create)
                $crmsPost = $this->parseCrmsPost();
                if (!empty($crmsPost)) {
                    $this->model->saveCrms((int)$id, $usuarioId, $crmsPost);
                }

                AuditLogger::log('create_medico', [
                    'id'   => $id,
                    'nome' => $dados['nome'],
                    'crm'  => $dados['crm'],
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

    // =========================================================
    // EDITAR
    // =========================================================

    public function edit($id): void
    {
        $usuarioId = Auth::user()->id;
        $medico    = $this->model->findById((int) $id);

        if (!$medico || (int) ($medico->usuario_id ?? 0) !== (int) $usuarioId) {
            header('Location: ' . self::BASE_ROUTE . '?error=not_found');
            exit();
        }

        // CRMs cadastrados para este médico
        $medicoCrms = $this->model->getCrms((int) $id);

        // Exames vinculados ao médico (com TAGs DICOM)
        $medicoExames = $this->medicoExameModel->findByMedicoId((int) $id);

        // Todos os exames da tabela (com TAGs DICOM) para o seletor
        $tabelaExames = $this->tabelaExameModel->findAllWithTagsByUsuarioId($usuarioId);

        View::render('medicos/edit', [
            '_layout'   => 'erp',
            'title'     => 'Editar Médico',
            'breadcrumb' => [
                'Cadastros'    => '#',
                'Corpo Clínico' => '#',
                'Médicos'      => self::BASE_ROUTE,
                0              => 'Editar Médico',
            ],
            'medico'        => $medico,
            'especialidades' => $this->especialidadeModel->listForSelect($usuarioId),
            'medicoCrms'    => $medicoCrms,
            'medicoExames'  => $medicoExames,
            'tabelaExames'  => $tabelaExames,
        ]);
    }

    public function update($id): void
    {
        try {
            $usuarioId = Auth::user()->id;
            $medico    = $this->model->findById((int) $id);

            if (!$medico || (int) ($medico->usuario_id ?? 0) !== (int) $usuarioId) {
                header('Location: ' . self::BASE_ROUTE . '?error=unauthorized');
                exit();
            }

            $dados = $this->validarDadosFormulario($usuarioId, $medico);

            // --- PROTECAO CONTRA DUPLICATAS NO UPDATE (camada de aplicacao) ---
            $crm   = preg_replace('/\D/', '', $dados['crm']   ?? '');
            $ufCrm = strtoupper(trim($dados['uf_crm'] ?? ''));
            $cpf   = preg_replace('/\D/', '', $dados['cpf']   ?? '');
            if ($crm !== '' && $ufCrm !== '' && $this->model->crmExists($crm, $ufCrm, $usuarioId, (int)$id)) {
                $this->logger->warning('[Medicos] update bloqueado: CRM duplicado', [
                    'usuario_id' => $usuarioId,
                    'medico_id'  => (int)$id,
                    'crm'        => $crm,
                    'uf_crm'     => $ufCrm,
                ]);
                AuditLogger::log('medico_update_crm_duplicado_bloqueado', [
                    'usuario_id' => $usuarioId,
                    'medico_id'  => (int)$id,
                    'crm'        => $crm,
                ]);
                header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?error=crm_duplicado");
                exit();
            }
            if ($cpf !== '' && $this->model->cpfExists($cpf, $usuarioId, (int)$id)) {
                $this->logger->warning('[Medicos] update bloqueado: CPF duplicado', [
                    'usuario_id' => $usuarioId,
                    'medico_id'  => (int)$id,
                    'cpf'        => $cpf,
                ]);
                AuditLogger::log('medico_update_cpf_duplicado_bloqueado', [
                    'usuario_id' => $usuarioId,
                    'medico_id'  => (int)$id,
                    'cpf'        => $cpf,
                ]);
                header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?error=cpf_cnpj_exists");
                exit();
            }
            // -------------------------------------------------------------------

            if ($this->model->update((int) $id, $dados)) {
                // Salvar lista completa de CRMs (inclui o principal e os adicionais)
                $crmsPost = $this->parseCrmsPost();
                $this->model->saveCrms((int)$id, $usuarioId, $crmsPost);

                AuditLogger::log('update_medico', [
                    'id'   => (int) $id,
                    'nome' => $dados['nome'],
                    'crm'  => $dados['crm'],
                ]);
                header('Location: ' . self::BASE_ROUTE . "/edit/{$id}?success=updated&tab=dados");
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

    // =========================================================
    // AJAX — Gerenciar CRMs do médico
    // =========================================================

    /**
     * POST /medicos/{id}/crms/save
     * Salva (substitui) todos os CRMs do médico via AJAX.
     * Recebe JSON: { crms: [{crm, uf_crm, principal}] }
     */
    public function saveCrms($medicoId): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        try {
            $usuarioId = Auth::user()->id;
            $medico    = $this->model->findById((int) $medicoId);

            if (!$medico || (int) ($medico->usuario_id ?? 0) !== (int) $usuarioId) {
                echo json_encode(['success' => false, 'message' => 'Médico não encontrado.']);
                exit();
            }

            $body  = json_decode(file_get_contents('php://input'), true) ?? [];
            $crms  = $body['crms'] ?? [];

            if (!is_array($crms)) {
                echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
                exit();
            }

            $ok = $this->model->saveCrms((int)$medicoId, $usuarioId, $crms);

            AuditLogger::log('save_medico_crms', [
                'medico_id' => (int)$medicoId,
                'total'     => count($crms),
            ]);

            echo json_encode(['success' => $ok]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao salvar CRMs do médico: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * GET /medicos/{id}/crms
     * Retorna os CRMs cadastrados para o médico (AJAX).
     */
    public function getCrms($medicoId): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        try {
            $usuarioId = Auth::user()->id;
            $medico    = $this->model->findById((int) $medicoId);

            if (!$medico || (int) ($medico->usuario_id ?? 0) !== (int) $usuarioId) {
                echo json_encode(['success' => false, 'message' => 'Médico não encontrado.']);
                exit();
            }

            $crms = $this->model->getCrms((int)$medicoId);
            echo json_encode(['success' => true, 'crms' => $crms]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao listar CRMs do médico: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // =========================================================
    // AJAX — Listar exames vinculados ao médico
    // =========================================================

    public function getExames($medicoId): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        try {
            $usuarioId = Auth::user()->id;
            $medico    = $this->model->findById((int) $medicoId);

            if (!$medico || (int) ($medico->usuario_id ?? 0) !== (int) $usuarioId) {
                echo json_encode(['success' => false, 'message' => 'Médico não encontrado.']);
                exit();
            }

            $exames = $this->medicoExameModel->findByMedicoId((int) $medicoId);

            echo json_encode(['success' => true, 'exames' => $exames]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao listar exames do médico: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // =========================================================
    // AJAX — Vincular/atualizar exame ao médico
    // =========================================================

    public function saveExame($medicoId): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        try {
            $usuarioId = Auth::user()->id;
            $medico    = $this->model->findById((int) $medicoId);

            if (!$medico || (int) ($medico->usuario_id ?? 0) !== (int) $usuarioId) {
                echo json_encode(['success' => false, 'message' => 'Médico não encontrado.']);
                exit();
            }

            $tabelaExameId = (int) ($_POST['tabela_exame_id'] ?? 0);
            $usaCustom     = (int) ($_POST['usa_valor_custom'] ?? 0);
            $valorRotina   = $this->normalizarValor((string) ($_POST['valor_rotina'] ?? '0'));
            $valorUrgencia = $this->normalizarValor((string) ($_POST['valor_urgencia'] ?? '0'));
            $observacoes   = trim(strip_tags((string) ($_POST['observacoes'] ?? '')));

            if ($tabelaExameId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Selecione um exame válido.']);
                exit();
            }

            // Verificar se o exame pertence ao tenant
            $exame = $this->tabelaExameModel->findById($tabelaExameId);
            if (!$exame || (int) ($exame->usuario_id ?? 0) !== (int) $usuarioId) {
                echo json_encode(['success' => false, 'message' => 'Exame não encontrado.']);
                exit();
            }

            $ok = $this->medicoExameModel->upsert([
                'usuario_id'      => $usuarioId,
                'medico_id'       => (int) $medicoId,
                'tabela_exame_id' => $tabelaExameId,
                'valor_rotina'    => $valorRotina,
                'valor_urgencia'  => $valorUrgencia,
                'usa_valor_custom' => $usaCustom,
                'observacoes'     => $observacoes !== '' ? $observacoes : null,
            ]);

            if ($ok) {
                AuditLogger::log('save_medico_exame', [
                    'medico_id'       => (int) $medicoId,
                    'tabela_exame_id' => $tabelaExameId,
                ]);
            }

            echo json_encode(['success' => $ok]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao salvar exame do médico: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // =========================================================
    // AJAX — Remover vínculo de exame do médico
    // =========================================================

    public function deleteExame($medicoId): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        try {
            $usuarioId = Auth::user()->id;
            $medico    = $this->model->findById((int) $medicoId);

            if (!$medico || (int) ($medico->usuario_id ?? 0) !== (int) $usuarioId) {
                echo json_encode(['success' => false, 'message' => 'Médico não encontrado.']);
                exit();
            }

            $tabelaExameId = (int) ($_POST['tabela_exame_id'] ?? 0);
            if ($tabelaExameId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID do exame inválido.']);
                exit();
            }

            $ok = $this->medicoExameModel->deleteByMedicoAndExame((int) $medicoId, $tabelaExameId);

            if ($ok) {
                AuditLogger::log('delete_medico_exame', [
                    'medico_id'       => (int) $medicoId,
                    'tabela_exame_id' => $tabelaExameId,
                ]);
            }

            echo json_encode(['success' => $ok]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao remover exame do médico: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // =========================================================
    // AJAX — Buscar dados de um exame da tabela
    // =========================================================

    public function getExameTabela($exameId): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        try {
            $usuarioId = Auth::user()->id;
            $exame     = $this->tabelaExameModel->findById((int) $exameId);

            if (!$exame || (int) ($exame->usuario_id ?? 0) !== (int) $usuarioId) {
                echo json_encode(['success' => false, 'message' => 'Exame não encontrado.']);
                exit();
            }

            $tags = $this->tabelaExameModel->getTagsByExameId((int) $exameId);
            $tagValores = array_values(array_filter(array_map(fn($t) => trim($t->tag_valor ?? ''), $tags)));

            echo json_encode([
                'success'        => true,
                'exame'          => $exame,
                'tags_dicom'     => $tagValores,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao buscar exame da tabela: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // =========================================================
    // Helpers privados
    // =========================================================

    /**
     * Lê do POST o array de CRMs enviado pelo formulário.
     * Formato esperado: crms[0][crm], crms[0][uf_crm], crms[0][principal], ...
     */
    private function parseCrmsPost(): array
    {
        $raw = $_POST['crms'] ?? [];
        if (!is_array($raw)) {
            return [];
        }
        $result = [];
        foreach ($raw as $item) {
            $crm  = trim(strip_tags((string)($item['crm'] ?? '')));
            $uf   = strtoupper(trim(strip_tags((string)($item['uf_crm'] ?? ''))));
            $prin = (int)(bool)($item['principal'] ?? 0);
            if ($crm !== '' && strlen($uf) === 2) {
                $result[] = ['crm' => $crm, 'uf_crm' => $uf, 'principal' => $prin];
            }
        }
        return $result;
    }

    private function validarDadosFormulario(int $usuarioId, ?object $medicoAtual = null): array
    {
        $nome           = trim(strip_tags((string) ($_POST['nome'] ?? '')));
        $crm            = trim(strip_tags((string) ($_POST['crm'] ?? '')));
        $ufCrm          = strtoupper(trim(strip_tags((string) ($_POST['uf_crm'] ?? ''))));
        $cpf            = preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? ''));
        $email          = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
        $telefone       = preg_replace('/\D/', '', (string) ($_POST['telefone'] ?? ''));
        $especialidadeId = (int) ($_POST['especialidade_id'] ?? 0);
        $subespecialidade = trim(strip_tags((string) ($_POST['subespecialidade'] ?? '')));
        $rqe            = trim(strip_tags((string) ($_POST['rqe'] ?? '')));
        $status         = ($_POST['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';

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
            'usuario_id'      => $usuarioId,
            'nome'            => $nome,
            'crm'             => $crm,
            'uf_crm'          => $ufCrm,
            'cpf'             => $cpf,
            'email'           => $email,
            'telefone'        => $telefone,
            'especialidade_id' => $especialidadeId,
            'subespecialidade' => $subespecialidade !== '' ? $subespecialidade : null,
            'rqe'             => $rqe !== '' ? $rqe : null,
            'assinatura_digital' => $this->processarAssinaturaDigital($usuarioId, $medicoAtual),
            'status'          => $status,
        ];
    }

    private function normalizarValor(string $valor): float
    {
        $valor = trim($valor);
        $valor = preg_replace('/[^\d,.]/', '', $valor);

        if (substr_count($valor, ',') === 1 && substr_count($valor, '.') >= 1) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } elseif (substr_count($valor, ',') === 1) {
            $valor = str_replace(',', '.', $valor);
        }

        return (float) $valor;
    }

    private function processarAssinaturaDigital(int $usuarioId, ?object $medicoAtual = null): ?string
    {
        $upload         = $_FILES['assinatura_digital'] ?? null;
        $assinaturaAtual = $medicoAtual->assinatura_digital ?? null;

        if (!$upload || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $assinaturaAtual;
        }

        if ((int) ($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('invalid_upload');
        }

        $tmpPath = (string) ($upload['tmp_name'] ?? '');
        $finfo   = new \finfo(FILEINFO_MIME_TYPE);
        $mime    = $finfo->file($tmpPath) ?: '';

        $allowed = [
            'image/png'       => 'png',
            'image/jpeg'      => 'jpg',
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
