<?php

namespace App\Core\Contracts;

interface PermissionProviderInterface
{
    /**
     * Retorna a lista de permissões associadas a um role.
     * A origem das permissões (hardcoded, banco, API) é irrelevante para o sistema.
     */
    public function getPermissionsForRole(string $role): array;
}
