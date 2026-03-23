<?php
// Script temporário de emergência - remover após uso
// Acesso: https://erp.inlaudo.com.br/opcache_clear.php

$token = $_GET['token'] ?? '';
if ($token !== 'inlaudo2026clear') {
    http_response_code(403);
    die('Acesso negado');
}

$results = [];

// 1. Limpar OPcache
if (function_exists('opcache_reset')) {
    $results['opcache_reset'] = opcache_reset() ? 'OK' : 'FALHOU';
} else {
    $results['opcache_reset'] = 'OPcache não disponível';
}

// 2. Verificar versão do EmailAlertaService
$serviceFile = __DIR__ . '/../app/Services/EmailAlertaService.php';
if (file_exists($serviceFile)) {
    $content = file_get_contents($serviceFile);
    $hasFindAll = strpos($content, '->findAll(') !== false;
    $hasPdoDirect = strpos($content, 'contas_receber cr') !== false;
    $results['service_file_exists'] = true;
    $results['has_findAll_bug'] = $hasFindAll;
    $results['has_pdo_direct_fix'] = $hasPdoDirect;
    $results['file_size'] = strlen($content);
    $results['file_mtime'] = date('Y-m-d H:i:s', filemtime($serviceFile));
} else {
    $results['service_file_exists'] = false;
}

// 3. Verificar git log
$gitLog = shell_exec('cd ' . dirname(__DIR__) . ' && git log --oneline -3 2>&1');
$results['git_log'] = $gitLog;

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
