-- -------------------------------------------------------
-- Migration: Adicionar coluna openfinance_connector
-- Tabela: contas_bancarias
-- Data: 2026-05-13
-- -------------------------------------------------------

ALTER TABLE `contas_bancarias`
    ADD COLUMN IF NOT EXISTS `openfinance_connector` VARCHAR(150) DEFAULT NULL
        COMMENT 'Nome do conector/instituição no Pluggy (ex: Itaú, Bradesco)'
        AFTER `openfinance_config`;
