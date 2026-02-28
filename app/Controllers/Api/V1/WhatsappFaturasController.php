<?php
namespace App\Controllers\Api\V1;

use App\Core\Database;
use App\Models\Integracao;
use App\Services\AsaasService;
use PDO;

/**
 * WhatsappFaturasController
 *
 * Endpoint: POST /api/v1/whatsapp/faturas
 *
 * Retorna as faturas (contas a receber) do cliente identificado pelo telefone.
 * Suporta filtros por status: 'aberta', 'vencida', 'recebida', 'todas'.
 *
 * Payload esperado:
 * {
 *   "telefone": "+5511999998888",
 *   "filtro": "aberta"  // opcional, padrão: "aberta"
 * }
 */
class WhatsappFaturasController extends WhatsappBaseController
{
    public function index(): void
    {
        $body     = $this->getRequestBody();
        $telefone = $this->getRequestPhone();
        $filtro   = $body['filtro'] ?? 'aberta'; // aberta, vencida, recebida, todas
        $endpoint = '/api/v1/whatsapp/faturas';

        // Identifica o cliente pelo telefone
        try {
            $cliente = $this->findClienteByPhone($telefone);
        } catch (\Throwable $e) {
            $summary = 'Exceção: ' . $this->safeLogMessage($e->getMessage());
            $this->logger->log($telefone, $endpoint, 'get_faturas', 'error', $summary, $this->tenantId, $this->integracaoId);
            $this->error('Erro interno ao buscar faturas.', 500);
        }
        if (!$cliente) {
            $this->logger->log($telefone, $endpoint, 'get_faturas', 'error', 'Cliente não encontrado', $this->tenantId, $this->integracaoId);
            $this->error('Cliente não encontrado para o telefone informado.', 404);
        }

        $clienteId = (int) $cliente->cliente_id;
        $pdo       = Database::getInstance();

        // Monta a query com filtro de status
        $where  = ['cr.cliente_id = :cliente_id', 'cr.usuario_id = :tenant_id'];
        $params = [':cliente_id' => $clienteId, ':tenant_id' => $this->tenantId];

        if ($filtro === 'vencida') {
            $where[] = "cr.status = 'aberta'";
            $where[] = "cr.data_vencimento < CURDATE()";
        } elseif ($filtro !== 'todas') {
            $where[]          = 'cr.status = :status';
            $params[':status'] = $filtro;
        }

        $sql = "SELECT cr.id, cr.descricao, cr.valor, cr.data_vencimento,
                       cr.status, cr.asaas_payment_id, cr.forma_pagamento,
                       cr.data_recebimento
                FROM contas_receber cr
                WHERE " . implode(' AND ', $where) . "
                ORDER BY cr.data_vencimento ASC
                LIMIT 10";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $contas = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Tenta buscar links de pagamento via Asaas para faturas abertas
        $asaas = $this->getAsaasService();

        $faturas = [];
        foreach ($contas as $conta) {
            $linkPagamento = null;

            if ($asaas && !empty($conta->asaas_payment_id) && $conta->status === 'aberta') {
                try {
                    $paymentInfo = $asaas->makeRequestPublic('GET', "/payments/{$conta->asaas_payment_id}");
                    $linkPagamento = $paymentInfo['invoiceUrl'] ?? $paymentInfo['bankSlipUrl'] ?? null;
                } catch (\Exception $e) {
                    // Falha na consulta ao Asaas não interrompe o fluxo
                }
            }

            $faturas[] = [
                'id'             => (int) $conta->id,
                'descricao'      => $conta->descricao ?? 'Cobrança',
                'valor'          => $this->formatMoney((float) $conta->valor),
                'valor_raw'      => (float) $conta->valor,
                'vencimento'     => $this->formatDate($conta->data_vencimento),
                'status'         => $conta->status,
                'status_label'   => $this->statusLabel($conta->status, $conta->data_vencimento),
                'link_pagamento' => $linkPagamento,
            ];
        }

        $total = count($faturas);
        $summary = "{$total} fatura(s) encontrada(s) com filtro '{$filtro}'";

        $this->logger->log($telefone, $endpoint, 'get_faturas', 'success', $summary, $this->tenantId, $this->integracaoId);

        $this->success(
            $total > 0 ? "{$total} fatura(s) encontrada(s)." : "Nenhuma fatura encontrada com o filtro '{$filtro}'.",
            [
                'cliente' => [
                    'nome'     => $cliente->razao_social ?? $cliente->nome_fantasia ?? 'N/A',
                    'cpf_cnpj' => $cliente->cpf_cnpj ?? '',
                ],
                'filtro'  => $filtro,
                'total'   => $total,
                'faturas' => $faturas,
            ]
        );
    }

    private function statusLabel(string $status, ?string $vencimento): string
    {
        if ($status === 'recebida') {
            return 'Paga';
        }
        if ($status === 'aberta' && !empty($vencimento) && $vencimento < date('Y-m-d')) {
            return 'Vencida';
        }
        if ($status === 'aberta') {
            return 'Em aberto';
        }
        return ucfirst($status);
    }

    private function getAsaasService(): ?AsaasService
    {
        try {
            $integracaoModel = new Integracao();
            $config = $integracaoModel->findByProvider('asaas', $this->tenantId);
            if (!$config || $config->status !== 'active' || empty($config->api_key)) {
                return null;
            }
            return new AsaasService($config->api_key, $config->environment ?? 'sandbox');
        } catch (\Exception $e) {
            return null;
        }
    }
}
