-- ============================================================
-- ERP InLaudo — Aceite e Assinatura Digital de Propostas
-- Migration: 2026-05-25_proposta_aceite_assinatura.sql
-- Execute via phpMyAdmin no servidor HostGator
-- Compatível com MySQL 5.6+ (sem IF NOT EXISTS em ADD COLUMN)
-- ============================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_proposta_aceite_migration $$
CREATE PROCEDURE sp_proposta_aceite_migration()
BEGIN

    -- ─── 1. Colunas extras em crm_propostas ─────────────────────────────────

    -- token_acesso (já pode existir)
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'crm_propostas'
                     AND COLUMN_NAME  = 'token_acesso') THEN
        ALTER TABLE `crm_propostas`
            ADD COLUMN `token_acesso` VARCHAR(64) NULL AFTER `pdf_path`;
    END IF;

    -- aceito_por_nome
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'crm_propostas'
                     AND COLUMN_NAME  = 'aceito_por_nome') THEN
        ALTER TABLE `crm_propostas`
            ADD COLUMN `aceito_por_nome` VARCHAR(255) NULL AFTER `aceito_em`;
    END IF;

    -- aceito_por_ip
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'crm_propostas'
                     AND COLUMN_NAME  = 'aceito_por_ip') THEN
        ALTER TABLE `crm_propostas`
            ADD COLUMN `aceito_por_ip` VARCHAR(45) NULL AFTER `aceito_por_nome`;
    END IF;

    -- assinatura_tipo  (rubrica | nome_digitado | portal)
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'crm_propostas'
                     AND COLUMN_NAME  = 'assinatura_tipo') THEN
        ALTER TABLE `crm_propostas`
            ADD COLUMN `assinatura_tipo` ENUM('rubrica','nome_digitado','portal') NULL AFTER `aceito_por_ip`;
    END IF;

    -- assinatura_imagem_path  (caminho para PNG da rubrica)
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'crm_propostas'
                     AND COLUMN_NAME  = 'assinatura_imagem_path') THEN
        ALTER TABLE `crm_propostas`
            ADD COLUMN `assinatura_imagem_path` VARCHAR(500) NULL AFTER `assinatura_tipo`;
    END IF;

    -- pdf_assinado_path  (PDF com assinatura incorporada)
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'crm_propostas'
                     AND COLUMN_NAME  = 'pdf_assinado_path') THEN
        ALTER TABLE `crm_propostas`
            ADD COLUMN `pdf_assinado_path` VARCHAR(500) NULL AFTER `assinatura_imagem_path`;
    END IF;

    -- recusado_em
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'crm_propostas'
                     AND COLUMN_NAME  = 'recusado_em') THEN
        ALTER TABLE `crm_propostas`
            ADD COLUMN `recusado_em` DATETIME NULL AFTER `aceito_em`;
    END IF;

    -- recusado_motivo
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'crm_propostas'
                     AND COLUMN_NAME  = 'recusado_motivo') THEN
        ALTER TABLE `crm_propostas`
            ADD COLUMN `recusado_motivo` TEXT NULL AFTER `recusado_em`;
    END IF;

    -- ─── 2. Índice único para token_acesso ──────────────────────────────────
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'crm_propostas'
                     AND INDEX_NAME   = 'uk_prop_token') THEN
        ALTER TABLE `crm_propostas`
            ADD UNIQUE INDEX `uk_prop_token` (`token_acesso`);
    END IF;

    -- ─── 3. Tabela crm_proposta_aceite (log de eventos de aceite) ───────────
    IF NOT EXISTS (SELECT 1 FROM information_schema.TABLES
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'crm_proposta_aceite') THEN
        CREATE TABLE `crm_proposta_aceite` (
            `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `proposta_id`           INT NOT NULL,
            `evento`                ENUM('visualizado','aceito','recusado','assinado') NOT NULL,
            `nome_assinante`        VARCHAR(255) NULL,
            `ip`                    VARCHAR(45) NULL,
            `user_agent`            VARCHAR(500) NULL,
            `assinatura_tipo`       ENUM('rubrica','nome_digitado','portal') NULL,
            `assinatura_imagem_path` VARCHAR(500) NULL,
            `motivo_recusa`         TEXT NULL,
            `created_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_aceite_proposta` (`proposta_id`),
            INDEX `idx_aceite_evento`   (`evento`),
            CONSTRAINT `fk_aceite_proposta`
                FOREIGN KEY (`proposta_id`) REFERENCES `crm_propostas`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    END IF;

    -- ─── 4. Alerta de e-mail: proposta aceita ───────────────────────────────
    -- Insere na tabela email_alertas_seed (padrão) se não existir
    IF EXISTS (SELECT 1 FROM information_schema.TABLES
               WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME   = 'email_alertas_seed') THEN
        IF NOT EXISTS (SELECT 1 FROM `email_alertas_seed`
                       WHERE `codigo` = 'crm_proposta_aceita') THEN
            INSERT INTO `email_alertas_seed`
                (`codigo`, `modulo`, `nome`, `descricao`,
                 `antecedencia_dias`, `hora_disparo`, `frequencia`,
                 `assunto_template`, `corpo_template`, `ativo`)
            VALUES
                ('crm_proposta_aceita', 'crm',
                 'Proposta Aceita pelo Cliente',
                 'Dispara um e-mail para o responsável quando o cliente aceita e assina a proposta.',
                 0, '00:00:00', 'unico',
                 'Proposta {{numero}} aceita por {{cliente_nome}}',
                 '<p>A proposta <strong>{{numero}}</strong> foi aceita e assinada por <strong>{{cliente_nome}}</strong> em {{data_aceite}}.</p><p>Acesse o ERP para gerar o Pedido de Venda.</p>',
                 1);
        END IF;
    END IF;

END $$

CALL sp_proposta_aceite_migration() $$
DROP PROCEDURE IF EXISTS sp_proposta_aceite_migration $$

DELIMITER ;
