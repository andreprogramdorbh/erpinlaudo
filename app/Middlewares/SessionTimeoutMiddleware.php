<?php

namespace App\Middlewares;

use App\Core\Middleware;

class SessionTimeoutMiddleware extends Middleware
{
    private int $timeout = 3600; // 60 minutos em segundos

    public function handle(): void
    {
        if (isset($_SESSION["user_id"])) {
            $currentTime = time();
            $lastActivity = $_SESSION["last_activity"] ?? $currentTime;

            if ($currentTime - $lastActivity > $this->timeout) {
                session_destroy();
                header("Location: /login?timeout=1");
                exit();
            }

            $_SESSION["last_activity"] = $currentTime;
        }
    }
}
