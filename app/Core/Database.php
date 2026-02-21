<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    /**
     * Impede a instanciação direta.
     */
    private function __construct() {}

    /**
     * Impede a clonagem.
     */
    private function __clone() {}

    /**
     * Retorna a instância única da conexão PDO.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/database.php';

            $dsn = "{$config["driver"]}:host={$config["host"]};port={$config["port"]};dbname={$config["database"]};charset={$config["charset"]}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $config["username"], $config["password"], $options);
            } catch (PDOException $e) {
                // Em um ambiente de produção, logar o erro em vez de exibi-lo.
                http_response_code(500);
                die("Erro de conexão com o banco de dados: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
