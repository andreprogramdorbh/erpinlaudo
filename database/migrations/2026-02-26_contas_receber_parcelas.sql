-- ============================================================
-- Migration: Contas a Receber — Campos de Parcelas e Grupo
-- Data: 2026-02-26
-- Compatível com: MariaDB 10.0.2+ (ADD COLUMN IF NOT EXISTS)
--                 MariaDB 10.1.4+ (CREATE INDEX IF NOT EXISTS)
-- Regra: ONLY ADD COLUMN. Nunca remover/renomear colunas existentes.
-- ============================================================

ALTER TABLE contas_receber
  ADD COLUMN IF NOT EXISTS numero_parcela SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Número da parcela atual'          AFTER asaas_subscription_id,
  ADD COLUMN IF NOT EXISTS total_parcelas SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Total de parcelas do grupo'       AFTER numero_parcela,
  ADD COLUMN IF NOT EXISTS grupo_parcelas VARCHAR(64)       NULL DEFAULT NULL COMMENT 'Identificador do grupo de parcelas' AFTER total_parcelas;

-- Índice criado separadamente com IF NOT EXISTS para idempotência
CREATE INDEX IF NOT EXISTS idx_cr_grupo_parcelas ON contas_receber (grupo_parcelas);
