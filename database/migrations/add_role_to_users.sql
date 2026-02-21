-- Migration: Add role column to users table
-- Date: 2026-02-03
-- Purpose: Enable proper RBAC implementation

ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'user' AFTER email;

-- Update existing users to have appropriate roles
UPDATE users SET role = 'superadmin' WHERE email IN ('admin@inlaudo.com.br', 'teste@email.com');
UPDATE users SET role = 'admin' WHERE email = 'financeiro@inlaudo.com.br';

-- Add index for performance
ALTER TABLE users ADD INDEX idx_role (role);
