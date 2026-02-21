<?php
/**
 * ERP InLaudo - Formulário de Criação de Usuário (Enterprise Layout)
 * Interface para criação de novos usuários com envio de e-mail de boas-vindas
 */

use App\Core\UI;

// Configuração do formulário enterprise
$formConfig = [
    'title' => 'Novo Usuário',
    'subtitle' => 'Crie uma nova conta de acesso ao sistema. O usuário receberá um e-mail com instruções para definir sua senha.',
    'is_edit' => false,
    'record_id' => null,
    'active_tab' => 'dados',
    'class' => 'usuario-create-form',

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
            'view' => 'usuarios.tabs.dados'
        ],
        [
            'id' => 'permissoes',
            'title' => 'Permissões de Acesso',
            'icon' => 'fas fa-shield-alt',
            'locked' => false,
            'view' => 'usuarios.tabs.permissoes'
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
            'label' => 'Criar Usuário',
            'color' => 'primary',
            'large' => true,
            'primary' => true,
            'form' => 'usuarioCreateForm'
        ]
    ]
];

// Renderiza o formulário enterprise
$data = $formConfig;
include_once __DIR__ . '/../components/form/enterprise-form.php';
