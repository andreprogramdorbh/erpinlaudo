-- ============================================================
-- Migration: Módulo Contas Bancárias
-- Data: 2026-05-07
-- Tabelas: contas_bancarias, contas_movimentacoes
-- ============================================================

-- -------------------------------------------------------
-- 1. Tabela de Contas Bancárias
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contas_bancarias` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`       INT UNSIGNED NOT NULL,
    `nome`             VARCHAR(120) NOT NULL COMMENT 'Nome/apelido da conta (ex: Conta Principal Itaú)',
    `banco_codigo`     VARCHAR(10)  DEFAULT NULL COMMENT 'Código COMPE do banco (ex: 341)',
    `banco_nome`       VARCHAR(100) DEFAULT NULL COMMENT 'Nome do banco (ex: Itaú Unibanco)',
    `banco_ispb`       VARCHAR(20)  DEFAULT NULL COMMENT 'ISPB do banco para Open Finance',
    `tipo`             ENUM('corrente','poupanca','investimento','caixa','outro') NOT NULL DEFAULT 'corrente',
    `agencia`          VARCHAR(20)  DEFAULT NULL,
    `agencia_digito`   VARCHAR(5)   DEFAULT NULL,
    `conta`            VARCHAR(30)  DEFAULT NULL,
    `conta_digito`     VARCHAR(5)   DEFAULT NULL,
    `titular`          VARCHAR(150) DEFAULT NULL COMMENT 'Nome do titular da conta',
    `cpf_cnpj`         VARCHAR(20)  DEFAULT NULL COMMENT 'CPF/CNPJ do titular',
    `saldo_inicial`    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `saldo_atual`      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `moeda`            VARCHAR(5)   NOT NULL DEFAULT 'BRL',
    `cor`              VARCHAR(10)  DEFAULT '#4361ee' COMMENT 'Cor de identificação visual',
    `icone`            VARCHAR(50)  DEFAULT 'fas fa-university',
    `ativa`            TINYINT(1)   NOT NULL DEFAULT 1,
    -- Open Finance / Pluggy
    `openfinance_item_id`    VARCHAR(100) DEFAULT NULL COMMENT 'Item ID no Pluggy/Open Finance',
    `openfinance_account_id` VARCHAR(100) DEFAULT NULL COMMENT 'Account ID no Pluggy/Open Finance',
    `openfinance_provider`   VARCHAR(50)  DEFAULT NULL COMMENT 'Provider: pluggy, belvo, etc.',
    `openfinance_last_sync`  DATETIME     DEFAULT NULL COMMENT 'Última sincronização Open Finance',
    `openfinance_status`     ENUM('connected','disconnected','error','pending') DEFAULT NULL,
    `openfinance_config`     JSON         DEFAULT NULL COMMENT 'Configurações extras do Open Finance',
    `observacoes`      TEXT         DEFAULT NULL,
    `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_usuario` (`usuario_id`),
    KEY `idx_ativa` (`ativa`),
    KEY `idx_openfinance_item` (`openfinance_item_id`),
    KEY `idx_openfinance_account` (`openfinance_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 2. Tabela de Movimentações Bancárias
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contas_movimentacoes` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `conta_bancaria_id`   INT UNSIGNED NOT NULL,
    `usuario_id`          INT UNSIGNED NOT NULL,
    -- Dados da transação
    `data_movimentacao`   DATE         NOT NULL,
    `data_compensacao`    DATE         DEFAULT NULL,
    `descricao`           VARCHAR(500) NOT NULL,
    `descricao_original`  VARCHAR(500) DEFAULT NULL COMMENT 'Descrição original do banco',
    `valor`               DECIMAL(15,2) NOT NULL COMMENT 'Positivo=crédito, Negativo=débito',
    `tipo`                ENUM('credito','debito') NOT NULL,
    `saldo_apos`          DECIMAL(15,2) DEFAULT NULL COMMENT 'Saldo após a movimentação',
    -- Categorização
    `categoria`           VARCHAR(100) DEFAULT NULL,
    `plano_conta_id`      INT UNSIGNED DEFAULT NULL,
    `tags`                JSON         DEFAULT NULL,
    -- Origem da movimentação
    `origem`              ENUM('manual','ofx','ofc','openfinance','apuracao','conta_pagar','conta_receber','importacao') NOT NULL DEFAULT 'manual',
    `origem_id`           VARCHAR(100) DEFAULT NULL COMMENT 'ID externo (ex: ID da transação no banco)',
    `origem_hash`         VARCHAR(64)  DEFAULT NULL COMMENT 'Hash para deduplicação de importações',
    -- Vinculação com módulos do ERP
    `conta_pagar_id`      INT UNSIGNED DEFAULT NULL,
    `conta_receber_id`    INT UNSIGNED DEFAULT NULL,
    -- Open Finance
    `openfinance_tx_id`   VARCHAR(200) DEFAULT NULL COMMENT 'Transaction ID do Open Finance',
    `openfinance_data`    JSON         DEFAULT NULL COMMENT 'Dados brutos da transação Open Finance',
    -- Conciliação
    `conciliada`          TINYINT(1)   NOT NULL DEFAULT 0,
    `data_conciliacao`    DATETIME     DEFAULT NULL,
    `observacoes`         TEXT         DEFAULT NULL,
    `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_conta_bancaria` (`conta_bancaria_id`),
    KEY `idx_usuario` (`usuario_id`),
    KEY `idx_data` (`data_movimentacao`),
    KEY `idx_tipo` (`tipo`),
    KEY `idx_origem` (`origem`),
    KEY `idx_origem_hash` (`origem_hash`),
    KEY `idx_openfinance_tx` (`openfinance_tx_id`),
    KEY `idx_conta_pagar` (`conta_pagar_id`),
    KEY `idx_conta_receber` (`conta_receber_id`),
    UNIQUE KEY `uq_origem_hash` (`conta_bancaria_id`, `origem_hash`),
    CONSTRAINT `fk_mov_conta_bancaria` FOREIGN KEY (`conta_bancaria_id`) REFERENCES `contas_bancarias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 3. Índices adicionais para performance
-- -------------------------------------------------------
ALTER TABLE `contas_movimentacoes`
    ADD KEY `idx_data_tipo` (`conta_bancaria_id`, `data_movimentacao`, `tipo`);
