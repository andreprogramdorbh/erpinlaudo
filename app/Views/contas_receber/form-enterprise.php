<?php

$conta = $conta ?? null;
$isEdit = !empty($conta);
$activeTab = $_GET['tab'] ?? 'geral';
$planos = $planos ?? [];
$clientes = $clientes ?? [];
$parcelas = $parcelas ?? [];
$resumoParcelas = $resumoParcelas ?? null;
$temParcelas = !empty($parcelas) && count($parcelas) > 1;

$formConfig = [
    'title' => $isEdit ? 'Editar Conta a Receber' : 'Nova Conta a Receber',
    'subtitle' => $isEdit
        ? 'Atualize os dados do recebimento'
        : 'Cadastre uma nova conta a receber',
    'is_edit' => $isEdit,
    'record_id' => $conta->id ?? null,
    'active_tab' => $activeTab,
    'class' => 'contas-receber-form',

    'actions' => [
        [
            'url' => '/financeiro/contas-a-receber',
            'label' => 'Voltar',
            'icon' => 'fas fa-arrow-left',
            'color' => 'light'
        ]
    ],

    'tabs' => [
        [
            'id' => 'geral',
            'title' => 'Dados do Recebimento',
            'icon' => 'fas fa-hand-holding-usd',
            'locked' => false,
            'view' => 'contas_receber.tabs.geral-enterprise'
        ],
        [
            'id' => 'parcelas',
            'title' => 'Parcelas' . ($temParcelas ? ' (' . count($parcelas) . ')' : ''),
            'icon' => 'fas fa-list-ol',
            'locked' => !$isEdit,
            'locked_message' => 'Salve o recebimento primeiro para visualizar parcelas.',
            'view' => $isEdit ? 'contas_receber.tabs.parcelas' : null
        ],
        [
            'id' => 'anexos',
            'title' => 'Anexos',
            'icon' => 'fas fa-paperclip',
            'locked' => !$isEdit,
            'locked_message' => 'Salve o recebimento primeiro para habilitar anexos.',
            'view' => $isEdit ? 'contas_receber.tabs.anexos-enterprise' : null
        ]
    ],

    'footer_actions' => [
        [
            'type' => 'button',
            'label' => 'Cancelar',
            'url' => '/financeiro/contas-a-receber',
            'color' => 'light',
            'large' => true
        ],
        [
            'type' => 'submit',
            'label' => $isEdit ? 'Salvar' : 'Criar Conta',
            'color' => 'primary',
            'large' => true,
            'primary' => true,
            'form' => 'contaReceberFormGeral'
        ]
    ]
];

$data = $formConfig;
include_once __DIR__ . '/../components/form/enterprise-form.php';
