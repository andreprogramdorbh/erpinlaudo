<?php

$nota      = $nota ?? null;
$isEdit    = !empty($nota);
$activeTab = $tab ?? ($_GET['tab'] ?? 'geral');
$clientes  = $clientes ?? [];
$anexos    = $anexos ?? [];

// Monta as abas — Anexos só aparece quando a nota já existe
$tabs = [
    [
        'id'     => 'geral',
        'title'  => 'Dados da Nota',
        'icon'   => 'fas fa-file-invoice',
        'locked' => false,
        'view'   => 'notas_fiscais.tabs.geral-enterprise'
    ],
];

if ($isEdit) {
    $tabs[] = [
        'id'     => 'anexos',
        'title'  => 'Anexos',
        'icon'   => 'fas fa-paperclip',
        'locked' => false,
        'view'   => 'notas_fiscais.tabs.anexos-enterprise'
    ];
}

$formConfig = [
    'title'      => $isEdit ? 'Editar Nota Fiscal' : 'Nova Nota Fiscal',
    'subtitle'   => $isEdit ? 'Atualize os dados da NF' : 'Cadastre uma nova nota fiscal',
    'is_edit'    => $isEdit,
    'record_id'  => $nota->id ?? null,
    'active_tab' => $activeTab,
    'class'      => 'notas-fiscais-form',

    'actions' => [
        [
            'url'   => '/faturamento/notas-fiscais',
            'label' => 'Voltar',
            'icon'  => 'fas fa-arrow-left',
            'color' => 'light'
        ]
    ],

    'tabs' => $tabs,

    'footer_actions' => [
        [
            'type'  => 'button',
            'label' => 'Cancelar',
            'url'   => '/faturamento/notas-fiscais',
            'color' => 'light',
            'large' => true
        ],
        [
            'type'    => 'submit',
            'label'   => $isEdit ? 'Salvar' : 'Criar Nota',
            'color'   => 'primary',
            'large'   => true,
            'primary' => true,
            'form'    => 'notaFiscalFormGeral'
        ]
    ]
];

$data = $formConfig;
include_once __DIR__ . '/../components/form/enterprise-form.php';
