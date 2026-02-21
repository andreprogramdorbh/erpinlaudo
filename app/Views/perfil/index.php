<?php
/**
 * ERP InLaudo - Formulário de Perfil do Usuário (Enterprise Layout)
 * Interface para gerenciamento de dados pessoais e segurança
 */

use App\Core\UI;

$usuario = $usuario ?? null;
$activeTab = $activeTab ?? 'geral';

// Configuração do formulário enterprise
$formConfig = [
    'title' => 'Meu Perfil',
    'subtitle' => 'Gerencie suas informações pessoais e configurações de segurança',
    'is_edit' => true,
    'record_id' => $usuario->id ?? null,
    'active_tab' => $activeTab,
    'class' => 'perfil-form',

    'actions' => [
        [
            'url' => '/dashboard',
            'label' => 'Voltar',
            'icon' => 'fas fa-arrow-left',
            'color' => 'light'
        ]
    ],

    'tabs' => [
        [
            'id' => 'geral',
            'title' => 'Dados Gerais',
            'icon' => 'fas fa-user',
            'locked' => false,
            'view' => 'perfil.tabs.geral'
        ],
        [
            'id' => 'seguranca',
            'title' => 'Segurança',
            'icon' => 'fas fa-shield-alt',
            'locked' => false,
            'view' => 'perfil.tabs.seguranca'
        ]
    ],

    'footer_actions' => [
        [
            'type' => 'button',
            'label' => 'Cancelar',
            'url' => '/dashboard',
            'color' => 'light',
            'large' => true
        ],
        [
            'type' => 'submit',
            'label' => 'Salvar Alterações',
            'color' => 'primary',
            'large' => true,
            'primary' => true,
            'form' => 'perfilFormGeral'
        ]
    ]
];

// Renderiza o formulário enterprise
$data = $formConfig;
include_once __DIR__ . '/../components/form/enterprise-form.php';
