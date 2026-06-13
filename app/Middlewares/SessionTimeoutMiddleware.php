<?php

namespace App\Middlewares;

use App\Core\Middleware;

class SessionTimeoutMiddleware extends Middleware
{
    private int $timeout = 3600; // 60 minutos em segundos

    public function handle(): void
    {
        if (isset($_SESSION["user_id"])) {
            $currentTime  = time();
            $lastActivity = $_SESSION["last_activity"] ?? $currentTime;

            if ($currentTime - $lastActivity > $this->timeout) {
                session_destroy();

                if ($this->isAjax()) {
                    http_response_code(401);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success'  => false,
                        'message'  => 'Sessão expirada por inatividade. Por favor, faça login novamente.',
                        'redirect' => '/login?timeout=1',
                    ]);
                    exit();
                }

                header("Location: /login?timeout=1");
                exit();
            }

            $_SESSION["last_activity"] = $currentTime;
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
