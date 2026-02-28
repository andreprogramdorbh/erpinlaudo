<?php
namespace App\Controllers\Api\V1;

use App\Core\Database;
use PDO;

/**
 * WhatsappAuthController
 *
 * Endpoint: POST /api/v1/whatsapp/identificar
 *
 * Identifica o cliente pelo número de telefone do WhatsApp.
 * Retorna os dados básicos do cliente se encontrado.
 *
 * Payload esperado:
 * {
 *   "telefone": "+5511999998888"
 * }
 *
 * Resposta de sucesso:
 * {
 *   "status": "success",
 *   "message": "Cliente identificado.",
 *   "data": {
 *     "cliente_id": 42,
 *     "nome": "Empresa Exemplo LTDA",
 *     "cpf_cnpj": "12.345.678/0001-99",
 *     "email": "contato@empresa.com"
 *   }
 * }
 */
class WhatsappAuthController extends WhatsappBaseController
{
    public function identificar(): void
    {
        $telefone = $this->getRequestPhone();
        $endpoint = '/api/v1/whatsapp/identificar';

        try {
            $cliente = $this->findClienteByPhone($telefone);
        } catch (\Throwable $e) {
            $summary = 'Exceção: ' . $this->safeLogMessage($e->getMessage());
            $this->logger->log(
                $telefone, $endpoint, 'identificar',
                'error', $summary,
                $this->tenantId, $this->integracaoId
            );
            $this->error('Erro interno ao identificar o cliente.', 500);
        }

        if (!$cliente) {
            $this->logger->log(
                $telefone, $endpoint, 'identificar',
                'error', 'Cliente não encontrado',
                $this->tenantId, $this->integracaoId
            );
            $this->error('Cliente não encontrado para o telefone informado. Verifique o número ou entre em contato com o suporte.', 404);
        }

        $this->logger->log(
            $telefone, $endpoint, 'identificar',
            'success', "Cliente ID={$cliente->cliente_id} identificado",
            $this->tenantId, $this->integracaoId
        );

        $this->success('Cliente identificado com sucesso.', [
            'cliente_id' => (int) $cliente->cliente_id,
            'nome'       => $cliente->razao_social ?? $cliente->nome_fantasia ?? 'N/A',
            'cpf_cnpj'   => $cliente->cpf_cnpj ?? '',
            'email'      => $cliente->email ?? '',
        ]);
    }

    /**
     * Busca o cliente pelo telefone ou celular no tenant correto.
     * Normaliza o número para comparação (remove formatação).
     */
    private function findClienteByPhone(string $telefoneNormalizado): object|false
    {
        $pdo = Database::getInstance();

        // Busca pelo número normalizado (apenas dígitos) no telefone ou celular
        // Compara os últimos 11 dígitos para cobrir variações de DDI
        $phoneShort = substr($telefoneNormalizado, -11); // Ex: 11999998888

        $telefoneExpr = $this->sqlDigitsExpr('c.telefone');
        $celularExpr  = $this->sqlDigitsExpr('c.celular');

        $stmt = $pdo->prepare(
            "SELECT pc.id, pc.cliente_id, pc.email,
                    c.razao_social, c.nome_fantasia, c.cpf_cnpj,
                    c.telefone, c.celular
             FROM portal_clientes pc
             INNER JOIN clientes c ON c.id = pc.cliente_id
             WHERE c.usuario_id = :tenant_id
               AND pc.ativo = 1
               AND (
                   {$telefoneExpr} LIKE :phone_like_1
                   OR {$celularExpr} LIKE :phone_like_2
               )
             LIMIT 1"
        );
        $stmt->execute([
            ':tenant_id'   => $this->tenantId,
            ':phone_like_1' => '%' . $phoneShort,
            ':phone_like_2' => '%' . $phoneShort,
        ]);

        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }
}
