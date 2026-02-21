<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;

class ConfiguracoesController extends Controller
{
    public function index()
    {
        View::render("placeholder/index", ["title" => "Configurações"]);
    }
}
