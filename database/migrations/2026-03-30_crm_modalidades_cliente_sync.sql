-- =============================================================================
-- Migration: Sincronização Cliente ↔ CrmLead + Tabela de Modalidades Dinâmicas
-- Data: 2026-03-30
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Adicionar campos faltantes na tabela clientes (espelhando crm_leads)
-- -----------------------------------------------------------------------------

-- Mídias sociais
ALTER TABLE clientes
  ADD COLUMN IF NOT EXISTS linkedin VARCHAR(255) NULL COMMENT 'Perfil LinkedIn' AFTER instagram;

-- Perfil clínico/comercial (vindo do CRM)
ALTER TABLE clientes
  ADD COLUMN IF NOT EXISTS segmento_principal      VARCHAR(100) NULL COMMENT 'Segmento: clinica_imagem, hospital, etc.' AFTER descricao_cnae,
  ADD COLUMN IF NOT EXISTS especialidades_interesse TEXT         NULL COMMENT 'JSON array de especialidades/modalidades' AFTER segmento_principal,
  ADD COLUMN IF NOT EXISTS volume_exames_mes        INT          NULL COMMENT 'Estimativa de exames/mês'                AFTER especialidades_interesse,
  ADD COLUMN IF NOT EXISTS equipamentos_possui      TEXT         NULL COMMENT 'Equipamentos que a clínica possui'       AFTER volume_exames_mes,
  ADD COLUMN IF NOT EXISTS sistema_atual            VARCHAR(255) NULL COMMENT 'Sistema/software atual'                  AFTER equipamentos_possui,
  ADD COLUMN IF NOT EXISTS num_medicos              INT          NULL COMMENT 'Número de médicos/radiologistas'         AFTER sistema_atual,
  ADD COLUMN IF NOT EXISTS num_unidades             INT          NULL COMMENT 'Número de unidades'                      AFTER num_medicos,
  ADD COLUMN IF NOT EXISTS acreditacao              VARCHAR(100) NULL COMMENT 'Acreditações (ONA, JCI, etc.)'           AFTER num_unidades;

-- Responsável técnico/comercial
ALTER TABLE clientes
  ADD COLUMN IF NOT EXISTS responsavel_nome      VARCHAR(255) NULL AFTER acreditacao,
  ADD COLUMN IF NOT EXISTS responsavel_cargo     VARCHAR(100) NULL COMMENT 'Ex: Diretor Clínico, Gestor de TI' AFTER responsavel_nome,
  ADD COLUMN IF NOT EXISTS responsavel_email     VARCHAR(255) NULL AFTER responsavel_cargo,
  ADD COLUMN IF NOT EXISTS responsavel_telefone  VARCHAR(20)  NULL AFTER responsavel_email;

-- Rastreabilidade CRM (qual lead originou este cliente)
ALTER TABLE clientes
  ADD COLUMN IF NOT EXISTS crm_lead_id INT UNSIGNED NULL COMMENT 'ID do lead CRM que originou este cliente' AFTER responsavel_telefone,
  ADD INDEX  IF NOT EXISTS idx_clientes_crm_lead (crm_lead_id);

-- -----------------------------------------------------------------------------
-- 2. Tabela de linhas dinâmicas de modalidades por oportunidade
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS crm_oportunidade_modalidades (
  id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  oportunidade_id     INT UNSIGNED    NOT NULL,
  modalidade          VARCHAR(100)    NOT NULL COMMENT 'Chave da modalidade (ex: tomografia, pacs)',
  tipo_contrato       VARCHAR(100)    NULL     COMMENT 'Tipo de contrato/comercialização',
  volume_estimado_mes INT             NULL     COMMENT 'Volume estimado de exames/mês para esta modalidade',
  observacao          VARCHAR(500)    NULL     COMMENT 'Observação específica desta linha',
  ordem               TINYINT         NOT NULL DEFAULT 1 COMMENT 'Ordem de exibição',
  created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  INDEX idx_crm_op_mod_op (oportunidade_id),

  CONSTRAINT fk_crm_op_mod_op
    FOREIGN KEY (oportunidade_id)
    REFERENCES crm_oportunidades(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Linhas dinâmicas de modalidades/contratos por oportunidade CRM';

-- -----------------------------------------------------------------------------
-- 3. Adicionar "Comercialização de Software" ao ENUM tipo_contrato (se for ENUM)
--    (Se for VARCHAR, nenhuma alteração necessária)
-- -----------------------------------------------------------------------------
-- ALTER TABLE crm_oportunidades
--   MODIFY COLUMN tipo_contrato ENUM(
--     'laudo_avulso','contrato_mensal','contrato_anual',
--     'projeto_implantacao','comercializacao_software','outro'
--   ) NULL;

-- Se tipo_contrato já for VARCHAR(100), apenas o PHP controla os valores — nada a fazer.
