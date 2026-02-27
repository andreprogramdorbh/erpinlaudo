<?php

namespace App\Core;

use App\Core\Contracts\PermissionProviderInterface;

class Permission implements PermissionProviderInterface
{
    protected array $roles = [
        'superadmin' => [
            'view_clients',
            'create_clients',
            'edit_clients',
            'delete_clients',

            'view_finance',
            'manage_finance',

            'view_plano_contas',
            'create_plano_contas',
            'edit_plano_contas',
            'delete_plano_contas',

            'view_fornecedores',
            'create_fornecedores',
            'edit_fornecedores',
            'delete_fornecedores',

            'view_contas_pagar',
            'create_contas_pagar',
            'edit_contas_pagar',
            'delete_contas_pagar',

            'view_contas_receber',
            'create_contas_receber',
            'edit_contas_receber',
            'delete_contas_receber',

            'view_faturamento',
            'view_notas_fiscais',
            'create_notas_fiscais',
            'edit_notas_fiscais',
            'delete_notas_fiscais',
            'import_notas_fiscais',

            'view_integracoes',
            'manage_integracoes',

            // CRM
            'view_crm',
            'manage_leads',
            'manage_oportunidades',

            'view_profile',
            'edit_profile',

            'view_users',
            'manage_users',

            'view_settings',
            'manage_settings',
        ],

        'admin' => [
            'view_clients',
            'create_clients',
            'edit_clients',
            'delete_clients',

            'view_finance',
            'manage_finance',

            'view_plano_contas',
            'create_plano_contas',
            'edit_plano_contas',
            'delete_plano_contas',

            'view_fornecedores',
            'create_fornecedores',
            'edit_fornecedores',
            'delete_fornecedores',

            'view_contas_pagar',
            'create_contas_pagar',
            'edit_contas_pagar',
            'delete_contas_pagar',

            'view_contas_receber',
            'create_contas_receber',
            'edit_contas_receber',
            'delete_contas_receber',

            'view_faturamento',
            'view_notas_fiscais',
            'create_notas_fiscais',
            'edit_notas_fiscais',
            'delete_notas_fiscais',
            'import_notas_fiscais',

            'view_integracoes',
            'manage_integracoes',

            // CRM
            'view_crm',
            'manage_leads',
            'manage_oportunidades',

            'view_profile',
            'edit_profile',

            'view_settings',
            'manage_settings',
        ],

        'financeiro' => [
            'view_clients',
            'view_finance',

            'view_plano_contas',
            'create_plano_contas',
            'edit_plano_contas',
            'delete_plano_contas',

            'view_fornecedores',
            'create_fornecedores',
            'edit_fornecedores',
            'delete_fornecedores',

            'view_contas_pagar',
            'create_contas_pagar',
            'edit_contas_pagar',
            'delete_contas_pagar',

            'view_contas_receber',
            'create_contas_receber',
            'edit_contas_receber',
            'delete_contas_receber',

            'view_faturamento',
            'view_notas_fiscais',
            'import_notas_fiscais',

            'view_profile',
            'edit_profile',
        ],

        'operador' => [
            'view_clients',
            'create_clients',
            'edit_clients',

            // CRM — operador pode gerenciar leads e oportunidades
            'view_crm',
            'manage_leads',
            'manage_oportunidades',

            'view_profile',
            'edit_profile',
        ],

        'leitura' => [
            'view_clients',

            'view_finance',
            'view_plano_contas',
            'view_fornecedores',
            'view_contas_pagar',
            'view_contas_receber',
            'view_faturamento',
            'view_notas_fiscais',
            'view_integracoes',

            // CRM — leitura pode visualizar o funil
            'view_crm',

            'view_profile',
        ],

        'user' => [
            'view_profile',
            'edit_profile',
        ],
    ];

    public function getPermissionsForRole(string $role): array
    {
        return $this->roles[strtolower($role)] ?? [];
    }
}
