-- ============================================================
-- Migration: 2026-03-30_cnes_fix_co_cnes.sql
-- Compatível com: MariaDB 10.x / MySQL 5.7+
--
-- Objetivo: Preencher co_cnes retroativamente em equipamentos
--           e profissionais importados antes da correção do
--           CnesImportService (co_cnes ficava NULL).
-- ============================================================

-- ─── 1. Preencher co_cnes nos equipamentos ───────────────────
UPDATE cnes_equipamentos e
INNER JOIN cnes_estabelecimentos est ON est.co_unidade = e.co_unidade
SET e.co_cnes = est.co_cnes
WHERE e.co_cnes IS NULL
  AND est.co_cnes IS NOT NULL;

-- ─── 2. Preencher co_cnes nos profissionais ──────────────────
UPDATE cnes_profissionais p
INNER JOIN cnes_estabelecimentos est ON est.co_unidade = p.co_unidade
SET p.co_cnes = est.co_cnes
WHERE p.co_cnes IS NULL
  AND est.co_cnes IS NOT NULL;

-- ─── 3. Índices para performance (MariaDB — sem IF NOT EXISTS) ─
-- Execute cada bloco separadamente se um índice já existir.

-- Equipamentos: índice simples por co_cnes
SET @exist := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'cnes_equipamentos'
      AND INDEX_NAME   = 'idx_co_cnes'
);
SET @sql := IF(@exist = 0,
    'ALTER TABLE cnes_equipamentos ADD INDEX idx_co_cnes (co_cnes)',
    'SELECT "idx_co_cnes em cnes_equipamentos ja existe"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Equipamentos: índice composto co_unidade + co_cnes
SET @exist := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'cnes_equipamentos'
      AND INDEX_NAME   = 'idx_unidade_cnes'
);
SET @sql := IF(@exist = 0,
    'ALTER TABLE cnes_equipamentos ADD INDEX idx_unidade_cnes (co_unidade, co_cnes)',
    'SELECT "idx_unidade_cnes em cnes_equipamentos ja existe"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Profissionais: índice simples por co_cnes
SET @exist := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'cnes_profissionais'
      AND INDEX_NAME   = 'idx_co_cnes'
);
SET @sql := IF(@exist = 0,
    'ALTER TABLE cnes_profissionais ADD INDEX idx_co_cnes (co_cnes)',
    'SELECT "idx_co_cnes em cnes_profissionais ja existe"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Profissionais: índice composto co_unidade + co_cnes
SET @exist := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'cnes_profissionais'
      AND INDEX_NAME   = 'idx_unidade_cnes'
);
SET @sql := IF(@exist = 0,
    'ALTER TABLE cnes_profissionais ADD INDEX idx_unidade_cnes (co_unidade, co_cnes)',
    'SELECT "idx_unidade_cnes em cnes_profissionais ja existe"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ─── 4. Verificação (opcional — descomente para checar) ──────
-- SELECT 'Equipamentos sem co_cnes' AS info, COUNT(*) AS total
--   FROM cnes_equipamentos WHERE co_cnes IS NULL;
-- SELECT 'Profissionais sem co_cnes' AS info, COUNT(*) AS total
--   FROM cnes_profissionais WHERE co_cnes IS NULL;
