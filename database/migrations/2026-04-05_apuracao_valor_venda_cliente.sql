-- =============================================================================
-- Migration: Apuração — Campos de valor de venda (cliente) e vínculo de cliente
-- Data: 2026-04-05
-- Compatível com: MariaDB 10.0.2+ (ADD COLUMN IF NOT EXISTS)
-- =============================================================================

-- 1. Adicionar valor_custo_total e valor_venda_total na tabela apuracoes
--    valor_total  = valor de custo (repasse ao médico/prestador)
--    valor_venda_total = valor de venda (cobrado do cliente)
ALTER TABLE `apuracoes`
  ADD COLUMN IF NOT EXISTS `valor_venda_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00
    COMMENT 'Valor total de venda (preço cobrado do cliente)' AFTER `valor_total`;

-- 2. Adicionar valor_calculado_venda na tabela apuracao_itens
--    valor_calculado      = valor de custo por item (repasse ao médico)
--    valor_calculado_venda = valor de venda por item (cobrado do cliente)
ALTER TABLE `apuracao_itens`
  ADD COLUMN IF NOT EXISTS `valor_calculado_venda` DECIMAL(12,2) NOT NULL DEFAULT 0.00
    COMMENT 'Valor de venda calculado por item (preço de venda da tabela de exames)' AFTER `valor_calculado`;
