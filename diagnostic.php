<?php
/**
 * ERP InLaudo - Script de Diagnóstico
 * Verifica configurações de ambiente e serviços
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\CryptoService;
use App\Services\MailService;

echo "<h1>🔍 Diagnóstico do ERP InLaudo</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .ok { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

// Função helper
function status($condition, $message) {
    $class = $condition ? 'ok' : 'error';
    $icon = $condition ? '✅' : '❌';
    echo "<span class='{$class}'>{$icon} {$message}</span><br>\n";
    return $condition;
}

// 1. Variáveis de Ambiente
echo "<div class='section'>";
echo "<h2>📋 Variáveis de Ambiente</h2>\n";

$envVars = [
    'APP_ENV' => 'Ambiente da aplicação',
    'APP_KEY' => 'Chave de criptografia primária',
    'APP_ENCRYPTION_KEY' => 'Chave de criptografia secundária',
    'DB_HOST' => 'Host do banco de dados',
    'DB_DATABASE' => 'Nome do banco',
    'DB_USERNAME' => 'Usuário do banco',
    'MAIL_HOST' => 'Servidor SMTP',
    'MAIL_USERNAME' => 'Usuário do e-mail',
    'MAIL_FROM_EMAIL' => 'E-mail remetente'
];

$allOk = true;
foreach ($envVars as $var => $desc) {
    $value = getenv($var) ?: $_ENV[$var] ?? null;
    $hasValue = !empty($value);
    if (!status($hasValue, "{$desc}: " . ($hasValue ? 'Configurado' : 'Não configurado'))) {
        $allOk = false;
    }
    echo "<small style='color: #666;'>Variável: {$var}</small><br>\n";
}
echo "</div>";

// 2. Criptografia
echo "<div class='section'>";
echo "<h2>🔐 Serviço de Criptografia</h2>\n";

try {
    $cryptoOk = CryptoService::isConfigured();
    status($cryptoOk, 'CryptoService configurado');
    
    if ($cryptoOk) {
        $crypto = new CryptoService();
        $test = 'Teste de criptografia 123';
        $encrypted = $crypto->encryptString($test);
        $decrypted = $crypto->decryptString($encrypted);
        status($decrypted === $test, 'Teste de criptografia/descriptografia');
        
        echo "<pre>Original: {$test}\n";
        echo "Criptografado: " . substr($encrypted, 0, 50) . "...\n";
        echo "Descriptografado: {$decrypted}</pre>\n";
    }
} catch (Exception $e) {
    status(false, 'Erro no CryptoService: ' . $e->getMessage());
}
echo "</div>";

// 3. E-mail
echo "<div class='section'>";
echo "<h2>📧 Serviço de E-mail</h2>\n";

try {
    $mailOk = MailService::isConfigured();
    status($mailOk, 'MailService configurado');
    
    if ($mailOk) {
        $mail = new MailService();
        echo "<pre>Configurações carregadas automaticamente do .env</pre>\n";
        
        // Teste de configuração (não envia e-mail)
        $config = [
            'host' => getenv('MAIL_HOST') ?: $_ENV['MAIL_HOST'] ?? '',
            'port' => getenv('MAIL_PORT') ?: $_ENV['MAIL_PORT'] ?? '',
            'username' => getenv('MAIL_USERNAME') ?: $_ENV['MAIL_USERNAME'] ?? '',
            'from_email' => getenv('MAIL_FROM_EMAIL') ?: $_ENV['MAIL_FROM_EMAIL'] ?? ''
        ];
        
        foreach ($config as $key => $value) {
            status(!empty($value), "{$key}: " . ($value ?: 'Não configurado'));
        }
    }
} catch (Exception $e) {
    status(false, 'Erro no MailService: ' . $e->getMessage());
}
echo "</div>";

// 4. Extensões PHP
echo "<div class='section'>";
echo "<h2>🔧 Extensões PHP Necessárias</h2>\n";

$extensions = [
    'pdo' => 'PDO (Banco de dados)',
    'pdo_mysql' => 'PDO MySQL',
    'openssl' => 'OpenSSL (Criptografia)',
    'mbstring' => 'Multibyte String',
    'curl' => 'cURL (Requisições HTTP)',
    'json' => 'JSON',
    'session' => 'Sessão'
];

foreach ($extensions as $ext => $desc) {
    status(extension_loaded($ext), $desc);
}
echo "</div>";

// 5. Permissões
echo "<div class='section'>";
echo "<h2>📁 Permissões de Diretórios</h2>\n";

$dirs = [
    __DIR__ . '/app/logs' => 'Logs',
    __DIR__ . '/storage' => 'Storage',
    __DIR__ . '/cache' => 'Cache'
];

foreach ($dirs as $dir => $desc) {
    if (is_dir($dir)) {
        $writable = is_writable($dir);
        status($writable, "{$desc}: " . ($writable ? 'Gravável' : 'Sem permissão de escrita'));
    } else {
        status(false, "{$desc}: Diretório não existe");
    }
}
echo "</div>";

// Resumo
echo "<div class='section'>";
echo "<h2>📊 Resumo</h2>\n";

if ($allOk) {
    echo "<span class='ok'>✅ Todas as configurações básicas estão OK!</span><br>\n";
    echo "<small>Se ainda houver problemas, verifique os logs de erro.</small>\n";
} else {
    echo "<span class='error'>❌ Existem configurações pendentes.</span><br>\n";
    echo "<small>Corrija os itens marcados em vermelho acima.</small>\n";
}
echo "</div>";

// Instruções
echo "<div class='section'>";
echo "<h2>📝 Instruções de Configuração</h2>\n";
echo "<pre>
1. Copie .env.example para .env:
   cp .env.example .env

2. Gere chaves de criptografia:
   openssl rand -base64 32

3. Configure as variáveis no .env:
   - APP_KEY=sua-chave-32-bytes
   - APP_ENCRYPTION_KEY=sua-chave-32-bytes
   - DB_HOST=localhost
   - DB_DATABASE=seu_banco
   - DB_USERNAME=seu_usuario
   - DB_PASSWORD=sua_senha
   - MAIL_HOST=smtp.gmail.com
   - MAIL_USERNAME=seu@email.com
   - MAIL_PASSWORD=sua_senha_app

4. Para Gmail, use 'App Password':
   - Ative 2FA na conta Google
   - Vá para: https://myaccount.google.com/apppasswords
   - Crie uma senha para 'ERP InLaudo'
   - Use essa senha em MAIL_PASSWORD

5. Teste novamente este script.
</pre>";
echo "</div>";

echo "<p><small>Diagnóstico concluído em " . date('Y-m-d H:i:s') . "</small></p>\n";
?>
