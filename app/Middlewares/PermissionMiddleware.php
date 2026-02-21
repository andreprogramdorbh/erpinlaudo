<?php

namespace App\Middlewares;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Audit\AuditLogger;

class PermissionMiddleware extends Middleware
{
    protected string $permission;

    public function __construct(string $permission = '')
    {
        $this->permission = $permission;
    }

    public function handle(): void
    {
        // Se a permissão não for atendida, redireciona ou nega
        if (!Auth::can($this->permission)) {
            AuditLogger::log('access_denied', ['permission' => $this->permission]);
            http_response_code(403);
            // Poderia redirecionar para uma página amigável de "Acesso Negado"
            echo "<h1>403 - Acesso Negado</h1>";
            echo "<p>Você não tem permissão para acessar esta área: <b>{$this->permission}</b></p>";
            echo "<a href='/dashboard'>Voltar ao Painel</a>";
            exit();
        }
    }
}
