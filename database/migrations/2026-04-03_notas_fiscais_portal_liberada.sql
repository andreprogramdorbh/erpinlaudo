-- ============================================================
-- Migration: Campos de liberação de NF no portal do cliente
-- Data: 2026-04-03
-- Descrição: Adiciona campos portal_liberada e portal_liberada_em
--            para controlar quando uma NF foi liberada para
--            visualização no portal após recebimento manual.
-- ============================================================
ALTER TABLE notas_fiscais
    ADD COLUMN IF NOT EXISTS portal_liberada    TINYINT(1)   NOT NULL DEFAULT 0 AFTER observacoes_nf,
    ADD COLUMN IF NOT EXISTS portal_liberada_em DATETIME     NULL     AFTER portal_liberada;

-- Libera automaticamente todas as NFs já emitidas/aprovadas
UPDATE notas_fiscais
SET portal_liberada = 1, portal_liberada_em = NOW()
WHERE status IN ('emitida', 'aprovada', 'autorizada')
  AND portal_liberada = 0;
