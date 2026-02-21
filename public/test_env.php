<?php

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

echo "=== TESTE 1: Carregamento de .env ===\n\n";

$vars = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];

foreach ($vars as $var) {
    $value = getenv($var);
    $status = empty($value) ? '❌ VAZIO' : '✅ OK';
    echo "{$var}: {$status}\n";
    if (!empty($value) && $var !== 'DB_PASSWORD') {
        echo "  Valor: {$value}\n";
    }
}

echo "\n=== TESTE 2: Carregamento de Config ===\n\n";

$config = require BASE_PATH . '/config/database.php';

echo "Host: {$config['host']}\n";
echo "Database: {$config['database']}\n";
echo "Username: {$config['username']}\n";
echo "Password: " . (empty($config['password']) ? '(vazio)' : '(preenchido)') . "\n";

echo "\n=== TESTE 3: Conexão com PDO ===\n\n";

try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "✅ Conexão bem-sucedida!\n";
    
    // Testar se a tabela users existe
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Tabela 'users' existe com " . $result['count'] . " registros\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
