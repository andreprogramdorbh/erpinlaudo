-- =============================================================================
-- Migration: Novo fluxo de apuração — apuração-mãe (cliente) + sub-apurações (prestador)
-- Data: 2026-04-26
-- Compatível com: MariaDB 10.3.2+
--
-- NOVO FLUXO:
--   1. Toda apuração nasce como tipo='cliente' (apuração-mãe)
--   2. Ao executar a planilha, o sistema separa os itens por médico/CRM
--   3. Para cada médico encontrado, cria automaticamente uma sub-apuração
--      tipo='prestador' vinculada à apuração-mãe via apuracao_mae_id
--   4. Os itens da apuração-mãe ficam com valor_calculado_venda (venda ao cliente)
--   5. Os itens de cada sub-apuração ficam com valor_calculado (custo ao prestador)
-- =============================================================================

-- 1. Adicionar campo apuracao_mae_id na tabela apuracoes
--    NULL = apuração independente (apuração-mãe de cliente ou prestador legado)
--    NOT NULL = sub-apuração de prestador gerada automaticamente
ALTER TABLE `apuracoes`
    ADD COLUMN IF NOT EXISTS `apuracao_mae_id` INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'ID da apuração-mãe (cliente) que gerou esta sub-apuração de prestador'
    AFTER `contrato_id`;

-- 2. Adicionar índice para busca rápida de sub-apurações por mãe
ALTER TABLE `apuracoes`
    ADD INDEX IF NOT EXISTS `idx_apuracoes_mae` (`apuracao_mae_id`);

-- 3. Adicionar FK (opcional — pode ser removida se causar problemas de ordem de execução)
-- ALTER TABLE `apuracoes`
--     ADD CONSTRAINT `fk_apuracoes_mae`
--     FOREIGN KEY (`apuracao_mae_id`) REFERENCES `apuracoes`(`id`) ON DELETE CASCADE;

-- 4. Adicionar campo medico_id_prestador em apuracao_itens para rastrear o médico de cada item
--    (já existe medico_nome e medico_crm como texto — este campo é a FK para o médico cadastrado)
ALTER TABLE `apuracao_itens`
    ADD COLUMN IF NOT EXISTS `medico_id_match` INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'FK para medicos.id — médico identificado pelo CRM na planilha'
    AFTER `medico_crm`;

-- 5. Corrigir apurações existentes de contratos de cliente que ficaram com tipo='prestador'
--    (já coberto pela migration 2026-04-26_corrigir_tipo_apuracoes_cliente.sql,
--     mas repetido aqui para garantir consistência em ambientes que não executaram aquela)
UPDATE `apuracoes` a
INNER JOIN `contratos` c ON c.id = a.contrato_id
SET a.tipo = 'cliente'
WHERE c.tipo_parte = 'cliente'
  AND a.tipo = 'prestador'
  AND a.apuracao_mae_id IS NULL;

-- Verificação
-- SELECT tipo, COUNT(*) AS total, SUM(apuracao_mae_id IS NOT NULL) AS sub_apuracoes
-- FROM apuracoes GROUP BY tipo;
