<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Logger;
use App\Core\Audit\AuditLogger;
use App\Models\CrmOportunidade;
use App\Models\CrmLead;
use App\Models\CrmInteracao;
use App\Models\Cliente;

class CrmOportunidadesController extends Controller
{
    private CrmOportunidade $opModel;
    private CrmInteracao $interacaoModel;
    private Logger $logger;

    public function __construct()
    {
        $this->opModel        = new CrmOportunidade();
        $this->interacaoModel = new CrmInteracao();
        $this->logger         = new Logger();
    }

    private function usuarioId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    // ---------------------------------------------------------------
    // GET /crm/oportunidades
    // ---------------------------------------------------------------
    public function index(): void
    {
        $uid     = $this->usuarioId();
        $filtros = [
            'etapa'  => $_GET['etapa']  ?? '',
            'status' => $_GET['status'] ?? 'aberta',
            'q'      => trim($_GET['q'] ?? ''),
        ];

        $oportunidades = $this->opModel->findByUsuarioId($uid, $filtros);
        $resumo        = $this->opModel->resumoFunilByUsuarioId($uid);

        $this->logger->info('[CRM] Oportunidades listadas', ['usuario_id' => $uid, 'total' => count($oportunidades)]);

        View::render('crm/oportunidades/index', [
            'title'         => 'Oportunidades',
            '_layout'       => 'erp',
            'breadcrumb'    => ['CRM' => '/crm/funil', 'Oportunidades'],
            'oportunidades' => $oportunidades,
            'resumo'        => $resumo,
            'filtros'       => $filtros,
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

        // Herda interações do lead de origem
        $interacoesLead = [];
        if ($op->lead_id) {
            $interacoesLead = $this->interacaoModel->findByRelated('lead', (int) $op->lead_id);
        }

        $leads = (new CrmLead())->findByUsuarioId($uid, []);

        // Decodifica modalidades de interesse salvas
        $modalidadesAtivas = json_decode($op->modalidades_interesse ?? '[]', true) ?: [];

        View::render('crm/oportunidades/form', [
            'title'              => 'Oportunidade — ' . htmlspecialchars($op->titulo_oportunidade),
            '_layout'            => 'erp',
            'breadcrumb'         => ['CRM' => '/crm/funil', 'Oportunidades' => '/crm/oportunidades', 'Editar'],
            'op'                 => $op,
            'isEdit'             => true,
            'interacoes'         => $interacoes,
            'interacoesLead'     => $interacoesLead,
            'leads'              => $leads,
            'etapas'             => CrmOportunidade::ETAPAS,
            'statusList'         => CrmOportunidade::STATUS,
            'modalidades'        => CrmOportunidade::MODALIDADES,
            'modalidadesAtivas'  => $modalidadesAtivas,
            'tiposContrato'      => CrmOportunidade::TIPOS_CONTRATO,
            'tiposInteracao'     => CrmInteracao::TIPOS,
            'iconesInteracao'    => CrmInteracao::ICONES,
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

        $data = [
            'usuario_id'     => $uid,
            'related_id'     => $opId,
            'related_type'   => 'oportunidade',
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

    private function jsonError(string $msg): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $msg]);
        exit();
    }
}
