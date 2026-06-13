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
        if (!Auth::can($this->permission)) {
            AuditLogger::log('access_denied', ['permission' => $this->permission]);
            http_response_code(403);

            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Acesso negado: você não tem permissão para esta ação.',
                ]);
                exit();
            }

            echo "<h1>403 - Acesso Negado</h1>";
            echo "<p>Você não tem permissão para acessar esta área: <b>{$this->permission}</b></p>";
            echo "<a href='/dashboard'>Voltar ao Painel</a>";
            exit();
        }
    }

    private function isAjax(): bool
    {
        $xrw = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        if ($xrw === 'xmlhttprequest') {
            return true;
        }
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($accept, 'application/json') && !str_contains($accept, 'text/html')) {
            return true;
        }
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains($ct, 'application/json');
    }
}
