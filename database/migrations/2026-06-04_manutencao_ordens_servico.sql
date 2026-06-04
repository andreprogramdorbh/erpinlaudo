-- ============================================================================
-- MIGRAÇÃO: Módulo de Manutenção — Ordens de Serviço
-- Sistema : ERP inlaudo (ASOARESBH/erpinlaudo)
-- Data    : 2026-06-04
-- Ambiente: MySQL 5.7.44 / Hostgator Shared / phpMyAdmin
-- Charset : utf8 / utf8_unicode_ci
-- ============================================================================
-- REGRAS APLICADAS:
--   - Sem ADD COLUMN IF NOT EXISTS (incompatível MySQL 5.7)
--   - Sem CREATE PROCEDURE / TRIGGER / EVENT / FUNCTION
--   - Sem INFORMATION_SCHEMA para validações automáticas
--   - Charset utf8 / utf8_unicode_ci (sem utf8mb4)
--   - Um ALTER TABLE por coluna para segurança em produção
-- ============================================================================
-- PRÉ-EXECUÇÃO: Verifique se as tabelas NÃO existem antes de rodar:
--   SHOW TABLES LIKE 'manut_%';
--   SHOW TABLES LIKE 'equipamentos_cliente';
-- ============================================================================

-- ─── PASSO 1: Tabela de Equipamentos vinculados a Clientes ──────────────────
-- Registra cada equipamento (produto com número de série) vinculado a um cliente.
-- Serve como base para rastrear histórico de manutenções por equipamento.

