-- ============================================================
-- Migration: 2026-03-30_cnes_fix_co_cnes.sql
-- Objetivo: Preencher co_cnes retroativamente em equipamentos
--           e profissionais que foram importados antes da
--           correção do CnesImportService.
--
-- Problema: Registros importados com versões antigas do CSV
--           tinham co_cnes = NULL, impossibilitando a busca
--           por co_cnes no CnesController::show().
--
-- Solução:  UPDATE usando JOIN com cnes_estabelecimentos
--           via co_unidade para recuperar o co_cnes correto.
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

-- ─── 3. Índices para performance nas buscas por co_cnes ──────
-- Equipamentos
ALTER TABLE cnes_equipamentos
  ADD INDEX IF NOT EXISTS idx_co_cnes (co_cnes);

-- Profissionais
ALTER TABLE cnes_profissionais
  ADD INDEX IF NOT EXISTS idx_co_cnes (co_cnes);

-- ─── 4. Índice composto para busca por unidade + cnes ─────────
ALTER TABLE cnes_equipamentos
  ADD INDEX IF NOT EXISTS idx_unidade_cnes (co_unidade, co_cnes);

ALTER TABLE cnes_profissionais
  ADD INDEX IF NOT EXISTS idx_unidade_cnes (co_unidade, co_cnes);

-- ─── 5. Verificação (opcional) ───────────────────────────────
-- SELECT 'Equipamentos sem co_cnes' AS info, COUNT(*) AS total
--   FROM cnes_equipamentos WHERE co_cnes IS NULL;
-- SELECT 'Profissionais sem co_cnes' AS info, COUNT(*) AS total
--   FROM cnes_profissionais WHERE co_cnes IS NULL;
