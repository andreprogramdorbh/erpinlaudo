<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;

class HomeController extends Controller
{
    public function index()
    {
        // Se o usuário não está autenticado, redireciona para o login
        if (!isset($_SESSION["user_id"])) {
            header("Location: /login");
            exit();
        }

        // Se está autenticado, redireciona para o dashboard
        header("Location: /dashboard");
        exit();
    }
}
