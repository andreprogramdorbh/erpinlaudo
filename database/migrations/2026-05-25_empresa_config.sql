-- ============================================================
-- Migration: empresa_config  (v2 — corrigida)
-- Armazena os dados da empresa gerida pelo usuário no ERP.
-- Um usuário pode ter apenas um registro (1:1 com users.id).
--
-- CORREÇÃO v2:
--   • id e usuario_id alterados de INT UNSIGNED para INT(11)
--     para corresponder ao tipo de users.id (int(11) sem UNSIGNED),
--     resolvendo o erro #1215 de chave estrangeira.
--   • Adicionados campos de assinatura digital (nome, rubrica,
--     imagem e autenticação) para uso em Propostas e Contratos.
-- ============================================================

CREATE TABLE IF NOT EXISTS `empresa_config` (
    `id`                    INT(11)         NOT NULL AUTO_INCREMENT,
    `usuario_id`            INT(11)         NOT NULL,           -- mesmo tipo de users.id

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

    -- ─── Assinatura do Responsável ────────────────────────────
    -- Usada em Propostas, Contratos e demais documentos gerados.
    --
    -- Fluxo de uso:
    --   1. Usuário digita o nome completo → exibido como texto
    --      estilizado em fonte cursiva no documento.
    --   2. Usuário pode fazer upload de imagem da assinatura
    --      manuscrita (PNG com fundo transparente recomendado).
    --   3. Rubrica: versão abreviada (iniciais) para rodapés
    --      de páginas intermediárias.
    --   4. Cargo: exibido abaixo da assinatura no documento.
    --   5. autenticacao_texto: frase legal impressa abaixo da
    --      assinatura (ex.: "Documento gerado eletronicamente
    --      em {data} por {nome} — {empresa}").
    --   6. usar_assinatura_imagem: 1 = usa imagem PNG;
    --      0 = usa nome em fonte cursiva (padrão).
    -- ─────────────────────────────────────────────────────────
    `assinatura_nome`           VARCHAR(200)    NOT NULL DEFAULT '',
    `assinatura_cargo`          VARCHAR(100)    NOT NULL DEFAULT '',
    `assinatura_rubrica`        VARCHAR(50)     NOT NULL DEFAULT '',
    `assinatura_imagem_path`    VARCHAR(300)    NOT NULL DEFAULT '',
    `usar_assinatura_imagem`    TINYINT(1)      NOT NULL DEFAULT 0,
    `autenticacao_texto`        TEXT,
    `autenticacao_ativa`        TINYINT(1)      NOT NULL DEFAULT 1,

    -- Timestamps
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_empresa_usuario` (`usuario_id`),
    CONSTRAINT `fk_empresa_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
