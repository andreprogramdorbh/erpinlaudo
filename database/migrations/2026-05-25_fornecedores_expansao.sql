-- ============================================================
-- ERP InLaudo — Expansão da tabela fornecedores
-- Migration: 2026-05-25_fornecedores_expansao.sql
-- Compatível com MySQL 5.6+ e MariaDB 5.5+
-- Execute via phpMyAdmin no servidor HostGator
-- ============================================================
-- Estratégia: usa PROCEDURE para verificar se a coluna existe
-- antes de tentar adicioná-la (evita erro se já existir).
-- ============================================================

DROP PROCEDURE IF EXISTS sp_add_col;

DELIMITER $$

CREATE PROCEDURE sp_add_col(
    IN p_table  VARCHAR(64),
    IN p_col    VARCHAR(64),
    IN p_def    TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM   information_schema.COLUMNS
        WHERE  TABLE_SCHEMA = DATABASE()
          AND  TABLE_NAME   = p_table
          AND  COLUMN_NAME  = p_col
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_col, '` ', p_def);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- Adiciona cada coluna individualmente (seguro para re-executar)
-- ============================================================

CALL sp_add_col('fornecedores', 'tipo',                "ENUM('PJ','PF') NOT NULL DEFAULT 'PJ' AFTER `usuario_id`");
CALL sp_add_col('fornecedores', 'nome_fantasia',       "VARCHAR(255) NULL AFTER `nome`");
CALL sp_add_col('fornecedores', 'celular',             "VARCHAR(30) NULL AFTER `telefone`");
CALL sp_add_col('fornecedores', 'contato_nome',        "VARCHAR(150) NULL AFTER `celular`");
CALL sp_add_col('fornecedores', 'website',             "VARCHAR(255) NULL AFTER `contato_nome`");
CALL sp_add_col('fornecedores', 'cep',                 "VARCHAR(10) NULL AFTER `website`");
CALL sp_add_col('fornecedores', 'endereco',            "VARCHAR(255) NULL AFTER `cep`");
CALL sp_add_col('fornecedores', 'numero',              "VARCHAR(20) NULL AFTER `endereco`");
CALL sp_add_col('fornecedores', 'complemento',         "VARCHAR(100) NULL AFTER `numero`");
CALL sp_add_col('fornecedores', 'bairro',              "VARCHAR(100) NULL AFTER `complemento`");
CALL sp_add_col('fornecedores', 'cidade',              "VARCHAR(100) NULL AFTER `bairro`");
CALL sp_add_col('fornecedores', 'estado',              "CHAR(2) NULL AFTER `cidade`");
CALL sp_add_col('fornecedores', 'inscricao_estadual',  "VARCHAR(30) NULL AFTER `estado`");
CALL sp_add_col('fornecedores', 'inscricao_municipal', "VARCHAR(30) NULL AFTER `inscricao_estadual`");
CALL sp_add_col('fornecedores', 'prazo_pagamento',     "SMALLINT UNSIGNED NULL AFTER `inscricao_municipal`");
CALL sp_add_col('fornecedores', 'cnae_principal',      "VARCHAR(20) NULL AFTER `prazo_pagamento`");
CALL sp_add_col('fornecedores', 'descricao_cnae',      "VARCHAR(255) NULL AFTER `cnae_principal`");
CALL sp_add_col('fornecedores', 'observacoes',         "TEXT NULL AFTER `descricao_cnae`");

-- Índices (ignora erro se já existirem — execute separadamente se necessário)
-- ALTER TABLE `fornecedores` ADD INDEX `idx_fornecedores_documento` (`documento`);
-- ALTER TABLE `fornecedores` ADD INDEX `idx_fornecedores_cidade`    (`cidade`);
-- ALTER TABLE `fornecedores` ADD INDEX `idx_fornecedores_estado`    (`estado`);

DROP PROCEDURE IF EXISTS sp_add_col;

-- ============================================================
-- FIM DA MIGRATION
-- ============================================================
