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
$deployParam = $_GET['deploy'] ?? '';
if ($deployParam) {
    $githubRaw = 'https://raw.githubusercontent.com/ASOARESBH/erpinlaudo/main';

    // Arquivos do módulo de Leads
    $deployFiles = [
        'app/Views/crm/leads/form.php',
        'app/Views/crm/leads/tabs/dados.php',
        'app/Views/crm/leads/tabs/interacoes.php',
        'app/Views/crm/leads/tabs/anexos.php',
        'app/Views/crm/leads/tabs/transferencia.php',
        'app/Views/components/form/enterprise-form.php',
        'app/Controllers/CrmLeadsController.php',
    ];

    // Se deploy=all, inclui também os arquivos de layout e core
    if ($deployParam === 'all' || $deployParam === 'layout') {
        $deployFiles = array_merge($deployFiles, [
            'app/Views/layout/erp_header.php',
            'app/Views/layout/erp_footer.php',
            'app/Core/View.php',
            'public/assets/css/form-layout.css',
            'public/assets/js/form-tabs.js',
            'public/assets/js/sidebar.js',
        ]);
    }

    foreach ($deployFiles as $file) {
        $url = "{$githubRaw}/{$file}";
        $localPath = "{$baseDir}/{$file}";
        $dir = dirname($localPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $content = @file_get_contents($url);
        if ($content === false) {
            $results['deploy'][$file] = 'ERRO: download falhou de ' . $url;
        } else {
            $bytes = file_put_contents($localPath, $content);
            $results['deploy'][$file] = $bytes !== false ? "OK: {$bytes} bytes" : 'ERRO: salvar falhou';
        }
    }
    if (function_exists('opcache_reset')) {
        opcache_reset();
        $results['opcache_after_deploy'] = 'Limpo';
    }
}

// 3. Verificar arquivos críticos
$criticalFiles = [
    'app/Views/crm/leads/form.php',
    'app/Views/crm/leads/tabs/dados.php',
    'app/Views/crm/leads/tabs/interacoes.php',
    'app/Views/crm/leads/tabs/anexos.php',
    'app/Views/crm/leads/tabs/transferencia.php',
    'app/Views/components/form/enterprise-form.php',
    'app/Views/layout/erp_header.php',
    'app/Views/layout/erp_footer.php',
    'app/Core/View.php',
];
foreach ($criticalFiles as $file) {
    $path = "{$baseDir}/{$file}";
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $results['files'][$file] = [
            'exists' => true,
            'size' => strlen($content),
            'mtime' => date('Y-m-d H:i:s', filemtime($path)),
            'has_enterprise_form' => strpos($content, 'enterprise-form') !== false,
            'has_old_crm_tabs' => strpos($content, 'crm-tabs') !== false,
            'has_doctype' => strpos($content, '<!DOCTYPE') !== false,
            'has_erp_view_rendering' => strpos($content, 'ERP_VIEW_RENDERING') !== false,
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
