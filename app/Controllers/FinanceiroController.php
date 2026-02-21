<?php

namespace App\Controllers;

use App\Core\View;

class FinanceiroController
{
    public function contasAPagar()
    {
        View::render('placeholder/index', ['title' => 'Contas a Pagar']);
    }

    public function contasAReceber()
    {
        View::render('placeholder/index', ['title' => 'Contas a Receber']);
    }
}
