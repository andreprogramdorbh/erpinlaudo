<?php
/**
 * Arquivo de Debug v2 - Para Verificar Após Reorganização
 * 
 * Use este arquivo após reorganizar os arquivos para confirmar que tudo está correto.
 */

ob_start();

echo "=== DEBUG DO ERRO 500 - VERSÃO 2 ===\n\n";

// Informações básicas
echo "📍 Localização do Script:\n";
echo "   Script: " . __FILE__ . "\n";
echo "   Diretório raiz detectado: " . dirname(dirname(__DIR__)) . "\n\n";

// 1. Verificar PHP
echo "1️⃣ Versão do PHP:\n";
$php_ok = phpversion() >= '8.0';
echo "   PHP " . phpversion() . " " . ($php_ok ? "✓" : "✗") . "\n\n";

// 2. Verificar extensões
echo "2️⃣ Extensões PHP:\n";
$extensions = ['pdo', 'pdo_mysql', 'json', 'curl', 'mbstring'];
$all_ext_ok = true;
foreach ($extensions as $ext) {
    $ok = extension_loaded($ext);
    $all_ext_ok = $all_ext_ok && $ok;
    echo "   - $ext: " . ($ok ? "✓" : "✗") . "\n";
}
echo "\n";

// 3. Verificar .env
echo "3️⃣ Arquivo .env:\n";
$env_path = dirname(dirname(__DIR__)) . '/.env';
$env_ok = file_exists($env_path);
echo "   Caminho: $env_path\n";
echo "   Status: " . ($env_ok ? "✓ EXISTE" : "✗ NÃO ENCONTRADO") . "\n";
if ($env_ok) {
    echo "   Tamanho: " . filesize($env_path) . " bytes\n";
    $env_content = file_get_contents($env_path);
    echo "   Linhas: " . count(explode("\n", $env_content)) . "\n";
}
echo "\n";

// 4. Verificar bootstrap
echo "4️⃣ Arquivo bootstrap.php:\n";
$bootstrap_path = dirname(dirname(__DIR__)) . '/app/bootstrap.php';
$bootstrap_ok = file_exists($bootstrap_path);
echo "   Caminho: $bootstrap_path\n";
echo "   Status: " . ($bootstrap_ok ? "✓ EXISTE" : "✗ NÃO ENCONTRADO") . "\n";
if ($bootstrap_ok) {
    echo "   Tamanho: " . filesize($bootstrap_path) . " bytes\n";
}
echo "\n";

// 5. Verificar Composer
echo "5️⃣ Autoloader do Composer:\n";
$autoload_path = dirname(dirname(__DIR__)) . '/vendor/autoload.php';
$composer_ok = file_exists($autoload_path);
echo "   Caminho: $autoload_path\n";
echo "   Status: " . ($composer_ok ? "✓ EXISTE" : "✗ NÃO ENCONTRADO") . "\n";
echo "\n";

// 6. Verificar diretórios
echo "6️⃣ Diretórios Necessários:\n";
$dirs = [
    'app' => dirname(dirname(__DIR__)) . '/app',
    'config' => dirname(dirname(__DIR__)) . '/config',
    'database' => dirname(dirname(__DIR__)) . '/database',
    'routes' => dirname(dirname(__DIR__)) . '/routes',
    'storage' => dirname(dirname(__DIR__)) . '/storage',
    'public_html' => dirname(dirname(__DIR__)) . '/public_html',
];
$all_dirs_ok = true;
foreach ($dirs as $name => $path) {
    $ok = is_dir($path);
    $all_dirs_ok = $all_dirs_ok && $ok;
    echo "   - $name: " . ($ok ? "✓" : "✗") . "\n";
}
echo "\n";

