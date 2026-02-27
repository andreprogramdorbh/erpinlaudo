-- ============================================================
-- Migration: Contas a Receber — Campos de Parcelas e Grupo
-- Data: 2026-02-26
-- Regra: ONLY ADD COLUMN. Nunca remover/renomear colunas existentes.
-- ============================================================
-- Adiciona campos para controle de parcelamento em contas a receber.
-- numero_parcela: número da parcela atual (ex: 1, 2, 3...)
-- total_parcelas: total de parcelas do grupo (ex: 3)
-- grupo_parcelas: identificador único do grupo de parcelas (UUID ou hash)
--                 permite agrupar todas as parcelas de um mesmo contrato/venda

ALTER TABLE contas_receber
  ADD COLUMN IF NOT EXISTS numero_parcela  SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Número da parcela atual' AFTER asaas_subscription_id,
  ADD COLUMN IF NOT EXISTS total_parcelas  SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Total de parcelas do grupo' AFTER numero_parcela,
  ADD COLUMN IF NOT EXISTS grupo_parcelas  VARCHAR(64) NULL DEFAULT NULL COMMENT 'Identificador do grupo de parcelas' AFTER total_parcelas,
  ADD INDEX IF NOT EXISTS idx_cr_grupo_parcelas (grupo_parcelas);
