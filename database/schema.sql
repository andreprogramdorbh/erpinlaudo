-- ============================================
-- SCRIPT SQL - Base de Dados SaaS
-- ============================================
-- Este script cria a estrutura do banco de dados
-- e um usuário de teste para validação do sistema.
-- ============================================

-- Criar a tabela de usuários
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir usuário de teste
-- E-mail: teste@email.com
-- Senha: 123456 (hash ARGON2ID)
INSERT INTO users (name, email, password) VALUES (
    'Usuário Teste',
    'teste@email.com',
    '$argon2id$v=19$m=65536,t=4,p=1$Ni9WbXdZbkZadGlWWmp2Mg$Z76OmFUUZApVtv6SkevgJrawn36P6kEbmI6ZnFxINwE'
);

-- Criar tabela de logs de auditoria
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details LONGTEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- FIM DO SCRIPT
-- ============================================

-- Inserir usuário administrador financeiro
-- E-mail: financeiro@inlaudo.com.br
-- Senha: Admin259087@ (hash ARGON2ID)
INSERT INTO users (name, email, password) VALUES (
    'Financeiro Admin',
    'financeiro@inlaudo.com.br',
    '$argon2id$v=19$m=65536,t=4,p=1$YkwuM01uMXo2Z0llTFFCdA$GinRyxrFvBw24dB98ogx2Fn4K9L/sx0tATRdiT2kiJM'
);
