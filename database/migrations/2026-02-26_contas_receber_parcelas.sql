-- ============================================================
-- Migration: Contas a Receber — Campos de Parcelas e Grupo
-- Data: 2026-02-26
-- Compatível com: MySQL 5.7+ e MariaDB 10.x+
-- Regra: ONLY ADD COLUMN. Nunca remover/renomear colunas existentes.
-- ============================================================
-- ATENÇÃO: Execute cada bloco separadamente se algum campo já existir.
-- Os campos abaixo são adicionados apenas se a tabela não os tiver ainda.

ALTER TABLE contas_receber
  ADD COLUMN numero_parcela  SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Número da parcela atual' AFTER asaas_subscription_id,
  ADD COLUMN total_parcelas  SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Total de parcelas do grupo' AFTER numero_parcela,
  ADD COLUMN grupo_parcelas  VARCHAR(64) NULL DEFAULT NULL COMMENT 'Identificador do grupo de parcelas' AFTER total_parcelas;

ALTER TABLE contas_receber
  ADD INDEX idx_cr_grupo_parcelas (grupo_parcelas);