CREATE TABLE `equipamentos_cliente` (
  `id`                    INT(11)       NOT NULL AUTO_INCREMENT,
  `usuario_id`            INT(11)       NOT NULL COMMENT 'Tenant (multi-empresa)',
  `cliente_id`            INT(11)       NULL     COMMENT 'FK clientes.id',
  `cliente_nome`          VARCHAR(255)  NOT NULL,
  `produto_id`            INT(11)       NULL     COMMENT 'FK produtos.id',
  `produto_nome`          VARCHAR(255)  NOT NULL,
  `produto_codigo`        VARCHAR(50)   NULL,
  `numero_serie`          VARCHAR(100)  NOT NULL,
  `modelo`                VARCHAR(100)  NULL,
  `marca`                 VARCHAR(100)  NULL,
  `data_instalacao`       DATE          NULL,
  `data_fabricacao`       DATE          NULL,
  `vida_util_meses`       INT(11)       NULL     COMMENT 'Vida útil em meses vinda do produto',
  `depreciacao_mensal`    DECIMAL(12,4) NULL     COMMENT 'Depreciação mensal calculada',
  `data_inicio_contador`  DATE          NULL     COMMENT 'Data em que o contador de vida útil foi iniciado (ao faturar)',
  `data_proxima_troca`    DATE          NULL     COMMENT 'Calculada: data_inicio_contador + vida_util_meses',
  `observacoes`           TEXT          NULL,
  `ativo`                 TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── PASSO 2: Índices da tabela equipamentos_cliente ────────────────────────

ALTER TABLE `equipamentos_cliente`
  ADD INDEX `idx_equip_usuario`    (`usuario_id`);

ALTER TABLE `equipamentos_cliente`
  ADD INDEX `idx_equip_cliente`    (`cliente_id`);

ALTER TABLE `equipamentos_cliente`
  ADD INDEX `idx_equip_produto`    (`produto_id`);

ALTER TABLE `equipamentos_cliente`
  ADD INDEX `idx_equip_serie`      (`numero_serie`(50));

-- ─── PASSO 3: Tabela principal de Ordens de Serviço ─────────────────────────

CREATE TABLE `manut_ordens_servico` (
  `id`                    INT(11)       NOT NULL AUTO_INCREMENT,
  `usuario_id`            INT(11)       NOT NULL COMMENT 'Tenant (multi-empresa)',
  `numero`                VARCHAR(20)   NOT NULL COMMENT 'OS-2026-00001',
  `tipo`                  ENUM('preventiva','corretiva') NOT NULL DEFAULT 'corretiva',
  `status`                ENUM('aberta','em_andamento','aguardando_peca','concluida','faturada','cancelada') NOT NULL DEFAULT 'aberta',
  `cliente_id`            INT(11)       NULL,
  `cliente_nome`          VARCHAR(255)  NOT NULL,
  `cliente_cpf_cnpj`      VARCHAR(20)   NULL,
  `cliente_email`         VARCHAR(255)  NULL,
  `cliente_telefone`      VARCHAR(30)   NULL,
  `cliente_endereco`      TEXT          NULL,
  `cliente_cidade`        VARCHAR(100)  NULL,
  `cliente_estado`        CHAR(2)       NULL,
  `equipamento_id`        INT(11)       NULL     COMMENT 'FK equipamentos_cliente.id',
  `produto_id`            INT(11)       NULL     COMMENT 'Produto/equipamento principal',
  `produto_nome`          VARCHAR(255)  NULL,
  `produto_codigo`        VARCHAR(50)   NULL,
  `numero_serie`          VARCHAR(100)  NULL,
  `motivo_chamado`        TEXT          NOT NULL COMMENT 'Descrição do motivo do chamado',
  `descricao_servico`     TEXT          NULL     COMMENT 'Descrição do serviço a realizar',
  `evolucao`              TEXT          NULL     COMMENT 'Evolução da manutenção — preenchido durante o atendimento',
  `data_abertura`         DATE          NOT NULL,
  `data_previsao`         DATE          NULL,
  `data_conclusao`        DATE          NULL,
  `tecnico_responsavel`   VARCHAR(255)  NULL,
  `prioridade`            ENUM('baixa','normal','alta','urgente') NOT NULL DEFAULT 'normal',
  `valor_servico`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `valor_pecas`           DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `valor_total`           DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `proposta_id`           INT(11)       NULL     COMMENT 'FK crm_propostas.id — proposta gerada ao criar OS',
  `pedido_venda_id`       INT(11)       NULL     COMMENT 'FK est_pedidos_venda.id — gerado ao aceitar proposta',
  `conta_receber_id`      INT(11)       NULL     COMMENT 'FK contasreceber.id — gerado ao faturar',
  `observacoes`           TEXT          NULL,
  `token_impressao`       VARCHAR(64)   NULL     COMMENT 'Token para impressão pública',
  `created_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── PASSO 4: Índices da tabela manut_ordens_servico ────────────────────────

ALTER TABLE `manut_ordens_servico`
  ADD INDEX `idx_os_usuario`       (`usuario_id`);

ALTER TABLE `manut_ordens_servico`
  ADD INDEX `idx_os_numero`        (`numero`);

ALTER TABLE `manut_ordens_servico`
  ADD INDEX `idx_os_status`        (`status`);

ALTER TABLE `manut_ordens_servico`
  ADD INDEX `idx_os_cliente`       (`cliente_id`);

ALTER TABLE `manut_ordens_servico`
  ADD INDEX `idx_os_equipamento`   (`equipamento_id`);

ALTER TABLE `manut_ordens_servico`
  ADD INDEX `idx_os_proposta`      (`proposta_id`);

ALTER TABLE `manut_ordens_servico`
  ADD INDEX `idx_os_pedido_venda`  (`pedido_venda_id`);

-- ─── PASSO 5: Tabela de Itens de Troca/Peças da O.S ─────────────────────────
-- Registra tudo que foi trocado ou feito na manutenção, vindo de Produtos.

CREATE TABLE `manut_os_trocas` (
  `id`                    INT(11)       NOT NULL AUTO_INCREMENT,
  `os_id`                 INT(11)       NOT NULL COMMENT 'FK manut_ordens_servico.id',
  `produto_id`            INT(11)       NULL     COMMENT 'FK produtos.id',
  `produto_codigo`        VARCHAR(50)   NULL,
  `descricao`             VARCHAR(500)  NOT NULL COMMENT 'O que foi trocado/feito',
  `unidade`               VARCHAR(20)   NULL DEFAULT 'UN',
  `quantidade`            DECIMAL(10,3) NOT NULL DEFAULT 1.000,
  `preco_unitario`        DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `preco_total`           DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `vida_util_meses`       INT(11)       NULL     COMMENT 'Vida útil da peça trocada (para calcular próxima troca)',
  `data_proxima_troca`    DATE          NULL     COMMENT 'Calculada: data_conclusao + vida_util_meses',
  `observacoes`           VARCHAR(500)  NULL,
  `created_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── PASSO 6: Índices da tabela manut_os_trocas ─────────────────────────────

ALTER TABLE `manut_os_trocas`
  ADD INDEX `idx_trocas_os`        (`os_id`);

ALTER TABLE `manut_os_trocas`
  ADD INDEX `idx_trocas_produto`   (`produto_id`);

-- ─── PASSO 7: Tabela de Evoluções/Histórico da O.S ──────────────────────────
-- Registra cada atualização de status e evolução do atendimento.

CREATE TABLE `manut_os_historico` (
  `id`                    INT(11)       NOT NULL AUTO_INCREMENT,
  `os_id`                 INT(11)       NOT NULL COMMENT 'FK manut_ordens_servico.id',
  `usuario_id`            INT(11)       NOT NULL,
  `usuario_nome`          VARCHAR(255)  NULL,
  `status_anterior`       VARCHAR(50)   NULL,
  `status_novo`           VARCHAR(50)   NULL,
  `descricao`             TEXT          NOT NULL COMMENT 'Descrição da evolução ou mudança',
  `created_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── PASSO 8: Índices da tabela manut_os_historico ──────────────────────────

ALTER TABLE `manut_os_historico`
  ADD INDEX `idx_oshist_os`        (`os_id`);

ALTER TABLE `manut_os_historico`
  ADD INDEX `idx_oshist_usuario`   (`usuario_id`);

-- ─── PASSO 9: Sequenciador de número da O.S ─────────────────────────────────

CREATE TABLE `manut_os_seq` (
  `usuario_id`            INT(11)       NOT NULL,
  `ano`                   YEAR          NOT NULL,
  `ultimo_numero`         INT(11)       NOT NULL DEFAULT 0,
  PRIMARY KEY (`usuario_id`, `ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ─── VALIDAÇÃO ──────────────────────────────────────────────────────────────

SELECT 'equipamentos_cliente'    AS tabela, COUNT(*) AS registros FROM `equipamentos_cliente`;
SELECT 'manut_ordens_servico'    AS tabela, COUNT(*) AS registros FROM `manut_ordens_servico`;
SELECT 'manut_os_trocas'         AS tabela, COUNT(*) AS registros FROM `manut_os_trocas`;
SELECT 'manut_os_historico'      AS tabela, COUNT(*) AS registros FROM `manut_os_historico`;
SELECT 'manut_os_seq'            AS tabela, COUNT(*) AS registros FROM `manut_os_seq`;

-- ─── ROLLBACK ───────────────────────────────────────────────────────────────
/*
DROP TABLE IF EXISTS `manut_os_seq`;
DROP TABLE IF EXISTS `manut_os_historico`;
DROP TABLE IF EXISTS `manut_os_trocas`;
DROP TABLE IF EXISTS `manut_ordens_servico`;
DROP TABLE IF EXISTS `equipamentos_cliente`;
*/
