-- ============================================================
-- Portal do Cliente — Área de Acesso do Cliente
-- Criado em: 2026-02-22
-- ============================================================

-- Tabela de credenciais do portal do cliente
-- Cada cliente pode ter um acesso ao portal vinculado ao seu e-mail principal
CREATE TABLE IF NOT EXISTS portal_clientes (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id        INT NOT NULL,
  email             VARCHAR(255) NOT NULL,
  password_hash     VARCHAR(255) NULL COMMENT 'NULL = primeiro acesso ainda não realizado',
  primeiro_acesso   TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = ainda não definiu senha',
  ativo             TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_acesso     DATETIME NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_portal_email (email),
  UNIQUE KEY uk_portal_cliente (cliente_id),
  INDEX idx_portal_ativo (ativo),
  CONSTRAINT fk_portal_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de tokens de primeiro acesso / redefinição de senha do portal
CREATE TABLE IF NOT EXISTS portal_clientes_tokens (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id  INT NOT NULL,
  token       VARCHAR(128) NOT NULL,
  tipo        ENUM('primeiro_acesso','reset_senha') NOT NULL DEFAULT 'primeiro_acesso',
  usado       TINYINT(1) NOT NULL DEFAULT 0,
  expira_em   DATETIME NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_portal_token (token),
  INDEX idx_portal_token_cliente (cliente_id),
  CONSTRAINT fk_portal_token_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
