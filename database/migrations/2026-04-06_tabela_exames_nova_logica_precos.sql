-- =============================================================================
-- Migration: Nova lógica de preços da tabela de exames
-- Data: 2026-04-06
-- Banco: MariaDB 10.x
-- Descrição:
--   - Remove valor_padrao, perc_rotina, perc_urgencia (valores agora são diretos)
--   - valor_rotina e valor_urgencia passam a ser valores DIRETOS do médico
--   - Adiciona campos de venda para Seção: perc_venda_rotina, perc_venda_urgencia,
--     valor_venda_rotina, valor_venda_urgencia
-- =============================================================================

-- 1. Adicionar novos campos de venda (Seção) com margem independente por tipo
-- Usamos PROCEDURE para compatibilidade com MariaDB (sem IF NOT EXISTS no ADD COLUMN)

DROP PROCEDURE IF EXISTS sp_migrate_tabela_exames_precos;

DELIMITER $$

CREATE PROCEDURE sp_migrate_tabela_exames_precos()
BEGIN
    -- Adicionar perc_venda_rotina
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'tabela_exames'
          AND COLUMN_NAME  = 'perc_venda_rotina'
    ) THEN
        ALTER TABLE `tabela_exames`
            ADD COLUMN `perc_venda_rotina` DECIMAL(8,4) NOT NULL DEFAULT 0.0000
            COMMENT 'Percentual de margem de lucro para rotina (Seção/venda)' AFTER `margem_lucro`;
    END IF;

    -- Adicionar perc_venda_urgencia
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'tabela_exames'
          AND COLUMN_NAME  = 'perc_venda_urgencia'
    ) THEN
        ALTER TABLE `tabela_exames`
            ADD COLUMN `perc_venda_urgencia` DECIMAL(8,4) NOT NULL DEFAULT 0.0000
            COMMENT 'Percentual de margem de lucro para urgência (Seção/venda)' AFTER `perc_venda_rotina`;
    END IF;

    -- Adicionar valor_venda_rotina
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'tabela_exames'
          AND COLUMN_NAME  = 'valor_venda_rotina'
    ) THEN
        ALTER TABLE `tabela_exames`
            ADD COLUMN `valor_venda_rotina` DECIMAL(12,2) NOT NULL DEFAULT 0.00
            COMMENT 'Valor de venda calculado para rotina (cliente)' AFTER `perc_venda_urgencia`;
    END IF;

    -- Adicionar valor_venda_urgencia
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'tabela_exames'
          AND COLUMN_NAME  = 'valor_venda_urgencia'
    ) THEN
        ALTER TABLE `tabela_exames`
            ADD COLUMN `valor_venda_urgencia` DECIMAL(12,2) NOT NULL DEFAULT 0.00
            COMMENT 'Valor de venda calculado para urgência (cliente)' AFTER `valor_venda_rotina`;
    END IF;

    -- Migrar dados existentes:
    -- valor_rotina e valor_urgencia já existem e passam a ser valores diretos do médico
    -- Zerar perc_rotina e perc_urgencia (não serão mais usados como percentual)
    -- Os valores diretos já estão em valor_rotina e valor_urgencia
    -- Inicializar valor_venda_rotina e valor_venda_urgencia com preco_venda (se existir)
    UPDATE `tabela_exames`
    SET
        `valor_venda_rotina`   = COALESCE(`preco_venda`, 0),
        `valor_venda_urgencia` = COALESCE(`preco_venda`, 0)
    WHERE `valor_venda_rotina` = 0 AND `valor_venda_urgencia` = 0;

END$$

DELIMITER ;

CALL sp_migrate_tabela_exames_precos();
DROP PROCEDURE IF EXISTS sp_migrate_tabela_exames_precos;

-- Verificação final
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'tabela_exames'
  AND COLUMN_NAME IN ('valor_rotina','valor_urgencia','perc_venda_rotina','perc_venda_urgencia','valor_venda_rotina','valor_venda_urgencia')
ORDER BY ORDINAL_POSITION;
