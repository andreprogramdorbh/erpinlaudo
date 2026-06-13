<?php

namespace App\Middlewares;

use App\Core\Middleware;

class AuthMiddleware extends Middleware
{
    public function handle(): void
    {
        if (!\App\Core\Auth::check()) {
            // Requisições AJAX/fetch esperam JSON — redirecionar para login
            // resultaria em receber o HTML da página de login, quebrando o parse.
            if (self::isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success'  => false,
                    'message'  => 'Sessão expirada. Por favor, faça login novamente.',
                    'redirect' => '/login',
                ]);
                exit();
            }
            header('Location: /login');
            exit();
        }
    }

    private static function isAjax(): bool
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
        if (str_contains($ct, 'application/json')) {
            return true;
        }
        return false;
    }
}
