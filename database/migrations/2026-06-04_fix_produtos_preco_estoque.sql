-- =============================================================================
-- Migração: 2026-06-04_fix_produtos_preco_estoque.sql
-- Sistema:  ERP inlaudo (ASOARESBH/erpinlaudo)
-- Data:     2026-06-04
-- Autor:    Manus AI
-- Motivo:   Correção de dados gravados incorretamente pelo bug no método toFloat()
--           do Produto.php — valores de preço podiam ser multiplicados por 10 ou 100
--           dependendo do formato enviado pelo formulário.
--           Correção aplicada no PHP: commit 36539f7
-- =============================================================================
--
-- ⚠️  VERIFICAÇÕES ANTES DE EXECUTAR:
--   1. Faça backup: CREATE TABLE produtos_bkp_20260604 SELECT * FROM produtos;
--   2. Execute em horário de baixo tráfego.
--   3. Confirme os dados suspeitos com o SELECT de diagnóstico abaixo ANTES
--      de executar qualquer UPDATE.
--   4. A tabela produtos NÃO precisa de novas colunas — elas já existem.
--      Este script apenas valida e corrige valores corrompidos.
--
-- =============================================================================


-- =============================================================================
-- PASSO 1 — BACKUP DE SEGURANÇA
-- Execute antes de qualquer alteração.
-- =============================================================================

CREATE TABLE `produtos_bkp_20260604`
SELECT * FROM `produtos`;


-- =============================================================================
-- PASSO 2 — DIAGNÓSTICO: identificar produtos com preços suspeitos
-- Critério: preco_venda > 10x o preco_custo (quando custo > 0)
-- Ajuste o multiplicador conforme a realidade do seu negócio.
-- =============================================================================

SELECT
    `id`,
    `codigo`,
    `nome`,
    `preco_custo`,
    `preco_venda`,
    `markup_percentual`,
    `estoque_atual`,
    `updated_at`,
    ROUND((`preco_venda` / NULLIF(`preco_custo`, 0)), 2) AS ratio_venda_custo
FROM `produtos`
WHERE
    `preco_custo`  > 0
    AND `preco_venda` > (`preco_custo` * 10)
ORDER BY ratio_venda_custo DESC;


-- =============================================================================
-- PASSO 3 — DIAGNÓSTICO: produtos com estoque_atual zerado mas que deveriam
-- ter saldo (criados antes do fix, quando o UPDATE não gravava estoque_atual)
-- =============================================================================

SELECT
    `id`,
    `codigo`,
    `nome`,
    `controla_estoque`,
    `estoque_atual`,
    `estoque_minimo`,
    `updated_at`
FROM `produtos`
WHERE
    `controla_estoque` = 1
    AND `estoque_atual` = 0
ORDER BY `updated_at` DESC;


-- =============================================================================
-- PASSO 4 — CORREÇÃO: normalizar preco_venda dividindo por 10
-- Execute SOMENTE para os produtos identificados no PASSO 2 como corrompidos.
-- Substitua os IDs reais identificados no diagnóstico.
--
-- ATENÇÃO: NÃO execute este bloco em massa sem revisar o diagnóstico acima.
--          Cada produto deve ser analisado individualmente.
-- =============================================================================

-- Exemplo para um produto específico (substitua o ID real):
-- UPDATE `produtos`
-- SET
--     `preco_custo`       = ROUND(`preco_custo`       / 10, 4),
--     `preco_venda`       = ROUND(`preco_venda`       / 10, 4),
--     `preco_minimo_venda`= ROUND(`preco_minimo_venda`/ 10, 4),
--     `preco_sugerido`    = ROUND(`preco_sugerido`    / 10, 4),
--     `despesas_acessorias`= ROUND(`despesas_acessorias`/ 10, 4),
--     `updated_at`        = NOW()
-- WHERE `id` = <ID_DO_PRODUTO>;


-- =============================================================================
-- PASSO 5 — CORREÇÃO: ajuste manual de estoque_atual para produtos zerados
-- Execute somente após confirmar o saldo real de cada produto.
-- Substitua os valores reais de estoque.
-- =============================================================================

-- Exemplo para um produto específico (substitua ID e quantidade real):
-- UPDATE `produtos`
-- SET
--     `estoque_atual` = <QUANTIDADE_REAL>,
--     `updated_at`    = NOW()
-- WHERE `id` = <ID_DO_PRODUTO>;


-- =============================================================================
-- VALIDAÇÃO FINAL — Execute após as correções para confirmar os resultados
-- =============================================================================

-- 1. Contagem geral de produtos
SELECT COUNT(*) AS total_produtos FROM `produtos`;

-- 2. Distribuição de status
SELECT `status`, COUNT(*) AS qtd
FROM `produtos`
GROUP BY `status`;

-- 3. Produtos com preço de venda zerado (possível dado incompleto)
SELECT COUNT(*) AS produtos_sem_preco
FROM `produtos`
WHERE `preco_venda` = 0 AND `status` = 'ativo';

-- 4. Produtos com controle de estoque ativo e saldo zerado
SELECT COUNT(*) AS produtos_estoque_zerado
FROM `produtos`
WHERE `controla_estoque` = 1 AND `estoque_atual` = 0;

-- 5. Verificar se ainda existem preços suspeitos (ratio > 10x)
SELECT COUNT(*) AS precos_suspeitos_restantes
FROM `produtos`
WHERE `preco_custo` > 0 AND `preco_venda` > (`preco_custo` * 10);

-- 6. Resumo financeiro para conferência
SELECT
    COUNT(*)                            AS total,
    MIN(`preco_venda`)                  AS menor_preco_venda,
    MAX(`preco_venda`)                  AS maior_preco_venda,
    ROUND(AVG(`preco_venda`), 2)        AS media_preco_venda,
    SUM(`estoque_atual`)                AS estoque_total_unidades
FROM `produtos`
WHERE `status` = 'ativo';


-- =============================================================================
-- ROLLBACK — Restaurar dados do backup caso necessário
-- Execute APENAS se as correções causarem problemas.
-- =============================================================================

/*
-- ROLLBACK COMPLETO: restaurar todos os dados do backup
UPDATE `produtos` p
INNER JOIN `produtos_bkp_20260604` b ON p.id = b.id
SET
    p.preco_custo        = b.preco_custo,
    p.preco_venda        = b.preco_venda,
    p.preco_minimo_venda = b.preco_minimo_venda,
    p.preco_sugerido     = b.preco_sugerido,
    p.despesas_acessorias= b.despesas_acessorias,
    p.markup_percentual  = b.markup_percentual,
    p.estoque_atual      = b.estoque_atual,
    p.updated_at         = b.updated_at;

-- Após confirmar que o rollback funcionou, remover o backup:
-- DROP TABLE `produtos_bkp_20260604`;
*/
