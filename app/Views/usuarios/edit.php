<?php
/**
 * ERP InLaudo - Formulário de Edição de Usuário (Enterprise Layout)
 * Interface para edição de dados e permissões de usuários existentes
 */

use App\Core\UI;

$usuario = $usuario ?? null;
$currentUser = $current_user ?? null;

// Configuração do formulário enterprise
$formConfig = [
    'title' => 'Editar Usuário',
    'subtitle' => 'Gerencie os dados e permissões do usuário: ' . htmlspecialchars($usuario->name),
    'is_edit' => true,
    'record_id' => $usuario->id ?? null,
    'active_tab' => 'dados',
    'class' => 'usuario-edit-form',

    'actions' => [
        [
            'url' => '/configuracoes/usuarios',
            'label' => 'Voltar',
            'icon' => 'fas fa-arrow-left',
            'color' => 'light'
        ]
    ],

    'tabs' => [
        [
            'id' => 'dados',
            'title' => 'Dados Pessoais',
            'icon' => 'fas fa-user',
            'locked' => false,
            'view' => 'usuarios.tabs.dados-edit'
        ],
        [
            'id' => 'permissoes',
            'title' => 'Permissões de Acesso',
            'icon' => 'fas fa-shield-alt',
            'locked' => false,
            'view' => 'usuarios.tabs.permissoes-edit'
        ]
    ],

    'footer_actions' => [
        [
            'type' => 'button',
            'label' => 'Cancelar',
            'url' => '/configuracoes/usuarios',
            'color' => 'light',
            'large' => true
        ],
        [
            'type' => 'submit',
            'label' => 'Salvar Alterações',
            'color' => 'primary',
            'large' => true,
            'primary' => true,
            'form' => 'usuarioEditForm'
        ]
    ]
];

// Renderiza o formulário enterprise
$data = $formConfig;
include_once __DIR__ . '/../components/form/enterprise-form.php';
