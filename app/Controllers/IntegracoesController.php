<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;

class IntegracoesController extends Controller
{
    public function index()
    {
        View::render("placeholder/index", ["title" => "Integrações"]);
    }
}
