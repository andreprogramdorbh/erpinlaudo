<?php
/**
 * Arquivo de Debug para Diagnóstico do Erro 500
 * 
 * Este arquivo ajuda a identificar o problema exato que está causando o erro 500.
 * Coloque este arquivo em public_html/public/ e acesse via navegador.
 */

// Desabilitar o buffer de saída para ver os erros em tempo real
ob_start();

echo "=== DEBUG DO ERRO 500 ===\n\n";

// 1. Verificar versão do PHP
echo "1. Versão do PHP:\n";
echo "   PHP " . phpversion() . "\n";
echo "   Status: " . (phpversion() >= '8.0' ? "✓ OK" : "✗ ERRO - PHP 8.0+ necessário") . "\n\n";

// 2. Verificar extensões necessárias
echo "2. Extensões PHP Necessárias:\n";
$extensoes_necessarias = ['pdo', 'pdo_mysql', 'json', 'curl', 'mbstring'];
foreach ($extensoes_necessarias as $ext) {
    $status = extension_loaded($ext) ? "✓ OK" : "✗ FALTANDO";
    echo "   - $ext: $status\n";
}
echo "\n";

// 3. Verificar caminhos de arquivo
echo "3. Caminhos de Arquivo:\n";
echo "   Script atual: " . __FILE__ . "\n";
echo "   Diretório: " . __DIR__ . "\n";
echo "   Diretório raiz: " . dirname(dirname(__DIR__)) . "\n\n";

// 4. Verificar se o arquivo .env existe
echo "4. Arquivo .env:\n";
$env_path = dirname(dirname(__DIR__)) . '/.env';
echo "   Caminho esperado: $env_path\n";
echo "   Status: " . (file_exists($env_path) ? "✓ EXISTE" : "✗ NÃO ENCONTRADO") . "\n";
if (file_exists($env_path)) {
    echo "   Tamanho: " . filesize($env_path) . " bytes\n";
    echo "   Permissões: " . substr(sprintf('%o', fileperms($env_path)), -4) . "\n";
}
echo "\n";

// 5. Verificar se o arquivo bootstrap.php existe
echo "5. Arquivo bootstrap.php:\n";
$bootstrap_path = dirname(dirname(__DIR__)) . '/app/bootstrap.php';
echo "   Caminho esperado: $bootstrap_path\n";
echo "   Status: " . (file_exists($bootstrap_path) ? "✓ EXISTE" : "✗ NÃO ENCONTRADO") . "\n";
if (file_exists($bootstrap_path)) {
    echo "   Tamanho: " . filesize($bootstrap_path) . " bytes\n";
}
echo "\n";

// 6. Verificar se o autoloader do Composer existe
echo "6. Autoloader do Composer:\n";
$autoload_path = dirname(dirname(__DIR__)) . '/vendor/autoload.php';
echo "   Caminho esperado: $autoload_path\n";
echo "   Status: " . (file_exists($autoload_path) ? "✓ EXISTE" : "✗ NÃO ENCONTRADO") . "\n";
if (!file_exists($autoload_path)) {
    echo "   ⚠️ PROBLEMA: Composer não foi instalado!\n";
    echo "   Solução: Execute 'composer install --no-dev' no terminal\n";
}
echo "\n";

// 7. Verificar permissões de diretórios
echo "7. Permissões de Diretórios:\n";
$dirs_to_check = [
    dirname(dirname(__DIR__)) . '/storage/logs' => 'storage/logs',
    dirname(dirname(__DIR__)) . '/app' => 'app',
    dirname(dirname(__DIR__)) . '/config' => 'config',
];
foreach ($dirs_to_check as $path => $name) {
    if (is_dir($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path) ? "✓ Gravável" : "✗ Não gravável";
        echo "   - $name: $perms ($writable)\n";
    } else {
        echo "   - $name: ✗ DIRETÓRIO NÃO ENCONTRADO\n";
    }
}
echo "\n";

// 8. Tentar carregar o bootstrap
echo "8. Tentando Carregar o Bootstrap:\n";
try {
    $bootstrap_path = dirname(dirname(__DIR__)) . '/app/bootstrap.php';
    if (file_exists($bootstrap_path)) {
        require_once $bootstrap_path;
        echo "   ✓ Bootstrap carregado com sucesso!\n";
    } else {
        echo "   ✗ Arquivo bootstrap.php não encontrado\n";
    }
} catch (Exception $e) {
    echo "   ✗ ERRO ao carregar bootstrap:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n";
}
echo "\n";

// 9. Verificar variáveis de ambiente
echo "9. Variáveis de Ambiente (.env):\n";
if (file_exists($env_path)) {
    $env_vars = parse_ini_file($env_path);
    if ($env_vars) {
        foreach ($env_vars as $key => $value) {
            // Mascarar valores sensíveis
            $display_value = (strpos($key, 'PASSWORD') !== false || strpos($key, 'SECRET') !== false) 
                ? '***' . substr($value, -3) 
                : $value;
            echo "   - $key: $display_value\n";
        }
    } else {
        echo "   ✗ Erro ao ler arquivo .env\n";
    }
} else {
    echo "   ✗ Arquivo .env não encontrado\n";
}
echo "\n";

// 10. Informações do servidor
echo "10. Informações do Servidor:\n";
echo "   SAPI: " . php_sapi_name() . "\n";
echo "   OS: " . php_uname() . "\n";
echo "   Memória disponível: " . ini_get('memory_limit') . "\n";
echo "   Max upload: " . ini_get('upload_max_filesize') . "\n";
echo "   Tempo máximo: " . ini_get('max_execution_time') . "s\n";
echo "\n";

// Obter saída e exibir
$output = ob_get_clean();

// Exibir como HTML
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Erro 500</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background-color: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: #252526;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #007acc;
        }
        h1 {
            color: #4ec9b0;
            border-bottom: 2px solid #007acc;
            padding-bottom: 10px;
        }
        pre {
            background-color: #1e1e1e;
            padding: 15px;
            border-radius: 3px;
            overflow-x: auto;
            border-left: 3px solid #007acc;
        }
        .success {
            color: #4ec9b0;
        }
        .error {
            color: #f48771;
        }
        .warning {
            color: #dcdcaa;
        }
        .info {
            color: #9cdcfe;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico do Erro 500</h1>
        <pre><?php echo htmlspecialchars($output); ?></pre>
        <hr>
        <p><strong>Próximos passos:</strong></p>
        <ul>
            <li>Se o autoloader não foi encontrado, execute: <code>composer install --no-dev</code></li>
            <li>Se houver erros de permissão, altere as permissões dos diretórios para 755</li>
            <li>Se o bootstrap não carregar, verifique o arquivo error.log em storage/logs/</li>
            <li>Após resolver os problemas, delete este arquivo por segurança</li>
        </ul>
    </div>
</body>
</html>
