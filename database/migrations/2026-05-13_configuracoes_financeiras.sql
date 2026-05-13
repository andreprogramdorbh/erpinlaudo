-- ============================================================
-- Migration: configuracoes_financeiras
-- Criado em: 2026-05-13
-- Descrição: Tabela de configurações financeiras por tenant
--            (juros, multa, desconto, meio de pagamento padrão)
-- ============================================================

CREATE TABLE IF NOT EXISTS `configuracoes_financeiras` (
  `id`                          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`                  INT UNSIGNED NOT NULL COMMENT 'Tenant owner (users.id)',

  -- ── Meio de pagamento padrão ──────────────────────────────
  `meio_pagamento_padrao`       VARCHAR(30)  NOT NULL DEFAULT 'checkout'
                                COMMENT 'Usado quando contrato gera CR sem meio definido: pix|boleto|checkout|cartao|dinheiro|transferencia',

  -- ── Juros por atraso ─────────────────────────────────────
  `juros_tipo`                  ENUM('PERCENTAGE','FIXED') NOT NULL DEFAULT 'PERCENTAGE'
                                COMMENT 'PERCENTAGE = % ao mês | FIXED = valor fixo R$',
  `juros_valor`                 DECIMAL(8,4) NOT NULL DEFAULT 1.0000
                                COMMENT 'Percentual ao mês (ex: 1.00 = 1%) ou valor fixo R$',
  `juros_dias_carencia`         TINYINT UNSIGNED NOT NULL DEFAULT 0
                                COMMENT 'Dias após vencimento para começar a cobrar juros',

  -- ── Multa por atraso ─────────────────────────────────────
  `multa_tipo`                  ENUM('PERCENTAGE','FIXED') NOT NULL DEFAULT 'PERCENTAGE'
                                COMMENT 'PERCENTAGE = % sobre o valor | FIXED = valor fixo R$',
  `multa_valor`                 DECIMAL(8,4) NOT NULL DEFAULT 2.0000
                                COMMENT 'Percentual (ex: 2.00 = 2%) ou valor fixo R$',
  `multa_dias_carencia`         TINYINT UNSIGNED NOT NULL DEFAULT 0
                                COMMENT 'Dias após vencimento para começar a cobrar multa',

  -- ── Desconto pontualidade ────────────────────────────────
  `desconto_ativo`              TINYINT(1) NOT NULL DEFAULT 0,
  `desconto_tipo`               ENUM('PERCENTAGE','FIXED') NOT NULL DEFAULT 'PERCENTAGE',
  `desconto_valor`              DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
  `desconto_dias_antes`         TINYINT UNSIGNED NOT NULL DEFAULT 0
                                COMMENT 'Dias antes do vencimento para aplicar desconto (0 = até o vencimento)',
  `desconto_limite_data`        DATE NULL DEFAULT NULL
                                COMMENT 'Data limite para desconto (opcional, sobrescreve dias_antes)',

  -- ── Boleto ───────────────────────────────────────────────
  `boleto_dias_vencimento`      TINYINT UNSIGNED NOT NULL DEFAULT 3
                                COMMENT 'Dias corridos a partir de hoje para vencimento padrão do boleto',
  `boleto_instrucoes`           VARCHAR(500) NOT NULL DEFAULT ''
                                COMMENT 'Instruções impressas no boleto (ex: Não receber após vencimento)',
  `boleto_aceite`               CHAR(1) NOT NULL DEFAULT 'N'
                                COMMENT 'N = Não aceite | A = Aceite',
  `boleto_banco`                VARCHAR(10) NOT NULL DEFAULT ''
                                COMMENT 'Código do banco emissor (opcional, Asaas define automaticamente)',

  -- ── PIX ──────────────────────────────────────────────────
  `pix_expiracao_segundos`      INT UNSIGNED NOT NULL DEFAULT 86400
                                COMMENT 'Tempo de expiração do QR Code PIX em segundos (86400 = 24h)',
  `pix_chave`                   VARCHAR(150) NOT NULL DEFAULT ''
                                COMMENT 'Chave PIX para exibição informativa (Asaas usa a chave cadastrada na conta)',

  -- ── Cartão de Crédito ────────────────────────────────────
  `cartao_max_parcelas`         TINYINT UNSIGNED NOT NULL DEFAULT 1
                                COMMENT 'Número máximo de parcelas no cartão (1 a 12)',
  `cartao_parcela_minima`       DECIMAL(10,2) NOT NULL DEFAULT 50.00
                                COMMENT 'Valor mínimo por parcela no cartão',
  `cartao_juros_parcelamento`   DECIMAL(6,4) NOT NULL DEFAULT 0.0000
                                COMMENT '% de juros por parcela no cartão (0 = sem juros)',

  -- ── Checkout Asaas (UNDEFINED) ───────────────────────────
  `checkout_meios_habilitados`  VARCHAR(200) NOT NULL DEFAULT 'BOLETO,PIX,CREDIT_CARD'
                                COMMENT 'Meios habilitados no checkout Asaas separados por vírgula',

  -- ── Notificações ─────────────────────────────────────────
  `notificar_email`             TINYINT(1) NOT NULL DEFAULT 1
                                COMMENT 'Enviar e-mail de cobrança ao cliente via Asaas',
  `notificar_sms`               TINYINT(1) NOT NULL DEFAULT 0,
  `notificar_whatsapp`          TINYINT(1) NOT NULL DEFAULT 0,
  `dias_aviso_vencimento`       TINYINT UNSIGNED NOT NULL DEFAULT 3
                                COMMENT 'Dias antes do vencimento para enviar aviso',

  -- ── Metadados ────────────────────────────────────────────
  `criado_em`                   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
