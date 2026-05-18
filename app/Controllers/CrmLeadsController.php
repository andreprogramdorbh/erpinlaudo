<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Audit\AuditLogger;
use App\Models\CrmLead;
use App\Models\CrmInteracao;
use App\Models\CrmOportunidade;
use App\Models\CrmAnexo;
use App\Models\CrmTransferencia;
use App\Models\User;
use App\Services\CnpjService;

class CrmLeadsController extends Controller
{
    private CrmLead $leadModel;
    private CrmInteracao $interacaoModel;
    private CrmAnexo $anexoModel;
    private CrmTransferencia $transferenciaModel;
    private User $userModel;
    private Logger $logger;

    public function __construct()
    {
        $this->leadModel          = new CrmLead();
        $this->interacaoModel     = new CrmInteracao();
        $this->anexoModel         = new CrmAnexo();
        $this->transferenciaModel = new CrmTransferencia();
        $this->userModel          = new User();
        $this->logger             = new Logger();
    }

    private function usuarioId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    private function isAdmin(): bool
    {
        $role = $_SESSION['user_role'] ?? '';
        return in_array(strtolower($role), ['admin', 'superadmin'], true);
    }

    // ---------------------------------------------------------------
    // GET /crm/leads
    // ---------------------------------------------------------------
    public function index(): void
    {
        $uid     = $this->usuarioId();
        $isAdmin = $this->isAdmin();

        // Admin pode filtrar por qualquer usuário; 0 = todos
        $filtroUid = $isAdmin ? (int) ($_GET['uid'] ?? 0) : $uid;
        $uidBusca  = ($isAdmin && $filtroUid === 0) ? 0 : ($isAdmin ? $filtroUid : $uid);

        $filtros = [
            'status'   => $_GET['status']   ?? '',
            'segmento' => $_GET['segmento'] ?? '',
            'origem'   => $_GET['origem']   ?? '',
            'q'        => trim($_GET['q']   ?? ''),
        ];

        $leads  = $this->leadModel->findByUsuarioId($uidBusca, $filtros);
        $counts = $this->leadModel->countByStatusAndUsuarioId($uidBusca);

        // Lista de usuários com leads (para o seletor do admin)
        $usuariosComLeads = $isAdmin
            ? $this->leadModel->findUsuariosComLeads()
            : [];

        $this->logger->info('[CRM] Leads listados', ['usuario_id' => $uid, 'filtro_uid' => $uidBusca, 'total' => count($leads)]);

        View::render('crm/leads/index', [
            'title'      => 'Leads',
            '_layout'    => 'erp',
            'breadcrumb' => ['CRM' => '/crm/funil', 'Leads'],
            'leads'      => $leads,
            'counts'     => $counts,
            'filtros'    => $filtros,
            'isAdmin'    => $isAdmin,
            'filtroUid'  => $filtroUid,
            'usuariosComLeads' => $usuariosComLeads,
            'statusList' => CrmLead::STATUS,
            'segmentos'  => CrmLead::SEGMENTOS,
            'origens'    => CrmLead::ORIGENS,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /crm/leads/create
    // ---------------------------------------------------------------
    public function create(): void
    {
        View::render('crm/leads/form', [
            'title'      => 'Novo Lead',
            '_layout'    => 'erp',
            'breadcrumb' => ['CRM' => '/crm/funil', 'Leads' => '/crm/leads', 'Novo Lead'],
            'lead'       => null,
            'isEdit'     => false,
            'interacoes' => [],
            'statusList' => CrmLead::STATUS,
            'segmentos'  => CrmLead::SEGMENTOS,
            'origens'    => CrmLead::ORIGENS,
            'especialidades' => CrmLead::ESPECIALIDADES,
            'tiposInteracao' => CrmInteracao::TIPOS,
        ]);
    }

    // ---------------------------------------------------------------
    // POST /crm/leads
    // ---------------------------------------------------------------
    public function store(): void
    {
        $uid  = $this->usuarioId();
        $data = $this->sanitizePost();
        $data['usuario_id'] = $uid;

        // Especialidades como JSON
        $data['especialidades_interesse'] = !empty($_POST['especialidades_interesse'])
            ? json_encode(array_values($_POST['especialidades_interesse']))
            : null;

        // Produtos como JSON
        $data['produtos_interesse'] = !empty($_POST['produtos_interesse'])
            ? json_encode(array_values($_POST['produtos_interesse']))
            : null;

        $id = $this->leadModel->create($data);

        if (!$id) {
            $this->logger->error('[CRM] Falha ao criar lead', ['usuario_id' => $uid, 'data' => $data]);
            header('Location: /crm/leads/create?error=falha_criar');
            exit();
        }

        AuditLogger::log('crm_lead_criado', ['lead_id' => $id, 'usuario_id' => $uid]);
        $this->logger->info('[CRM] Lead criado', ['lead_id' => $id, 'usuario_id' => $uid]);

        header("Location: /crm/leads/edit/{$id}?success=criado");
        exit();
    }

    // ---------------------------------------------------------------
    // GET /crm/leads/edit/{id}
    // ---------------------------------------------------------------
    public function edit(int $id): void
    {
        $uid  = $this->usuarioId();
        $lead = $this->leadModel->findById($id);

        if (!$lead || (int) $lead->usuario_id !== $uid) {
            header('Location: /crm/leads?error=nao_encontrado');
            exit();
        }

        $interacoes      = $this->interacaoModel->findByRelated('lead', $id);
        $anexos          = $this->anexoModel->findByRelated('lead', $id);
        $transferencias  = $this->transferenciaModel->findByRelated('lead', $id);
        $todosUsuarios   = $this->userModel->findAll();
        $donoAtual       = $this->userModel->findById((int)($lead->usuario_id ?? 0));
        View::render('crm/leads/form', [
            'title'      => 'Editar Lead — ' . htmlspecialchars($lead->nome_lead),
            '_layout'    => 'erp',
            'breadcrumb' => ['CRM' => '/crm/funil', 'Leads' => '/crm/leads', 'Editar Lead'],
            'lead'       => $lead,
            'isEdit'     => true,
            'interacoes'     => $interacoes,
            'anexos'         => $anexos,
            'transferencias' => $transferencias,
            'todosUsuarios'  => $todosUsuarios,
            'donomeAtual'    => $donoAtual->name ?? ($_SESSION['user_name'] ?? 'Usuário'),
            'motivosTransferencia' => CrmTransferencia::MOTIVOS,
            'statusList' => CrmLead::STATUS,
            'segmentos'  => CrmLead::SEGMENTOS,
            'origens'    => CrmLead::ORIGENS,
            'especialidades' => CrmLead::ESPECIALIDADES,
            'tiposInteracao' => CrmInteracao::TIPOS,
            'iconesInteracao'=> CrmInteracao::ICONES,
            'tiposAnexo'     => CrmAnexo::TIPOS,
            'iconesAnexo'    => CrmAnexo::ICONES,
        ]);
    }

    // ---------------------------------------------------------------
    // POST /crm/leads/update/{id}
    // ---------------------------------------------------------------
    public function update(int $id): void
    {
        $uid  = $this->usuarioId();
        $lead = $this->leadModel->findById($id);

        if (!$lead || (int) $lead->usuario_id !== $uid) {
            header('Location: /crm/leads?error=nao_encontrado');
            exit();
        }

        $data = $this->sanitizePost();

        $data['especialidades_interesse'] = !empty($_POST['especialidades_interesse'])
            ? json_encode(array_values($_POST['especialidades_interesse']))
            : null;

        $data['produtos_interesse'] = !empty($_POST['produtos_interesse'])
            ? json_encode(array_values($_POST['produtos_interesse']))
            : null;

        $ok = $this->leadModel->update($id, $data);

        if (!$ok) {
            $this->logger->error('[CRM] Falha ao atualizar lead', ['lead_id' => $id]);
            header("Location: /crm/leads/edit/{$id}?error=falha_atualizar");
            exit();
        }

        AuditLogger::log('crm_lead_atualizado', ['lead_id' => $id, 'usuario_id' => $uid]);
        $this->logger->info('[CRM] Lead atualizado', ['lead_id' => $id]);

        header("Location: /crm/leads/edit/{$id}?success=atualizado");
        exit();
    }

    // ---------------------------------------------------------------
    // POST /crm/leads/delete/{id}
    // ---------------------------------------------------------------
    public function delete(int $id): void
    {
        $uid  = $this->usuarioId();
        $lead = $this->leadModel->findById($id);

        if (!$lead || (int) $lead->usuario_id !== $uid) {
            header('Location: /crm/leads?error=nao_encontrado');
            exit();
        }

        $this->leadModel->delete($id);
        AuditLogger::log('crm_lead_excluido', ['lead_id' => $id, 'usuario_id' => $uid]);
        $this->logger->info('[CRM] Lead excluído', ['lead_id' => $id]);

        header('Location: /crm/leads?success=excluido');
        exit();
    }

    // ---------------------------------------------------------------
    // POST /crm/leads/converter/{id}  — converte lead em oportunidade
    // ---------------------------------------------------------------
    public function converter(int $id): void
    {
        $uid  = $this->usuarioId();
        $lead = $this->leadModel->findById($id);

        if (!$lead || (int) $lead->usuario_id !== $uid) {
            header('Location: /crm/leads?error=nao_encontrado');
            exit();
        }

        if ($lead->convertido_em === 'oportunidade') {
            header("Location: /crm/oportunidades/edit/{$lead->convertido_id}");
            exit();
        }

        $opModel = new CrmOportunidade();
        $opId    = $opModel->create([
            'usuario_id'              => $uid,
            'lead_id'                 => $id,
            'titulo_oportunidade'     => 'Oportunidade — ' . $lead->nome_lead,
            'etapa_funil'             => 'qualificacao',
            'status_oportunidade'     => 'aberta',
            'modalidade_principal'    => null,
            'tipo_contrato'           => null,
            'valor_estimado'          => null,
            'data_fechamento_prevista'=> null,
            'probabilidade_sucesso'   => 20,
        ]);

        if ($opId) {
            $this->leadModel->update($id, [
                'status_lead'        => 'qualificado',
                'convertido_em'      => 'oportunidade',
                'convertido_id'      => $opId,
                'convertido_em_data' => date('Y-m-d H:i:s'),
            ]);
            AuditLogger::log('crm_lead_convertido', ['lead_id' => $id, 'oportunidade_id' => $opId]);
            $this->logger->info('[CRM] Lead convertido em oportunidade', ['lead_id' => $id, 'op_id' => $opId]);
            header("Location: /crm/oportunidades/edit/{$opId}?success=convertido");
        } else {
            $this->logger->error('[CRM] Falha ao converter lead', ['lead_id' => $id]);
            header("Location: /crm/leads/edit/{$id}?error=falha_converter");
        }
        exit();
    }

    // ---------------------------------------------------------------
    // POST /crm/leads/interacao/add  — adiciona interação via AJAX/POST
    // ---------------------------------------------------------------
    public function addInteracao(): void
    {
        $uid      = $this->usuarioId();
        $leadId   = (int) ($_POST['related_id'] ?? 0);
        $lead     = $this->leadModel->findById($leadId);

        if (!$lead || (int) $lead->usuario_id !== $uid) {
            $this->jsonError('Lead não encontrado ou sem permissão.');
            return;
        }

        $data = [
            'usuario_id'     => $uid,
            'related_id'     => $leadId,
            'related_type'   => 'lead',
            'data_interacao' => trim($_POST['data_interacao'] ?? date('Y-m-d H:i:s')),
            'tipo_interacao' => trim($_POST['tipo_interacao'] ?? 'outro'),
            'resumo'         => trim($_POST['resumo'] ?? ''),
        ];

        if (empty($data['resumo'])) {
            $this->jsonError('O resumo da interação é obrigatório.');
            return;
        }

        $id = $this->interacaoModel->create($data);

        if (!$id) {
            $this->logger->error('[CRM] Falha ao registrar interação', ['lead_id' => $leadId]);
            $this->jsonError('Falha ao registrar interação.');
            return;
        }

        AuditLogger::log('crm_interacao_criada', ['interacao_id' => $id, 'lead_id' => $leadId]);
        $this->logger->info('[CRM] Interação registrada', ['interacao_id' => $id, 'lead_id' => $leadId]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id' => $id]);
        exit();
    }

    // ---------------------------------------------------------------
    // POST /crm/leads/interacao/delete/{id}
    // ---------------------------------------------------------------
    public function deleteInteracao(int $id): void
    {
        $uid        = $this->usuarioId();
        $interacao  = $this->interacaoModel->findById($id);

        if (!$interacao || (int) $interacao->usuario_id !== $uid) {
            $this->jsonError('Interação não encontrada.');
            return;
        }

        $this->interacaoModel->delete($id);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }

    // ---------------------------------------------------------------
    // GET /crm/leads/buscar-cnpj?cnpj=...
    // ---------------------------------------------------------------
    public function buscarCnpj(): void
    {
        $cnpj = preg_replace('/\D/', '', $_GET['cnpj'] ?? '');

        if (strlen($cnpj) !== 14) {
            $this->jsonError('CNPJ inválido.');
            return;
        }

        try {
            $service   = new CnpjService();
            $resultado = $service->consultar($cnpj);

            if (!$resultado) {
                $this->jsonError('CNPJ não encontrado.');
                return;
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $resultado]);
        } catch (\Throwable $e) {
            $this->logger->error('[CRM] Erro ao buscar CNPJ', ['cnpj' => $cnpj, 'error' => $e->getMessage()]);
            $this->jsonError('Erro ao consultar CNPJ: ' . $e->getMessage());
        }
        exit();
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------
    private function sanitizePost(): array
    {
        $fields = [
            'nome_lead','email','telefone','celular','cnpj','cpf','tipo_pessoa',
            'razao_social','nome_fantasia','cnae_principal','descricao_cnae',
            'endereco','numero','complemento','bairro','cidade','estado','cep',
            'origem','status_lead','segmento_principal',
            'volume_exames_mes','equipamentos_possui','sistema_atual',
            'num_medicos','num_unidades','acreditacao',
            'responsavel_nome','responsavel_cargo','responsavel_email','responsavel_telefone',
            'data_proximo_contato','observacoes',
            'website','instagram','linkedin',
        ];

        $data = [];
        foreach ($fields as $f) {
            $data[$f] = trim($_POST[$f] ?? '');
        }
        return $data;
    }

    // ---------------------------------------------------------------
    // POST /crm/leads/anexo/upload  — faz upload de anexo
    // ---------------------------------------------------------------
    public function uploadAnexo(): void
    {
        $uid    = $this->usuarioId();
        $leadId = (int) ($_POST['related_id'] ?? 0);
        $lead   = $this->leadModel->findById($leadId);

        if (!$lead || (int) $lead->usuario_id !== $uid) {
            header("Location: /crm/leads?error=nao_encontrado");
            exit();
        }

        $nomeDoc = trim($_POST['nome_documento'] ?? '');
        $tipoDoc = trim($_POST['tipo_documento'] ?? 'outro');

        if (empty($nomeDoc)) {
            header("Location: /crm/leads/edit/{$leadId}?error=nome_obrigatorio&tab=anexos");
            exit();
        }

        if (!array_key_exists($tipoDoc, CrmAnexo::TIPOS)) {
            $tipoDoc = 'outro';
        }

        if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            header("Location: /crm/leads/edit/{$leadId}?error=upload_failed&tab=anexos");
            exit();
        }

        $file    = $_FILES['arquivo'];
        $maxSize = 10 * 1024 * 1024; // 10 MB

        if ($file['size'] > $maxSize) {
            header("Location: /crm/leads/edit/{$leadId}?error=file_too_large&tab=anexos");
            exit();
        }

        $finfo   = new \finfo(FILEINFO_MIME_TYPE);
        $mime    = $finfo->file($file['tmp_name']) ?: '';
        $origExt = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $allowed = [
            'application/pdf'  => 'pdf',
            'image/jpeg'       => 'jpg',
            'image/png'        => 'png',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel' => 'xls',
        ];
        $ext = $allowed[$mime] ?? null;
        if ($ext === null && in_array($origExt, ['doc','docx','xls','xlsx','pdf','jpg','jpeg','png'], true)) {
            $ext = $origExt === 'jpeg' ? 'jpg' : $origExt;
        }
        if ($ext === null) {
            $this->logger->warning('[CRM] Upload anexo lead: tipo inválido', [
                'lead_id' => $leadId, 'mime' => $mime, 'ext' => $origExt,
            ]);
            header("Location: /crm/leads/edit/{$leadId}?error=invalid_file_type&tab=anexos");
            exit();
        }

        $baseDir = BASE_PATH . '/storage/uploads/crm/leads/' . $uid . '/' . $leadId;
        if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
            $this->logger->error('[CRM] Falha ao criar diretório de upload', ['dir' => $baseDir]);
            header("Location: /crm/leads/edit/{$leadId}?error=upload_failed&tab=anexos");
            exit();
        }

        $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $baseDir . '/' . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $this->logger->error('[CRM] Falha ao mover arquivo de upload', ['dest' => $destPath]);
            header("Location: /crm/leads/edit/{$leadId}?error=upload_failed&tab=anexos");
            exit();
        }

        $relativePath = 'storage/uploads/crm/leads/' . $uid . '/' . $leadId . '/' . $safeName;

        $anexoId = $this->anexoModel->create([
            'usuario_id'     => $uid,
            'related_type'   => 'lead',
            'related_id'     => $leadId,
            'nome_documento' => $nomeDoc,
            'tipo_documento' => $tipoDoc,
            'file_path'      => $relativePath,
            'original_name'  => $file['name'] !== '' ? $file['name'] : 'documento',
            'mime_type'      => $mime,
            'file_size'      => $file['size'] ?: null,
        ]);

        if ($anexoId) {
            AuditLogger::log('crm_lead_anexo_upload', [
                'anexo_id' => $anexoId, 'lead_id' => $leadId, 'nome' => $nomeDoc, 'tipo' => $tipoDoc,
            ]);
            $this->logger->info('[CRM] Anexo de lead salvo', [
                'anexo_id' => $anexoId, 'lead_id' => $leadId, 'file' => $relativePath,
            ]);
            header("Location: /crm/leads/edit/{$leadId}?success=anexo_salvo&tab=anexos");
        } else {
            @unlink($destPath);
            $this->logger->error('[CRM] Falha ao salvar anexo no banco', ['lead_id' => $leadId]);
            header("Location: /crm/leads/edit/{$leadId}?error=db_failure&tab=anexos");
        }
        exit();
    }

    // ---------------------------------------------------------------
    // GET /crm/leads/anexo/download/{id}  — faz download do anexo
    // ---------------------------------------------------------------
    public function downloadAnexo(int $id): void
    {
        $uid   = $this->usuarioId();
        $anexo = $this->anexoModel->findById($id);

        if (!$anexo || $anexo->related_type !== 'lead') {
            header('Location: /crm/leads?error=nao_encontrado');
            exit();
        }

        $lead = $this->leadModel->findById((int) $anexo->related_id);
        if (!$lead || (int) $lead->usuario_id !== $uid) {
            header('Location: /crm/leads?error=sem_permissao');
            exit();
        }

        $fullPath = BASE_PATH . '/' . $anexo->file_path;
        if (!file_exists($fullPath)) {
            header('Location: /crm/leads/edit/' . $anexo->related_id . '?error=arquivo_nao_encontrado&tab=anexos');
            exit();
        }

        $mime = $anexo->mime_type ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . addslashes($anexo->original_name) . '"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: private, no-cache');
        readfile($fullPath);
        exit();
    }

