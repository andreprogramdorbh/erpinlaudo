<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Audit\AuditLogger;
use App\Models\EmailAlerta;
use App\Models\User;
use App\Services\EmailAlertaService;
use PDO;

/**
 * CronController
 *
 * Endpoint HTTP seguro para execução de tarefas agendadas (cron jobs).
 * Protegido por CRON_KEY definida no .env.
 *
 * Configuração no servidor (cPanel / crontab):
 *   * * * * * curl -s "https://erp.inlaudo.com.br/api/cron/alertas?key=SUA_CRON_KEY" > /dev/null 2>&1
 *
 * Ou via crontab do servidor:
 *   0 8 * * * /usr/bin/php /home2/inlaud99/erp.inlaudo.com.br/cron/processar_alertas.php >> /dev/null 2>&1
 */
class CronController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // -------------------------------------------------------------------------
    // Autenticação do cron
    // -------------------------------------------------------------------------

    private function autenticar(): bool
    {
        $cronKey = $_ENV['CRON_KEY'] ?? '';

        // Se não há CRON_KEY configurada, bloqueia por segurança
        if (empty($cronKey)) {
            return false;
        }

        $keyRecebida = $_GET['key'] ?? $_SERVER['HTTP_X_CRON_KEY'] ?? '';

        return hash_equals($cronKey, $keyRecebida);
    }

    // -------------------------------------------------------------------------
    // GET /api/cron/alertas
    // Processa todos os alertas ativos de todos os usuários
    // -------------------------------------------------------------------------

    public function alertas(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->autenticar()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        $inicio   = microtime(true);
        $resumo   = ['usuarios' => 0, 'enviados' => 0, 'falhas' => 0, 'ignorados' => 0];
        $erros    = [];

        try {
            // Busca todos os usuários que têm alertas ativos
            $usuarios = $this->buscarUsuariosComAlertas();

            foreach ($usuarios as $usuarioId) {
                try {
                    $service  = new EmailAlertaService((int) $usuarioId);
                    $parcial  = $service->processarTodos();

                    $resumo['usuarios']++;
                    $resumo['enviados']  += $parcial['enviados'];
                    $resumo['falhas']    += $parcial['falhas'];
                    $resumo['ignorados'] += $parcial['ignorados'];
                } catch (\Throwable $e) {
                    $erros[] = ['usuario_id' => $usuarioId, 'error' => $e->getMessage()];
                    $resumo['falhas']++;
                }
            }

            $duracao = round(microtime(true) - $inicio, 3);

            AuditLogger::log('cron_alertas_executado', [
                'resumo'  => $resumo,
                'duracao' => $duracao . 's',
                'erros'   => $erros,
            ]);

            echo json_encode([
                'success' => true,
                'resumo'  => $resumo,
                'duracao' => $duracao . 's',
                'erros'   => $erros,
            ]);

        } catch (\Throwable $e) {
            AuditLogger::log('cron_alertas_erro_fatal', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/cron/alertas-crm
    // Processa SOMENTE os alertas do módulo CRM (mais leve, pode rodar mais vezes)
    // -------------------------------------------------------------------------

    public function alertasCrm(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->autenticar()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        $inicio = microtime(true);
        $resumo = ['usuarios' => 0, 'enviados' => 0, 'falhas' => 0, 'ignorados' => 0];
        $erros  = [];

        try {
            $usuarios = $this->buscarUsuariosComAlertas('crm');

            foreach ($usuarios as $usuarioId) {
                try {
                    $service = new EmailAlertaService((int) $usuarioId);
                    $parcial = $service->processarModulo('crm');

                    $resumo['usuarios']++;
                    $resumo['enviados']  += $parcial['enviados'];
                    $resumo['falhas']    += $parcial['falhas'];
                    $resumo['ignorados'] += $parcial['ignorados'];
                } catch (\Throwable $e) {
                    $erros[] = ['usuario_id' => $usuarioId, 'error' => $e->getMessage()];
                    $resumo['falhas']++;
                }
            }

            $duracao = round(microtime(true) - $inicio, 3);

            AuditLogger::log('cron_alertas_crm_executado', [
                'resumo'  => $resumo,
                'duracao' => $duracao . 's',
            ]);

            echo json_encode([
                'success' => true,
                'resumo'  => $resumo,
                'duracao' => $duracao . 's',
                'erros'   => $erros,
            ]);

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Retorna IDs de usuários que possuem alertas ativos.
     * Se $modulo for informado, filtra pelo módulo.
     */
    private function buscarUsuariosComAlertas(?string $modulo = null): array
    {
        if ($modulo) {
            $stmt = $this->pdo->prepare(
                "SELECT DISTINCT usuario_id
                 FROM email_alertas
                 WHERE ativo = 1
                   AND modulo = :modulo
                   AND usuario_id > 0"
            );
            $stmt->execute([':modulo' => $modulo]);
        } else {
            $stmt = $this->pdo->query(
                "SELECT DISTINCT usuario_id
                 FROM email_alertas
                 WHERE ativo = 1
                   AND usuario_id > 0"
            );
        }

        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
}
