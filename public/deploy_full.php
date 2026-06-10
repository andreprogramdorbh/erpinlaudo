<?php
/**
 * Deploy completo - baixa todos os arquivos críticos do GitHub
 * Acesso: https://erp.inlaudo.com.br/deploy_full.php?token=inlaudo2026clear
 */
$token = $_GET['token'] ?? '';
if ($token !== 'inlaudo2026clear') { http_response_code(403); die('Acesso negado'); }

$baseDir = dirname(__DIR__);
$githubRaw = 'https://raw.githubusercontent.com/ASOARESBH/erpinlaudo/main';
$results = [];

$allFiles = [
    // Core
    'app/Core/View.php',
    'app/Core/Permission.php',

    // Layout
    'app/Views/layout/erp_header.php',
    'app/Views/layout/erp_footer.php',

    // Assets CSS/JS — críticos para estilização (sem estes o layout quebra)
    'public/assets/css/form-layout.css',
    'public/assets/js/form-tabs.js',
    'public/assets/js/sidebar.js',

    // Componente enterprise-form
    'app/Views/components/form/enterprise-form.php',

    // CRM — Leads
    'app/Views/crm/leads/form.php',
    'app/Views/crm/leads/tabs/dados.php',
    'app/Views/crm/leads/tabs/interacoes.php',
    'app/Views/crm/leads/tabs/anexos.php',
    'app/Views/crm/leads/tabs/transferencia.php',
    'app/Controllers/CrmLeadsController.php',

    // CRM — Propostas
    'app/Controllers/CrmPropostasController.php',

    // Clientes
    'app/Controllers/ClientesController.php',
    'app/Views/clientes/form-enterprise.php',
    'app/Views/clientes/tabs/equipamentos.php',
    'app/Views/clientes/tabs/historico.php',

    // Manutenção
    'app/Controllers/ManutencaoController.php',
    'app/Views/manutencao/ordens/index.php',
    'app/Views/manutencao/ordens/form.php',
    'app/Views/manutencao/ordens/show.php',
    'app/Views/manutencao/ordens/print.php',

    // Serviços e utilitários
    'app/Services/MailService.php',
    'public/opcache_clear.php',
];

foreach ($allFiles as $file) {
    $url = "{$githubRaw}/{$file}";
    $localPath = "{$baseDir}/{$file}";
    $dir = dirname($localPath);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $content = @file_get_contents($url);
    if ($content === false) {
        $results[$file] = 'ERRO: download falhou';
    } else {
        $bytes = file_put_contents($localPath, $content);
        $results[$file] = $bytes !== false ? "OK: {$bytes} bytes" : 'ERRO: salvar falhou';
    }
}

if (function_exists('opcache_reset')) opcache_reset();

header('Content-Type: application/json');
echo json_encode(['status' => 'done', 'results' => $results], JSON_PRETTY_PRINT);
