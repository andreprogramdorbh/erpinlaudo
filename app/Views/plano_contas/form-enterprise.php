<?php

$conta = $conta ?? null;
$isEdit = !empty($conta);
$activeTab = $_GET['tab'] ?? 'geral';
$contasPai = $contasPai ?? [];

$formConfig = [
    'title' => $isEdit ? 'Editar Conta' : 'Nova Conta',
    'subtitle' => $isEdit
        ? 'Atualize as informações da conta selecionada'
        : 'Cadastre uma nova conta no seu plano de contas',
    'is_edit' => $isEdit,
    'record_id' => $conta->id ?? null,
    'active_tab' => $activeTab,
    'class' => 'plano-contas-form',

    'actions' => [
        [
            'url' => '/financeiro/plano-contas',
            'label' => 'Voltar',
            'icon' => 'fas fa-arrow-left',
            'color' => 'light'
        ]
    ],

    'tabs' => [
        [
            'id' => 'geral',
            'title' => 'Dados da Conta',
            'icon' => 'fas fa-list',
            'locked' => false,
            'view' => 'plano_contas.tabs.geral-enterprise'
        ]
    ],

    'footer_actions' => [
        [
            'type' => 'button',
            'label' => 'Cancelar',
            'url' => '/financeiro/plano-contas',
            'color' => 'light',
            'large' => true
        ],
        [
            'type' => 'submit',
            'label' => $isEdit ? 'Salvar' : 'Criar Conta',
            'color' => 'primary',
            'large' => true,
            'primary' => true,
            'form' => 'planoContaFormGeral'
        ]
    ]
];

$data = $formConfig;
include_once __DIR__ . '/../components/form/enterprise-form.php';
