-- Migration: Adiciona coluna status à tabela users
-- Data: 2026-03-16
-- Motivo: A coluna estava comentada na migration anterior e nunca foi criada,
--         causando erro 500 ao tentar atualizar usuários.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo' AFTER role;

-- Garante que todos os usuários existentes ficam com status 'ativo'
UPDATE users SET status = 'ativo' WHERE status IS NULL OR status = '';
