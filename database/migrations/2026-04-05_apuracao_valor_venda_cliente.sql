-- =============================================================================
-- Migration: Apuração — Campos de valor de venda (cliente) e vínculo de cliente
-- Data: 2026-04-05
-- Compatível com: MariaDB 10.0+ / MySQL 5.7+
--
-- Uso seguro: verifica se a coluna já existe antes de adicionar,
-- evitando erro "Duplicate column name" em re-execuções.
-- =============================================================================

-- -----------------------------------------------------------------------
-- 1. Adicionar valor_venda_total na tabela `apuracoes`
--    valor_total       = valor de custo (repasse ao médico/prestador)
--    valor_venda_total = valor de venda (cobrado do cliente)
-- -----------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _mig_add_valor_venda_total;

DELIMITER $$
CREATE PROCEDURE _mig_add_valor_venda_total()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM   INFORMATION_SCHEMA.COLUMNS
        WHERE  TABLE_SCHEMA = DATABASE()
          AND  TABLE_NAME   = 'apuracoes'
          AND  COLUMN_NAME  = 'valor_venda_total'
    ) THEN
        ALTER TABLE `apuracoes`
            ADD COLUMN `valor_venda_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00
            COMMENT 'Valor total de venda (preço cobrado do cliente)'
            AFTER `valor_total`;
    END IF;
END$$
DELIMITER ;

CALL _mig_add_valor_venda_total();
DROP PROCEDURE IF EXISTS _mig_add_valor_venda_total;


-- -----------------------------------------------------------------------
-- 2. Adicionar valor_calculado_venda na tabela `apuracao_itens`
--    valor_calculado       = valor de custo por item (repasse ao médico)
--    valor_calculado_venda = valor de venda por item (cobrado do cliente)
-- -----------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _mig_add_valor_calculado_venda;

DELIMITER $$
CREATE PROCEDURE _mig_add_valor_calculado_venda()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM   INFORMATION_SCHEMA.COLUMNS
        WHERE  TABLE_SCHEMA = DATABASE()
          AND  TABLE_NAME   = 'apuracao_itens'
          AND  COLUMN_NAME  = 'valor_calculado_venda'
    ) THEN
        ALTER TABLE `apuracao_itens`
            ADD COLUMN `valor_calculado_venda` DECIMAL(12,2) NOT NULL DEFAULT 0.00
            COMMENT 'Valor de venda calculado por item (preço de venda da tabela de exames)'
            AFTER `valor_calculado`;
    END IF;
END$$
DELIMITER ;

CALL _mig_add_valor_calculado_venda();
DROP PROCEDURE IF EXISTS _mig_add_valor_calculado_venda;
