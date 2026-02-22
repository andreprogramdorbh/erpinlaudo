<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define o caminho raiz da aplicação
define('BASE_PATH', dirname(__DIR__));

// Carrega o arquivo de bootstrap
require_once BASE_PATH . '/app/bootstrap.php';
