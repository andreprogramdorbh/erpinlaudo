<?php
/**
 * ERP InLaudo - Teste de Envio de E-mail
 * Script para testar configurações SMTP
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\MailService;
use App\Core\Audit\AuditLogger;

echo "<h1>📧 Teste de Envio de E-mail</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .ok { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    input, button { padding: 5px; margin: 5px; }
</style>";

// Processar formulário
if ($_POST['action'] === 'send_test') {
    $toEmail = $_POST['email'] ?? '';
    $subject = 'Teste de E-mail - ERP InLaudo';
    $body = "Este é um e-mail de teste enviado pelo ERP InLaudo.\n\n";
    $body .= "Data/hora: " . date('Y-m-d H:i:s') . "\n";
    $body .= "IP do servidor: " . ($_SERVER['SERVER_ADDR'] ?? 'N/A') . "\n\n";
    $body .= "Se você recebeu este e-mail, as configurações SMTP estão corretas!";
    
    try {
        $mail = new MailService();
        $success = $mail->send($toEmail, $subject, $body);
        
        if ($success) {
            // Log de sucesso
            AuditLogger::log('email_test_success', [
                'to_email' => $toEmail,
                'subject' => $subject,
                'host' => getenv('MAIL_HOST') ?: $_ENV['MAIL_HOST'] ?? 'unknown'
            ]);
            
            echo "<div class='section'>";
            echo "<span class='ok'>✅ E-mail enviado com sucesso para {$toEmail}</span><br>\n";
            echo "<small>Verifique sua caixa de entrada (e spam).</small>\n";
            echo "</div>";
        } else {
            // Log de falha genérica
            AuditLogger::log('email_test_failure', [
                'to_email' => $toEmail,
                'error' => 'Falha desconhecida ao enviar e-mail',
                'host' => getenv('MAIL_HOST') ?: $_ENV['MAIL_HOST'] ?? 'unknown'
            ]);
            
            echo "<div class='section'>";
            echo "<span class='error'>❌ Falha ao enviar e-mail</span><br>\n";
            echo "</div>";
        }
    } catch (\Exception $e) {
        // Log detalhado da falha com contexto seguro
        AuditLogger::log('email_test_failure', [
            'to_email' => $toEmail,
            'error' => $e->getMessage(),
            'host' => getenv('MAIL_HOST') ?: $_ENV['MAIL_HOST'] ?? 'unknown',
            'port' => getenv('MAIL_PORT') ?: $_ENV['MAIL_PORT'] ?? 'unknown',
            'username' => getenv('MAIL_USERNAME') ?: $_ENV['MAIL_USERNAME'] ?? 'unknown'
            // NUNCA registrar senha ou chaves de criptografia
        ]);
        
        echo "<div class='section'>";
        echo "<span class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</span><br>\n";
        echo "<small>Erro registrado no log de auditoria para análise.</small>\n";
        echo "</div>";
    }
}

// Verificar configurações
echo "<div class='section'>";
echo "<h2>🔍 Configurações Atuais</h2>\n";

$configVars = [
    'MAIL_HOST' => 'Servidor SMTP',
    'MAIL_PORT' => 'Porta',
    'MAIL_USERNAME' => 'Usuário',
    'MAIL_ENCRYPTION' => 'Criptografia',
    'MAIL_FROM_EMAIL' => 'E-mail remetente'
];

foreach ($configVars as $var => $desc) {
    $value = getenv($var) ?: $_ENV[$var] ?? null;
    $displayValue = $value;
    
    // Oculta senha
    if ($var === 'MAIL_PASSWORD') {
        $displayValue = $value ? '***' : 'Não configurado';
    }
    
    $hasValue = !empty($value);
    $class = $hasValue ? 'ok' : 'error';
    $icon = $hasValue ? '✅' : '❌';
    
    echo "<span class='{$class}'>{$icon} {$desc}: " . htmlspecialchars($displayValue) . "</span><br>\n";
}

echo "</div>";

// Formulário de teste
echo "<div class='section'>";
echo "<h2>🧪 Enviar E-mail de Teste</h2>\n";
echo "<form method='POST'>\n";
echo "<input type='hidden' name='action' value='send_test'>\n";
echo "<label for='email'>E-mail de destino:</label><br>\n";
echo "<input type='email' id='email' name='email' required placeholder='seu@email.com' style='width: 300px;'>\n";
echo "<button type='submit' style='background: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;'>📧 Enviar Teste</button>\n";
echo "</form>\n";
echo "</div>";

// Informações de configuração
echo "<div class='section'>";
echo "<h2>⚙️ Configurações Comuns</h2>\n";
echo "<h3>Gmail</h3>\n";
echo "<pre>
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=seu@gmail.com
MAIL_PASSWORD=sua_app_password

# Para gerar App Password:
# 1. Ative 2FA na conta Google
# 2. Vá para: https://myaccount.google.com/apppasswords
# 3. Selecione 'Outro (nome personalizado)'
# 4. Digite 'ERP InLaudo'
# 5. Use a senha gerada em MAIL_PASSWORD
</pre>";

echo "<h3>Outlook/Hotmail</h3>\n";
echo "<pre>
MAIL_HOST=smtp-mail.outlook.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=seu@outlook.com
MAIL_PASSWORD=sua_senha
</pre>";

echo "<h3>Yahoo</h3>\n";
echo "<pre>
MAIL_HOST=smtp.mail.yahoo.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=seu@yahoo.com
MAIL_PASSWORD=sua_senha_app
</pre>";
echo "</div>";

// Dicas de solução de problemas
echo "<div class='section'>";
echo "<h2>🔧 Solução de Problemas</h2>\n";
echo "<ul>\n";
echo "<li><strong>Erro de autenticação:</strong> Verifique usuário e senha. Para Gmail, use App Password.</li>\n";
echo "<li><strong>Erro de conexão:</strong> Verifique host e porta. Firewalls podem bloquear.</li>\n";
echo "<li><strong>Erro TLS:</strong> Tente mudar MAIL_ENCRYPTION para 'ssl' (porta 465) ou vazio.</li>\n";
echo "<li><strong>E-mail vai para spam:</strong> Verifique configurações SPF/DNS do domínio.</li>\n";
echo "<li><strong>Timeout:</strong> Aumente timeout ou verifique conexão com servidor.</li>\n";
echo "</ul>\n";
echo "</div>";

echo "<p><small>Teste executado em " . date('Y-m-d H:i:s') . "</small></p>\n";
?>
