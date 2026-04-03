-- Migration: Add role column to users table
-- Date: 2026-02-03
-- Compatível com: MariaDB 10.0.2+ (ADD COLUMN IF NOT EXISTS)
--                 MariaDB 10.1.4+ (CREATE INDEX IF NOT EXISTS)
-- Purpose: Enable proper RBAC implementation

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS role VARCHAR(50) NOT NULL DEFAULT 'user' AFTER email;

-- Atribuir roles iniciais (seguro executar múltiplas vezes)
UPDATE users SET role = 'superadmin' WHERE email IN ('admin@inlaudo.com.br', 'teste@email.com') AND role = 'user';
UPDATE users SET role = 'admin'      WHERE email = 'financeiro@inlaudo.com.br'                  AND role = 'user';

-- Índice criado separadamente com IF NOT EXISTS para idempotência
CREATE INDEX IF NOT EXISTS idx_role ON users (role);
