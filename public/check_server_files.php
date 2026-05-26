<?php
$token = $_GET['token'] ?? '';
if ($token !== 'inlaudo2026check') {
    die('Acesso negado');
}
$baseDir = dirname(__DIR__);
$files = [
    'app/Views/crm/leads/form.php',
    'app/Views/crm/leads/tabs/dados.php',
    'app/Views/crm/leads/tabs/interacoes.php',
    'app/Views/crm/leads/tabs/anexos.php',
    'app/Views/crm/leads/tabs/transferencia.php',
    'app/Views/components/form/enterprise-form.php',
    'app/Controllers/CrmLeadsController.php',
];
$results = [];
foreach ($files as $file) {
    $path = "{$baseDir}/{$file}";
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $results[$file] = [
            'exists' => true,
            'size' => strlen($content),
            'mtime' => date('Y-m-d H:i:s', filemtime($path)),
            'has_enterprise_form' => strpos($content, 'enterprise-form') !== false,
            'has_crm_tabs' => strpos($content, 'crm-tabs') !== false,
            'first_80' => substr($content, 0, 80),
        ];
    } else {
        $results[$file] = ['exists' => false];
    }
}
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
