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

    /**
     * Expõe o PDO para operações avançadas (ex.: exclusão em cascata pelo superadmin).
     * Uso restrito: apenas controllers com verificação de permissão elevada.
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
