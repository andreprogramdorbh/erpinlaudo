-- =============================================================================
-- Migração: Adicionar marca, modelo e vida_util_meses na tabela manut_ordens_servico
-- Data: 2026-06-06 | Sistema: ERP InLaudo
-- Motivo: Campos necessários para importação automática do cadastro de produtos
--         ao selecionar equipamento na Ordem de Serviço.
-- =============================================================================

-- ⚠️ VERIFICAÇÕES ANTES DE EXECUTAR:
-- 1. Confirme que a coluna NÃO existe: SHOW COLUMNS FROM manut_ordens_servico LIKE 'marca';
-- 2. Confirme que a coluna NÃO existe: SHOW COLUMNS FROM manut_ordens_servico LIKE 'modelo';
-- 3. Confirme que a coluna NÃO existe: SHOW COLUMNS FROM manut_ordens_servico LIKE 'vida_util_meses';
-- 4. Faça backup: CREATE TABLE manut_ordens_servico_bkp_20260606 SELECT * FROM manut_ordens_servico;

-- ─── PASSO 1: Adicionar colunas ───────────────────────────────────────────────

ALTER TABLE `manut_ordens_servico`
  ADD COLUMN `marca`           VARCHAR(100) NULL COMMENT 'Marca do equipamento (importada do cadastro de produtos)'
  AFTER `numero_serie`;

ALTER TABLE `manut_ordens_servico`
  ADD COLUMN `modelo`          VARCHAR(100) NULL COMMENT 'Modelo do equipamento (importado do cadastro de produtos)'
  AFTER `marca`;

ALTER TABLE `manut_ordens_servico`
  ADD COLUMN `vida_util_meses` INT(11)      NULL COMMENT 'Vida útil em meses (importada do cadastro de produtos)'
  AFTER `modelo`;

-- ─── PASSO 2: Índices ────────────────────────────────────────────────────────
-- (Nenhum índice necessário para estes campos — são apenas informativos)

-- ─── VALIDAÇÃO ───────────────────────────────────────────────────────────────
SHOW COLUMNS FROM `manut_ordens_servico` LIKE 'marca';
SHOW COLUMNS FROM `manut_ordens_servico` LIKE 'modelo';
SHOW COLUMNS FROM `manut_ordens_servico` LIKE 'vida_util_meses';

SELECT COUNT(*) AS total_ordens FROM `manut_ordens_servico`;

-- ─── ROLLBACK ────────────────────────────────────────────────────────────────
-- Para desfazer, execute:
-- ALTER TABLE `manut_ordens_servico` DROP COLUMN `vida_util_meses`;
-- ALTER TABLE `manut_ordens_servico` DROP COLUMN `modelo`;
-- ALTER TABLE `manut_ordens_servico` DROP COLUMN `marca`;
