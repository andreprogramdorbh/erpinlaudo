-- =============================================================================
-- Migration: Módulo Contratos + Apuração de Prestador/Cliente
-- Data: 2026-03-27
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Tabela principal de contratos
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contratos` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `usuario_id`       INT NOT NULL,
    `numero`           VARCHAR(30)  NOT NULL COMMENT 'Número único do contrato',
    `nome`             VARCHAR(255) NOT NULL,
    `tipo_parte`       ENUM('medico','cliente') NOT NULL COMMENT 'medico=pagamento, cliente=recebimento',
    `medico_id`        INT UNSIGNED NULL DEFAULT NULL,
    `cliente_id`       INT UNSIGNED NULL DEFAULT NULL,
    `data_inicio`      DATE NOT NULL,
    `data_fim`         DATE NULL DEFAULT NULL,
    `vigencia_tipo`    ENUM('determinado','indeterminado') NOT NULL DEFAULT 'determinado',
    `recorrencia`      ENUM('diario','semanal','mensal','anual') NOT NULL DEFAULT 'mensal',
    `valor`            DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `observacoes`      TEXT NULL,
    `status`           ENUM('ativo','encerrado','suspenso') NOT NULL DEFAULT 'ativo',
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_contratos_usuario`    (`usuario_id`),
    INDEX `idx_contratos_medico`     (`medico_id`),
    INDEX `idx_contratos_cliente`    (`cliente_id`),
    INDEX `idx_contratos_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Grupos de modalidade vinculados ao contrato (para contratos de médico)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contrato_modalidades` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `contrato_id`  INT UNSIGNED NOT NULL,
    `modalidade`   VARCHAR(20) NOT NULL COMMENT 'TC, RM, RX, US, DX, CR, etc.',
    `exame_id`     INT UNSIGNED NULL DEFAULT NULL COMMENT 'FK tabela_exames (opcional)',
    INDEX `idx_contrato_modalidades_contrato` (`contrato_id`),
    CONSTRAINT `fk_contrato_modalidades_contrato`
        FOREIGN KEY (`contrato_id`) REFERENCES `contratos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Anexos do contrato
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contratos_anexos` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `contrato_id`   INT UNSIGNED NOT NULL,
    `usuario_id`    INT NOT NULL,
    `file_path`     VARCHAR(500) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `file_size`     INT UNSIGNED NULL,
    `mime_type`     VARCHAR(100) NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_contratos_anexos_contrato` (`contrato_id`),
    CONSTRAINT `fk_contratos_anexos_contrato`
        FOREIGN KEY (`contrato_id`) REFERENCES `contratos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Apurações (cabeçalho)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `apuracoes` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `usuario_id`      INT NOT NULL,
    `contrato_id`     INT UNSIGNED NOT NULL,
    `numero`          VARCHAR(30) NOT NULL COMMENT 'Número aleatório gerado',
    `tipo`            ENUM('prestador','cliente') NOT NULL,
    `medico_id`       INT UNSIGNED NULL DEFAULT NULL,
    `cliente_id`      INT UNSIGNED NULL DEFAULT NULL,
    `periodo_inicio`  DATE NULL,
    `periodo_fim`     DATE NULL,
    `total_exames`    INT UNSIGNED NOT NULL DEFAULT 0,
    `total_normal`    INT UNSIGNED NOT NULL DEFAULT 0,
    `total_urgencia`  INT UNSIGNED NOT NULL DEFAULT 0,
    `valor_total`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status`          ENUM('rascunho','processando','concluido','faturado','erro') NOT NULL DEFAULT 'rascunho',
    `origem`          ENUM('manual','automatico','pacs') NOT NULL DEFAULT 'manual',
    `arquivo_import`  VARCHAR(500) NULL COMMENT 'Caminho do arquivo importado',
    `log_execucao`    TEXT NULL COMMENT 'Log do processo de apuração',
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_apuracoes_usuario`   (`usuario_id`),
    INDEX `idx_apuracoes_contrato`  (`contrato_id`),
    INDEX `idx_apuracoes_medico`    (`medico_id`),
    INDEX `idx_apuracoes_status`    (`status`),
    CONSTRAINT `fk_apuracoes_contrato`
        FOREIGN KEY (`contrato_id`) REFERENCES `contratos`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Itens da apuração (cada linha do CSV/XLSX importado)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `apuracao_itens` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `apuracao_id`       INT UNSIGNED NOT NULL,
    `linha_original`    INT UNSIGNED NULL COMMENT 'Número da linha no arquivo importado',
    `unidade`           VARCHAR(255) NULL,
    `medico_nome`       VARCHAR(255) NULL,
    `medico_crm`        VARCHAR(50)  NULL,
    `revisor`           VARCHAR(255) NULL,
    `data_revisao`      DATETIME NULL,
    `modalidade`        VARCHAR(20)  NULL,
    `study_description` VARCHAR(500) NULL,
    `paciente_nome`     VARCHAR(255) NULL,
    `paciente_id`       VARCHAR(50)  NULL,
    `prioridade`        VARCHAR(30)  NULL COMMENT 'Normal ou Urgencia',
    `origem`            VARCHAR(100) NULL,
    `registro`          VARCHAR(100) NULL,
    `data_estudo`       DATETIME NULL,
    `data_conclusao`    DATETIME NULL,
    `sla`               VARCHAR(100) NULL,
    `accession_number`  VARCHAR(100) NULL,
    `visita`            VARCHAR(100) NULL,
    `convenio`          VARCHAR(255) NULL,
    `valor_importado`   DECIMAL(12,2) NULL DEFAULT 0.00,
    `valor_exame_import` DECIMAL(12,2) NULL DEFAULT 0.00,
    -- Valores calculados após matching com tabela_exames
    `exame_id`          INT UNSIGNED NULL DEFAULT NULL COMMENT 'FK tabela_exames',
    `valor_calculado`   DECIMAL(12,2) NULL DEFAULT 0.00,
    `tipo_prioridade`   ENUM('normal','urgencia') NOT NULL DEFAULT 'normal',
    `status_item`       ENUM('ok','sem_match','erro') NOT NULL DEFAULT 'ok',
    `obs_item`          VARCHAR(500) NULL,
    `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_apuracao_itens_apuracao`  (`apuracao_id`),
    INDEX `idx_apuracao_itens_modalidade` (`modalidade`),
    CONSTRAINT `fk_apuracao_itens_apuracao`
        FOREIGN KEY (`apuracao_id`) REFERENCES `apuracoes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. Layout de exames (padronização para importação - configurado em /perfil)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `layout_exames` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `usuario_id`      INT NOT NULL,
    `nome`            VARCHAR(255) NOT NULL COMMENT 'Nome do layout (ex: PACS Tasy, RIS Pixeon)',
    `descricao`       TEXT NULL,
    `formato`         ENUM('xlsx','csv','xml') NOT NULL DEFAULT 'xlsx',
    `mapeamento_json` LONGTEXT NOT NULL COMMENT 'JSON com mapeamento de colunas do arquivo para campos do sistema',
    `ativo`           TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_layout_exames_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. Inserir layout padrão baseado no arquivo analisado
-- -----------------------------------------------------------------------------
INSERT INTO `layout_exames` (`usuario_id`, `nome`, `descricao`, `formato`, `mapeamento_json`, `ativo`)
SELECT 
    u.id,
    'Layout Padrão InLaudo (XLSX)',
    'Layout padrão baseado no relatório de conclusão detalhado exportado do PACS/RIS. Colunas: #, Unidade, ID, Medico, CRM, Revisor, Data/Hora Revisão, Modalidade, Study Description, Paciente, Paciente ID, Prioridade, Origem, Registro, Data Estudo, Data Conclusão, SLA, Accession number, Visita, Convenio, Valor, Valor do exame',
    'xlsx',
    '{"col_seq":"A","col_unidade":"B","col_id":"C","col_medico":"D","col_crm":"E","col_revisor":"F","col_data_revisao":"G","col_modalidade":"H","col_study_description":"I","col_paciente":"J","col_paciente_id":"K","col_prioridade":"L","col_origem":"M","col_registro":"N","col_data_estudo":"O","col_data_conclusao":"P","col_sla":"Q","col_accession_number":"R","col_visita":"S","col_convenio":"T","col_valor":"U","col_valor_exame":"V","linha_inicio":2,"campo_prioridade_urgencia":"Urgente","campo_prioridade_normal":"Normal"}',
    1
FROM users u
WHERE u.id = (SELECT MIN(id) FROM users)
LIMIT 1;
