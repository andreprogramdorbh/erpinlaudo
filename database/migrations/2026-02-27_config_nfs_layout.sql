-- ============================================================
-- Migration: Configurações de NF-s (Layout Padrão / Personalizado)
-- Data: 2026-02-27
-- Descrição: Cria tabela para armazenar configurações de emissão
--            de NFS-e via Asaas (Portal Nacional), incluindo
--            Layout Padrão e Layout Personalizado.
-- ============================================================

CREATE TABLE IF NOT EXISTS `config_nfs` (
    `id`                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `usuario_id`              INT UNSIGNED NOT NULL COMMENT 'Tenant/usuário dono da config',

    -- Tipo de layout
    `layout_tipo`             ENUM('padrao', 'personalizado') NOT NULL DEFAULT 'padrao'
                              COMMENT 'padrao = envia apenas valor+data; personalizado = envia JSON completo',

    -- Campos do Layout Padrão (fixos, configurados uma vez)
    `service_description`     VARCHAR(255) NOT NULL DEFAULT 'SERVIÇOS DE LAUDO'
                              COMMENT 'Descrição padrão do serviço para todas as NFs',
    `observations`            TEXT NULL
                              COMMENT 'Observações padrão impressas na NF',
    `municipal_service_name`  VARCHAR(255) NOT NULL DEFAULT 'Serviços de Saúde / Radiologia'
                              COMMENT 'Nome do serviço municipal configurado no Asaas',
    `municipal_service_code`  VARCHAR(30) NULL
                              COMMENT 'Código de serviço municipal (ex: 4.03)',
    `municipal_service_id`    VARCHAR(100) NULL
                              COMMENT 'ID único do serviço municipal no Asaas (alternativo ao code)',
    `cnae`                    VARCHAR(20) NULL DEFAULT '8640205'
                              COMMENT 'CNAE da empresa (ex: 8640205 para radiologia)',
    `deductions`              DECIMAL(10,2) NOT NULL DEFAULT 0.00
                              COMMENT 'Deduções padrão (não alteram o valor, apenas a base de cálculo do ISS)',

    -- Impostos padrão
    `retain_iss`              TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Reter ISS na fonte',
    `iss_aliquota`            DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Alíquota ISS (%)',
    `pis_aliquota`            DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `cofins_aliquota`         DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `csll_aliquota`           DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `inss_aliquota`           DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `ir_aliquota`             DECIMAL(5,2) NOT NULL DEFAULT 0.00,

    -- Campos do Layout Personalizado
    `json_template`           TEXT NULL
                              COMMENT 'Template JSON personalizado. Placeholders: {{value}}, {{effectiveDate}}, {{payment}}, {{descricao}}',

    -- Portal Nacional
    `emite_portal_nacional`   TINYINT(1) NOT NULL DEFAULT 1
                              COMMENT '1 = emite pelo Portal Nacional (NFS-e Nacional)',
    `serie_nf`                VARCHAR(10) NULL
                              COMMENT 'Série da NF (80000-89999 para Portal Nacional)',

    -- Controle
    `ativo`                   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_config_nfs_usuario` (`usuario_id`),
    INDEX `idx_config_nfs_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configurações de emissão de NFS-e via Asaas (Portal Nacional)';
