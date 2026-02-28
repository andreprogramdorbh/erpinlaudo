<?php
namespace App\Middlewares;

use App\Core\Database;
use App\Services\CryptoService;

/**
 * Middleware de Autenticação para a API do Bot WhatsApp.
 *
 * Valida o token secreto enviado no cabeçalho X-API-Key.
 * O token é armazenado criptografado na tabela `integracoes`
 * com o nome 'whatsapp_bot'.
 *
 * Retorna 401 JSON se o token for inválido ou ausente.
 * Injeta o `tenant_id` (usuario_id do ERP) no $_REQUEST para uso nos controllers.
 */
class WhatsappApiAuthMiddleware
{
    public function handle(): void
    {
        // Lê o token do cabeçalho HTTP
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

        if (empty($apiKey)) {
            $this->unauthorized('API Key ausente. Envie o token no cabeçalho X-API-Key.');
        }

        try {
            $pdo = Database::getInstance();

            // Busca todas as integrações do tipo whatsapp_bot ativas
            $stmt = $pdo->prepare(
                "SELECT id, usuario_id, config_json
                 FROM integracoes
                 WHERE nome = 'whatsapp_bot' AND status = 'ativo'
                 LIMIT 50"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);

            if (empty($rows)) {
                $this->unauthorized('Integração WhatsApp não configurada ou inativa.');
            }

            $crypto = new CryptoService();
            $tenantId = null;
            $integracaoId = null;

            foreach ($rows as $row) {
                $config = json_decode((string) ($row->config_json ?? '{}'), true) ?: [];
                $storedKeyEnc = $config['api_key_enc'] ?? '';

                if (empty($storedKeyEnc)) {
                    continue;
                }

                try {
                    $storedKey = $crypto->decryptString($storedKeyEnc);
                    if (hash_equals($storedKey, $apiKey)) {
                        $tenantId     = (int) $row->usuario_id;
                        $integracaoId = (int) $row->id;
                        break;
                    }
                } catch (\Exception $e) {
                    // Token corrompido, ignora e tenta o próximo
                    continue;
                }
            }

            if ($tenantId === null) {
                $this->unauthorized('API Key inválida.');
            }

            // Injeta o tenant_id e integracao_id para uso nos controllers
            $_REQUEST['_bot_tenant_id']     = $tenantId;
            $_REQUEST['_bot_integracao_id'] = $integracaoId;

        } catch (\PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => 'error',
                'message' => 'Erro interno do servidor.',
                'data'    => null,
            ]);
            exit();
        }
    }

    private function unauthorized(string $message): void
    {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'error',
            'message' => $message,
            'data'    => null,
        ]);
        exit();
    }
}
