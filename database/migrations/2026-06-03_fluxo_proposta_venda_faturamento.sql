-- ============================================================
-- Migration: Fluxo Proposta → Pedido de Venda → Faturamento
-- Data: 2026-06-03
-- Descrição: Adiciona campos e tabelas para suportar o fluxo
--   automático: Proposta aceita → Pedido de Venda criado →
--   Faturado → Contas a Receber + Cliente + NF
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. Adicionar campos de rastreamento na tabela crm_propostas
-- ─────────────────────────────────────────────────────────────

ALTER TABLE `crm_propostas`
    ADD COLUMN IF NOT EXISTS `pedido_venda_id`    INT(11)      NULL COMMENT 'Pedido de venda gerado ao aceitar'
        AFTER `oportunidade_id`,
    ADD COLUMN IF NOT EXISTS `aceito_por_nome`    VARCHAR(255) NULL COMMENT 'Nome de quem assinou/aceitou'
        AFTER `aceito_em`,
    ADD COLUMN IF NOT EXISTS `aceito_por_ip`      VARCHAR(45)  NULL COMMENT 'IP do aceite'
        AFTER `aceito_por_nome`,
    ADD COLUMN IF NOT EXISTS `assinatura_tipo`    VARCHAR(30)  NULL COMMENT 'rubrica | nome_digitado'
        AFTER `aceito_por_ip`,
    ADD COLUMN IF NOT EXISTS `assinatura_imagem_path` VARCHAR(500) NULL COMMENT 'Caminho da imagem da rubrica'
        AFTER `assinatura_tipo`,
    ADD COLUMN IF NOT EXISTS `recusado_em`        DATETIME     NULL COMMENT 'Data/hora da recusa'
        AFTER `assinatura_imagem_path`,
    ADD COLUMN IF NOT EXISTS `recusado_motivo`    TEXT         NULL COMMENT 'Motivo da recusa'
        AFTER `recusado_em`;

-- Índice para FK proposta → pedido_venda
ALTER TABLE `crm_propostas`
    ADD INDEX IF NOT EXISTS `idx_prop_pedido_venda` (`pedido_venda_id`);

-- ─────────────────────────────────────────────────────────────
-- 2. Adicionar campos no est_pedidos_venda para rastreamento
--    de faturamento e origem
-- ─────────────────────────────────────────────────────────────

ALTER TABLE `est_pedidos_venda`
    ADD COLUMN IF NOT EXISTS `conta_receber_id`   INT(11)      NULL COMMENT 'Conta a receber gerada ao faturar'
        AFTER `oportunidade_id`,
    ADD COLUMN IF NOT EXISTS `nota_fiscal_id`     INT(11)      NULL COMMENT 'NF gerada ao faturar'
        AFTER `conta_receber_id`,
    ADD COLUMN IF NOT EXISTS `faturado_em`        DATETIME     NULL COMMENT 'Data/hora do faturamento'
        AFTER `data_faturamento`,
    ADD COLUMN IF NOT EXISTS `faturado_por`       INT(11)      NULL COMMENT 'Usuário que faturou'
        AFTER `faturado_em`;

-- Índices adicionais
ALTER TABLE `est_pedidos_venda`
    ADD INDEX IF NOT EXISTS `idx_pv_conta_receber` (`conta_receber_id`),
    ADD INDEX IF NOT EXISTS `idx_pv_nota_fiscal`   (`nota_fiscal_id`);

-- ─────────────────────────────────────────────────────────────
-- 3. Tabela de histórico do pedido de venda
--    (rastreia cada mudança de status com quem/quando/obs)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `est_pedidos_venda_historico` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `pedido_id`     INT(11)      NOT NULL,
    `usuario_id`    INT(11)      NULL,
    `status_de`     VARCHAR(40)  NULL,
    `status_para`   VARCHAR(40)  NOT NULL,
    `observacao`    TEXT         NULL,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_pvh_pedido`   (`pedido_id`),
    INDEX `idx_pvh_usuario`  (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Histórico de status dos pedidos de venda';

-- ─────────────────────────────────────────────────────────────
-- 4. Adicionar campos extras na tabela contasreceber
--    para rastrear a origem (pedido de venda)
-- ─────────────────────────────────────────────────────────────

ALTER TABLE `contasreceber`
    ADD COLUMN IF NOT EXISTS `pedido_venda_id`    INT(11)      NULL COMMENT 'Pedido de venda de origem'
        AFTER `id`,
    ADD COLUMN IF NOT EXISTS `proposta_id`        INT(11)      NULL COMMENT 'Proposta CRM de origem'
        AFTER `pedido_venda_id`,
    ADD COLUMN IF NOT EXISTS `grupo_parcelas`     VARCHAR(50)  NULL COMMENT 'Identificador de grupo de parcelas'
        AFTER `proposta_id`;

ALTER TABLE `contasreceber`
    ADD INDEX IF NOT EXISTS `idx_cr_pedido_venda` (`pedido_venda_id`),
    ADD INDEX IF NOT EXISTS `idx_cr_proposta`     (`proposta_id`);

-- ─────────────────────────────────────────────────────────────
-- 5. Tabela de aceite/assinatura de proposta (se não existir)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `crm_proposta_aceite` (
    `id`                    INT(11)      NOT NULL AUTO_INCREMENT,
    `proposta_id`           INT(11)      NOT NULL,
    `evento`                VARCHAR(30)  NOT NULL COMMENT 'aceito | recusado | visualizado',
    `nome_assinante`        VARCHAR(255) NULL,
    `ip`                    VARCHAR(45)  NULL,
    `user_agent`            TEXT         NULL,
    `assinatura_tipo`       VARCHAR(30)  NULL,
    `assinatura_imagem_path` VARCHAR(500) NULL,
    `motivo_recusa`         TEXT         NULL,
    `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_aceite_proposta` (`proposta_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Registro de eventos de aceite/recusa de propostas';
