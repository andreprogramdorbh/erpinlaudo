<?php

namespace App\Core;

use PDO;

abstract class Model
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }
}
