-- ============================================================
-- Migration: Recorrência Completa de Contas a Receber
-- Data: 2026-03-31
-- Objetivo: Suporte a geração de todas as parcelas de uma vez
--           ao criar conta a receber recorrente, e vinculação
--           com contratos para geração automática de cobranças.
-- Compatível com: MySQL 5.7+ e MariaDB 10.x+
-- ============================================================

-- ─────────────────────────────────────────────────────────────
-- 1. Adicionar campos em contas_receber
-- ─────────────────────────────────────────────────────────────

-- Vincular conta a receber a um contrato (opcional)
ALTER TABLE contas_receber
  ADD COLUMN IF NOT EXISTS contrato_id INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Contrato que originou esta conta a receber'
    AFTER grupo_parcelas;

-- Modo de geração: 'rolling' (gera próxima ao receber) ou 'antecipado' (gera todas de uma vez)
ALTER TABLE contas_receber
  ADD COLUMN IF NOT EXISTS recorrencia_modo VARCHAR(20) NULL DEFAULT 'rolling'
    COMMENT 'rolling = gera próxima ao receber; antecipado = gerou todas de uma vez'
    AFTER contrato_id;

-- Índice para busca por contrato
ALTER TABLE contas_receber
  ADD INDEX IF NOT EXISTS idx_cr_contrato_id (contrato_id);

-- ─────────────────────────────────────────────────────────────
-- 2. Adicionar campos em contratos
-- ─────────────────────────────────────────────────────────────

-- Dia do mês de vencimento das parcelas (1-28)
ALTER TABLE contratos
  ADD COLUMN IF NOT EXISTS dia_vencimento TINYINT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Dia do mês para vencimento das parcelas (1-28)'
    AFTER valor;

-- Número de parcelas a gerar (quando recorrência é definida)
ALTER TABLE contratos
  ADD COLUMN IF NOT EXISTS num_parcelas SMALLINT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Número de parcelas a gerar automaticamente'
    AFTER dia_vencimento;

-- Plano de conta padrão para as contas a receber geradas
ALTER TABLE contratos
  ADD COLUMN IF NOT EXISTS plano_conta_id INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Plano de conta para as cobranças geradas automaticamente'
    AFTER num_parcelas;

-- Meio de pagamento padrão para as cobranças geradas
ALTER TABLE contratos
  ADD COLUMN IF NOT EXISTS meio_pagamento VARCHAR(30) NULL DEFAULT NULL
    COMMENT 'Meio de pagamento padrão para cobranças do contrato'
    AFTER plano_conta_id;

-- Status de geração das cobranças
ALTER TABLE contratos
  ADD COLUMN IF NOT EXISTS cobrancas_geradas TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = cobranças já foram geradas para este contrato'
    AFTER meio_pagamento;

-- Data da última geração de cobranças
ALTER TABLE contratos
  ADD COLUMN IF NOT EXISTS cobrancas_geradas_em DATETIME NULL DEFAULT NULL
    COMMENT 'Quando as cobranças foram geradas pela última vez'
    AFTER cobrancas_geradas;

-- Índice para plano de conta
ALTER TABLE contratos
  ADD INDEX IF NOT EXISTS idx_contratos_plano_conta (plano_conta_id);
