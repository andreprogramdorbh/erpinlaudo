<?php
namespace App\Controllers\Api\V1;

use App\Core\Database;
use PDO;

/**
 * WhatsappResumoController
 *
 * Endpoint: POST /api/v1/whatsapp/resumo
 *
 * Retorna um resumo financeiro do cliente:
 *  - Total em aberto
 *  - Quantidade de faturas em aberto
 *  - Quantidade de faturas vencidas
 *  - Próxima fatura a vencer
 *
 * Payload esperado:
 * {
 *   "telefone": "+5511999998888"
 * }
 */
class WhatsappResumoController extends WhatsappBaseController
{
    public function index(): void
    {
        $telefone = $this->getRequestPhone();
        $endpoint = '/api/v1/whatsapp/resumo';

        try {
            $cliente = $this->findClienteByPhone($telefone);
        } catch (\Throwable $e) {
            $summary = 'Exceção: ' . $this->safeLogMessage($e->getMessage());
            $this->logger->log($telefone, $endpoint, 'get_resumo', 'error', $summary, $this->tenantId, $this->integracaoId);
            $this->error('Erro interno ao obter resumo financeiro.', 500);
        }
        if (!$cliente) {
            $this->logger->log($telefone, $endpoint, 'get_resumo', 'error', 'Cliente não encontrado', $this->tenantId, $this->integracaoId);
            $this->error('Cliente não encontrado para o telefone informado.', 404);
        }

        $clienteId = (int) $cliente->cliente_id;
        $pdo       = Database::getInstance();
        $hoje      = date('Y-m-d');

        // Resumo de faturas
        $stmt = $pdo->prepare(
            "SELECT
                COUNT(*) AS total_abertas,
                SUM(valor) AS total_valor,
                SUM(CASE WHEN data_vencimento < :hoje THEN 1 ELSE 0 END) AS total_vencidas,
                SUM(CASE WHEN data_vencimento < :hoje THEN valor ELSE 0 END) AS valor_vencido
             FROM contas_receber
             WHERE cliente_id = :cliente_id
               AND usuario_id = :tenant_id
               AND status = 'aberta'"
        );
        $stmt->execute([
            ':cliente_id' => $clienteId,
            ':tenant_id'  => $this->tenantId,
            ':hoje'       => $hoje,
        ]);
        $resumo = $stmt->fetch(PDO::FETCH_OBJ);

        // Próxima fatura a vencer
        $stmt2 = $pdo->prepare(
            "SELECT descricao, valor, data_vencimento
             FROM contas_receber
             WHERE cliente_id = :cliente_id
               AND usuario_id = :tenant_id
               AND status = 'aberta'
               AND data_vencimento >= :hoje
             ORDER BY data_vencimento ASC
             LIMIT 1"
        );
        $stmt2->execute([
            ':cliente_id' => $clienteId,
            ':tenant_id'  => $this->tenantId,
            ':hoje'       => $hoje,
        ]);
        $proxima = $stmt2->fetch(PDO::FETCH_OBJ);

        $summary = "Resumo: {$resumo->total_abertas} fatura(s) em aberto, {$resumo->total_vencidas} vencida(s)";
        $this->logger->log($telefone, $endpoint, 'get_resumo', 'success', $summary, $this->tenantId, $this->integracaoId);

        $this->success('Resumo financeiro obtido com sucesso.', [
            'cliente' => [
                'nome'     => $cliente->razao_social ?? $cliente->nome_fantasia ?? 'N/A',
                'cpf_cnpj' => $cliente->cpf_cnpj ?? '',
            ],
            'resumo' => [
                'total_faturas_abertas' => (int) ($resumo->total_abertas ?? 0),
                'total_faturas_vencidas' => (int) ($resumo->total_vencidas ?? 0),
                'valor_total_aberto'    => $this->formatMoney((float) ($resumo->total_valor ?? 0)),
                'valor_vencido'         => $this->formatMoney((float) ($resumo->valor_vencido ?? 0)),
            ],
            'proxima_fatura' => $proxima ? [
                'descricao'  => $proxima->descricao ?? 'Cobrança',
                'valor'      => $this->formatMoney((float) $proxima->valor),
                'vencimento' => $this->formatDate($proxima->data_vencimento),
            ] : null,
        ]);
    }

}
