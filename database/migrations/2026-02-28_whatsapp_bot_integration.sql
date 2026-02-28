-- ============================================================
-- Migration: Integração Bot WhatsApp
-- Data: 2026-02-28
-- Descrição: Cria a tabela de logs do bot WhatsApp.
--            O token de API é armazenado na tabela `integracoes`
--            existente, com nome='whatsapp_bot'.
-- ============================================================

-- Tabela de logs de consultas do bot WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_bot_logs (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id     INT NOT NULL COMMENT 'ID do usuário do ERP (tenant)',
  integracao_id INT NOT NULL DEFAULT 0 COMMENT 'ID da integração na tabela integracoes',
  telefone_hash VARCHAR(12) NOT NULL COMMENT 'Hash SHA-256 truncado do telefone (privacidade)',
  endpoint      VARCHAR(255) NOT NULL COMMENT 'Endpoint consultado',
  intent        VARCHAR(100) NOT NULL COMMENT 'Intenção identificada',
  status        ENUM('success', 'error') NOT NULL DEFAULT 'success',
  summary       VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'Resumo da resposta',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_wbl_tenant    (tenant_id),
  INDEX idx_wbl_status    (status),
  INDEX idx_wbl_created   (created_at),
  INDEX idx_wbl_integracao (integracao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Logs de consultas do chatbot WhatsApp';
