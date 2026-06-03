-- ============================================================
-- Migration: Fluxo Proposta -> Pedido de Venda -> Faturamento
-- Data: 2026-06-03
-- Sistema: ERP inlaudo
-- MySQL: 5.7.44 | Hostgator Shared Hosting
-- Charset: utf8 / utf8_unicode_ci
-- ============================================================
--
-- ⚠️  VERIFICACOES ANTES DE EXECUTAR:
--
-- 1. Verifique se a coluna pedido_venda_id NAO existe em crm_propostas:
--    SHOW COLUMNS FROM `crm_propostas` LIKE 'pedido_venda_id';
--
-- 2. Verifique se a coluna conta_receber_id NAO existe em est_pedidos_venda:
--    SHOW COLUMNS FROM `est_pedidos_venda` LIKE 'conta_receber_id';
--
-- 3. Verifique se a coluna pedido_venda_id NAO existe em contasreceber:
--    SHOW COLUMNS FROM `contasreceber` LIKE 'pedido_venda_id';
--
-- 4. Faca backup das tabelas antes:
--    CREATE TABLE crm_propostas_bkp_20260603 SELECT * FROM crm_propostas;
--    CREATE TABLE est_pedidos_venda_bkp_20260603 SELECT * FROM est_pedidos_venda;
--    CREATE TABLE contasreceber_bkp_20260603 SELECT * FROM contasreceber;
--
-- 5. Execute em horario de baixo trafego.
-- ============================================================


-- ─────────────────────────────────────────────────────────────
-- 1. Campos de rastreamento na tabela crm_propostas
-- ─────────────────────────────────────────────────────────────

ALTER TABLE `crm_propostas`
    ADD COLUMN `pedido_venda_id`        INT(11)      NULL COMMENT 'Pedido de venda gerado ao aceitar';

ALTER TABLE `crm_propostas`
    ADD COLUMN `aceito_por_nome`        VARCHAR(255) NULL COMMENT 'Nome de quem assinou/aceitou';

ALTER TABLE `crm_propostas`
    ADD COLUMN `aceito_por_ip`          VARCHAR(45)  NULL COMMENT 'IP do aceite';

ALTER TABLE `crm_propostas`
    ADD COLUMN `assinatura_tipo`        VARCHAR(30)  NULL COMMENT 'rubrica | nome_digitado';

ALTER TABLE `crm_propostas`
    ADD COLUMN `assinatura_imagem_path` VARCHAR(500) NULL COMMENT 'Caminho da imagem da rubrica';

ALTER TABLE `crm_propostas`
    ADD COLUMN `recusado_em`            DATETIME     NULL COMMENT 'Data/hora da recusa';

ALTER TABLE `crm_propostas`
    ADD COLUMN `recusado_motivo`        TEXT         NULL COMMENT 'Motivo da recusa';

-- Indice: proposta -> pedido_venda
ALTER TABLE `crm_propostas`
    ADD INDEX `idx_prop_pedido_venda` (`pedido_venda_id`);


-- ─────────────────────────────────────────────────────────────
-- 2. Campos de rastreamento de faturamento em est_pedidos_venda
-- ─────────────────────────────────────────────────────────────

ALTER TABLE `est_pedidos_venda`
    ADD COLUMN `conta_receber_id` INT(11)   NULL COMMENT 'Conta a receber gerada ao faturar';

ALTER TABLE `est_pedidos_venda`
    ADD COLUMN `nota_fiscal_id`   INT(11)   NULL COMMENT 'NF gerada ao faturar';

ALTER TABLE `est_pedidos_venda`
    ADD COLUMN `faturado_em`      DATETIME  NULL COMMENT 'Data/hora do faturamento';

ALTER TABLE `est_pedidos_venda`
    ADD COLUMN `faturado_por`     INT(11)   NULL COMMENT 'Usuario que faturou';

-- Indices
ALTER TABLE `est_pedidos_venda`
    ADD INDEX `idx_pv_conta_receber` (`conta_receber_id`);

ALTER TABLE `est_pedidos_venda`
    ADD INDEX `idx_pv_nota_fiscal` (`nota_fiscal_id`);


