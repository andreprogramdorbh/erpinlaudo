-- ============================================================
-- Migration: 2026-04-26_contas_receber_cliente_nullable.sql
-- Objetivo : Tornar cliente_id opcional (NULL) na tabela
--            contas_receber para suportar faturamento de
--            apurações de cliente sem cliente_id definido.
--
-- Contexto : A tabela foi criada com cliente_id INT NOT NULL,
--            mas o sistema de apuração pode gerar contas a
--            receber sem cliente vinculado (quando a apuração
--            de cliente não tem cliente_id preenchido).
--            A FK é mantida — apenas o NOT NULL é relaxado.
--
-- Compatível com MariaDB 10.x / MySQL 5.7+
-- ============================================================

-- 1. Remover FK existente (necessário para alterar a coluna)
ALTER TABLE `contas_receber`
    DROP FOREIGN KEY `fk_contas_receber_cliente`;

-- 2. Tornar cliente_id nullable
ALTER TABLE `contas_receber`
    MODIFY COLUMN `cliente_id` INT NULL DEFAULT NULL
    COMMENT 'Cliente vinculado (opcional para apurações sem cliente definido)';

-- 3. Recriar FK com ON DELETE SET NULL (para não bloquear exclusão de clientes)
ALTER TABLE `contas_receber`
    ADD CONSTRAINT `fk_contas_receber_cliente`
        FOREIGN KEY (`cliente_id`)
        REFERENCES `clientes` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
