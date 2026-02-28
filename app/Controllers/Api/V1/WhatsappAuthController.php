<?php
namespace App\Controllers\Api\V1;

use App\Core\Database;
use App\Helpers\TelefoneHelper;
use PDO;

/**
 * WhatsappAuthController
 *
 * Endpoint: POST /api/v1/whatsapp/identificar
 *
 * Identifica o cliente pelo número de telefone do WhatsApp.
 *
 * Payload esperado:
 * {
 *   "telefone": "+5531992746755"
 * }
 */
class WhatsappAuthController extends WhatsappBaseController
{
    public function identificar(): void
    {
        $body             = $this->getRequestBody();
        $telefoneOriginal = (string) ($body['telefone'] ?? '');
        $endpoint         = '/api/v1/whatsapp/identificar';
        $telefoneLog      = preg_replace('/\D/', '', $telefoneOriginal) ?: $telefoneOriginal;

        try {
            if ($telefoneOriginal === '') {
                $this->logger->log(
                    '',
                    $endpoint,
                    'identificar',
                    'error',
                    'Campo telefone ausente',
                    $this->tenantId,
                    $this->integracaoId
                );
                $this->error('O campo "telefone" é obrigatório.', 422);
            }

            $variacoes = TelefoneHelper::normalizarTelefone($telefoneOriginal);
            if (empty($variacoes)) {
                $this->logger->log(
                    $telefoneLog,
                    $endpoint,
                    'identificar',
                    'error',
                    'Telefone inválido para normalização (esperado 10/11 dígitos com DDD)',
                    $this->tenantId,
                    $this->integracaoId
                );
                $this->error('Telefone inválido. Informe um número com DDD (10 ou 11 dígitos).', 422);
            }

            $cliente = $this->findClienteByPhoneVariacoes($variacoes);
        } catch (\Throwable $e) {
            $summary = 'Exceção: ' . $this->safeLogMessage($e->getMessage());
            $this->logger->log(
                $telefoneLog,
                $endpoint,
                'identificar',
                'error',
                $summary,
                $this->tenantId,
                $this->integracaoId
            );
            $this->error('Erro interno ao identificar o cliente.', 500);
        }

        if (!$cliente) {
            $this->logger->log(
                $telefoneLog,
                $endpoint,
                'identificar',
                'error',
                'Cliente não encontrado',
                $this->tenantId,
                $this->integracaoId
            );
            $this->error(
                'Cliente não encontrado para o telefone informado. Verifique o número ou entre em contato com o suporte.',
                404
            );
        }

        $this->logger->log(
            $telefoneLog,
            $endpoint,
            'identificar',
            'success',
            "Cliente ID={$cliente->cliente_id} identificado",
            $this->tenantId,
            $this->integracaoId
        );

        $this->success('Cliente identificado com sucesso.', [
            'cliente_id' => (int) $cliente->cliente_id,
            'nome'       => $cliente->razao_social ?? $cliente->nome_fantasia ?? 'N/A',
            'cpf_cnpj'   => $cliente->cpf_cnpj ?? '',
            'email'      => $cliente->email ?? '',
        ]);
    }

    /**
     * Busca o cliente a partir de variações normalizadas (DDD+local), sem usar REGEXP no MySQL.
     * Compatível com MySQL 5.7+.
     *
     * @param string[] $variacoes Ex.: ["31992746755", "3192746755"]
     */
    private function findClienteByPhoneVariacoes(array $variacoes): object|false
    {
        $pdo = Database::getInstance();

        $telPlaceholders = [];
        $celPlaceholders = [];
        $params          = [':tenant_id' => $this->tenantId];

        $i = 0;
        foreach ($variacoes as $v) {
            $v = (string) $v;
            if ($v === '') {
                continue;
            }
            $telKey = ':tel_' . $i;
            $celKey = ':cel_' . $i;
            $telPlaceholders[] = $telKey;
            $celPlaceholders[] = $celKey;
            $params[$telKey] = $v;
            $params[$celKey] = $v;
            $i++;
        }

        if ($i === 0) {
            return false;
        }

        $telIn = implode(', ', $telPlaceholders);
        $celIn = implode(', ', $celPlaceholders);

        $stmt = $pdo->prepare(
            "SELECT c.id AS cliente_id, c.razao_social, c.nome_fantasia, c.cpf_cnpj,
                    c.telefone, c.celular,
                    pc.email, pc.ativo AS portal_ativo
             FROM clientes c
             LEFT JOIN portal_clientes pc ON pc.cliente_id = c.id AND pc.ativo = 1
             WHERE c.usuario_id = :tenant_id
               AND c.status = 'ativo'
               AND (
                   c.telefone IN ({$telIn})
                   OR c.celular IN ({$celIn})
               )
             ORDER BY pc.ativo DESC
             LIMIT 1"
        );

        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: false;
    }
}