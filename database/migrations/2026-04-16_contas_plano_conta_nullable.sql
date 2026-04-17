-- ============================================================
-- Migration: 2026-04-16_contas_plano_conta_nullable.sql
-- Objetivo : Tornar plano_conta_id opcional (NULL) nas tabelas
--            contas_pagar e contas_receber.
--
-- Contexto : O campo foi criado como NOT NULL, mas o sistema de
--            apuração gera contas sem plano de conta definido.
--            A FK é mantida — apenas o NOT NULL é relaxado para
--            NULL DEFAULT NULL.
--
-- Compatível com MariaDB 10.x (sem DELIMITER / PROCEDURE)
-- ============================================================

-- 1. contas_pagar: remover FK, alterar coluna, recriar FK
ALTER TABLE `contas_pagar`
    DROP FOREIGN KEY `fk_contas_pagar_plano_conta`;

ALTER TABLE `contas_pagar`
    MODIFY COLUMN `plano_conta_id` INT NULL DEFAULT NULL
    COMMENT 'Plano de contas (opcional)';

ALTER TABLE `contas_pagar`
    ADD CONSTRAINT `fk_contas_pagar_plano_conta`
        FOREIGN KEY (`plano_conta_id`)
        REFERENCES `plano_contas` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;

-- 2. contas_receber: remover FK, alterar coluna, recriar FK
ALTER TABLE `contas_receber`
    DROP FOREIGN KEY `fk_contas_receber_plano_conta`;

ALTER TABLE `contas_receber`
    MODIFY COLUMN `plano_conta_id` INT NULL DEFAULT NULL
    COMMENT 'Plano de contas (opcional)';

ALTER TABLE `contas_receber`
    ADD CONSTRAINT `fk_contas_receber_plano_conta`
        FOREIGN KEY (`plano_conta_id`)
        REFERENCES `plano_contas` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
