-- =============================================================================
-- Migration: Tabela contrato_exames
-- Data: 2026-04-07
-- Compatível com: MariaDB 10.3.2+ (CREATE TABLE IF NOT EXISTS nativo)
--
-- Descrição:
--   Cria a tabela contrato_exames para armazenar os exames vinculados a um
--   contrato com possibilidade de override dos valores da tabela de exames.
--   Para contratos de médico (prestador): valor_rotina e valor_urgencia
--   Para contratos de cliente: valor_venda_rotina e valor_venda_urgencia
--   Quando usa_valor_custom = 1, os valores do contrato são a base contábil.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `contrato_exames` (
    `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`            INT UNSIGNED NOT NULL,
    `contrato_id`           INT UNSIGNED NOT NULL,
    `tabela_exame_id`       INT UNSIGNED NOT NULL,

    -- Valores para contratos de médico (prestador)
    `valor_rotina`          DECIMAL(12,2) NOT NULL DEFAULT 0.00
        COMMENT 'Valor de rotina para o médico (override do contrato)',
    `valor_urgencia`        DECIMAL(12,2) NOT NULL DEFAULT 0.00
        COMMENT 'Valor de urgência para o médico (override do contrato)',

    -- Valores para contratos de cliente (venda)
    `valor_venda_rotina`    DECIMAL(12,2) NOT NULL DEFAULT 0.00
        COMMENT 'Valor de venda rotina para o cliente (override do contrato)',
    `valor_venda_urgencia`  DECIMAL(12,2) NOT NULL DEFAULT 0.00
        COMMENT 'Valor de venda urgência para o cliente (override do contrato)',

    -- Flag: indica se os valores do contrato sobrescrevem a tabela
    `usa_valor_custom`      TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = usa valores do contrato como base contábil; 0 = usa tabela de exames',

    `observacoes`           TEXT NULL,
    `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_contrato_exame` (`contrato_id`, `tabela_exame_id`),
    KEY `idx_contrato_id`     (`contrato_id`),
    KEY `idx_tabela_exame_id` (`tabela_exame_id`),
    KEY `idx_usuario_id`      (`usuario_id`),

    CONSTRAINT `fk_ce_contrato`
        FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ce_tabela_exame`
        FOREIGN KEY (`tabela_exame_id`) REFERENCES `tabela_exames` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Exames vinculados a contratos com possibilidade de override de valores';

-- Verificação
SELECT
    TABLE_NAME,
    TABLE_COMMENT,
    ENGINE,
    TABLE_COLLATION
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'contrato_exames';
