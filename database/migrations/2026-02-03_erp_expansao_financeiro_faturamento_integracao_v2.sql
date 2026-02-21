-- Migration: ERP InLaudo - Expansao Financeiro, Faturamento e Integracoes
-- Date: 2026-02-03
-- Rules: ONLY CREATE TABLE / ADD COLUMN. Never drop/rename existing columns.

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS plano_contas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  codigo VARCHAR(50) NOT NULL,
  nome VARCHAR(255) NOT NULL,
  tipo ENUM('Receita','Despesa') NOT NULL,
  nivel INT NOT NULL DEFAULT 1,
  conta_pai_id INT NULL,
  status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_plano_contas_usuario (usuario_id),
  INDEX idx_plano_contas_codigo (codigo),
  INDEX idx_plano_contas_conta_pai (conta_pai_id),
  CONSTRAINT fk_plano_contas_usuario FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_plano_contas_pai FOREIGN KEY (conta_pai_id) REFERENCES plano_contas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fornecedores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  nome VARCHAR(255) NOT NULL,
  documento VARCHAR(30) NULL,
  email VARCHAR(255) NULL,
  telefone VARCHAR(30) NULL,
  status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_fornecedores_usuario (usuario_id),
  CONSTRAINT fk_fornecedores_usuario FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contas_pagar (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  plano_conta_id INT NOT NULL,
  fornecedor_id INT NULL,
  descricao VARCHAR(255) NOT NULL,
  valor DECIMAL(15,2) NOT NULL,
  data_vencimento DATE NOT NULL,
  data_pagamento DATE NULL,
  codigo_barras VARCHAR(255) NULL,
  recorrente TINYINT(1) NOT NULL DEFAULT 0,
  recorrencia_tipo ENUM('mensal','semanal','anual','customizada') NULL,
  recorrencia_intervalo INT NULL,
  status ENUM('aberta','paga','cancelada') NOT NULL DEFAULT 'aberta',
  observacoes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_contas_pagar_usuario (usuario_id),
  INDEX idx_contas_pagar_plano_conta (plano_conta_id),
  INDEX idx_contas_pagar_fornecedor (fornecedor_id),
  INDEX idx_contas_pagar_vencimento (data_vencimento),
  CONSTRAINT fk_contas_pagar_usuario FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_contas_pagar_plano_conta FOREIGN KEY (plano_conta_id) REFERENCES plano_contas(id) ON DELETE RESTRICT,
  CONSTRAINT fk_contas_pagar_fornecedor FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contas_pagar_anexos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  conta_pagar_id INT NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NULL,
  file_size INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_contas_pagar_anexos_usuario (usuario_id),
  INDEX idx_contas_pagar_anexos_conta (conta_pagar_id),
  CONSTRAINT fk_contas_pagar_anexos_usuario FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_contas_pagar_anexos_conta FOREIGN KEY (conta_pagar_id) REFERENCES contas_pagar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contas_receber (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  cliente_id INT NOT NULL,
  plano_conta_id INT NOT NULL,
  descricao VARCHAR(255) NOT NULL,
  valor DECIMAL(15,2) NOT NULL,
  data_vencimento DATE NOT NULL,
  data_recebimento DATE NULL,
  status ENUM('aberta','recebida','cancelada') NOT NULL DEFAULT 'aberta',
  observacoes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_contas_receber_usuario (usuario_id),
  INDEX idx_contas_receber_cliente (cliente_id),
  INDEX idx_contas_receber_plano_conta (plano_conta_id),
  INDEX idx_contas_receber_vencimento (data_vencimento),
  CONSTRAINT fk_contas_receber_usuario FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_contas_receber_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_contas_receber_plano_conta FOREIGN KEY (plano_conta_id) REFERENCES plano_contas(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notas_fiscais (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  cliente_id INT NOT NULL,
  numero_nf VARCHAR(50) NOT NULL,
  serie VARCHAR(20) NOT NULL,
  valor_total DECIMAL(15,2) NOT NULL,
  data_emissao DATE NOT NULL,
  status ENUM('rascunho','emitida','cancelada','importada') NOT NULL DEFAULT 'rascunho',
  xml_path VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_notas_fiscais_usuario (usuario_id),
  INDEX idx_notas_fiscais_cliente (cliente_id),
  INDEX idx_notas_fiscais_numero (numero_nf),
  INDEX idx_notas_fiscais_emissao (data_emissao),
  CONSTRAINT fk_notas_fiscais_usuario FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_notas_fiscais_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notas_fiscais_importacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  arquivo_xml_path VARCHAR(500) NOT NULL,
  status ENUM('sucesso','falha') NOT NULL DEFAULT 'sucesso',
  mensagem TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_nf_import_usuario (usuario_id),
  CONSTRAINT fk_nf_import_usuario FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS integracoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  nome VARCHAR(255) NOT NULL,
  tipo ENUM('API','Webhook','Fiscal','Financeira') NOT NULL,
  status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  config_json LONGTEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_integracoes_usuario (usuario_id),
  INDEX idx_integracoes_tipo (tipo),
  CONSTRAINT fk_integracoes_usuario FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS integracoes_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  integracao_id INT NOT NULL,
  evento VARCHAR(255) NOT NULL,
  status ENUM('sucesso','falha') NOT NULL DEFAULT 'sucesso',
  details LONGTEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_integracoes_logs_usuario (usuario_id),
  INDEX idx_integracoes_logs_integracao (integracao_id),
  CONSTRAINT fk_integracoes_logs_usuario FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_integracoes_logs_integracao FOREIGN KEY (integracao_id) REFERENCES integracoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: User status for activate/deactivate.
-- Execute manually if the column does not exist in your production database.
-- ALTER TABLE users ADD COLUMN status ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo' AFTER role;

SET FOREIGN_KEY_CHECKS = 1;
