-- ============================================================
-- DIAGNÓSTICO CNES — Execute no phpMyAdmin para identificar
-- por que equipamentos e profissionais não aparecem
-- ============================================================

-- 1. Quantos registros existem nas tabelas?
SELECT 'cnes_estabelecimentos' AS tabela, COUNT(*) AS total FROM cnes_estabelecimentos
UNION ALL
SELECT 'cnes_equipamentos',    COUNT(*) FROM cnes_equipamentos
UNION ALL
SELECT 'cnes_profissionais',   COUNT(*) FROM cnes_profissionais;

-- 2. Verificar o estabelecimento 9170383 especificamente
SELECT co_cnes, co_unidade, no_razao_social, co_estado_gestor
FROM cnes_estabelecimentos
WHERE co_cnes = '9170383';

-- 3. Buscar equipamentos pelo co_cnes
SELECT COUNT(*) AS equip_por_co_cnes
FROM cnes_equipamentos
WHERE co_cnes = '9170383';

-- 4. Buscar equipamentos pelo co_unidade (pegar o co_unidade do resultado acima)
SELECT COUNT(*) AS equip_por_co_unidade
FROM cnes_equipamentos e
INNER JOIN cnes_estabelecimentos est ON est.co_unidade = e.co_unidade
WHERE est.co_cnes = '9170383';

-- 5. Buscar profissionais pelo co_cnes
SELECT COUNT(*) AS prof_por_co_cnes
FROM cnes_profissionais
WHERE co_cnes = '9170383';

-- 6. Buscar profissionais pelo co_unidade
SELECT COUNT(*) AS prof_por_co_unidade
FROM cnes_profissionais p
INNER JOIN cnes_estabelecimentos est ON est.co_unidade = p.co_unidade
WHERE est.co_cnes = '9170383';

-- 7. Ver os primeiros 5 equipamentos (qualquer um) para ver como os dados estão
SELECT id, co_cnes, co_unidade, co_equipamento, no_equipamento, co_tipo_equipamento
FROM cnes_equipamentos
LIMIT 5;

-- 8. Ver os primeiros 5 profissionais (qualquer um) para ver como os dados estão
SELECT id, co_cnes, co_unidade, co_profissional_sus, no_profissional, co_cbo
FROM cnes_profissionais
LIMIT 5;

-- 9. Verificar se co_cnes está NULL nos equipamentos
SELECT
    COUNT(*) AS total_equip,
    SUM(CASE WHEN co_cnes IS NULL THEN 1 ELSE 0 END) AS co_cnes_nulo,
    SUM(CASE WHEN co_cnes IS NOT NULL THEN 1 ELSE 0 END) AS co_cnes_preenchido
FROM cnes_equipamentos;

-- 10. Verificar se co_cnes está NULL nos profissionais
SELECT
    COUNT(*) AS total_prof,
    SUM(CASE WHEN co_cnes IS NULL THEN 1 ELSE 0 END) AS co_cnes_nulo,
    SUM(CASE WHEN co_cnes IS NOT NULL THEN 1 ELSE 0 END) AS co_cnes_preenchido
FROM cnes_profissionais;

-- 11. Ver o co_unidade do estabelecimento 9170383
SELECT co_unidade FROM cnes_estabelecimentos WHERE co_cnes = '9170383';

-- 12. Buscar equipamentos direto pelo co_unidade obtido acima
-- (substitua XXXXX pelo co_unidade do resultado da query 11)
-- SELECT * FROM cnes_equipamentos WHERE co_unidade = 'XXXXX' LIMIT 10;
-- SELECT * FROM cnes_profissionais WHERE co_unidade = 'XXXXX' LIMIT 10;
