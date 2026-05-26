<?php
$token = $_GET['token'] ?? '';
if ($token !== 'inlaudo2026clear') { die('Acesso negado'); }
$baseDir = dirname(__DIR__);
$files = [
    'app/Views/components/form/enterprise-form.php',
    'app/Core/View.php',
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
            'has_erp_view_rendering' => strpos($content, 'ERP_VIEW_RENDERING') !== false,
            'has_erp_header_included' => strpos($content, 'ERP_HEADER_INCLUDED') !== false,
            'has_ob_start' => strpos($content, 'ob_start') !== false,
            'first_300' => substr($content, 0, 300),
        ];
    } else {
        $results[$file] = ['exists' => false];
    }
}
// Verificar também o erp_header.php
$headerPath = "{$baseDir}/app/Views/layout/erp_header.php";
if (file_exists($headerPath)) {
    $content = file_get_contents($headerPath);
    $results['app/Views/layout/erp_header.php'] = [
        'exists' => true,
        'size' => strlen($content),
        'mtime' => date('Y-m-d H:i:s', filemtime($headerPath)),
        'has_doctype' => strpos($content, '<!DOCTYPE') !== false,
        'first_100' => substr($content, 0, 100),
    ];
}
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
