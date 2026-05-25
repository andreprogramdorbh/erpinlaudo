-- ============================================================
-- ERP InLaudo — Expansão da tabela fornecedores
-- Migration: 2026-05-25_fornecedores_expansao.sql
-- Execute via phpMyAdmin no servidor HostGator
-- ============================================================

-- Adiciona novos campos à tabela fornecedores (se ainda não existirem)

ALTER TABLE `fornecedores`
    ADD COLUMN IF NOT EXISTS `tipo`                ENUM('PJ','PF')   NOT NULL DEFAULT 'PJ'   AFTER `usuario_id`,
    ADD COLUMN IF NOT EXISTS `nome_fantasia`       VARCHAR(255)      NULL                    AFTER `nome`,
    ADD COLUMN IF NOT EXISTS `celular`             VARCHAR(30)       NULL                    AFTER `telefone`,
    ADD COLUMN IF NOT EXISTS `contato_nome`        VARCHAR(150)      NULL                    AFTER `celular`,
    ADD COLUMN IF NOT EXISTS `website`             VARCHAR(255)      NULL                    AFTER `contato_nome`,
    ADD COLUMN IF NOT EXISTS `cep`                 VARCHAR(10)       NULL                    AFTER `website`,
    ADD COLUMN IF NOT EXISTS `endereco`            VARCHAR(255)      NULL                    AFTER `cep`,
    ADD COLUMN IF NOT EXISTS `numero`              VARCHAR(20)       NULL                    AFTER `endereco`,
    ADD COLUMN IF NOT EXISTS `complemento`         VARCHAR(100)      NULL                    AFTER `numero`,
    ADD COLUMN IF NOT EXISTS `bairro`              VARCHAR(100)      NULL                    AFTER `complemento`,
    ADD COLUMN IF NOT EXISTS `cidade`              VARCHAR(100)      NULL                    AFTER `bairro`,
    ADD COLUMN IF NOT EXISTS `estado`              CHAR(2)           NULL                    AFTER `cidade`,
    ADD COLUMN IF NOT EXISTS `inscricao_estadual`  VARCHAR(30)       NULL                    AFTER `estado`,
    ADD COLUMN IF NOT EXISTS `inscricao_municipal` VARCHAR(30)       NULL                    AFTER `inscricao_estadual`,
    ADD COLUMN IF NOT EXISTS `prazo_pagamento`     SMALLINT UNSIGNED NULL                    AFTER `inscricao_municipal`,
    ADD COLUMN IF NOT EXISTS `cnae_principal`      VARCHAR(20)       NULL                    AFTER `prazo_pagamento`,
    ADD COLUMN IF NOT EXISTS `descricao_cnae`      VARCHAR(255)      NULL                    AFTER `cnae_principal`,
    ADD COLUMN IF NOT EXISTS `observacoes`         TEXT              NULL                    AFTER `descricao_cnae`;

-- Índices adicionais para pesquisa
ALTER TABLE `fornecedores`
    ADD INDEX IF NOT EXISTS `idx_fornecedores_cidade`    (`cidade`),
    ADD INDEX IF NOT EXISTS `idx_fornecedores_estado`    (`estado`),
    ADD INDEX IF NOT EXISTS `idx_fornecedores_documento` (`documento`);

-- ============================================================
-- NOTA: Os campos de histórico (pedidos de compra e
-- movimentações) são lidos diretamente das tabelas
-- est_pedidos_compra e est_movimentacoes pelo Model.
-- Não é necessário criar tabelas adicionais.
-- ============================================================
