<?php

namespace App\Middlewares;

use App\Core\Middleware;

/**
 * Middleware que protege as rotas do Portal do Cliente.
 * Verifica se há uma sessão de portal ativa.
 */
class PortalClienteMiddleware extends Middleware
{
    public function handle(): void
    {
        if (empty($_SESSION['portal_cliente_id'])) {
            header('Location: /login');
            exit();
        }
    }
}
