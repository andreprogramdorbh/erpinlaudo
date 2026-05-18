<?php

/**
 * ERP InLaudo — Script CLI de Processamento de Alertas
 *
 * Executado via crontab do servidor (alternativa ao endpoint HTTP).
 *
 * Configuração no cPanel / crontab:
 *   # Todos os alertas — 1x por dia às 07:45
 *   45 7 * * * /usr/bin/php /home2/inlaud99/erp.inlaudo.com.br/cron/processar_alertas.php >> /home2/inlaud99/logs/alertas.log 2>&1
 *
 *   # Somente CRM — 1x por dia às 07:30 (antes do expediente)
 *   30 7 * * * /usr/bin/php /home2/inlaud99/erp.inlaudo.com.br/cron/processar_alertas.php crm >> /home2/inlaud99/logs/alertas_crm.log 2>&1
 */

// Detecta se está rodando via CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acesso negado. Este script só pode ser executado via CLI.');
}

// Carrega o bootstrap da aplicação
define('RUNNING_FROM_CRON', true);
$rootDir = dirname(__DIR__);

require_once $rootDir . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Database;
use App\Services\EmailAlertaService;
use App\Core\Audit\AuditLogger;

// Carrega variáveis de ambiente
$dotenv = Dotenv::createImmutable($rootDir);
$dotenv->load();

// Módulo a processar (argumento CLI ou 'todos')
$modulo = $argv[1] ?? 'todos';
$inicio = microtime(true);

echo "[" . date('Y-m-d H:i:s') . "] Iniciando processamento de alertas — módulo: {$modulo}\n";

try {
    $pdo = Database::getInstance();

    // Busca usuários com alertas ativos
    if ($modulo === 'todos') {
        $stmt = $pdo->query(
            "SELECT DISTINCT usuario_id FROM email_alertas WHERE ativo = 1 AND usuario_id > 0"
        );
    } else {
        $stmt = $pdo->prepare(
            "SELECT DISTINCT usuario_id FROM email_alertas WHERE ativo = 1 AND modulo = :m AND usuario_id > 0"
        );
        $stmt->execute([':m' => $modulo]);
    }

    $usuarios = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    echo "[" . date('Y-m-d H:i:s') . "] Usuários com alertas ativos: " . count($usuarios) . "\n";

    $totalEnviados  = 0;
    $totalFalhas    = 0;
    $totalIgnorados = 0;

    foreach ($usuarios as $usuarioId) {
        echo "[" . date('Y-m-d H:i:s') . "] Processando usuário #{$usuarioId}...\n";

        try {
            $service = new EmailAlertaService((int) $usuarioId);

            if ($modulo === 'todos') {
                $resultado = $service->processarTodos();
            } else {
                $resultado = $service->processarModulo($modulo);
            }

            $totalEnviados  += $resultado['enviados'];
            $totalFalhas    += $resultado['falhas'];
            $totalIgnorados += $resultado['ignorados'];

            echo "  → Enviados: {$resultado['enviados']} | Falhas: {$resultado['falhas']} | Ignorados: {$resultado['ignorados']}\n";

        } catch (\Throwable $e) {
            echo "  → ERRO no usuário #{$usuarioId}: " . $e->getMessage() . "\n";
            $totalFalhas++;
        }
    }

    $duracao = round(microtime(true) - $inicio, 3);

    echo "[" . date('Y-m-d H:i:s') . "] Concluído em {$duracao}s — "
        . "Enviados: {$totalEnviados} | Falhas: {$totalFalhas} | Ignorados: {$totalIgnorados}\n";

    AuditLogger::log('cron_alertas_cli', [
        'modulo'    => $modulo,
        'usuarios'  => count($usuarios),
        'enviados'  => $totalEnviados,
        'falhas'    => $totalFalhas,
        'ignorados' => $totalIgnorados,
        'duracao'   => $duracao . 's',
    ]);

} catch (\Throwable $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERRO FATAL: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
