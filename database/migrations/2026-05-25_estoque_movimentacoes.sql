-- ============================================================
-- Migration: Estoque — Movimentações, Pedidos de Compra e Venda
-- Versão: 1.0 — 2026-05-25
-- Obs: users.id é int(11) sem UNSIGNED — FKs usam INT(11)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─── 1. Movimentações de estoque ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `est_movimentacoes` (
    `id`                INT(11)         NOT NULL AUTO_INCREMENT,
    `usuario_id`        INT(11)         NOT NULL,
    `produto_id`        INT(11)         NOT NULL,

    -- Tipo e origem
    `tipo`              ENUM('entrada','saida','ajuste','transferencia','devolucao_compra','devolucao_venda')
                                        NOT NULL DEFAULT 'entrada',
    `origem`            ENUM('manual','xml_nfe','pedido_compra','pedido_venda','ajuste_inventario','devolucao')
                                        NOT NULL DEFAULT 'manual',

    -- Referências externas
    `pedido_compra_id`  INT(11)         NULL,
    `pedido_venda_id`   INT(11)         NULL,
    `nfe_chave`         VARCHAR(44)     NULL COMMENT 'Chave de acesso da NF-e',
    `nfe_numero`        VARCHAR(20)     NULL,
    `nfe_serie`         VARCHAR(5)      NULL,
    `nfe_emitente_cnpj` VARCHAR(20)     NULL,
    `nfe_emitente_nome` VARCHAR(200)    NULL,
    `nfe_data_emissao`  DATE            NULL,

    -- Quantidades e valores
    `quantidade`        DECIMAL(15,4)   NOT NULL DEFAULT 0,
    `unidade`           VARCHAR(10)     NOT NULL DEFAULT 'UN',
    `preco_unitario`    DECIMAL(15,4)   NOT NULL DEFAULT 0,
    `valor_total`       DECIMAL(15,4)   NOT NULL DEFAULT 0,
    `custo_unitario`    DECIMAL(15,4)   NOT NULL DEFAULT 0,

    -- Lote e validade
    `lote`              VARCHAR(50)     NULL,
    `data_fabricacao`   DATE            NULL,
    `data_validade`     DATE            NULL,

    -- Localização
    `localizacao`       VARCHAR(100)    NULL,

    -- Estoque antes/depois (snapshot)
    `estoque_antes`     DECIMAL(15,4)   NOT NULL DEFAULT 0,
    `estoque_depois`    DECIMAL(15,4)   NOT NULL DEFAULT 0,

    -- Metadados
    `motivo`            TEXT            NULL,
    `observacoes`       TEXT            NULL,
    `usuario_responsavel` INT(11)       NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    INDEX `idx_mov_produto`     (`produto_id`),
    INDEX `idx_mov_usuario`     (`usuario_id`),
    INDEX `idx_mov_tipo`        (`tipo`),
    INDEX `idx_mov_origem`      (`origem`),
    INDEX `idx_mov_nfe_chave`   (`nfe_chave`),
    INDEX `idx_mov_created`     (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Histórico completo de movimentações de estoque';

-- ─── 2. Pedidos de compra ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `est_pedidos_compra` (
    `id`                INT(11)         NOT NULL AUTO_INCREMENT,
    `usuario_id`        INT(11)         NOT NULL,
    `numero`            VARCHAR(20)     NOT NULL,

    -- Fornecedor
    `fornecedor_id`     INT(11)         NULL,
    `fornecedor_nome`   VARCHAR(200)    NOT NULL DEFAULT '',
    `fornecedor_cnpj`   VARCHAR(20)     NULL,
    `fornecedor_email`  VARCHAR(150)    NULL,
    `fornecedor_telefone` VARCHAR(20)   NULL,

    -- Status e datas
    `status`            ENUM('rascunho','enviado','confirmado','parcialmente_recebido','recebido','cancelado')
                                        NOT NULL DEFAULT 'rascunho',
    `data_pedido`       DATE            NOT NULL,
    `data_previsao`     DATE            NULL,
    `data_recebimento`  DATE            NULL,

    -- Valores
    `valor_produtos`    DECIMAL(15,2)   NOT NULL DEFAULT 0,
    `valor_frete`       DECIMAL(15,2)   NOT NULL DEFAULT 0,
    `valor_desconto`    DECIMAL(15,2)   NOT NULL DEFAULT 0,
    `valor_total`       DECIMAL(15,2)   NOT NULL DEFAULT 0,

    -- NF-e de entrada
    `nfe_chave`         VARCHAR(44)     NULL,
    `nfe_numero`        VARCHAR(20)     NULL,
    `nfe_xml_path`      VARCHAR(300)    NULL,

    -- Metadados
    `condicao_pagamento` VARCHAR(100)   NULL,
    `observacoes`       TEXT            NULL,
    `observacoes_internas` TEXT         NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pc_numero_usuario` (`numero`, `usuario_id`),
    INDEX `idx_pc_usuario`      (`usuario_id`),
    INDEX `idx_pc_fornecedor`   (`fornecedor_id`),
    INDEX `idx_pc_status`       (`status`),
    INDEX `idx_pc_data`         (`data_pedido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pedidos de compra / ordens de compra';

-- ─── 3. Itens do pedido de compra ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `est_pedidos_compra_itens` (
    `id`                INT(11)         NOT NULL AUTO_INCREMENT,
    `pedido_id`         INT(11)         NOT NULL,
    `produto_id`        INT(11)         NULL,
    `codigo_produto`    VARCHAR(50)     NULL,
    `descricao`         VARCHAR(300)    NOT NULL DEFAULT '',
    `unidade`           VARCHAR(10)     NOT NULL DEFAULT 'UN',
    `quantidade`        DECIMAL(15,4)   NOT NULL DEFAULT 0,
    `quantidade_recebida` DECIMAL(15,4) NOT NULL DEFAULT 0,
    `preco_unitario`    DECIMAL(15,4)   NOT NULL DEFAULT 0,
    `desconto_perc`     DECIMAL(5,2)    NOT NULL DEFAULT 0,
    `valor_total`       DECIMAL(15,4)   NOT NULL DEFAULT 0,
    `lote`              VARCHAR(50)     NULL,
    `data_validade`     DATE            NULL,
    `observacoes`       VARCHAR(300)    NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    INDEX `idx_pci_pedido`  (`pedido_id`),
    INDEX `idx_pci_produto` (`produto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 4. Pedidos de venda ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `est_pedidos_venda` (
    `id`                INT(11)         NOT NULL AUTO_INCREMENT,
    `usuario_id`        INT(11)         NOT NULL,
    `numero`            VARCHAR(20)     NOT NULL,

    -- Origem (pode vir de proposta CRM)
    `proposta_id`       INT(11)         NULL,
    `oportunidade_id`   INT(11)         NULL,

    -- Cliente
    `cliente_id`        INT(11)         NULL,
    `cliente_nome`      VARCHAR(200)    NOT NULL DEFAULT '',
    `cliente_cpf_cnpj`  VARCHAR(20)     NULL,
    `cliente_email`     VARCHAR(150)    NULL,
    `cliente_telefone`  VARCHAR(20)     NULL,
    `cliente_endereco`  VARCHAR(300)    NULL,

    -- Status e datas
    `status`            ENUM('rascunho','confirmado','em_separacao','parcialmente_entregue','entregue','faturado','cancelado')
                                        NOT NULL DEFAULT 'rascunho',
    `data_pedido`       DATE            NOT NULL,
    `data_previsao_entrega` DATE        NULL,
    `data_entrega`      DATE            NULL,
    `data_faturamento`  DATE            NULL,

    -- Valores
    `valor_produtos`    DECIMAL(15,2)   NOT NULL DEFAULT 0,
    `valor_frete`       DECIMAL(15,2)   NOT NULL DEFAULT 0,
    `valor_desconto`    DECIMAL(15,2)   NOT NULL DEFAULT 0,
    `valor_total`       DECIMAL(15,2)   NOT NULL DEFAULT 0,
    `valor_custo_total` DECIMAL(15,2)   NOT NULL DEFAULT 0,
    `margem_total`      DECIMAL(15,2)   NOT NULL DEFAULT 0,

    -- Financeiro
    `condicao_pagamento` VARCHAR(100)   NULL,
    `forma_pagamento`   VARCHAR(100)    NULL,
    `comissao_percentual` DECIMAL(5,2)  NOT NULL DEFAULT 0,
    `comissao_valor`    DECIMAL(15,2)   NOT NULL DEFAULT 0,
    `colaborador_id`    INT(11)         NULL,

    -- Entrega
    `tipo_frete`        ENUM('cif','fob','gratis','valor_fixo') NOT NULL DEFAULT 'cif',
    `transportadora`    VARCHAR(150)    NULL,
    `endereco_entrega`  VARCHAR(300)    NULL,

    -- Metadados
    `observacoes`       TEXT            NULL,
    `observacoes_internas` TEXT         NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pv_numero_usuario` (`numero`, `usuario_id`),
    INDEX `idx_pv_usuario`      (`usuario_id`),
    INDEX `idx_pv_cliente`      (`cliente_id`),
    INDEX `idx_pv_status`       (`status`),
    INDEX `idx_pv_proposta`     (`proposta_id`),
    INDEX `idx_pv_data`         (`data_pedido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pedidos de venda integrados ao estoque e CRM';

-- ─── 5. Itens do pedido de venda ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `est_pedidos_venda_itens` (
    `id`                INT(11)         NOT NULL AUTO_INCREMENT,
    `pedido_id`         INT(11)         NOT NULL,
    `produto_id`        INT(11)         NULL,
    `codigo_produto`    VARCHAR(50)     NULL,
    `descricao`         VARCHAR(300)    NOT NULL DEFAULT '',
    `unidade`           VARCHAR(10)     NOT NULL DEFAULT 'UN',
    `quantidade`        DECIMAL(15,4)   NOT NULL DEFAULT 0,
    `quantidade_entregue` DECIMAL(15,4) NOT NULL DEFAULT 0,
    `preco_unitario`    DECIMAL(15,4)   NOT NULL DEFAULT 0,
    `preco_custo`       DECIMAL(15,4)   NOT NULL DEFAULT 0,
    `desconto_perc`     DECIMAL(5,2)    NOT NULL DEFAULT 0,
    `valor_total`       DECIMAL(15,4)   NOT NULL DEFAULT 0,
    `margem_item`       DECIMAL(15,4)   NOT NULL DEFAULT 0,
    `lote`              VARCHAR(50)     NULL,
    `observacoes`       VARCHAR(300)    NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    INDEX `idx_pvi_pedido`  (`pedido_id`),
    INDEX `idx_pvi_produto` (`produto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 6. Sequência de números de pedido por tenant ────────────────────────────
CREATE TABLE IF NOT EXISTS `est_pedido_seq` (
    `usuario_id`        INT(11)         NOT NULL,
    `tipo`              ENUM('compra','venda') NOT NULL,
    `ultimo_seq`        INT(11)         NOT NULL DEFAULT 0,
    PRIMARY KEY (`usuario_id`, `tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 7. Sequência de código de produto (se não existir) ──────────────────────
CREATE TABLE IF NOT EXISTS `produto_codigo_seq` (
    `usuario_id`        INT(11)         NOT NULL,
    `ultimo_seq`        INT(11)         NOT NULL DEFAULT 0,
    PRIMARY KEY (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 8. Histórico de preços (se não existir) ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `produto_historico_precos` (
    `id`                INT(11)         NOT NULL AUTO_INCREMENT,
    `produto_id`        INT(11)         NOT NULL,
    `usuario_id`        INT(11)         NOT NULL,
    `preco_custo`       DECIMAL(15,4)   NOT NULL DEFAULT 0,
    `preco_venda`       DECIMAL(15,4)   NOT NULL DEFAULT 0,
    `markup_percentual` DECIMAL(8,4)    NOT NULL DEFAULT 0,
    `motivo`            VARCHAR(300)    NULL,
    `usuario_responsavel` INT(11)       NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_php_produto` (`produto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
