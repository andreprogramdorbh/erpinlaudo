<?php
/**
 * Diagnóstico completo do fluxo de renderização
 * Acesso: https://erp.inlaudo.com.br/diag_render.php?token=inlaudo2026clear
 */
$token = $_GET['token'] ?? '';
if ($token !== 'inlaudo2026clear') { http_response_code(403); die('Acesso negado'); }

$baseDir = dirname(__DIR__);
$results = [];

// 1. Verificar arquivos de layout
$layoutFiles = [
    'app/Views/layout/erp_header.php',
    'app/Views/layout/erp_footer.php',
    'app/Core/View.php',
    'app/Views/components/form/enterprise-form.php',
    'app/Views/crm/leads/form.php',
];
foreach ($layoutFiles as $file) {
    $path = "{$baseDir}/{$file}";
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $results['files'][$file] = [
            'exists' => true,
            'size' => strlen($content),
            'mtime' => date('Y-m-d H:i:s', filemtime($path)),
            'has_doctype' => strpos($content, '<!DOCTYPE') !== false,
            'has_erp_view_rendering' => strpos($content, 'ERP_VIEW_RENDERING') !== false,
            'has_erp_header_included' => strpos($content, 'ERP_HEADER_INCLUDED') !== false,
            'has_ob_start' => strpos($content, 'ob_start') !== false,
            'has_form_layout_css' => strpos($content, 'form-layout.css') !== false,
            'first_100' => substr($content, 0, 100),
        ];
    } else {
        $results['files'][$file] = ['exists' => false, 'path' => $path];
    }
}

// 2. Verificar se ERP_VIEW_RENDERING está definido (não deveria estar fora do View::render)
$results['constants'] = [
    'ERP_VIEW_RENDERING' => defined('ERP_VIEW_RENDERING') ? constant('ERP_VIEW_RENDERING') : 'NOT DEFINED',
    'ERP_HEADER_INCLUDED' => defined('ERP_HEADER_INCLUDED') ? constant('ERP_HEADER_INCLUDED') : 'NOT DEFINED',
];

// 3. Verificar o View.php - extrair o método render
$viewPath = "{$baseDir}/app/Core/View.php";
if (file_exists($viewPath)) {
    $content = file_get_contents($viewPath);
    // Extrair a lógica do render
    preg_match('/public static function render.*?(?=public static function|\}$)/s', $content, $matches);
    $results['view_render_method'] = $matches[0] ?? 'NOT FOUND';
}

// 4. Verificar se há output buffer ativo
$results['ob_level'] = ob_get_level();
$results['ob_status'] = ob_get_status(true);

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
