<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model ConfiguracaoFinanceira
 *
 * Gerencia as configurações financeiras por tenant:
 * - Meio de pagamento padrão
 * - Juros e multa por atraso
 * - Desconto por pontualidade
 * - Parâmetros por meio de pagamento (boleto, PIX, cartão, checkout)
 * - Notificações de cobrança
 */
class ConfiguracaoFinanceira
{
    private \PDO $pdo;
    private string $table = 'configuracoes_financeiras';

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->garantirTabela();
    }

    // ================================================================
    // CRUD
    // ================================================================

    /**
     * Busca a configuração financeira do tenant.
     * Retorna objeto com valores padrão se não existir.
     */
    public function findByUsuarioId(int $usuarioId): object
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE usuario_id = :uid LIMIT 1"
        );
        $stmt->execute([':uid' => $usuarioId]);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);

        return $row ?: $this->defaults($usuarioId);
    }

    /**
     * Cria ou atualiza a configuração financeira do tenant (upsert).
     */
    public function upsert(int $usuarioId, array $data): bool
    {
        $data['usuario_id'] = $usuarioId;

        // Verificar se já existe
        $stmt = $this->pdo->prepare(
            "SELECT id FROM {$this->table} WHERE usuario_id = :uid LIMIT 1"
        );
        $stmt->execute([':uid' => $usuarioId]);
        $existing = $stmt->fetch(\PDO::FETCH_OBJ);

        try {
            if ($existing) {
                // UPDATE
                $sets = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($data)));
                $stmt = $this->pdo->prepare(
                    "UPDATE {$this->table} SET {$sets} WHERE usuario_id = :usuario_id"
                );
            } else {
                // INSERT
                $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
                $vals = implode(', ', array_map(fn($k) => ":{$k}", array_keys($data)));
                $stmt = $this->pdo->prepare(
                    "INSERT INTO {$this->table} ({$cols}) VALUES ({$vals})"
                );
            }
            return $stmt->execute($data);
        } catch (\Throwable $e) {
            error_log('[ConfiguracaoFinanceira::upsert] Erro: ' . $e->getMessage());
            return false;
        }
    }

    // ================================================================
    // HELPERS — PAYLOAD ASAAS
    // ================================================================

    /**
     * Monta o bloco de fine (multa) para a API do Asaas.
     * Ref: https://docs.asaas.com/reference/create-new-payment
     */
    public function montarFine(object $config): array
    {
        if ((float)$config->multa_valor <= 0) {
            return [];
        }
        return [
            'value'   => (float) $config->multa_valor,
            'type'    => $config->multa_tipo ?? 'PERCENTAGE',
            'dueDateLimitDays' => (int) ($config->multa_dias_carencia ?? 0),
        ];
    }

    /**
     * Monta o bloco de interest (juros) para a API do Asaas.
     */
    public function montarInterest(object $config): array
    {
        if ((float)$config->juros_valor <= 0) {
            return [];
        }
        return [
            'value' => (float) $config->juros_valor,
            'type'  => $config->juros_tipo ?? 'PERCENTAGE',
        ];
    }

    /**
     * Monta o bloco de discount (desconto) para a API do Asaas.
     */
    public function montarDiscount(object $config): array
    {
        if (!(bool)$config->desconto_ativo || (float)$config->desconto_valor <= 0) {
            return [];
        }

        $discount = [
            'value'            => (float) $config->desconto_valor,
            'type'             => $config->desconto_tipo ?? 'PERCENTAGE',
            'dueDateLimitDays' => (int) ($config->desconto_dias_antes ?? 0),
        ];

        // Data limite explícita sobrescreve dias_antes
        if (!empty($config->desconto_limite_data)) {
            $discount['limitDate'] = $config->desconto_limite_data;
            unset($discount['dueDateLimitDays']);
        }

        return $discount;
    }

    /**
     * Monta o payload completo de cobrança para o Asaas,
     * mesclando os dados da conta a receber com as configurações financeiras.
     *
     * @param object $config     Configuração financeira do tenant
     * @param array  $dadosBase  Dados base da cobrança (customer, value, dueDate, etc.)
     * @param string $billingType BOLETO | PIX | CREDIT_CARD | UNDEFINED
     * @return array
     */
    public function montarPayloadAsaas(object $config, array $dadosBase, string $billingType): array
    {
        $payload = $dadosBase;
        $payload['billingType'] = $billingType;

        // Juros e multa (apenas para boleto)
        if ($billingType === 'BOLETO') {
            $fine     = $this->montarFine($config);
            $interest = $this->montarInterest($config);
            $discount = $this->montarDiscount($config);

            if (!empty($fine))     $payload['fine']     = $fine;
            if (!empty($interest)) $payload['interest'] = $interest;
            if (!empty($discount)) $payload['discount'] = $discount;

            // Instruções do boleto
            if (!empty($config->boleto_instrucoes)) {
                $payload['bankSlipInstructions'] = $config->boleto_instrucoes;
            }
        }

        // PIX — tempo de expiração
        if ($billingType === 'PIX') {
            $expiracao = (int)($config->pix_expiracao_segundos ?? 86400);
            if ($expiracao > 0) {
                $payload['pixExpirationDate'] = date('Y-m-d\TH:i:s', time() + $expiracao);
            }
        }

        // Cartão de crédito — parcelamento
        if ($billingType === 'CREDIT_CARD') {
            $maxParcelas = (int)($config->cartao_max_parcelas ?? 1);
            if ($maxParcelas > 1) {
                $payload['installmentCount'] = $maxParcelas;
                $payload['installmentValue'] = round(
                    (float)($dadosBase['value'] ?? 0) / $maxParcelas,
                    2
                );
            }
        }

        // Notificações
        $payload['notificationDisabled'] = !(bool)($config->notificar_email ?? true);

        return $payload;
    }

    /**
     * Retorna o billingType Asaas para o meio de pagamento padrão configurado.
     */
    public function getBillingTypePadrao(object $config): string
    {
        return match ($config->meio_pagamento_padrao ?? 'checkout') {
            'boleto'       => 'BOLETO',
            'pix'          => 'PIX',
            'cartao'       => 'CREDIT_CARD',
            'checkout'     => 'UNDEFINED',
            default        => 'UNDEFINED',
        };
    }

    // ================================================================
    // PRIVADOS
    // ================================================================

    /**
     * Retorna um objeto com os valores padrão quando não há configuração salva.
     */
    private function defaults(int $usuarioId): object
    {
        return (object) [
            'id'                         => null,
            'usuario_id'                 => $usuarioId,
            'meio_pagamento_padrao'      => 'checkout',
            'juros_tipo'                 => 'PERCENTAGE',
            'juros_valor'                => 1.00,
            'juros_dias_carencia'        => 0,
            'multa_tipo'                 => 'PERCENTAGE',
            'multa_valor'                => 2.00,
            'multa_dias_carencia'        => 0,
            'desconto_ativo'             => 0,
            'desconto_tipo'              => 'PERCENTAGE',
            'desconto_valor'             => 0.00,
            'desconto_dias_antes'        => 0,
            'desconto_limite_data'       => null,
            'boleto_dias_vencimento'     => 3,
            'boleto_instrucoes'          => '',
            'boleto_aceite'              => 'N',
            'boleto_banco'               => '',
            'pix_expiracao_segundos'     => 86400,
            'pix_chave'                  => '',
            'cartao_max_parcelas'        => 1,
            'cartao_parcela_minima'      => 50.00,
            'cartao_juros_parcelamento'  => 0.00,
            'checkout_meios_habilitados' => 'BOLETO,PIX,CREDIT_CARD',
            'notificar_email'            => 1,
            'notificar_sms'              => 0,
            'notificar_whatsapp'         => 0,
            'dias_aviso_vencimento'      => 3,
        ];
    }

    /**
     * Garante que a tabela existe no banco (compatível com MySQL 5.7+).
     */
    private function garantirTabela(): void
    {
        try {
            $this->pdo->query("SELECT 1 FROM {$this->table} LIMIT 1");
        } catch (\Throwable $e) {
            // Tabela não existe — criar
            try {
                $this->pdo->exec("
                    CREATE TABLE IF NOT EXISTS `{$this->table}` (
                      `id`                          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                      `usuario_id`                  INT UNSIGNED NOT NULL,
                      `meio_pagamento_padrao`       VARCHAR(30)  NOT NULL DEFAULT 'checkout',
                      `juros_tipo`                  ENUM('PERCENTAGE','FIXED') NOT NULL DEFAULT 'PERCENTAGE',
                      `juros_valor`                 DECIMAL(8,4) NOT NULL DEFAULT 1.0000,
                      `juros_dias_carencia`         TINYINT UNSIGNED NOT NULL DEFAULT 0,
                      `multa_tipo`                  ENUM('PERCENTAGE','FIXED') NOT NULL DEFAULT 'PERCENTAGE',
                      `multa_valor`                 DECIMAL(8,4) NOT NULL DEFAULT 2.0000,
                      `multa_dias_carencia`         TINYINT UNSIGNED NOT NULL DEFAULT 0,
                      `desconto_ativo`              TINYINT(1) NOT NULL DEFAULT 0,
                      `desconto_tipo`               ENUM('PERCENTAGE','FIXED') NOT NULL DEFAULT 'PERCENTAGE',
                      `desconto_valor`              DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
                      `desconto_dias_antes`         TINYINT UNSIGNED NOT NULL DEFAULT 0,
                      `desconto_limite_data`        DATE NULL DEFAULT NULL,
                      `boleto_dias_vencimento`      TINYINT UNSIGNED NOT NULL DEFAULT 3,
                      `boleto_instrucoes`           VARCHAR(500) NOT NULL DEFAULT '',
                      `boleto_aceite`               CHAR(1) NOT NULL DEFAULT 'N',
                      `boleto_banco`                VARCHAR(10) NOT NULL DEFAULT '',
                      `pix_expiracao_segundos`      INT UNSIGNED NOT NULL DEFAULT 86400,
                      `pix_chave`                   VARCHAR(150) NOT NULL DEFAULT '',
                      `cartao_max_parcelas`         TINYINT UNSIGNED NOT NULL DEFAULT 1,
                      `cartao_parcela_minima`       DECIMAL(10,2) NOT NULL DEFAULT 50.00,
                      `cartao_juros_parcelamento`   DECIMAL(6,4) NOT NULL DEFAULT 0.0000,
                      `checkout_meios_habilitados`  VARCHAR(200) NOT NULL DEFAULT 'BOLETO,PIX,CREDIT_CARD',
                      `notificar_email`             TINYINT(1) NOT NULL DEFAULT 1,
                      `notificar_sms`               TINYINT(1) NOT NULL DEFAULT 0,
                      `notificar_whatsapp`          TINYINT(1) NOT NULL DEFAULT 0,
                      `dias_aviso_vencimento`       TINYINT UNSIGNED NOT NULL DEFAULT 3,
                      `criado_em`                   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `atualizado_em`               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `uk_usuario_id` (`usuario_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            } catch (\Throwable $e2) {
                error_log('[ConfiguracaoFinanceira] Erro ao criar tabela: ' . $e2->getMessage());
            }
        }
    }
}
