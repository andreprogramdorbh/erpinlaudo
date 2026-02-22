<?php
/**
 * ERP InLaudo - Formulário de Clientes (Enterprise Layout)
 * Implementação do novo padrão de layout para o módulo de clientes
 */

use App\Core\UI;

$cliente = $cliente ?? null;
$isEdit = !empty($cliente);
$activeTab = $_GET['tab'] ?? 'geral';
$contatos = $contatos ?? [];

// Configuração do formulário enterprise
$formConfig = [
    'title' => $isEdit ? 'Editar Cliente' : 'Novo Cliente',
    'subtitle' => $isEdit
        ? "Gerencie os detalhes de {$cliente->razao_social}"
        : 'Cadastre um novo cliente na sua rede',
    'is_edit' => $isEdit,
    'record_id' => $cliente->id ?? null,
    'active_tab' => $activeTab,
    'class' => 'clientes-form',

    'actions' => [
        [
            'url' => '/clientes',
            'label' => 'Voltar',
            'icon' => 'fas fa-arrow-left',
            'color' => 'light'
        ]
    ],

    'tabs' => [
        [
            'id' => 'geral',
            'title' => 'Dados Gerais & Endereço',
            'icon' => 'fas fa-info-circle',
            'locked' => false,
            'view' => 'clientes.tabs.geral-enterprise'
        ],
        [
            'id' => 'contatos',
            'title' => 'Gestão de Contatos',
            'icon' => 'fas fa-address-book',
            'locked' => !$isEdit,
            'locked_message' => 'Salve os dados gerais primeiro para habilitar os contatos.',
            'view' => $isEdit ? 'clientes.tabs.contatos-enterprise' : null
        ],
        [
            'id' => 'anexos',
            'title' => 'Anexos',
            'icon' => 'fas fa-paperclip',
            'locked' => !$isEdit,
            'locked_message' => 'Salve os dados gerais primeiro para habilitar os anexos.',
            'view' => $isEdit ? 'clientes.tabs.anexos-enterprise' : null
        ]
    ],

    'footer_actions' => [
        [
            'type' => 'button',
            'label' => 'Cancelar',
            'url' => '/clientes',
            'color' => 'light',
            'large' => true
        ],
        [
            'type' => 'submit',
            'label' => $isEdit ? 'Salvar e Continuar' : 'Salvar e Próxima Etapa',
            'color' => 'primary',
            'large' => true,
            'primary' => true,
            'form' => 'clienteFormGeral'
        ]
    ]
];

// Renderiza o formulário enterprise
$data = $formConfig;
include_once __DIR__ . '/../components/form/enterprise-form.php';
?>