-- ============================================================
-- Migration: 2026-05-12_crm_interacoes_data_retorno.sql
-- Adiciona coluna data_retorno na tabela crm_interacoes
-- ============================================================

ALTER TABLE `crm_interacoes`
    ADD COLUMN IF NOT EXISTS `data_retorno` DATE NULL DEFAULT NULL
        COMMENT 'Data programada para o próximo retorno/contato após esta interação'
    AFTER `resumo`;

-- Índice para facilitar consultas de retornos pendentes
CREATE INDEX IF NOT EXISTS `idx_crm_interacoes_data_retorno`
    ON `crm_interacoes` (`data_retorno`);
