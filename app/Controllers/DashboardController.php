<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;

class DashboardController extends Controller
{
    public function index()
    {
        View::render("dashboard/index", [
            "title" => "Dashboard",
            "userName" => $_SESSION["user_name"] ?? "Usuário"
        ]);
    }
}
