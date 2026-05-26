<?php
// Script de emergência - deploy + opcache
// Acesso: https://erp.inlaudo.com.br/opcache_clear.php
$token = $_GET['token'] ?? '';
if ($token !== 'inlaudo2026clear') {
    http_response_code(403);
    die('Acesso negado');
}
$results = [];
$baseDir = dirname(__DIR__);

// 1. Limpar OPcache
if (function_exists('opcache_reset')) {
    $results['opcache_reset'] = opcache_reset() ? 'OK' : 'FALHOU';
} else {
    $results['opcache_reset'] = 'OPcache não disponível';
}

// 2. Deploy via GitHub (se solicitado)
if (isset($_GET['deploy']) && $_GET['deploy'] === 'leads') {
    $githubRaw = 'https://raw.githubusercontent.com/ASOARESBH/erpinlaudo/main';
    $deployFiles = [
        'app/Views/crm/leads/form.php',
        'app/Views/crm/leads/tabs/dados.php',
        'app/Views/crm/leads/tabs/interacoes.php',
        'app/Views/crm/leads/tabs/anexos.php',
        'app/Views/crm/leads/tabs/transferencia.php',
        'app/Views/components/form/enterprise-form.php',
        'app/Controllers/CrmLeadsController.php',
    ];
    foreach ($deployFiles as $file) {
        $url = "{$githubRaw}/{$file}";
        $localPath = "{$baseDir}/{$file}";
        $dir = dirname($localPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $content = @file_get_contents($url);
        if ($content === false) {
            $results['deploy'][$file] = 'ERRO: download falhou';
        } else {
            $bytes = file_put_contents($localPath, $content);
            $results['deploy'][$file] = $bytes !== false ? "OK: {$bytes} bytes" : 'ERRO: salvar falhou';
        }
    }
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

// 3. Verificar arquivos do módulo de Leads
$leadsFiles = [
    'app/Views/crm/leads/form.php',
    'app/Views/crm/leads/tabs/dados.php',
    'app/Views/crm/leads/tabs/interacoes.php',
    'app/Views/crm/leads/tabs/anexos.php',
    'app/Views/crm/leads/tabs/transferencia.php',
];
foreach ($leadsFiles as $file) {
    $path = "{$baseDir}/{$file}";
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $results['files'][$file] = [
            'exists' => true,
            'size' => strlen($content),
            'mtime' => date('Y-m-d H:i:s', filemtime($path)),
            'has_enterprise_form' => strpos($content, 'enterprise-form') !== false,
            'has_old_crm_tabs' => strpos($content, 'crm-tabs') !== false,
        ];
    } else {
        $results['files'][$file] = ['exists' => false];
    }
}

// 4. Verificar git log
$gitLog = shell_exec('cd ' . $baseDir . ' && git log --oneline -3 2>&1');
$results['git_log'] = $gitLog;

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
