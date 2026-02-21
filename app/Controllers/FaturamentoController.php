<?php

namespace App\Controllers;

class FaturamentoController
{
    public function index()
    {
        header('Location: /faturamento/notas-fiscais');
        exit();
    }
}
