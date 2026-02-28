<?php
namespace App\Services;

use App\Core\Database;

/**
 * WhatsappLogger — Serviço de log dedicado para o Bot WhatsApp.
 *
 * Registra todas as interações do bot em dois lugares:
 *  1. Arquivo de texto: storage/logs/whatsapp_bot.log
 *  2. Tabela do banco: whatsapp_bot_logs (para visualização no painel)
 *
 * Campos registrados:
 *  - telefone (hash SHA-256 para privacidade no arquivo)
 *  - endpoint consultado
 *  - intent (intenção do usuário)
 *  - status da resposta (success/error)
 *  - tenant_id (usuário do ERP)
 *  - horário (UTC)
 */
class WhatsappLogger
{
    private string $logFile;

    public function __construct()
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $this->logFile = $logDir . '/whatsapp_bot.log';
    }

    /**
     * Registra uma consulta do bot.
     *
     * @param string $telefone    Número do telefone do cliente
     * @param string $endpoint    Endpoint chamado (ex: /api/v1/whatsapp/faturas)
     * @param string $intent      Intenção identificada (ex: get_faturas)
     * @param string $status      'success' ou 'error'
     * @param string $summary     Resumo da resposta (ex: "2 faturas encontradas")
     * @param int    $tenantId    ID do usuário do ERP (tenant)
     * @param int    $integracaoId ID da integração
     */
    public function log(
        string $telefone,
        string $endpoint,
        string $intent,
        string $status,
        string $summary,
        int    $tenantId,
        int    $integracaoId = 0
    ): void {
        $now         = date('Y-m-d H:i:s');
        $phoneHash   = substr(hash('sha256', $telefone), 0, 12); // Anonimiza no arquivo
        $phoneMasked = $this->maskPhone($telefone);

        // 1. Log em arquivo de texto
        $line = sprintf(
            "[%s] [%s] tenant=%d | phone=%s | endpoint=%s | intent=%s | status=%s | %s\n",
            $now,
            strtoupper($status),
            $tenantId,
            $phoneMasked,
            $endpoint,
            $intent,
            $status,
            $summary
        );
        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);

        // 2. Log no banco de dados
        try {
            $pdo  = Database::getInstance();
            $stmt = $pdo->prepare(
                "INSERT INTO whatsapp_bot_logs
                    (tenant_id, integracao_id, telefone_hash, endpoint, intent, status, summary, created_at)
                 VALUES
                    (:tenant_id, :integracao_id, :telefone_hash, :endpoint, :intent, :status, :summary, NOW())"
            );
            $stmt->execute([
                ':tenant_id'     => $tenantId,
                ':integracao_id' => $integracaoId,
                ':telefone_hash' => $phoneHash,
                ':endpoint'      => $endpoint,
                ':intent'        => $intent,
                ':status'        => $status,
                ':summary'       => $summary,
            ]);
        } catch (\Exception $e) {
            // Falha no log do banco não deve interromper o fluxo principal
            $errLine = sprintf(
                "[%s] [LOG_DB_ERROR] Falha ao salvar log no banco: %s | tenant=%d | integracao=%d | endpoint=%s | intent=%s | status=%s\n",
                $now,
                $e->getMessage(),
                $tenantId,
                $integracaoId,
                $endpoint,
                $intent,
                $status
            );
            file_put_contents($this->logFile, $errLine, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Mascara o número de telefone para exibição (ex: +5511*****8888)
     */
    private function maskPhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        $len   = strlen($clean);
        if ($len < 8) {
            return '***';
        }
        return substr($clean, 0, 4) . str_repeat('*', $len - 8) . substr($clean, -4);
    }
}
