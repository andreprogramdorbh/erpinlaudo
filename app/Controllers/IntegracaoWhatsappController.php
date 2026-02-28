<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\View;
use App\Services\CryptoService;
use PDO;

/**
 * IntegracaoWhatsappController
 *
 * Gerencia a configuração da integração do Bot WhatsApp no painel do ERP.
 *
 * Rotas:
 *  GET  /integracao/whatsapp              → Exibe a tela de configuração
 *  POST /integracao/whatsapp/gerar-token  → Gera ou regenera a API Key
 *  POST /integracao/whatsapp/revogar      → Desativa a integração
 *  GET  /integracao/whatsapp/logs/export  → Exporta logs em CSV
 */
class IntegracaoWhatsappController extends Controller
{
    private int $userId;
    private CryptoService $crypto;
    private PDO $pdo;

    public function __construct()
    {
        $this->userId = (int) ($_SESSION['user_id'] ?? 0);
        $this->crypto = new CryptoService();
        $this->pdo    = Database::getInstance();
    }

    /**
     * Exibe a tela de configuração da integração WhatsApp.
     */
    public function index(): void
    {
        $config = $this->getIntegracao();
        $logs   = $this->getLogs();
        $apiUrl = $this->getAppUrl();

        // Verifica se há uma nova chave na sessão (gerada no request anterior)
        $apiKey = $_SESSION['whatsapp_new_api_key'] ?? null;
        unset($_SESSION['whatsapp_new_api_key']);

        View::render('integracoes/whatsapp', [
            'title'   => 'Integração WhatsApp Bot',
            'config'  => $config,
            'logs'    => $logs,
            'apiKey'  => $apiKey,
            'apiUrl'  => $apiUrl,
            'breadcrumb' => [
                'Configurações' => '/configuracoes',
                'Integrações' => '#',
                'WhatsApp' => '/integracao/whatsapp',
            ],
            '_layout' => 'erp',
        ]);
    }

    /**
     * Gera ou regenera a API Key da integração WhatsApp.
     * Retorna JSON para consumo via fetch() no frontend.
     */
    public function gerarToken(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->validateCsrf()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            return;
        }

        try {
            // Gera um token seguro (UUID v4 + entropia extra)
            $rawToken = $this->generateSecureToken();
            $encToken = $this->crypto->encryptString($rawToken);

            $config = $this->getIntegracao();

            if ($config) {
                // Atualiza a chave existente
                $configJson = json_decode($config->config_json ?? '{}', true) ?: [];
                $configJson['api_key_enc'] = $encToken;
                $configJson['updated_at']  = date('Y-m-d H:i:s');

                $stmt = $this->pdo->prepare(
                    "UPDATE integracoes
                     SET config_json = :config, status = 'ativo', updated_at = NOW()
                     WHERE id = :id AND usuario_id = :uid"
                );
                $stmt->execute([
                    ':config' => json_encode($configJson),
                    ':id'     => $config->id,
                    ':uid'    => $this->userId,
                ]);
            } else {
                // Cria nova integração
                $configJson = [
                    'api_key_enc' => $encToken,
                    'created_at'  => date('Y-m-d H:i:s'),
                ];

                $stmt = $this->pdo->prepare(
                    "INSERT INTO integracoes (usuario_id, nome, tipo, status, config_json, created_at, updated_at)
                     VALUES (:uid, 'whatsapp_bot', 'API', 'ativo', :config, NOW(), NOW())"
                );
                $stmt->execute([
                    ':uid'    => $this->userId,
                    ':config' => json_encode($configJson),
                ]);
            }

            // Armazena o token na sessão para exibição única
            $_SESSION['whatsapp_new_api_key'] = $rawToken;

            echo json_encode(['success' => true, 'message' => 'API Key gerada com sucesso.']);

        } catch (\Exception $e) {
            // Log de erro para qualquer eventualidade
            $logFile = dirname(__DIR__, 2) . '/storage/logs/whatsapp_bot.log';
            file_put_contents(
                $logFile,
                sprintf("[%s] [GERAR_TOKEN_ERROR] %s\n", date('Y-m-d H:i:s'), $e->getMessage()),
                FILE_APPEND | LOCK_EX
            );

            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno ao gerar token.']);
        }
    }

    /**
     * Desativa a integração WhatsApp (revoga o acesso do bot).
     */
    public function revogar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->validateCsrf()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                "UPDATE integracoes
                 SET status = 'inativo', updated_at = NOW()
                 WHERE nome = 'whatsapp_bot' AND usuario_id = :uid"
            );
            $stmt->execute([':uid' => $this->userId]);

            echo json_encode(['success' => true, 'message' => 'Integração revogada com sucesso.']);

        } catch (\Exception $e) {
            $logFile = dirname(__DIR__, 2) . '/storage/logs/whatsapp_bot.log';
            file_put_contents(
                $logFile,
                sprintf("[%s] [REVOGAR_ERROR] %s\n", date('Y-m-d H:i:s'), $e->getMessage()),
                FILE_APPEND | LOCK_EX
            );

            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno ao revogar integração.']);
        }
    }

    /**
     * Exporta os logs do bot em formato CSV.
     */
    public function exportLogs(): void
    {
        $logs = $this->getLogs(500);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="whatsapp_bot_logs_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($out, ['Data/Hora', 'Telefone (Hash)', 'Endpoint', 'Intenção', 'Status', 'Resumo'], ';');

        foreach ($logs as $log) {
            fputcsv($out, [
                date('d/m/Y H:i:s', strtotime($log->created_at)),
                $log->telefone_hash,
                $log->endpoint,
                $log->intent,
                $log->status,
                $log->summary,
            ], ';');
        }

        fclose($out);
        exit();
    }

    // ─── Helpers privados ────────────────────────────────────────────────────

    private function getIntegracao(): object|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM integracoes
             WHERE nome = 'whatsapp_bot' AND usuario_id = :uid AND status = 'ativo'
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':uid' => $this->userId]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }

    private function getLogs(int $limit = 50): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM whatsapp_bot_logs
                 WHERE tenant_id = :uid
                 ORDER BY created_at DESC
                 LIMIT :limit"
            );
            $stmt->bindValue(':uid',   $this->userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit,        PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\Exception $e) {
            $logFile = dirname(__DIR__, 2) . '/storage/logs/whatsapp_bot.log';
            file_put_contents(
                $logFile,
                sprintf(
                    "[%s] [PANEL_LOGS_ERROR] tenant=%d limit=%d | %s\n",
                    date('Y-m-d H:i:s'),
                    $this->userId,
                    $limit,
                    $e->getMessage()
                ),
                FILE_APPEND | LOCK_EX
            );
            return [];
        }
    }

    private function getAppUrl(): string
    {
        $url = $_ENV['APP_URL'] ?? $_SERVER['APP_URL'] ?? '';
        if (empty($url)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $url    = $scheme . '://' . $host;
        }
        return rtrim($url, '/');
    }

    private function generateSecureToken(): string
    {
        // Formato: wab_<uuid_v4_sem_hifens>_<8_bytes_hex>
        $uuid = sprintf(
            '%04x%04x%04x%04x%04x%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $extra = bin2hex(random_bytes(8));
        return 'wab_' . $uuid . '_' . $extra;
    }

    private function validateCsrf(): bool
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $token = $body['csrf_token']
            ?? $_POST['csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
