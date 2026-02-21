<?php

namespace App\Middlewares;

use App\Core\Middleware;

class CsrfMiddleware extends Middleware
{
    public function handle(): void
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!isset($_POST["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])) {
                // Token inválido ou ausente
                http_response_code(403);
                echo "Erro 403: Acesso proibido. Token CSRF inválido.";
                exit();
            }
        }
    }
}
