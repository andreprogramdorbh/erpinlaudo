<?php

/**
 * Configuração de Banco de Dados
 * 
 * Este arquivo carrega as credenciais do banco de dados a partir do arquivo .env
 * Se as variáveis de ambiente não estiverem definidas, usa valores padrão como fallback.
 */

return [
    'driver'   => 'mysql',
    'host'     => isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost',
    'port'     => isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : 3306,
    'database' => isset($_ENV['DB_DATABASE']) ? $_ENV['DB_DATABASE'] : '',
    'username' => isset($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : '',
    'password' => isset($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : '',
    'charset'  => 'utf8mb4',
];
