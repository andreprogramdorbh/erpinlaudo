-- ============================================================
-- Migration: Atualizar no_cbo nos profissionais já importados
-- Preenche o campo no_cbo com a descrição da tabela cnes_dom_cbo
-- para todos os registros que ainda não têm a descrição
-- ============================================================

-- Atualizar no_cbo dos profissionais existentes usando JOIN com cnes_dom_cbo
UPDATE cnes_profissionais p
INNER JOIN cnes_dom_cbo d ON d.co_cbo = p.co_cbo
SET p.no_cbo = d.no_cbo
WHERE p.no_cbo IS NULL OR p.no_cbo = p.co_cbo OR p.no_cbo = '';

-- Verificar resultado
SELECT
    COUNT(*) AS total_profissionais,
    SUM(CASE WHEN no_cbo IS NULL THEN 1 ELSE 0 END) AS sem_descricao_cbo,
    SUM(CASE WHEN no_cbo IS NOT NULL AND no_cbo != co_cbo THEN 1 ELSE 0 END) AS com_descricao_cbo
FROM cnes_profissionais;
