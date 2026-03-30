-- ============================================================
-- DIAGNÓSTICO CNES — Execute no phpMyAdmin (aba SQL)
-- Cada bloco pode ser executado separadamente
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- BLOCO 1: Contagem geral das tabelas CNES
-- Esperado: cnes_estabelecimentos > 0, equipamentos > 0, profissionais > 0
-- ─────────────────────────────────────────────────────────────
SELECT 'cnes_estabelecimentos' AS tabela, COUNT(*) AS total_registros FROM cnes_estabelecimentos
UNION ALL
SELECT 'cnes_equipamentos',              COUNT(*)                     FROM cnes_equipamentos
UNION ALL
SELECT 'cnes_profissionais',             COUNT(*)                     FROM cnes_profissionais;


-- ─────────────────────────────────────────────────────────────
-- BLOCO 2: Dados do estabelecimento 9170383
-- Mostra co_unidade, co_cnes e co_estado_gestor (sigla ou código IBGE?)
-- ─────────────────────────────────────────────────────────────
SELECT
    id,
    co_cnes,
    co_unidade,
    no_razao_social,
    no_fantasia,
    co_estado_gestor,
    co_municipio_gestor,
    competencia
FROM cnes_estabelecimentos
WHERE co_cnes = '9170383';


-- ─────────────────────────────────────────────────────────────
-- BLOCO 3: Equipamentos — situação do campo co_cnes
-- Se co_cnes_nulo for alto, a migration de fix não rodou corretamente
-- ─────────────────────────────────────────────────────────────
SELECT
    COUNT(*)                                                      AS total_equipamentos,
    SUM(CASE WHEN co_cnes IS NULL     THEN 1 ELSE 0 END)          AS co_cnes_nulo,
    SUM(CASE WHEN co_cnes IS NOT NULL THEN 1 ELSE 0 END)          AS co_cnes_preenchido,
    SUM(CASE WHEN co_unidade IS NULL  THEN 1 ELSE 0 END)          AS co_unidade_nulo
FROM cnes_equipamentos;


-- ─────────────────────────────────────────────────────────────
-- BLOCO 4: Profissionais — situação do campo co_cnes
-- ─────────────────────────────────────────────────────────────
SELECT
    COUNT(*)                                                      AS total_profissionais,
    SUM(CASE WHEN co_cnes IS NULL     THEN 1 ELSE 0 END)          AS co_cnes_nulo,
    SUM(CASE WHEN co_cnes IS NOT NULL THEN 1 ELSE 0 END)          AS co_cnes_preenchido,
    SUM(CASE WHEN co_unidade IS NULL  THEN 1 ELSE 0 END)          AS co_unidade_nulo
FROM cnes_profissionais;


-- ─────────────────────────────────────────────────────────────
-- BLOCO 5: Buscar equipamentos do CNES 9170383 por co_cnes
-- Se retornar 0, os equipamentos não foram importados OU co_cnes está NULL
-- ─────────────────────────────────────────────────────────────
SELECT
    e.id,
    e.co_cnes,
    e.co_unidade,
    e.co_equipamento,
    e.no_equipamento,
    e.co_tipo_equipamento,
    e.no_tipo_equipamento,
    e.qt_existente,
    e.qt_uso
FROM cnes_equipamentos e
WHERE e.co_cnes = '9170383'
ORDER BY e.co_tipo_equipamento, e.no_equipamento;


-- ─────────────────────────────────────────────────────────────
-- BLOCO 6: Buscar equipamentos por co_unidade (via JOIN)
-- Alternativa caso co_cnes esteja NULL nos equipamentos
-- ─────────────────────────────────────────────────────────────
SELECT
    e.id,
    e.co_cnes,
    e.co_unidade,
    e.co_equipamento,
    e.no_equipamento,
    e.co_tipo_equipamento,
    e.qt_existente
FROM cnes_equipamentos e
INNER JOIN cnes_estabelecimentos est ON est.co_unidade = e.co_unidade
WHERE est.co_cnes = '9170383';


-- ─────────────────────────────────────────────────────────────
-- BLOCO 7: Buscar profissionais do CNES 9170383 por co_cnes
-- ─────────────────────────────────────────────────────────────
SELECT
    p.id,
    p.co_cnes,
    p.co_unidade,
    p.co_profissional_sus,
    p.no_profissional,
    p.co_cbo,
    p.no_cbo,
    p.co_conselho_classe,
    p.nu_registro,
    p.sg_uf_crm,
    p.qt_carga_horaria_amb
