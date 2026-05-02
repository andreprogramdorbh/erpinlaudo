-- ============================================================
-- Migration: Tabela de Anexos do CRM (Leads e Oportunidades)
-- Data: 2026-05-02
-- ============================================================

CREATE TABLE IF NOT EXISTS `crm_anexos` (
  `id`              INT(11)       NOT NULL AUTO_INCREMENT,
  `usuario_id`      INT(11)       NOT NULL,
  `related_type`    ENUM('lead','oportunidade') NOT NULL COMMENT 'Tipo do registro vinculado',
  `related_id`      INT(11)       NOT NULL COMMENT 'ID do lead ou oportunidade',
  `nome_documento`  VARCHAR(255)  NOT NULL COMMENT 'Nome descritivo do documento',
  `tipo_documento`  ENUM('contrato','termo_aceite','proposta_comercial','edital','outro') NOT NULL DEFAULT 'outro' COMMENT 'Tipo do documento',
  `file_path`       VARCHAR(500)  NOT NULL COMMENT 'Caminho relativo do arquivo no servidor',
  `original_name`   VARCHAR(255)  NOT NULL COMMENT 'Nome original do arquivo enviado',
  `mime_type`       VARCHAR(100)  DEFAULT NULL,
  `file_size`       INT(11)       DEFAULT NULL COMMENT 'Tamanho em bytes',
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_related`     (`related_type`, `related_id`),
  KEY `idx_usuario_id`  (`usuario_id`),
  KEY `idx_tipo`        (`tipo_documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Anexos de documentos vinculados a Leads e Oportunidades do CRM';
