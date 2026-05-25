-- ============================================================
-- Migration: empresa_config
-- Armazena os dados da empresa gerida pelo usuário no ERP.
-- Um usuário pode ter apenas um registro (1:1 com users.id).
-- ============================================================

CREATE TABLE IF NOT EXISTS `empresa_config` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `usuario_id`            INT UNSIGNED    NOT NULL,

    -- Identificação
    `tipo_pessoa`           ENUM('pf','pj') NOT NULL DEFAULT 'pj',
    `razao_social`          VARCHAR(200)    NOT NULL DEFAULT '',
    `nome_fantasia`         VARCHAR(200)    NOT NULL DEFAULT '',
    `cpf_cnpj`              VARCHAR(20)     NOT NULL DEFAULT '',
    `inscricao_estadual`    VARCHAR(30)     NOT NULL DEFAULT '',
    `inscricao_municipal`   VARCHAR(30)     NOT NULL DEFAULT '',

    -- Contato
    `email_responsavel`     VARCHAR(150)    NOT NULL DEFAULT '',
    `email_financeiro`      VARCHAR(150)    NOT NULL DEFAULT '',
    `financeiro_mesmo_responsavel` TINYINT(1) NOT NULL DEFAULT 0,
    `telefone`              VARCHAR(20)     NOT NULL DEFAULT '',
    `site`                  VARCHAR(150)    NOT NULL DEFAULT '',

    -- Endereço
    `cep`                   VARCHAR(10)     NOT NULL DEFAULT '',
    `logradouro`            VARCHAR(200)    NOT NULL DEFAULT '',
    `numero`                VARCHAR(20)     NOT NULL DEFAULT '',
    `complemento`           VARCHAR(100)    NOT NULL DEFAULT '',
    `bairro`                VARCHAR(100)    NOT NULL DEFAULT '',
    `cidade`                VARCHAR(100)    NOT NULL DEFAULT '',
    `estado`                CHAR(2)         NOT NULL DEFAULT '',

    -- Logo
    `logo_path`             VARCHAR(300)    NOT NULL DEFAULT '',

    -- Timestamps
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_empresa_usuario` (`usuario_id`),
    CONSTRAINT `fk_empresa_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
