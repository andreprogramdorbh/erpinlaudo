-- Migration: Financeiro - Contas a Receber (Meios de Pagamento + Recorrencia + Asaas refs)
-- Date: 2026-02-05
-- Rules: ONLY ADD COLUMN. Never drop/rename existing columns.
-- MariaDB: ADD COLUMN IF NOT EXISTS suportado desde 10.0.2
--          CREATE INDEX IF NOT EXISTS suportado desde 10.1.4

ALTER TABLE contas_receber
  ADD COLUMN IF NOT EXISTS meio_pagamento        VARCHAR(50)                                          NULL AFTER observacoes,
  ADD COLUMN IF NOT EXISTS recorrente            TINYINT(1)                             NOT NULL DEFAULT 0 AFTER meio_pagamento,
  ADD COLUMN IF NOT EXISTS recorrencia_tipo      ENUM('mensal','semanal','anual','customizada')        NULL AFTER recorrente,
  ADD COLUMN IF NOT EXISTS recorrencia_intervalo INT                                                   NULL AFTER recorrencia_tipo,
  ADD COLUMN IF NOT EXISTS asaas_payment_id      VARCHAR(60)                                           NULL AFTER recorrencia_intervalo,
  ADD COLUMN IF NOT EXISTS asaas_subscription_id VARCHAR(60)                                           NULL AFTER asaas_payment_id,
  ADD COLUMN IF NOT EXISTS external_reference    VARCHAR(120)                                          NULL AFTER asaas_subscription_id;

-- Índices criados separadamente com IF NOT EXISTS para idempotência
CREATE INDEX IF NOT EXISTS idx_contas_receber_asaas_payment ON contas_receber (asaas_payment_id);
CREATE INDEX IF NOT EXISTS idx_contas_receber_external_ref  ON contas_receber (external_reference);
