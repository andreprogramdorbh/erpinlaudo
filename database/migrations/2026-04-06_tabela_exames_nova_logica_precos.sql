-- =============================================================================
-- Migration: Nova lógica de preços da tabela de exames
-- Data: 2026-04-06
-- Compatível com: MariaDB 10.3.2+ (ADD COLUMN IF NOT EXISTS nativo)
--
-- Descrição:
--   - valor_rotina e valor_urgencia passam a ser valores DIRETOS do médico
--   - Adiciona campos de venda (Seção): perc_venda_rotina, perc_venda_urgencia,
--     valor_venda_rotina, valor_venda_urgencia
--   - Margens independentes para rotina e urgência no preço de venda
-- =============================================================================

-- 1. Adicionar percentual de margem de lucro para rotina (Seção/venda)
ALTER TABLE `tabela_exames`
    ADD COLUMN IF NOT EXISTS `perc_venda_rotina` DECIMAL(8,4) NOT NULL DEFAULT 0.0000
    COMMENT 'Percentual de margem de lucro para rotina (Seção/venda)'
    AFTER `margem_lucro`;

-- 2. Adicionar percentual de margem de lucro para urgência (Seção/venda)
ALTER TABLE `tabela_exames`
    ADD COLUMN IF NOT EXISTS `perc_venda_urgencia` DECIMAL(8,4) NOT NULL DEFAULT 0.0000
    COMMENT 'Percentual de margem de lucro para urgência (Seção/venda)'
    AFTER `perc_venda_rotina`;

-- 3. Adicionar valor de venda calculado para rotina (cliente)
ALTER TABLE `tabela_exames`
    ADD COLUMN IF NOT EXISTS `valor_venda_rotina` DECIMAL(12,2) NOT NULL DEFAULT 0.00
    COMMENT 'Valor de venda calculado para rotina (cliente)'
    AFTER `perc_venda_urgencia`;

-- 4. Adicionar valor de venda calculado para urgência (cliente)
ALTER TABLE `tabela_exames`
    ADD COLUMN IF NOT EXISTS `valor_venda_urgencia` DECIMAL(12,2) NOT NULL DEFAULT 0.00
    COMMENT 'Valor de venda calculado para urgência (cliente)'
    AFTER `valor_venda_rotina`;

-- 5. Migrar dados existentes:
--    Inicializar valor_venda_rotina e valor_venda_urgencia com preco_venda (se existir)
--    Apenas para registros onde ainda não foram preenchidos
UPDATE `tabela_exames`
SET
    `valor_venda_rotina`   = COALESCE(`preco_venda`, 0),
    `valor_venda_urgencia` = COALESCE(`preco_venda`, 0)
WHERE `valor_venda_rotina` = 0
  AND `valor_venda_urgencia` = 0
  AND COALESCE(`preco_venda`, 0) > 0;

-- Verificação final
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'tabela_exames'
  AND COLUMN_NAME IN (
      'valor_rotina', 'valor_urgencia',
      'perc_venda_rotina', 'perc_venda_urgencia',
      'valor_venda_rotina', 'valor_venda_urgencia'
  )
ORDER BY ORDINAL_POSITION;
