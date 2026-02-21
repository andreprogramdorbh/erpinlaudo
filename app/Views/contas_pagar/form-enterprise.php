<?php

$conta = $conta ?? null;
$isEdit = !empty($conta);
$activeTab = $_GET['tab'] ?? 'geral';
$planos = $planos ?? [];
$fornecedores = $fornecedores ?? [];
$anexos = $anexos ?? [];

$formConfig = [
    'title' => $isEdit ? 'Editar Conta a Pagar' : 'Nova Conta a Pagar',
    'subtitle' => $isEdit
        ? 'Atualize os dados e gerencie anexos'
        : 'Cadastre uma nova conta a pagar',
    'is_edit' => $isEdit,
    'record_id' => $conta->id ?? null,
    'active_tab' => $activeTab,
    'class' => 'contas-pagar-form',

    'actions' => [
        [
            'url' => '/financeiro/contas-a-pagar',
            'label' => 'Voltar',
            'icon' => 'fas fa-arrow-left',
            'color' => 'light'
        ]
    ],

    'tabs' => [
        [
            'id' => 'geral',
            'title' => 'Dados da Conta',
            'icon' => 'fas fa-file-invoice-dollar',
            'locked' => false,
            'view' => 'contas_pagar.tabs.geral-enterprise'
        ],
        [
            'id' => 'anexos',
            'title' => 'Anexos',
            'icon' => 'fas fa-paperclip',
            'locked' => !$isEdit,
            'locked_message' => 'Salve a conta primeiro para habilitar anexos.',
            'view' => $isEdit ? 'contas_pagar.tabs.anexos-enterprise' : null
        ]
    ],

    'footer_actions' => [
        [
            'type' => 'button',
            'label' => 'Cancelar',
            'url' => '/financeiro/contas-a-pagar',
            'color' => 'light',
            'large' => true
        ],
        [
            'type' => 'submit',
            'label' => $isEdit ? 'Salvar' : 'Criar Conta',
            'color' => 'primary',
            'large' => true,
            'primary' => true,
            'form' => 'contaPagarFormGeral'
        ]
    ]
];

$data = $formConfig;
include_once __DIR__ . '/../components/form/enterprise-form.php';
