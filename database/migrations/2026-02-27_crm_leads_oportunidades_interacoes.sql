-- ============================================================
-- Módulo CRM — Leads, Oportunidades e Interações
-- Criado em: 2026-02-27
-- Segmento: Saúde / Radiologia
-- ============================================================

-- -------------------------------------------------------
-- 1. crm_leads
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS crm_leads (
  id                        INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id                INT NOT NULL                    COMMENT 'Tenant (users.id)',

  -- Identificação
  nome_lead                 VARCHAR(255) NOT NULL           COMMENT 'Nome da empresa ou pessoa',
  email                     VARCHAR(255) NULL,
  telefone                  VARCHAR(20)  NULL,
  celular                   VARCHAR(20)  NULL,
  cnpj                      VARCHAR(18)  NULL               COMMENT 'CNPJ para busca automática',
  cpf                       VARCHAR(14)  NULL,
  tipo_pessoa               ENUM('PJ','PF') NOT NULL DEFAULT 'PJ',

  -- Dados preenchidos via CnpjService
  razao_social              VARCHAR(255) NULL,
  nome_fantasia             VARCHAR(255) NULL,
  cnae_principal            VARCHAR(20)  NULL,
  descricao_cnae            VARCHAR(255) NULL,
  endereco                  VARCHAR(255) NULL,
  numero                    VARCHAR(20)  NULL,
  complemento               VARCHAR(100) NULL,
  bairro                    VARCHAR(100) NULL,
  cidade                    VARCHAR(100) NULL,
  estado                    CHAR(2)      NULL,
  cep                       VARCHAR(9)   NULL,

  -- Qualificação comercial
  origem                    ENUM(
                              'indicacao',
                              'site',
                              'evento',
                              'linkedin',
                              'prospeccao_ativa',
                              'parceiro',
                              'outro'
                            ) NOT NULL DEFAULT 'outro'       COMMENT 'Como o lead chegou',
  status_lead               ENUM(
                              'novo',
                              'contatado',
                              'qualificado',
                              'descartado'
                            ) NOT NULL DEFAULT 'novo',

  -- Campos específicos de Radiologia / Saúde
  segmento_principal        ENUM(
                              'clinica_imagem',
                              'hospital',
                              'upa_pronto_socorro',
                              'laboratorio',
                              'clinica_ortopedica',
                              'clinica_oncologica',
                              'consultorio_medico',
                              'outro'
                            ) NULL                          COMMENT 'Tipo de estabelecimento',
  especialidades_interesse  TEXT NULL                       COMMENT 'JSON array: ["Tomografia","Ressonância","Raio-X","Mamografia","Ultrassom","Densitometria","PET-CT","Outro"]',
  volume_exames_mes         INT NULL                        COMMENT 'Estimativa de exames/mês',
  equipamentos_possui       TEXT NULL                       COMMENT 'Equipamentos que o lead já possui',
  sistema_atual             VARCHAR(255) NULL               COMMENT 'Sistema/software que utiliza atualmente',
  num_medicos               INT NULL                        COMMENT 'Quantidade de médicos/radiologistas',
  num_unidades              INT NULL                        COMMENT 'Quantidade de unidades/filiais',
  acreditacao               VARCHAR(100) NULL               COMMENT 'Acreditações (ONA, JCI, etc.)',

  -- Contato decisor
  responsavel_nome          VARCHAR(255) NULL,
  responsavel_cargo         VARCHAR(100) NULL               COMMENT 'Ex: Diretor Clínico, Gestor de TI, Sócio',
  responsavel_email         VARCHAR(255) NULL,
  responsavel_telefone      VARCHAR(20)  NULL,

  -- Produtos de interesse
  produtos_interesse        TEXT NULL                       COMMENT 'JSON array com IDs ou nomes de produtos/serviços',

  -- Controle
  data_proximo_contato      DATE NULL                       COMMENT 'Agendamento de follow-up',
  observacoes               TEXT NULL,
  convertido_em             ENUM('oportunidade','cliente') NULL COMMENT 'Indica se foi convertido',
  convertido_id             INT NULL                        COMMENT 'ID da oportunidade ou cliente gerado',
  convertido_em_data        DATETIME NULL,

  created_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_crm_lead_usuario   (usuario_id),
  INDEX idx_crm_lead_status    (status_lead),
  INDEX idx_crm_lead_segmento  (segmento_principal),
  INDEX idx_crm_lead_proximo   (data_proximo_contato),
  CONSTRAINT fk_crm_lead_usuario FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 2. crm_oportunidades
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS crm_oportunidades (
  id                        INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id                INT NOT NULL                    COMMENT 'Tenant (users.id)',
  lead_id                   INT NULL                        COMMENT 'Lead de origem (crm_leads.id)',
  cliente_id                INT NULL                        COMMENT 'Cliente existente (clientes.id) — up-sell/cross-sell',

  titulo_oportunidade       VARCHAR(255) NOT NULL           COMMENT 'Ex: Contrato Laudos TC — Hospital São Lucas',
  etapa_funil               ENUM(
                              'qualificacao',
                              'proposta',
                              'negociacao',
                              'fechamento'
                            ) NOT NULL DEFAULT 'qualificacao',
  valor_estimado            DECIMAL(12,2) NULL,
  data_fechamento_prevista  DATE NULL,
  probabilidade_sucesso     TINYINT UNSIGNED NULL           COMMENT '0-100 (%)',

  status_oportunidade       ENUM('aberta','ganha','perdida') NOT NULL DEFAULT 'aberta',
  motivo_perda              VARCHAR(255) NULL,

  -- Campos específicos de Radiologia
  modalidade_principal      ENUM(
                              'tomografia',
                              'ressonancia',
                              'raio_x',
                              'mamografia',
                              'ultrassom',
                              'densitometria',
                              'pet_ct',
                              'laudos_gerais',
                              'outro'
                            ) NULL                          COMMENT 'Principal modalidade de interesse',
  tipo_contrato             ENUM(
                              'laudo_avulso',
                              'contrato_mensal',
                              'contrato_anual',
                              'projeto_implantacao',
                              'outro'
                            ) NULL,
  volume_estimado_mes       INT NULL                        COMMENT 'Volume mensal estimado de exames',

  observacoes               TEXT NULL,

  created_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_crm_op_usuario  (usuario_id),
  INDEX idx_crm_op_lead     (lead_id),
  INDEX idx_crm_op_cliente  (cliente_id),
  INDEX idx_crm_op_etapa    (etapa_funil),
  INDEX idx_crm_op_status   (status_oportunidade),
  CONSTRAINT fk_crm_op_usuario  FOREIGN KEY (usuario_id)  REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_crm_op_lead     FOREIGN KEY (lead_id)     REFERENCES crm_leads(id) ON DELETE SET NULL,
  CONSTRAINT fk_crm_op_cliente  FOREIGN KEY (cliente_id)  REFERENCES clientes(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 3. crm_interacoes
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS crm_interacoes (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id        INT NOT NULL                    COMMENT 'Quem registrou a interação (users.id)',
  related_id        INT NOT NULL                    COMMENT 'ID do lead ou oportunidade',
  related_type      ENUM('lead','oportunidade') NOT NULL,
  data_interacao    DATETIME NOT NULL               COMMENT 'Data e hora da interação',
  tipo_interacao    ENUM(
                      'email',
                      'telefone',
                      'whatsapp',
                      'reuniao_presencial',
                      'reuniao_online',
                      'visita_tecnica',
                      'proposta_enviada',
                      'contrato_enviado',
                      'outro'
                    ) NOT NULL,
  resumo            TEXT NOT NULL                   COMMENT 'O que foi discutido / resultado',
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_crm_int_related  (related_type, related_id),
  INDEX idx_crm_int_usuario  (usuario_id),
  INDEX idx_crm_int_data     (data_interacao),
  CONSTRAINT fk_crm_int_usuario FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
