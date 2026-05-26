<?php
/**
 * Teste direto do erp_header.php
 * Acesso: https://erp.inlaudo.com.br/test_header.php?token=inlaudo2026clear
 */
$token = $_GET['token'] ?? '';
if ($token !== 'inlaudo2026clear') { http_response_code(403); die('Acesso negado'); }

// Simular sessão de usuário
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test';
$_SESSION['user_role'] = 'admin';

// Definir ERP_VIEW_RENDERING como o View::render faz
if (!defined('ERP_VIEW_RENDERING')) {
    define('ERP_VIEW_RENDERING', true);
}

// Tentar incluir o erp_header.php e capturar erros
$baseDir = dirname(__DIR__);
$headerFile = "{$baseDir}/app/Views/layout/erp_header.php";

echo "=== TESTE DO ERP_HEADER.PHP ===\n";
echo "Arquivo existe: " . (file_exists($headerFile) ? 'SIM' : 'NÃO') . "\n";
echo "Tamanho: " . filesize($headerFile) . " bytes\n\n";

// Capturar erros
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "ERRO PHP: [$errno] $errstr em $errfile linha $errline\n";
    return true;
});

// Tentar incluir
echo "=== TENTANDO INCLUIR erp_header.php ===\n";
ob_start();
try {
    require $headerFile;
    $output = ob_get_clean();
    echo "SUCESSO! Output length: " . strlen($output) . " bytes\n";
    echo "Primeiros 200 chars do output:\n";
    echo htmlspecialchars(substr($output, 0, 200)) . "\n";
} catch (Throwable $e) {
    $output = ob_get_clean();
    echo "EXCEÇÃO: " . $e->getMessage() . "\n";
    echo "Em: " . $e->getFile() . " linha " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
