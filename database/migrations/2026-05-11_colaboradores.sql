-- ============================================================
-- Migration: Módulo Colaboradores
-- Data: 2026-05-11
-- Banco: MariaDB 10.3+
-- Descrição: Cria as tabelas do módulo de colaboradores com
--   suporte a CLT e PJ, anexos, comissões e vínculo com usuário.
-- ============================================================

-- ─── 1. Tabela principal de colaboradores ────────────────────
CREATE TABLE IF NOT EXISTS `colaboradores` (
  `id`                    INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`            INT NOT NULL COMMENT 'Tenant: ID do usuário dono do cadastro',

  -- Tipo de contratação
  `tipo_contratacao`      ENUM('CLT','PJ') NOT NULL DEFAULT 'CLT'
                          COMMENT 'CLT = Pessoa Física; PJ = Pessoa Jurídica',

  -- Dados de identificação (CLT = CPF / PJ = CNPJ)
  `cpf_cnpj`              VARCHAR(20)  NOT NULL COMMENT 'CPF (CLT) ou CNPJ (PJ) sem formatação',
  `nome`                  VARCHAR(255) NOT NULL COMMENT 'Nome completo (CLT) ou Razão Social (PJ)',
  `nome_social`           VARCHAR(255) DEFAULT NULL COMMENT 'Nome social / Nome Fantasia',

  -- Dados CLT
  `data_nascimento`       DATE         DEFAULT NULL,
  `rg`                    VARCHAR(30)  DEFAULT NULL,
  `orgao_emissor`         VARCHAR(20)  DEFAULT NULL,
  `pis_pasep`             VARCHAR(20)  DEFAULT NULL,
  `ctps`                  VARCHAR(30)  DEFAULT NULL COMMENT 'Carteira de Trabalho',
  `ctps_serie`            VARCHAR(20)  DEFAULT NULL,
  `estado_civil`          ENUM('solteiro','casado','divorciado','viuvo','uniao_estavel','outro')
                          DEFAULT NULL,
  `escolaridade`          VARCHAR(50)  DEFAULT NULL,

  -- Dados PJ
  `inscricao_estadual`    VARCHAR(30)  DEFAULT NULL,
  `inscricao_municipal`   VARCHAR(30)  DEFAULT NULL,
  `cnae_principal`        VARCHAR(10)  DEFAULT NULL,
  `descricao_cnae`        VARCHAR(255) DEFAULT NULL,
  `nome_responsavel`      VARCHAR(255) DEFAULT NULL COMMENT 'Nome do responsável legal (PJ)',
  `cpf_responsavel`       VARCHAR(14)  DEFAULT NULL,

  -- Contato
  `email`                 VARCHAR(255) NOT NULL,
  `telefone`              VARCHAR(20)  DEFAULT NULL,
  `celular`               VARCHAR(20)  DEFAULT NULL,

  -- Endereço
  `cep`                   VARCHAR(10)  DEFAULT NULL,
  `endereco`              VARCHAR(255) DEFAULT NULL,
  `numero`                VARCHAR(20)  DEFAULT NULL,
  `complemento`           VARCHAR(100) DEFAULT NULL,
  `bairro`                VARCHAR(100) DEFAULT NULL,
  `cidade`                VARCHAR(100) DEFAULT NULL,
  `estado`                VARCHAR(2)   DEFAULT NULL,

  -- Dados profissionais
  `cargo`                 VARCHAR(100) DEFAULT NULL,
  `departamento`          VARCHAR(100) DEFAULT NULL,
  `data_admissao`         DATE         DEFAULT NULL,
  `data_demissao`         DATE         DEFAULT NULL,
  `salario_base`          DECIMAL(15,2) DEFAULT NULL,
  `banco`                 VARCHAR(100) DEFAULT NULL,
  `agencia`               VARCHAR(20)  DEFAULT NULL,
  `conta`                 VARCHAR(30)  DEFAULT NULL,
  `tipo_conta`            ENUM('corrente','poupanca','salario') DEFAULT NULL,
  `chave_pix`             VARCHAR(150) DEFAULT NULL,

  -- Vínculo com usuário do sistema
  `user_id`               INT DEFAULT NULL COMMENT 'FK para users.id — usuário do sistema vinculado',

  -- Status
  `status`                ENUM('ativo','inativo','afastado','demitido') NOT NULL DEFAULT 'ativo',
  `observacoes`           TEXT DEFAULT NULL,

  `created_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX `idx_colaboradores_usuario`    (`usuario_id`),
  INDEX `idx_colaboradores_cpf_cnpj`   (`cpf_cnpj`),
  INDEX `idx_colaboradores_status`     (`status`),
  INDEX `idx_colaboradores_tipo`       (`tipo_contratacao`),
  INDEX `idx_colaboradores_user_id`    (`user_id`),

  CONSTRAINT `fk_colaboradores_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_colaboradores_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tabela de Colaboradores (CLT e PJ)';

-- ─── 2. Anexos do colaborador ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `colaboradores_anexos` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `colaborador_id` INT NOT NULL,
  `usuario_id`    INT NOT NULL,
  `nome_anexo`    VARCHAR(255) NOT NULL COMMENT 'Nome/descrição do documento',
  `file_path`     VARCHAR(500) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `file_size`     INT NOT NULL DEFAULT 0,
  `mime_type`     VARCHAR(100) NOT NULL DEFAULT 'application/octet-stream',
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX `idx_col_anexos_colaborador` (`colaborador_id`),
  INDEX `idx_col_anexos_usuario`     (`usuario_id`),

  CONSTRAINT `fk_col_anexos_colaborador`
    FOREIGN KEY (`colaborador_id`) REFERENCES `colaboradores`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_col_anexos_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Anexos e documentos dos colaboradores';

-- ─── 3. Regras de comissão do colaborador ────────────────────
CREATE TABLE IF NOT EXISTS `colaboradores_comissoes` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `colaborador_id`    INT NOT NULL,
  `usuario_id`        INT NOT NULL,
  `descricao`         VARCHAR(255) NOT NULL COMMENT 'Descrição da regra de comissão',
  `tipo`              ENUM('percentual','valor_fixo','por_exame','por_contrato')
                      NOT NULL DEFAULT 'percentual',
  `valor`             DECIMAL(10,4) NOT NULL DEFAULT 0.0000
                      COMMENT 'Percentual (ex: 5.5000 = 5,5%) ou valor fixo',
  `base_calculo`      ENUM('faturamento_bruto','faturamento_liquido','valor_exame','valor_contrato')
                      NOT NULL DEFAULT 'faturamento_bruto',
  `vigencia_inicio`   DATE DEFAULT NULL,
  `vigencia_fim`      DATE DEFAULT NULL,
  `ativo`             TINYINT(1) NOT NULL DEFAULT 1,
  `observacoes`       TEXT DEFAULT NULL,
  `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX `idx_col_comissoes_colaborador` (`colaborador_id`),
  INDEX `idx_col_comissoes_usuario`     (`usuario_id`),

  CONSTRAINT `fk_col_comissoes_colaborador`
    FOREIGN KEY (`colaborador_id`) REFERENCES `colaboradores`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_col_comissoes_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Regras de comissão dos colaboradores';

-- ─── 4. Vincular colaborador_id nas contas a receber ─────────
-- Permite filtrar o faturamento por colaborador
ALTER TABLE `contas_receber`
  ADD COLUMN IF NOT EXISTS `colaborador_id` INT DEFAULT NULL
    COMMENT 'FK para colaboradores.id — colaborador vinculado à conta';

CREATE INDEX IF NOT EXISTS `idx_cr_colaborador` ON `contas_receber` (`colaborador_id`);