-- ─────────────────────────────────────────────────────────────
-- 3. Tabela de historico do pedido de venda
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `est_pedidos_venda_historico` (
    `id`          INT(11)     NOT NULL AUTO_INCREMENT,
    `pedido_id`   INT(11)     NOT NULL,
    `usuario_id`  INT(11)     NULL,
    `status_de`   VARCHAR(40) NULL,
    `status_para` VARCHAR(40) NOT NULL,
    `observacao`  TEXT        NULL,
    `created_at`  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_pvh_pedido`  (`pedido_id`),
    INDEX `idx_pvh_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
  COMMENT='Historico de status dos pedidos de venda';


-- ─────────────────────────────────────────────────────────────
-- 4. Campos de origem em contasreceber
-- ─────────────────────────────────────────────────────────────

ALTER TABLE `contasreceber`
    ADD COLUMN `pedido_venda_id` INT(11)     NULL COMMENT 'Pedido de venda de origem';

ALTER TABLE `contasreceber`
    ADD COLUMN `proposta_id`     INT(11)     NULL COMMENT 'Proposta CRM de origem';

ALTER TABLE `contasreceber`
    ADD COLUMN `grupo_parcelas`  VARCHAR(50) NULL COMMENT 'Identificador de grupo de parcelas';

-- Indices
ALTER TABLE `contasreceber`
    ADD INDEX `idx_cr_pedido_venda` (`pedido_venda_id`);

ALTER TABLE `contasreceber`
    ADD INDEX `idx_cr_proposta` (`proposta_id`);


-- ─────────────────────────────────────────────────────────────
-- 5. Tabela de aceite/assinatura de proposta
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `crm_proposta_aceite` (
    `id`                     INT(11)      NOT NULL AUTO_INCREMENT,
    `proposta_id`            INT(11)      NOT NULL,
    `evento`                 VARCHAR(30)  NOT NULL COMMENT 'aceito | recusado | visualizado',
    `nome_assinante`         VARCHAR(255) NULL,
    `ip`                     VARCHAR(45)  NULL,
    `user_agent`             TEXT         NULL,
    `assinatura_tipo`        VARCHAR(30)  NULL,
    `assinatura_imagem_path` VARCHAR(500) NULL,
    `motivo_recusa`          TEXT         NULL,
    `created_at`             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_aceite_proposta` (`proposta_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
  COMMENT='Registro de eventos de aceite/recusa de propostas';


-- ─────────────────────────────────────────────────────────────
-- VALIDACAO
-- ─────────────────────────────────────────────────────────────

-- Verificar colunas adicionadas em crm_propostas
SHOW COLUMNS FROM `crm_propostas` LIKE 'pedido_venda_id';
SHOW COLUMNS FROM `crm_propostas` LIKE 'recusado_em';

-- Verificar colunas adicionadas em est_pedidos_venda
SHOW COLUMNS FROM `est_pedidos_venda` LIKE 'conta_receber_id';
SHOW COLUMNS FROM `est_pedidos_venda` LIKE 'faturado_em';

-- Verificar colunas adicionadas em contasreceber
SHOW COLUMNS FROM `contasreceber` LIKE 'pedido_venda_id';

-- Verificar tabelas criadas
SELECT COUNT(*) AS historico_registros FROM `est_pedidos_venda_historico`;
SELECT COUNT(*) AS aceite_registros    FROM `crm_proposta_aceite`;


-- ─────────────────────────────────────────────────────────────
-- ROLLBACK (executar em caso de necessidade de reverter)
-- ─────────────────────────────────────────────────────────────

/*

-- Remover indices e colunas de crm_propostas
ALTER TABLE `crm_propostas` DROP INDEX `idx_prop_pedido_venda`;
ALTER TABLE `crm_propostas` DROP COLUMN `pedido_venda_id`;
ALTER TABLE `crm_propostas` DROP COLUMN `aceito_por_nome`;
ALTER TABLE `crm_propostas` DROP COLUMN `aceito_por_ip`;
ALTER TABLE `crm_propostas` DROP COLUMN `assinatura_tipo`;
ALTER TABLE `crm_propostas` DROP COLUMN `assinatura_imagem_path`;
ALTER TABLE `crm_propostas` DROP COLUMN `recusado_em`;
ALTER TABLE `crm_propostas` DROP COLUMN `recusado_motivo`;

-- Remover indices e colunas de est_pedidos_venda
ALTER TABLE `est_pedidos_venda` DROP INDEX `idx_pv_conta_receber`;
ALTER TABLE `est_pedidos_venda` DROP INDEX `idx_pv_nota_fiscal`;
ALTER TABLE `est_pedidos_venda` DROP COLUMN `conta_receber_id`;
ALTER TABLE `est_pedidos_venda` DROP COLUMN `nota_fiscal_id`;
ALTER TABLE `est_pedidos_venda` DROP COLUMN `faturado_em`;
ALTER TABLE `est_pedidos_venda` DROP COLUMN `faturado_por`;

-- Remover indices e colunas de contasreceber
ALTER TABLE `contasreceber` DROP INDEX `idx_cr_pedido_venda`;
ALTER TABLE `contasreceber` DROP INDEX `idx_cr_proposta`;
ALTER TABLE `contasreceber` DROP COLUMN `pedido_venda_id`;
ALTER TABLE `contasreceber` DROP COLUMN `proposta_id`;
ALTER TABLE `contasreceber` DROP COLUMN `grupo_parcelas`;

-- Remover tabelas criadas
DROP TABLE IF EXISTS `est_pedidos_venda_historico`;
DROP TABLE IF EXISTS `crm_proposta_aceite`;

*/
