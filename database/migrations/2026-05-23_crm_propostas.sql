-- ============================================================
-- CRM Propostas
-- ============================================================

CREATE TABLE IF NOT EXISTS crm_propostas (
  id                    INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id            INT NOT NULL                    COMMENT 'Tenant (users.id)',
  numero                VARCHAR(20) NOT NULL            COMMENT 'Ex: PROP-2026-0001',
  oportunidade_id       INT NULL                        COMMENT 'Oportunidade de origem',
  lead_id               INT NULL                        COMMENT 'Lead de origem',
  cliente_id            INT NULL                        COMMENT 'Cliente existente',

  -- Dados do cliente (snapshot no momento da proposta)
  cliente_nome          VARCHAR(255) NOT NULL,
  cliente_razao_social  VARCHAR(255) NULL,
  cliente_cnpj_cpf      VARCHAR(20)  NULL,
  cliente_email         VARCHAR(255) NULL,
  cliente_telefone      VARCHAR(20)  NULL,
  cliente_endereco      TEXT         NULL,
  cliente_cidade        VARCHAR(100) NULL,
  cliente_estado        CHAR(2)      NULL,
  cliente_cep           VARCHAR(10)  NULL,
  cliente_responsavel   VARCHAR(255) NULL,

  -- Dados da proposta
  titulo                VARCHAR(255) NOT NULL,
  descricao             TEXT         NULL,
  validade_proposta     DATE         NOT NULL           COMMENT 'Data de validade da proposta',
  status                ENUM(
                          'gerada',
                          'enviada',
                          'visualizada',
                          'aceita',
                          'recusada',
                          'expirada'
                        ) NOT NULL DEFAULT 'gerada',

  -- Valores
  subtotal              DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  desconto_tipo         ENUM('percentual','fixo') NULL,
  desconto_valor        DECIMAL(12,2) NULL DEFAULT 0.00,
  desconto_total        DECIMAL(12,2) NULL DEFAULT 0.00,
  total                 DECIMAL(12,2) NOT NULL DEFAULT 0.00,

  -- Entrega
  prazo_entrega         VARCHAR(100) NULL              COMMENT 'Ex: 15 dias úteis',
  condicao_pagamento    VARCHAR(255) NULL              COMMENT 'Ex: 50% entrada + 50% na entrega',
  frete_tipo            ENUM('cif','fob','sem_frete','a_calcular') NULL DEFAULT 'a_calcular',
  frete_valor           DECIMAL(10,2) NULL DEFAULT 0.00,
  local_entrega         TEXT NULL,

  -- Observações
  observacoes           TEXT NULL,
  notas_internas        TEXT NULL                      COMMENT 'Não aparece no PDF',

  -- Controle
  enviado_em            DATETIME NULL,
  visualizado_em        DATETIME NULL,
  aceito_em             DATETIME NULL,
  pdf_path              VARCHAR(500) NULL,
  token_acesso          VARCHAR(64) NULL               COMMENT 'Token para link público de aceite',

  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_prop_usuario     (usuario_id),
  INDEX idx_prop_oportunidade(oportunidade_id),
  INDEX idx_prop_cliente     (cliente_id),
  INDEX idx_prop_status      (status),
  INDEX idx_prop_numero      (numero),
  INDEX idx_prop_token       (token_acesso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Itens da proposta
-- ============================================================

CREATE TABLE IF NOT EXISTS crm_proposta_itens (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  proposta_id     INT NOT NULL,
  produto_id      INT NULL                COMMENT 'Referência futura ao módulo de estoque',
  codigo          VARCHAR(50)  NULL,
  descricao       VARCHAR(500) NOT NULL,
  unidade         VARCHAR(20)  NULL DEFAULT 'un',
  quantidade      DECIMAL(10,3) NOT NULL DEFAULT 1.000,
  preco_custo     DECIMAL(12,2) NULL DEFAULT 0.00,
  margem_lucro    DECIMAL(5,2) NULL DEFAULT 0.00  COMMENT 'Percentual de margem',
  preco_unitario  DECIMAL(12,2) NOT NULL,
  desconto_item   DECIMAL(5,2) NULL DEFAULT 0.00  COMMENT 'Desconto % por item',
  total_item      DECIMAL(12,2) NOT NULL,
  ordem           SMALLINT NOT NULL DEFAULT 0,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_item_proposta (proposta_id),
  CONSTRAINT fk_item_proposta FOREIGN KEY (proposta_id)
    REFERENCES crm_propostas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Histórico de status da proposta
-- ============================================================

CREATE TABLE IF NOT EXISTS crm_proposta_historico (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  proposta_id   INT NOT NULL,
  usuario_id    INT NULL,
  status_de     VARCHAR(30) NULL,
  status_para   VARCHAR(30) NOT NULL,
  observacao    TEXT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_hist_proposta (proposta_id),
  CONSTRAINT fk_hist_proposta FOREIGN KEY (proposta_id)
    REFERENCES crm_propostas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
