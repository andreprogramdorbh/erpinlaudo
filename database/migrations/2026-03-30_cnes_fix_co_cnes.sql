-- ============================================================
-- Migration: 2026-03-30_cnes_fix_co_cnes.sql
-- Compatível com: MariaDB 10.1.4+ (CREATE INDEX IF NOT EXISTS)
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

-- ─── 3. Índices para performance (MariaDB 10.1.4+ nativo) ────
-- CREATE INDEX IF NOT EXISTS é idempotente — seguro executar múltiplas vezes.

CREATE INDEX IF NOT EXISTS idx_co_cnes       ON cnes_equipamentos  (co_cnes);
CREATE INDEX IF NOT EXISTS idx_unidade_cnes  ON cnes_equipamentos  (co_unidade, co_cnes);
CREATE INDEX IF NOT EXISTS idx_co_cnes       ON cnes_profissionais (co_cnes);
CREATE INDEX IF NOT EXISTS idx_unidade_cnes  ON cnes_profissionais (co_unidade, co_cnes);

-- ─── 4. Verificação (opcional — descomente para checar) ──────
-- SELECT 'Equipamentos sem co_cnes' AS info, COUNT(*) AS total
--   FROM cnes_equipamentos WHERE co_cnes IS NULL;
-- SELECT 'Profissionais sem co_cnes' AS info, COUNT(*) AS total
--   FROM cnes_profissionais WHERE co_cnes IS NULL;
