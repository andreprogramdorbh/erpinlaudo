<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Audit\AuditLogger;
use App\Models\CrmLead;
use App\Models\CrmInteracao;
use App\Models\CrmOportunidade;
use App\Services\CnpjService;

class CrmLeadsController extends Controller
{
    private CrmLead $leadModel;
    private CrmInteracao $interacaoModel;
    private Logger $logger;

    public function __construct()
    {
        $this->leadModel      = new CrmLead();
        $this->interacaoModel = new CrmInteracao();
        $this->logger         = new Logger();
    }

    private function usuarioId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    // ---------------------------------------------------------------
    // GET /crm/leads
    // ---------------------------------------------------------------
    public function index(): void
    {
        $uid     = $this->usuarioId();
        $filtros = [
            'status'   => $_GET['status']   ?? '',
            'segmento' => $_GET['segmento'] ?? '',
            'origem'   => $_GET['origem']   ?? '',
            'q'        => trim($_GET['q']   ?? ''),
        ];

        $leads  = $this->leadModel->findByUsuarioId($uid, $filtros);
        $counts = $this->leadModel->countByStatusAndUsuarioId($uid);

        $this->logger->info('[CRM] Leads listados', ['usuario_id' => $uid, 'total' => count($leads)]);

        View::render('crm/leads/index', [
            'title'      => 'Leads',
            '_layout'    => 'erp',
            'breadcrumb' => ['CRM' => '/crm/funil', 'Leads'],
            'leads'      => $leads,
            'counts'     => $counts,
            'filtros'    => $filtros,
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

        $interacoes = $this->interacaoModel->findByRelated('lead', $id);

        View::render('crm/leads/form', [
            'title'      => 'Editar Lead — ' . htmlspecialchars($lead->nome_lead),
            '_layout'    => 'erp',
            'breadcrumb' => ['CRM' => '/crm/funil', 'Leads' => '/crm/leads', 'Editar Lead'],
            'lead'       => $lead,
            'isEdit'     => true,
            'interacoes' => $interacoes,
            'statusList' => CrmLead::STATUS,
            'segmentos'  => CrmLead::SEGMENTOS,
            'origens'    => CrmLead::ORIGENS,
            'especialidades' => CrmLead::ESPECIALIDADES,
            'tiposInteracao' => CrmInteracao::TIPOS,
            'iconesInteracao'=> CrmInteracao::ICONES,
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
        ];

        $data = [];
        foreach ($fields as $f) {
            $data[$f] = trim($_POST[$f] ?? '');
        }
        return $data;
    }

    private function jsonError(string $msg): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $msg]);
        exit();
    }
}