// 7. Verificar storage/logs
echo "7️⃣ Diretório storage/logs:\n";
$logs_path = dirname(dirname(__DIR__)) . '/storage/logs';
$logs_ok = is_dir($logs_path);
echo "   Caminho: $logs_path\n";
echo "   Status: " . ($logs_ok ? "✓ EXISTE" : "✗ NÃO ENCONTRADO") . "\n";
if ($logs_ok) {
    $writable = is_writable($logs_path);
    echo "   Gravável: " . ($writable ? "✓" : "✗") . "\n";
    $perms = substr(sprintf('%o', fileperms($logs_path)), -4);
    echo "   Permissões: $perms\n";
}
echo "\n";

// 8. Tentar carregar bootstrap
echo "8️⃣ Tentando Carregar Bootstrap:\n";
$bootstrap_error = null;
try {
    if (file_exists($bootstrap_path)) {
        require_once $bootstrap_path;
        echo "   ✓ Bootstrap carregado com sucesso!\n";
    } else {
        echo "   ✗ Arquivo bootstrap.php não encontrado\n";
    }
} catch (Throwable $e) {
    $bootstrap_error = $e->getMessage();
    echo "   ✗ ERRO: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n";
}
echo "\n";

// 9. Resumo geral
echo "9️⃣ Resumo Geral:\n";
$all_ok = $php_ok && $all_ext_ok && $env_ok && $bootstrap_ok && $composer_ok && $all_dirs_ok && $logs_ok;
echo "   Status Geral: " . ($all_ok ? "✓ TUDO OK" : "✗ PROBLEMAS ENCONTRADOS") . "\n";
echo "\n";

// 10. Recomendações
echo "🔟 Recomendações:\n";
if (!$env_ok) {
    echo "   ⚠️ Arquivo .env não encontrado - Crie-o na raiz do projeto\n";
}
if (!$bootstrap_ok) {
    echo "   ⚠️ Arquivo bootstrap.php não encontrado - Verifique a estrutura\n";
}
if (!$composer_ok) {
    echo "   ⚠️ Composer não instalado - Execute: composer install --no-dev\n";
}
if (!$all_dirs_ok) {
    echo "   ⚠️ Alguns diretórios estão faltando - Verifique a estrutura\n";
}
if ($bootstrap_error) {
    echo "   ⚠️ Erro ao carregar bootstrap - Verifique o arquivo error.log\n";
}
if ($all_ok) {
    echo "   ✓ Tudo parece estar correto! Teste acessando seu domínio.\n";
}

$output = ob_get_clean();

// Exibir como HTML
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug v2 - Erro 500</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            padding: 20px;
            margin: 0;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-top: 0;
        }
        pre {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            overflow-x: auto;
            border-left: 4px solid #667eea;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
        .warning {
            color: #f39c12;
            font-weight: bold;
        }
        .info {
            color: #3498db;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #666;
        }
        .status-ok {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .status-error {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico do Erro 500 - Versão 2</h1>
        
        <?php if ($all_ok): ?>
            <div class="status-ok">
                <strong>✓ Tudo parece estar correto!</strong><br>
                Se ainda estiver recebendo erro 500, o problema pode estar em outro lugar.
                Verifique o arquivo <code>error.log</code> em <code>storage/logs/</code>.
            </div>
        <?php else: ?>
            <div class="status-error">
                <strong>✗ Problemas encontrados</strong><br>
                Verifique os detalhes abaixo e resolva cada problema.
            </div>
        <?php endif; ?>
        
        <pre><?php echo htmlspecialchars($output); ?></pre>
        
        <div class="footer">
            <p><strong>Próximos passos:</strong></p>
            <ul>
                <li>Se todos os itens estão com ✓, o projeto está configurado corretamente</li>
                <li>Se há ✗, resolva cada problema conforme as recomendações acima</li>
                <li>Após resolver, teste acessando seu domínio: <code>http://erp.inlaudo.com.br/</code></li>
                <li><strong>Importante:</strong> Delete este arquivo por segurança após resolver</li>
            </ul>
        </div>
    </div>
</body>
</html>
