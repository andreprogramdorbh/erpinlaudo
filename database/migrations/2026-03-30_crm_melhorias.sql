-- ============================================================
-- Migration: CRM Melhorias — 2026-03-30
-- Adiciona campos de mídias sociais em crm_leads,
-- modalidades extras (PACS, RIS, HIS, Teleradiologia),
-- múltiplas modalidades em oportunidades e data_proximo_contato
-- ============================================================

-- 1. Campos de mídias sociais em crm_leads
ALTER TABLE crm_leads
  ADD COLUMN IF NOT EXISTS website   VARCHAR(255) NULL COMMENT 'Site da empresa'       AFTER celular,
  ADD COLUMN IF NOT EXISTS instagram VARCHAR(255) NULL COMMENT 'Perfil Instagram'      AFTER website,
  ADD COLUMN IF NOT EXISTS linkedin  VARCHAR(255) NULL COMMENT 'Perfil LinkedIn'       AFTER instagram;

-- 2. Múltiplas modalidades em crm_oportunidades (JSON) e data_proximo_contato
ALTER TABLE crm_oportunidades
  ADD COLUMN IF NOT EXISTS modalidades_interesse TEXT NULL COMMENT 'JSON array com todas as modalidades de interesse' AFTER modalidade_principal,
  ADD COLUMN IF NOT EXISTS data_proximo_contato  DATE NULL COMMENT 'Data do próximo contato agendado'                AFTER observacoes;

-- 3. Atualizar ENUM modalidade_principal em crm_oportunidades para incluir PACS, RIS, HIS, Teleradiologia
ALTER TABLE crm_oportunidades
  MODIFY COLUMN modalidade_principal ENUM(
    'tomografia',
    'ressonancia',
    'raio_x',
    'mamografia',
    'ultrassom',
    'densitometria',
    'pet_ct',
    'laudos_gerais',
    'pacs',
    'ris',
    'his',
    'teleradiologia',
    'outro'
  ) NULL COMMENT 'Principal modalidade de interesse';

-- 4. Atualizar ENUM especialidades_interesse (crm_leads usa TEXT/JSON, não ENUM)
-- Nenhuma alteração de coluna necessária — o campo já é TEXT (JSON array)
-- As novas opções são adicionadas apenas no PHP (CrmLead::ESPECIALIDADES)

-- 5. Índice para data_proximo_contato em oportunidades
ALTER TABLE crm_oportunidades
  ADD INDEX IF NOT EXISTS idx_crm_op_proximo (data_proximo_contato);
