<?php

$fornecedor = $fornecedor ?? null;
$isEdit = !empty($fornecedor);
$activeTab = $_GET['tab'] ?? 'geral';

$formConfig = [
    'title' => $isEdit ? 'Editar Fornecedor' : 'Novo Fornecedor',
    'subtitle' => $isEdit
        ? 'Atualize os dados do fornecedor'
        : 'Cadastre um novo fornecedor para suas contas a pagar',
    'is_edit' => $isEdit,
    'record_id' => $fornecedor->id ?? null,
    'active_tab' => $activeTab,
    'class' => 'fornecedores-form',

    'actions' => [
        [
            'url' => '/fornecedores',
            'label' => 'Voltar',
            'icon' => 'fas fa-arrow-left',
            'color' => 'light'
        ]
    ],

    'tabs' => [
        [
            'id' => 'geral',
            'title' => 'Dados do Fornecedor',
            'icon' => 'fas fa-truck',
            'locked' => false,
            'view' => 'fornecedores.tabs.geral-enterprise'
        ]
    ],

    'footer_actions' => [
        [
            'type' => 'button',
            'label' => 'Cancelar',
            'url' => '/fornecedores',
            'color' => 'light',
            'large' => true
        ],
        [
            'type' => 'submit',
            'label' => $isEdit ? 'Salvar' : 'Criar Fornecedor',
            'color' => 'primary',
            'large' => true,
            'primary' => true,
            'form' => 'fornecedorFormGeral'
        ]
    ]
];

$data = $formConfig;
include_once __DIR__ . '/../components/form/enterprise-form.php';
