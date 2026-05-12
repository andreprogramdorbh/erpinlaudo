<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Audit\AuditLogger;
use App\Core\Auth;
use App\Models\CrmOportunidade;
use App\Models\CrmLead;
use App\Models\CrmInteracao;
use App\Models\CrmOportunidadeModalidade;
use App\Models\CrmAnexo;
use App\Models\Cliente;
use App\Models\User;

class CrmOportunidadesController extends Controller
{
    private CrmOportunidade $opModel;
    private CrmInteracao $interacaoModel;
    private CrmOportunidadeModalidade $modModel;
    private CrmAnexo $anexoModel;
    private Logger $logger;

    public function __construct()
    {
        $this->opModel        = new CrmOportunidade();
        $this->interacaoModel = new CrmInteracao();
        $this->modModel       = new CrmOportunidadeModalidade();
        $this->anexoModel     = new CrmAnexo();
        $this->logger         = new Logger();
    }

    private function usuarioId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    // ---------------------------------------------------------------
    // Verifica se o usuário atual é admin ou superadmin
    // ---------------------------------------------------------------
    private function isAdmin(): bool
    {
        $role = $_SESSION['user_role'] ?? '';
        return in_array(strtolower($role), ['admin', 'superadmin'], true);
    }

    // ---------------------------------------------------------------
    // GET /crm/oportunidades
    // ---------------------------------------------------------------
    public function index(): void
    {
        $uid     = $this->usuarioId();
        $isAdmin = $this->isAdmin();

        // Admin pode filtrar por qualquer usuário; 0 = todos
        $filtroUid = $isAdmin ? (int) ($_GET['uid'] ?? 0) : $uid;

        $filtros = [
            'etapa'  => $_GET['etapa']  ?? '',
            'status' => $_GET['status'] ?? 'aberta',
            'q'      => trim($_GET['q'] ?? ''),
        ];

        // Para admin sem filtro de usuário específico, busca todos (uid=0)
        $uidBusca = ($isAdmin && $filtroUid === 0) ? 0 : ($isAdmin ? $filtroUid : $uid);

        $oportunidades = $this->opModel->findByUsuarioId($uidBusca, $filtros);
        $resumo        = $this->opModel->resumoFunilByUsuarioId($uidBusca);

        // Lista de usuários com oportunidades (para o seletor do admin)
        $usuariosComOportunidades = $isAdmin
            ? $this->opModel->findUsuariosComOportunidades()
            : [];

        $this->logger->info('[CRM] Oportunidades listadas', ['usuario_id' => $uid, 'filtro_uid' => $uidBusca, 'total' => count($oportunidades)]);

        View::render('crm/oportunidades/index', [
            'title'         => 'Oportunidades',
            '_layout'       => 'erp',
            'breadcrumb'    => ['CRM' => '/crm/funil', 'Oportunidades'],
            'oportunidades' => $oportunidades,
            'resumo'        => $resumo,
            'filtros'       => $filtros,
            'isAdmin'       => $isAdmin,
            'filtroUid'     => $filtroUid,
            'usuariosComOportunidades' => $usuariosComOportunidades,
            'etapas'        => CrmOportunidade::ETAPAS,
            'statusList'    => CrmOportunidade::STATUS,
            'modalidades'   => CrmOportunidade::MODALIDADES,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /crm/oportunidades/create
    // ---------------------------------------------------------------
    public function create(): void
    {
        $uid   = $this->usuarioId();
        $leads = (new CrmLead())->findByUsuarioId($uid, ['status' => 'qualificado']);

        View::render('crm/oportunidades/form', [
            'title'          => 'Nova Oportunidade',
            '_layout'        => 'erp',
            'breadcrumb'     => ['CRM' => '/crm/funil', 'Oportunidades' => '/crm/oportunidades', 'Nova'],
            'op'             => null,
            'isEdit'         => false,
            'interacoes'     => [],
            'leads'          => $leads,
            'etapas'         => CrmOportunidade::ETAPAS,
            'statusList'     => CrmOportunidade::STATUS,
            'modalidades'    => CrmOportunidade::MODALIDADES,
            'tiposContrato'  => CrmOportunidade::TIPOS_CONTRATO,
            'tiposInteracao' => CrmInteracao::TIPOS,
        ]);
    }

    // ---------------------------------------------------------------
    // POST /crm/oportunidades
    // ---------------------------------------------------------------
    public function store(): void
    {
        $uid  = $this->usuarioId();
        $data = $this->sanitizePost();
        $data['usuario_id'] = $uid;

        // Múltiplas modalidades como JSON
        $data['modalidades_interesse'] = !empty($_POST['modalidades_interesse'])
            ? json_encode(array_values($_POST['modalidades_interesse']))
            : null;

        $id = $this->opModel->create($data);

        if (!$id) {
            $this->logger->error('[CRM] Falha ao criar oportunidade', ['usuario_id' => $uid]);
            header('Location: /crm/oportunidades/create?error=falha_criar');
            exit();
        }

        // Salva linhas dinâmicas de modalidades
        $this->salvarLinhasModalidades((int) $id);

        // Criação automática de cliente a partir do lead vinculado
        $clienteId = $this->sincronizarClienteDoLead((int) ($data['lead_id'] ?? 0), $uid);
        if ($clienteId) {
            $this->opModel->update((int) $id, ['cliente_id' => $clienteId]);
        }

        AuditLogger::log('crm_oportunidade_criada', ['op_id' => $id, 'usuario_id' => $uid]);
        $this->logger->info('[CRM] Oportunidade criada', ['op_id' => $id, 'cliente_id' => $clienteId]);

        header("Location: /crm/oportunidades/edit/{$id}?success=criado");
        exit();
    }

    // ---------------------------------------------------------------
    // GET /crm/oportunidades/edit/{id}
    // ---------------------------------------------------------------
    public function edit(int $id): void
    {
        $uid = $this->usuarioId();
        $op  = $this->opModel->findById($id);

        if (!$op || (int) $op->usuario_id !== $uid) {
            header('Location: /crm/oportunidades?error=nao_encontrado');
            exit();
        }

        $interacoes = $this->interacaoModel->findByRelated('oportunidade', $id);
        $anexos     = $this->anexoModel->findByRelated('oportunidade', $id);

        // Herda interações do lead de origem
        $interacoesLead = [];
        if ($op->lead_id) {
            $interacoesLead = $this->interacaoModel->findByRelated('lead', (int) $op->lead_id);
        }

        $leads             = (new CrmLead())->findByUsuarioId($uid, []);
        $linhasModalidades = $this->modModel->findByOportunidadeId($id);

        // Decodifica modalidades de interesse salvas (chips)
        $modalidadesAtivas = json_decode($op->modalidades_interesse ?? '[]', true) ?: [];

        View::render('crm/oportunidades/form', [
            'title'              => 'Oportunidade — ' . htmlspecialchars($op->titulo_oportunidade),
            '_layout'            => 'erp',
            'breadcrumb'         => ['CRM' => '/crm/funil', 'Oportunidades' => '/crm/oportunidades', 'Editar'],
            'op'                 => $op,
            'isEdit'             => true,
            'interacoes'         => $interacoes,
            'interacoesLead'     => $interacoesLead,
            'anexos'             => $anexos,
            'leads'              => $leads,
            'etapas'             => CrmOportunidade::ETAPAS,
            'statusList'         => CrmOportunidade::STATUS,
            'modalidades'        => CrmOportunidade::MODALIDADES,
            'modalidadesAtivas'  => $modalidadesAtivas,
            'linhasModalidades'  => $linhasModalidades,
            'tiposContrato'      => CrmOportunidade::TIPOS_CONTRATO,
            'tiposInteracao'     => CrmInteracao::TIPOS,
            'iconesInteracao'    => CrmInteracao::ICONES,
            'tiposAnexo'         => CrmAnexo::TIPOS,
            'iconesAnexo'        => CrmAnexo::ICONES,
        ]);
    }

    // ---------------------------------------------------------------
    // POST /crm/oportunidades/update/{id}
    // ---------------------------------------------------------------
    public function update(int $id): void
    {
        $uid = $this->usuarioId();
        $op  = $this->opModel->findById($id);

        if (!$op || (int) $op->usuario_id !== $uid) {
            header('Location: /crm/oportunidades?error=nao_encontrado');
            exit();
        }

        $data = $this->sanitizePost();

        // Múltiplas modalidades como JSON
        $data['modalidades_interesse'] = !empty($_POST['modalidades_interesse'])
            ? json_encode(array_values($_POST['modalidades_interesse']))
            : null;

        $ok = $this->opModel->update($id, $data);

        if (!$ok) {
            $this->logger->error('[CRM] Falha ao atualizar oportunidade', ['op_id' => $id]);
            header("Location: /crm/oportunidades/edit/{$id}?error=falha_atualizar");
            exit();
        }

        // Atualiza linhas dinâmicas de modalidades
        $this->salvarLinhasModalidades($id);

        // Sincroniza cliente ao atualizar (caso lead tenha sido vinculado/alterado)
        $clienteId = $this->sincronizarClienteDoLead((int) ($data['lead_id'] ?? $op->lead_id ?? 0), $uid);
        if ($clienteId && (int) ($op->cliente_id ?? 0) !== $clienteId) {
            $this->opModel->update($id, ['cliente_id' => $clienteId]);
        }

        AuditLogger::log('crm_oportunidade_atualizada', ['op_id' => $id, 'usuario_id' => $uid]);
        $this->logger->info('[CRM] Oportunidade atualizada', ['op_id' => $id]);

        header("Location: /crm/oportunidades/edit/{$id}?success=atualizado");
        exit();
    }

    // ---------------------------------------------------------------
    // POST /crm/oportunidades/delete/{id}
    // ---------------------------------------------------------------
    public function delete(int $id): void
    {
        $uid = $this->usuarioId();
        $op  = $this->opModel->findById($id);

        if (!$op || (int) $op->usuario_id !== $uid) {
            header('Location: /crm/oportunidades?error=nao_encontrado');
            exit();
        }

        $this->opModel->delete($id);
        AuditLogger::log('crm_oportunidade_excluida', ['op_id' => $id, 'usuario_id' => $uid]);
        $this->logger->info('[CRM] Oportunidade excluída', ['op_id' => $id]);

        header('Location: /crm/oportunidades?success=excluido');
        exit();
    }

    // ---------------------------------------------------------------
    // POST /crm/oportunidades/mover  — drag-and-drop do funil (JSON)
    // ---------------------------------------------------------------
    public function moverEtapa(): void
    {
        $uid   = $this->usuarioId();
        $opId  = (int) ($_POST['id']    ?? 0);
        $etapa = trim($_POST['etapa']   ?? '');

        if (!array_key_exists($etapa, CrmOportunidade::ETAPAS)) {
            $this->jsonError('Etapa inválida.');
            return;
        }

        $op = $this->opModel->findById($opId);
        if (!$op || (int) $op->usuario_id !== $uid) {
            $this->jsonError('Oportunidade não encontrada.');
            return;
        }

        $ok = $this->opModel->updateEtapa($opId, $etapa);

        AuditLogger::log('crm_oportunidade_etapa_movida', ['op_id' => $opId, 'etapa' => $etapa]);
        $this->logger->info('[CRM] Oportunidade movida no funil', ['op_id' => $opId, 'etapa' => $etapa]);

        header('Content-Type: application/json');
        echo json_encode(['success' => $ok]);
        exit();
    }

    // ---------------------------------------------------------------
    // POST /crm/oportunidades/interacao/add
    // ---------------------------------------------------------------
    public function addInteracao(): void
    {
        $uid  = $this->usuarioId();
        $opId = (int) ($_POST['related_id'] ?? 0);
        $op   = $this->opModel->findById($opId);

        if (!$op || (int) $op->usuario_id !== $uid) {
            $this->jsonError('Oportunidade não encontrada.');
            return;
        }

        $dataRetorno = trim($_POST['data_retorno'] ?? '');

        $data = [
            'usuario_id'     => $uid,
            'related_id'     => $opId,
            'related_type'   => 'oportunidade',
            'data_interacao' => trim($_POST['data_interacao'] ?? date('Y-m-d H:i:s')),
            'tipo_interacao' => trim($_POST['tipo_interacao'] ?? 'outro'),
            'resumo'         => trim($_POST['resumo'] ?? ''),
            'data_retorno'   => $dataRetorno ?: null,
        ];

        if (empty($data['resumo'])) {
            $this->jsonError('O resumo da interação é obrigatório.');
            return;
        }

        $id = $this->interacaoModel->create($data);

        if (!$id) {
            $this->logger->error('[CRM] Falha ao registrar interação em oportunidade', ['op_id' => $opId]);
            $this->jsonError('Falha ao registrar interação.');
            return;
        }

        AuditLogger::log('crm_interacao_criada', ['interacao_id' => $id, 'op_id' => $opId]);
        $this->logger->info('[CRM] Interação registrada em oportunidade', ['interacao_id' => $id, 'op_id' => $opId]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id' => $id]);
        exit();
    }

    // ---------------------------------------------------------------
    // POST /crm/oportunidades/interacao/delete/{id}
    // ---------------------------------------------------------------
    public function deleteInteracao(int $id): void
    {
        $uid       = $this->usuarioId();
        $interacao = $this->interacaoModel->findById($id);

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
    // POST /crm/oportunidades/update-retorno/{id}
    // Atualiza apenas o campo data_proximo_contato (chamado pelo JS após salvar interação)
    // ---------------------------------------------------------------
    public function updateRetorno(int $id): void
    {
        $uid = $this->usuarioId();
        $op  = $this->opModel->findById($id);

        if (!$op || (int) $op->usuario_id !== $uid) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Não autorizado.']);
            exit();
        }

        $dataRetorno = trim($_POST['data_proximo_contato'] ?? '');
        if ($dataRetorno) {
            $this->opModel->updateField($id, 'data_proximo_contato', $dataRetorno);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Verifica se o lead já tem um cliente cadastrado (por CNPJ/CPF ou e-mail).
     * Se não existir, cria o cliente automaticamente com os dados do lead.
     * Retorna o ID do cliente (existente ou recém-criado), ou null se não for possível.
     */
    private function sincronizarClienteDoLead(int $leadId, int $usuarioId): ?int
    {
        if ($leadId <= 0) {
            return null;
        }

        try {
            $leadModel = new CrmLead();
            $lead      = $leadModel->findById($leadId);

            if (!$lead) {
                return null;
            }

            $clienteModel = new Cliente();

            // Tenta localizar cliente existente por CNPJ/CPF
            $cpfCnpj  = $lead->cnpj ?? $lead->cpf ?? null;
            $existing = null;

            if ($cpfCnpj) {
                $existing = $clienteModel->findByCpfCnpjAndUsuarioId(
                    preg_replace('/\D/', '', $cpfCnpj),
                    $usuarioId
                );
            }

            // Fallback: busca por e-mail se não encontrou por CNPJ
            if (!$existing && !empty($lead->email)) {
                $byEmail = $clienteModel->findByEmail($lead->email);
                if ($byEmail && (int) $byEmail->usuario_id === $usuarioId) {
                    $existing = $byEmail;
                }
            }

            if ($existing) {
                $this->logger->info('[CRM] Cliente já existe, vinculando à oportunidade', [
                    'cliente_id' => $existing->id,
                    'lead_id'    => $leadId,
                ]);
                return (int) $existing->id;
            }

            // Cria novo cliente com dados do lead
            $novoCliente = [
                'usuario_id'    => $usuarioId,
                'tipo'          => $lead->tipo_pessoa === 'PF' ? 'PF' : 'PJ',
                'cpf_cnpj'      => $cpfCnpj ? preg_replace('/\D/', '', $cpfCnpj) : null,
                'razao_social'  => $lead->razao_social ?? $lead->nome_lead,
                'nome_fantasia' => $lead->nome_fantasia ?? null,
                'email'         => $lead->email ?? null,
                'telefone'      => $lead->telefone ?? null,
                'celular'       => $lead->celular ?? null,
                'cnae_principal'=> $lead->cnae_principal ?? null,
                'descricao_cnae'=> $lead->descricao_cnae ?? null,
                'endereco'      => $lead->endereco ?? null,
                'numero'        => $lead->numero ?? null,
                'complemento'   => $lead->complemento ?? null,
                'bairro'        => $lead->bairro ?? null,
                'cidade'        => $lead->cidade ?? null,
                'estado'        => $lead->estado ?? null,
                'cep'           => $lead->cep ?? null,
                'website'       => $lead->website ?? null,
                'instagram'     => $lead->instagram ?? null,
                'status'        => 'ativo',
            ];

            $novoId = $clienteModel->create($novoCliente);

            if ($novoId) {
                AuditLogger::log('crm_cliente_criado_automaticamente', [
                    'cliente_id' => $novoId,
                    'lead_id'    => $leadId,
                    'usuario_id' => $usuarioId,
                ]);
                $this->logger->info('[CRM] Cliente criado automaticamente a partir do lead', [
                    'cliente_id' => $novoId,
                    'lead_id'    => $leadId,
                ]);
                return (int) $novoId;
            }

            $this->logger->error('[CRM] Falha ao criar cliente automaticamente', [
                'lead_id'    => $leadId,
                'usuario_id' => $usuarioId,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('[CRM] Exceção ao sincronizar cliente', [
                'lead_id' => $leadId,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }

        return null;
    }

    /**
     * Lê os arrays mod_modalidade[], mod_tipo_contrato[], mod_volume[], mod_observacao[]
     * do POST e substitui todas as linhas da oportunidade.
     */
    private function salvarLinhasModalidades(int $opId): void
    {
        $modalidades  = $_POST['mod_modalidade']   ?? [];
        $contratos    = $_POST['mod_tipo_contrato'] ?? [];
        $volumes      = $_POST['mod_volume']        ?? [];
        $observacoes  = $_POST['mod_observacao']    ?? [];

        $linhas = [];
        foreach ($modalidades as $i => $mod) {
            if (empty($mod)) continue; // ignora linhas vazias
            $linhas[] = [
                'modalidade'         => $mod,
                'tipo_contrato'      => $contratos[$i]   ?? null,
                'volume_estimado_mes'=> $volumes[$i]     ?? null,
                'observacao'         => $observacoes[$i] ?? null,
            ];
        }

        try {
            $this->modModel->replaceAll($opId, $linhas);
            $this->logger->info('[CRM] Linhas de modalidades salvas', [
                'op_id' => $opId,
                'total' => count($linhas),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[CRM] Falha ao salvar linhas de modalidades', [
                'op_id' => $opId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function sanitizePost(): array
    {
        $fields = [
            'lead_id','cliente_id','titulo_oportunidade','etapa_funil',
            'valor_estimado','data_fechamento_prevista','probabilidade_sucesso',
            'status_oportunidade','motivo_perda','modalidade_principal',
            'tipo_contrato','volume_estimado_mes','observacoes','data_proximo_contato',
        ];

        $data = [];
        foreach ($fields as $f) {
            $data[$f] = trim($_POST[$f] ?? '');
        }
        return $data;
    }

    // ---------------------------------------------------------------
    // POST /crm/oportunidades/anexo/upload  — faz upload de anexo
    // ---------------------------------------------------------------
    public function uploadAnexo(): void
    {
        $uid  = $this->usuarioId();
        $opId = (int) ($_POST['related_id'] ?? 0);
        $op   = $this->opModel->findById($opId);

        if (!$op || (int) $op->usuario_id !== $uid) {
            header("Location: /crm/oportunidades?error=nao_encontrado");
            exit();
        }

        $nomeDoc = trim($_POST['nome_documento'] ?? '');
        $tipoDoc = trim($_POST['tipo_documento'] ?? 'outro');

        if (empty($nomeDoc)) {
            header("Location: /crm/oportunidades/edit/{$opId}?error=nome_obrigatorio&tab=anexos");
            exit();
        }

        if (!array_key_exists($tipoDoc, CrmAnexo::TIPOS)) {
            $tipoDoc = 'outro';
        }

        if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            header("Location: /crm/oportunidades/edit/{$opId}?error=upload_failed&tab=anexos");
            exit();
        }

        $file    = $_FILES['arquivo'];
        $maxSize = 10 * 1024 * 1024; // 10 MB

        if ($file['size'] > $maxSize) {
            header("Location: /crm/oportunidades/edit/{$opId}?error=file_too_large&tab=anexos");
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
            $this->logger->warning('[CRM] Upload anexo oportunidade: tipo inválido', [
                'op_id' => $opId, 'mime' => $mime, 'ext' => $origExt,
            ]);
            header("Location: /crm/oportunidades/edit/{$opId}?error=invalid_file_type&tab=anexos");
            exit();
        }

        $baseDir = BASE_PATH . '/storage/uploads/crm/oportunidades/' . $uid . '/' . $opId;
        if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
            $this->logger->error('[CRM] Falha ao criar diretório de upload', ['dir' => $baseDir]);
            header("Location: /crm/oportunidades/edit/{$opId}?error=upload_failed&tab=anexos");
            exit();
        }

        $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $baseDir . '/' . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $this->logger->error('[CRM] Falha ao mover arquivo de upload', ['dest' => $destPath]);
            header("Location: /crm/oportunidades/edit/{$opId}?error=upload_failed&tab=anexos");
            exit();
        }

        $relativePath = 'storage/uploads/crm/oportunidades/' . $uid . '/' . $opId . '/' . $safeName;

        $anexoId = $this->anexoModel->create([
            'usuario_id'     => $uid,
            'related_type'   => 'oportunidade',
            'related_id'     => $opId,
            'nome_documento' => $nomeDoc,
            'tipo_documento' => $tipoDoc,
            'file_path'      => $relativePath,
            'original_name'  => $file['name'] !== '' ? $file['name'] : 'documento',
            'mime_type'      => $mime,
            'file_size'      => $file['size'] ?: null,
        ]);

        if ($anexoId) {
            AuditLogger::log('crm_oportunidade_anexo_upload', [
                'anexo_id' => $anexoId, 'op_id' => $opId, 'nome' => $nomeDoc, 'tipo' => $tipoDoc,
            ]);
            $this->logger->info('[CRM] Anexo de oportunidade salvo', [
                'anexo_id' => $anexoId, 'op_id' => $opId, 'file' => $relativePath,
            ]);
            header("Location: /crm/oportunidades/edit/{$opId}?success=anexo_salvo&tab=anexos");
        } else {
            @unlink($destPath);
            $this->logger->error('[CRM] Falha ao salvar anexo no banco', ['op_id' => $opId]);
            header("Location: /crm/oportunidades/edit/{$opId}?error=db_failure&tab=anexos");
        }
        exit();
    }

    // ---------------------------------------------------------------
    // GET /crm/oportunidades/anexo/download/{id}  — faz download
    // ---------------------------------------------------------------
    public function downloadAnexo(int $id): void
    {
        $uid   = $this->usuarioId();
        $anexo = $this->anexoModel->findById($id);

        if (!$anexo || $anexo->related_type !== 'oportunidade') {
            header('Location: /crm/oportunidades?error=nao_encontrado');
            exit();
        }

        $op = $this->opModel->findById((int) $anexo->related_id);
        if (!$op || (int) $op->usuario_id !== $uid) {
            header('Location: /crm/oportunidades?error=sem_permissao');
            exit();
        }

        $fullPath = BASE_PATH . '/' . $anexo->file_path;
        if (!file_exists($fullPath)) {
            header('Location: /crm/oportunidades/edit/' . $anexo->related_id . '?error=arquivo_nao_encontrado&tab=anexos');
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
    // POST /crm/oportunidades/anexo/delete/{id}  — exclui um anexo
    // ---------------------------------------------------------------
    public function deleteAnexo(int $id): void
    {
        $uid   = $this->usuarioId();
        $anexo = $this->anexoModel->findById($id);

        if (!$anexo || $anexo->related_type !== 'oportunidade') {
            $this->jsonError('Anexo não encontrado.');
            return;
        }

        $op = $this->opModel->findById((int) $anexo->related_id);
        if (!$op || (int) $op->usuario_id !== $uid) {
            $this->jsonError('Sem permissão para excluir este anexo.');
            return;
        }

        $fullPath = BASE_PATH . '/' . $anexo->file_path;
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }

        $this->anexoModel->delete($id);
        AuditLogger::log('crm_oportunidade_anexo_excluido', ['anexo_id' => $id, 'op_id' => $anexo->related_id]);
        $this->logger->info('[CRM] Anexo de oportunidade excluído', ['anexo_id' => $id]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
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