    // ---------------------------------------------------------------
    // POST /crm/leads/anexo/delete/{id}  — exclui um anexo
    // ---------------------------------------------------------------
    public function deleteAnexo(int $id): void
    {
        $uid   = $this->usuarioId();
        $anexo = $this->anexoModel->findById($id);

        if (!$anexo || $anexo->related_type !== 'lead') {
            $this->jsonError('Anexo não encontrado.');
            return;
        }

        $lead = $this->leadModel->findById((int) $anexo->related_id);
        if (!$lead || (int) $lead->usuario_id !== $uid) {
            $this->jsonError('Sem permissão para excluir este anexo.');
            return;
        }

        $fullPath = BASE_PATH . '/' . $anexo->file_path;
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }

        $this->anexoModel->delete($id);
        AuditLogger::log('crm_lead_anexo_excluido', ['anexo_id' => $id, 'lead_id' => $anexo->related_id]);
        $this->logger->info('[CRM] Anexo de lead excluído', ['anexo_id' => $id]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }

    // ---------------------------------------------------------------
    // POST /crm/leads/transferir/{id}
    // ---------------------------------------------------------------
    public function transferir(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $uid  = $this->usuarioId();
        $lead = $this->leadModel->findById($id);

        // Apenas o dono ou admin pode transferir
        $isAdmin = $this->isAdmin();
        if (!$lead || ((int) $lead->usuario_id !== $uid && !$isAdmin)) {
            $this->jsonError('Lead não encontrado ou sem permissão.');
            return;
        }

        $paraUsuarioId = (int) ($_POST['para_usuario_id'] ?? 0);
        $motivo        = trim($_POST['motivo'] ?? '');
        $observacao    = trim($_POST['observacao'] ?? '');

        // Validações
        if (!$paraUsuarioId) {
            $this->jsonError('Selecione o usuário de destino.');
            return;
        }
        if ((int) $lead->usuario_id === $paraUsuarioId) {
            $this->jsonError('O usuário de destino é o mesmo dono atual.');
            return;
        }
        if (!array_key_exists($motivo, CrmTransferencia::MOTIVOS)) {
            $this->jsonError('Motivo inválido.');
            return;
        }

        $usuarioDestino = $this->userModel->findById($paraUsuarioId);
        if (!$usuarioDestino) {
            $this->jsonError('Usuário de destino não encontrado.');
            return;
        }

        $deUsuarioId = (int) $lead->usuario_id;

        // 1. Registra na tabela de transferências
        $transId = $this->transferenciaModel->create([
            'usuario_id'      => $uid,
            'related_id'      => $id,
            'related_type'    => 'lead',
            'de_usuario_id'   => $deUsuarioId,
            'para_usuario_id' => $paraUsuarioId,
            'motivo'          => $motivo,
            'observacao'      => $observacao ?: null,
        ]);

        if (!$transId) {
            $this->jsonError('Falha ao registrar transferência.');
            return;
        }

        // 2. Atualiza o dono do lead
        $this->leadModel->update($id, ['usuario_id' => $paraUsuarioId]);

        // 3. Registra interação automática na timeline
        $motivoLabel  = CrmTransferencia::MOTIVOS[$motivo];
        $deNome       = $this->userModel->findById($deUsuarioId)->name ?? 'Desconhecido';
        $paraNome     = $usuarioDestino->name;
        $resumo       = "Transferência de Lead\n"
                      . "De: {$deNome}\n"
                      . "Para: {$paraNome}\n"
                      . "Motivo: {$motivoLabel}"
                      . ($observacao ? "\nObservação: {$observacao}" : '');

        $this->interacaoModel->create([
            'usuario_id'     => $uid,
            'related_id'     => $id,
            'related_type'   => 'lead',
            'data_interacao' => date('Y-m-d H:i:s'),
            'tipo_interacao' => 'transferencia',
            'resumo'         => $resumo,
            'data_retorno'   => null,
        ]);

        // 4. Audit log
        AuditLogger::log('crm_lead_transferido', [
            'lead_id'         => $id,
            'executor_id'     => $uid,
            'de_usuario_id'   => $deUsuarioId,
            'para_usuario_id' => $paraUsuarioId,
            'motivo'          => $motivo,
        ]);
        $this->logger->info('[CRM] Lead transferido', [
            'lead_id' => $id,
            'de'      => $deUsuarioId,
            'para'    => $paraUsuarioId,
            'motivo'  => $motivo,
        ]);

        echo json_encode([
            'success'   => true,
            'para_nome' => $paraNome,
            'motivo'    => $motivoLabel,
        ]);
        exit();
    }

    private function jsonError(string $msg): void
    {
        ob_start(); ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $msg]);
        exit();
    }
}
