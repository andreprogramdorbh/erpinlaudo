-- Migration: Financeiro - Contas a Receber (Meios de Pagamento + Recorrencia + Asaas refs)
-- Date: 2026-02-05
-- Rules: ONLY ADD COLUMN. Never drop/rename existing columns.

ALTER TABLE contas_receber
  ADD COLUMN meio_pagamento VARCHAR(50) NULL AFTER observacoes,
  ADD COLUMN recorrente TINYINT(1) NOT NULL DEFAULT 0 AFTER meio_pagamento,
  ADD COLUMN recorrencia_tipo ENUM('mensal','semanal','anual','customizada') NULL AFTER recorrente,
  ADD COLUMN recorrencia_intervalo INT NULL AFTER recorrencia_tipo,
  ADD COLUMN asaas_payment_id VARCHAR(60) NULL AFTER recorrencia_intervalo,
  ADD COLUMN asaas_subscription_id VARCHAR(60) NULL AFTER asaas_payment_id,
  ADD COLUMN external_reference VARCHAR(120) NULL AFTER asaas_subscription_id,
  ADD INDEX idx_contas_receber_asaas_payment (asaas_payment_id),
  ADD INDEX idx_contas_receber_external_ref (external_reference);
