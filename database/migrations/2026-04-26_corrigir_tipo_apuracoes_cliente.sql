-- =============================================================================
-- Migration: Corrigir tipo das apurações de contratos de cliente
-- Data: 2026-04-26
-- Problema: Apurações criadas a partir de contratos com tipo_parte='cliente'
--           foram salvas com tipo='prestador' (valor padrão antigo).
--           Isso fazia com que a tela "Apuração Cliente" ficasse vazia.
-- Solução: Atualizar o campo tipo para 'cliente' nas apurações cujo contrato
--          tem tipo_parte='cliente'.
-- =============================================================================

-- Diagnóstico: ver quantas apurações serão corrigidas
-- SELECT a.id, a.numero, a.tipo, c.tipo_parte, c.nome AS contrato
-- FROM apuracoes a
-- INNER JOIN contratos c ON c.id = a.contrato_id
-- WHERE c.tipo_parte = 'cliente' AND a.tipo = 'prestador';

-- Correção: atualizar tipo para 'cliente' onde o contrato é de cliente
UPDATE apuracoes a
INNER JOIN contratos c ON c.id = a.contrato_id
SET a.tipo = 'cliente'
WHERE c.tipo_parte = 'cliente'
  AND a.tipo = 'prestador';

-- Verificação pós-correção
-- SELECT tipo, COUNT(*) AS total FROM apuracoes GROUP BY tipo;
