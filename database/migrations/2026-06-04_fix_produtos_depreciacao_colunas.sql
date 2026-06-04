-- =============================================================================
-- MIGRAÇÃO: 2026-06-04_fix_produtos_depreciacao_colunas.sql
-- Sistema:  ERP inlaudo
-- Objetivo: Adicionar colunas de depreciação na tabela `produtos`
--           caso não existam (compatível MySQL 5.7 / Hostgator)
-- Autor:    Manus AI
-- Data:     2026-06-04
-- =============================================================================
-- ATENÇÃO: Execute cada bloco separadamente no phpMyAdmin.
--          Antes de executar, verifique quais colunas já existem:
--
--   SHOW COLUMNS FROM `produtos` LIKE 'controla_depreciacao';
--   SHOW COLUMNS FROM `produtos` LIKE 'vida_util_meses';
--   SHOW COLUMNS FROM `produtos` LIKE 'valor_residual';
--   SHOW COLUMNS FROM `produtos` LIKE 'metodo_depreciacao';
--   SHOW COLUMNS FROM `produtos` LIKE 'depreciacao_mensal';
--   SHOW COLUMNS FROM `produtos` LIKE 'alerta_substituicao_meses';
--
--   Se o resultado for vazio (0 rows), execute o ALTER TABLE correspondente.
--   Se já existir, COMENTE a linha antes de executar.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- PASSO 1 — Backup de segurança (execute PRIMEIRO)
-- -----------------------------------------------------------------------------
CREATE TABLE `produtos_bkp_deprec_20260604`
SELECT `id`, `usuario_id`, `nome`,
       `controla_depreciacao`,
       `vida_util_meses`,
       `valor_residual`,
       `metodo_depreciacao`,
       `depreciacao_mensal`,
       `alerta_substituicao_meses`
FROM `produtos`;

-- Verificar backup:
-- SELECT COUNT(*) AS total_backup FROM `produtos_bkp_deprec_20260604`;

-- -----------------------------------------------------------------------------
-- PASSO 2 — Adicionar coluna controla_depreciacao
-- (COMENTE se já existir — verificar com SHOW COLUMNS acima)
-- -----------------------------------------------------------------------------
ALTER TABLE `produtos`
ADD COLUMN `controla_depreciacao` TINYINT(1) NOT NULL DEFAULT 0
COMMENT 'Ativa controle de depreciação para o produto'
AFTER `lote_obrigatorio`;

-- -----------------------------------------------------------------------------
-- PASSO 3 — Adicionar coluna vida_util_meses
-- (COMENTE se já existir)
-- -----------------------------------------------------------------------------
ALTER TABLE `produtos`
ADD COLUMN `vida_util_meses` INT(11) NULL DEFAULT NULL
COMMENT 'Vida útil em meses (ex: 60 = 5 anos)'
AFTER `controla_depreciacao`;

-- -----------------------------------------------------------------------------
-- PASSO 4 — Adicionar coluna valor_residual
-- (COMENTE se já existir)
-- -----------------------------------------------------------------------------
ALTER TABLE `produtos`
ADD COLUMN `valor_residual` DECIMAL(15,4) NULL DEFAULT 0.0000
COMMENT 'Valor ao final da vida útil'
AFTER `vida_util_meses`;

-- -----------------------------------------------------------------------------
-- PASSO 5 — Adicionar coluna metodo_depreciacao
-- (COMENTE se já existir)
-- -----------------------------------------------------------------------------
ALTER TABLE `produtos`
ADD COLUMN `metodo_depreciacao` ENUM('linear','soma_digitos','unidades_produzidas')
NOT NULL DEFAULT 'linear'
COMMENT 'Método de cálculo da depreciação'
AFTER `valor_residual`;

-- -----------------------------------------------------------------------------
-- PASSO 6 — Adicionar coluna depreciacao_mensal
-- (COMENTE se já existir)
-- -----------------------------------------------------------------------------
ALTER TABLE `produtos`
ADD COLUMN `depreciacao_mensal` DECIMAL(15,4) NULL DEFAULT 0.0000
COMMENT 'Valor mensal calculado automaticamente'
AFTER `metodo_depreciacao`;

-- -----------------------------------------------------------------------------
-- PASSO 7 — Adicionar coluna alerta_substituicao_meses
-- (COMENTE se já existir)
-- -----------------------------------------------------------------------------
ALTER TABLE `produtos`
ADD COLUMN `alerta_substituicao_meses` INT(11) NULL DEFAULT NULL
COMMENT 'Meses antes do fim da vida útil para sugerir troca'
AFTER `depreciacao_mensal`;

-- -----------------------------------------------------------------------------
-- PASSO 8 — Índice para consultas de depreciação
-- (COMENTE se já existir)
-- -----------------------------------------------------------------------------
ALTER TABLE `produtos`
ADD INDEX `idx_produtos_deprec` (`usuario_id`, `controla_depreciacao`);

-- =============================================================================
-- VALIDAÇÃO — Execute após os passos acima
-- =============================================================================

-- 1. Confirmar que as colunas existem:
SHOW COLUMNS FROM `produtos` WHERE Field IN (
    'controla_depreciacao',
    'vida_util_meses',
    'valor_residual',
    'metodo_depreciacao',
    'depreciacao_mensal',
    'alerta_substituicao_meses'
);

-- 2. Confirmar contagem de produtos com depreciação ativa:
SELECT
    COUNT(*) AS total_produtos,
    SUM(`controla_depreciacao` = 1) AS com_depreciacao,
    SUM(`controla_depreciacao` = 0) AS sem_depreciacao
FROM `produtos`;

-- 3. Verificar produtos com vida_util preenchida:
SELECT `id`, `nome`, `controla_depreciacao`, `vida_util_meses`, `depreciacao_mensal`
FROM `produtos`
WHERE `controla_depreciacao` = 1
LIMIT 10;

-- =============================================================================
-- ROLLBACK — Execute apenas se precisar desfazer
-- =============================================================================
/*
ALTER TABLE `produtos` DROP INDEX `idx_produtos_deprec`;
ALTER TABLE `produtos` DROP COLUMN `alerta_substituicao_meses`;
ALTER TABLE `produtos` DROP COLUMN `depreciacao_mensal`;
ALTER TABLE `produtos` DROP COLUMN `metodo_depreciacao`;
ALTER TABLE `produtos` DROP COLUMN `valor_residual`;
ALTER TABLE `produtos` DROP COLUMN `vida_util_meses`;
ALTER TABLE `produtos` DROP COLUMN `controla_depreciacao`;
DROP TABLE IF EXISTS `produtos_bkp_deprec_20260604`;
*/
