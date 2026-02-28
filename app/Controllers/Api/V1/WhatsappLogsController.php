<?php
namespace App\Controllers\Api\V1;

use App\Core\Database;
use PDO;

/**
 * WhatsappLogsController
 *
 * Endpoint: POST /api/v1/whatsapp/logs
 *
 * Retorna os últimos logs do bot para auditoria/diagnóstico.
 * Protegido pelo WhatsappApiAuthMiddleware (X-API-Key).
 *
 * Payload opcional:
 * {
 *   "limit": 50
 * }
 */
class WhatsappLogsController extends WhatsappBaseController
{
    public function index(): void
    {
        $body  = $this->getRequestBody();
        $limit = (int) ($body['limit'] ?? 50);
        if ($limit <= 0) {
            $limit = 50;
        }
        if ($limit > 200) {
            $limit = 200;
        }

        try {
            $pdo  = Database::getInstance();
            $stmt = $pdo->prepare(
                "SELECT id, integracao_id, telefone_hash, endpoint, intent, status, summary, created_at
                 FROM whatsapp_bot_logs
                 WHERE tenant_id = :tenant_id
                 ORDER BY created_at DESC
                 LIMIT :limit"
            );
            $stmt->bindValue(':tenant_id', $this->tenantId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $this->success('Logs retornados com sucesso.', [
                'tenant_id' => $this->tenantId,
                'limit'     => $limit,
                'items'     => $rows,
            ]);
        } catch (\Exception $e) {
            $this->error('Falha ao consultar logs.', 500, [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