FROM cnes_profissionais p
WHERE p.co_cnes = '9170383'
ORDER BY p.no_profissional
LIMIT 50;


-- ─────────────────────────────────────────────────────────────
-- BLOCO 8: Buscar profissionais por co_unidade (via JOIN)
-- ─────────────────────────────────────────────────────────────
SELECT
    p.id,
    p.co_cnes,
    p.co_unidade,
    p.no_profissional,
    p.co_cbo,
    p.no_cbo,
    p.co_conselho_classe,
    p.nu_registro
FROM cnes_profissionais p
INNER JOIN cnes_estabelecimentos est ON est.co_unidade = p.co_unidade
WHERE est.co_cnes = '9170383'
ORDER BY p.no_profissional
LIMIT 50;


-- ─────────────────────────────────────────────────────────────
-- BLOCO 9: Ver amostra dos equipamentos importados (qualquer um)
-- Para confirmar como co_unidade e co_cnes estão salvos no banco
-- ─────────────────────────────────────────────────────────────
SELECT
    id,
    co_cnes,
    co_unidade,
    co_equipamento,
    no_equipamento,
    co_tipo_equipamento,
    competencia
FROM cnes_equipamentos
ORDER BY id DESC
LIMIT 10;


-- ─────────────────────────────────────────────────────────────
-- BLOCO 10: Ver amostra dos profissionais importados (qualquer um)
-- ─────────────────────────────────────────────────────────────
SELECT
    id,
    co_cnes,
    co_unidade,
    no_profissional,
    co_cbo,
    no_cbo,
    competencia
FROM cnes_profissionais
ORDER BY id DESC
LIMIT 10;


-- ─────────────────────────────────────────────────────────────
-- BLOCO 11: Cruzamento — quantos co_unidade de equipamentos
-- têm correspondência em cnes_estabelecimentos?
-- Se retornar 0, os co_unidade não batem com os estabelecimentos
-- ─────────────────────────────────────────────────────────────
SELECT
    COUNT(DISTINCT e.co_unidade)                                   AS unidades_em_equipamentos,
    COUNT(DISTINCT est.co_unidade)                                 AS unidades_com_match_estab,
    COUNT(DISTINCT e.co_unidade) - COUNT(DISTINCT est.co_unidade)  AS unidades_sem_match
FROM cnes_equipamentos e
LEFT JOIN cnes_estabelecimentos est ON est.co_unidade = e.co_unidade;


-- ─────────────────────────────────────────────────────────────
-- BLOCO 12: Cruzamento — quantos co_unidade de profissionais
-- têm correspondência em cnes_estabelecimentos?
-- ─────────────────────────────────────────────────────────────
SELECT
    COUNT(DISTINCT p.co_unidade)                                   AS unidades_em_profissionais,
    COUNT(DISTINCT est.co_unidade)                                 AS unidades_com_match_estab,
    COUNT(DISTINCT p.co_unidade) - COUNT(DISTINCT est.co_unidade)  AS unidades_sem_match
FROM cnes_profissionais p
LEFT JOIN cnes_estabelecimentos est ON est.co_unidade = p.co_unidade;


-- ─────────────────────────────────────────────────────────────
-- BLOCO 13: Verificar se existe algum equipamento cujo
-- co_unidade bate com algum estabelecimento importado
-- (mostra os 5 primeiros matches)
-- ─────────────────────────────────────────────────────────────
SELECT
    est.co_cnes,
    est.co_unidade,
    est.no_razao_social,
    COUNT(e.id) AS qtd_equipamentos
FROM cnes_estabelecimentos est
INNER JOIN cnes_equipamentos e ON e.co_unidade = est.co_unidade
GROUP BY est.co_cnes, est.co_unidade, est.no_razao_social
ORDER BY qtd_equipamentos DESC
LIMIT 10;


-- ─────────────────────────────────────────────────────────────
-- BLOCO 14: Mesmo para profissionais
-- ─────────────────────────────────────────────────────────────
SELECT
    est.co_cnes,
    est.co_unidade,
    est.no_razao_social,
    COUNT(p.id) AS qtd_profissionais
FROM cnes_estabelecimentos est
INNER JOIN cnes_profissionais p ON p.co_unidade = est.co_unidade
GROUP BY est.co_cnes, est.co_unidade, est.no_razao_social
ORDER BY qtd_profissionais DESC
LIMIT 10;
