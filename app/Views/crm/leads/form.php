<?php
/**
 * ERP InLaudo - Formulário de Leads (Enterprise Layout)
 * Usa o componente enterprise-form padrão do sistema para garantir
 * compatibilidade com form-tabs.js e o layout ERP.
 */
use App\Core\UI;

$lead   = $lead ?? null;
$isEdit = !empty($lead);
$activeTab = $_GET['tab'] ?? 'dados';

// Garante que variáveis existam mesmo em create()
$interacoes     = $interacoes     ?? [];
$anexos         = $anexos         ?? [];
$transferencias = $transferencias ?? [];
$todosUsuarios  = $todosUsuarios  ?? [];
$tiposAnexo     = $tiposAnexo     ?? [];
$iconesAnexo    = $iconesAnexo    ?? [];
$motivosTransferencia = $motivosTransferencia ?? [];
$donomeAtual    = $donomeAtual    ?? ($_SESSION['user_name'] ?? 'Usuário');

// Botões de ação do header
$headerActions = [
    [
        'url'   => '/crm/leads',
        'label' => 'Voltar',
        'icon'  => 'fas fa-arrow-left',
        'color' => 'light',
    ],
];

// Adiciona botão de converter em oportunidade quando aplicável
if ($isEdit && empty($lead->convertido_em)) {
    $headerActions[] = [
        'url'   => '/crm/leads/converter/' . $lead->id,
        'label' => 'Converter em Oportunidade',
        'icon'  => 'fas fa-arrow-right',
        'color' => 'success',
    ];
} elseif ($isEdit && $lead->convertido_em === 'oportunidade') {
    $headerActions[] = [
        'url'   => '/crm/oportunidades/edit/' . ($lead->convertido_id ?? ''),
        'label' => 'Ver Oportunidade',
        'icon'  => 'fas fa-chart-line',
        'color' => 'outline-success',
    ];
}

// Definição das abas
$tabs = [
    [
        'id'     => 'dados',
        'title'  => 'Dados do Lead',
        'icon'   => 'fas fa-id-card',
        'locked' => false,
        'view'   => 'crm.leads.tabs.dados',
    ],
    [
        'id'             => 'interacoes',
        'title'          => 'Interações' . ($isEdit && !empty($interacoes) ? ' <span class="badge bg-primary rounded-pill ms-1" style="font-size:.65rem">' . count($interacoes) . '</span>' : ''),
        'icon'           => 'fas fa-comments',
        'locked'         => !$isEdit,
        'locked_message' => 'Salve o lead primeiro para registrar interações.',
        'view'           => $isEdit ? 'crm.leads.tabs.interacoes' : null,
    ],
    [
        'id'             => 'anexos',
        'title'          => 'Anexos' . ($isEdit && !empty($anexos) ? ' <span class="badge bg-success rounded-pill ms-1" style="font-size:.65rem">' . count($anexos) . '</span>' : ''),
        'icon'           => 'fas fa-paperclip',
        'locked'         => !$isEdit,
        'locked_message' => 'Salve o lead primeiro para adicionar anexos.',
        'view'           => $isEdit ? 'crm.leads.tabs.anexos' : null,
    ],
    [
        'id'             => 'transferencia',
        'title'          => 'Transferência' . ($isEdit && !empty($transferencias) ? ' <span class="badge bg-warning rounded-pill ms-1" style="font-size:.65rem">' . count($transferencias) . '</span>' : ''),
        'icon'           => 'fas fa-exchange-alt',
        'locked'         => !$isEdit,
        'locked_message' => 'Salve o lead primeiro para transferi-lo.',
        'view'           => $isEdit ? 'crm.leads.tabs.transferencia' : null,
    ],
];

$formConfig = [
    'title'      => $isEdit ? 'Editar Lead' : 'Novo Lead',
    'subtitle'   => $isEdit
        ? htmlspecialchars($lead->nome_lead ?? '')
        : 'Cadastre um novo contato comercial',
    'is_edit'    => $isEdit,
    'record_id'  => $lead->id ?? null,
    'active_tab' => $activeTab,
    'class'      => 'crm-leads-form',
    'actions'    => $headerActions,
    'tabs'       => $tabs,
    'footer_actions' => [
        [
            'type'  => 'link',
            'label' => 'Cancelar',
            'url'   => '/crm/leads',
            'color' => 'light',
            'large' => true,
        ],
        [
            'type'    => 'submit',
            'label'   => $isEdit ? 'Salvar Alterações' : 'Cadastrar Lead',
            'icon'    => 'fas fa-save',
            'color'   => 'primary',
            'large'   => true,
            'primary' => true,
            'form'    => 'leadForm',
        ],
    ],
];

$data = $formConfig;
include_once __DIR__ . '/../../components/form/enterprise-form.php';
?>
