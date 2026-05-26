<?php
/**
 * Script de Deploy - Fix do módulo de Leads
 * Faz download dos arquivos necessários diretamente do GitHub
 * Uso: https://erp.inlaudo.com.br/deploy_leads_fix.php?token=inlaudo2026deploy
 */
$token = $_GET['token'] ?? '';
if ($token !== 'inlaudo2026deploy') {
    http_response_code(403);
    die('Acesso negado');
}

$results = [];
$baseDir = dirname(__DIR__);
$githubRepo = 'ASOARESBH/erpinlaudo';
$branch = 'main';
$githubRaw = "https://raw.githubusercontent.com/{$githubRepo}/{$branch}";

// Arquivos a fazer download
$files = [
    'app/Views/crm/leads/form.php',
    'app/Views/crm/leads/tabs/dados.php',
    'app/Views/crm/leads/tabs/interacoes.php',
    'app/Views/crm/leads/tabs/anexos.php',
    'app/Views/crm/leads/tabs/transferencia.php',
    'app/Views/components/form/enterprise-form.php',
    'app/Controllers/CrmLeadsController.php',
];

foreach ($files as $file) {
    $url = "{$githubRaw}/{$file}";
    $localPath = "{$baseDir}/{$file}";
    
    // Criar diretório se não existir
    $dir = dirname($localPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        $results[$file]['mkdir'] = "Criado: {$dir}";
    }
    
    // Fazer download
    $content = @file_get_contents($url);
    if ($content === false) {
        $results[$file]['status'] = 'ERRO: Falha ao baixar de ' . $url;
        continue;
    }
    
    // Salvar arquivo
    $bytes = file_put_contents($localPath, $content);
    if ($bytes === false) {
        $results[$file]['status'] = 'ERRO: Falha ao salvar em ' . $localPath;
    } else {
        $results[$file]['status'] = "OK: {$bytes} bytes";
        $results[$file]['size'] = $bytes;
    }
}

// Limpar OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    $results['opcache'] = 'Limpo';
}

// Verificar o form.php atual
$formPath = "{$baseDir}/app/Views/crm/leads/form.php";
if (file_exists($formPath)) {
    $content = file_get_contents($formPath);
    $results['form_php_check'] = [
        'size' => strlen($content),
        'has_enterprise_form' => strpos($content, 'enterprise-form') !== false,
        'has_old_crm_tabs' => strpos($content, 'crm-tabs') !== false,
        'first_100_chars' => substr($content, 0, 100),
    ];
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
