-- ============================================================
-- Migration: Adicionar colunas faltantes nas tabelas CNES
-- Executar no phpMyAdmin caso a tabela já exista com a versão
-- antiga da migration (sem as colunas abaixo).
-- É seguro executar múltiplas vezes (usa IF NOT EXISTS via IGNORE).
-- ============================================================

-- ─── cnes_estabelecimentos ───────────────────────────────────────────────────

ALTER TABLE `cnes_estabelecimentos`
  ADD COLUMN IF NOT EXISTS `no_fantasia_abrev`       VARCHAR(100)  DEFAULT NULL AFTER `no_fantasia`,
  ADD COLUMN IF NOT EXISTS `co_tipo_unidade`         VARCHAR(10)   DEFAULT NULL AFTER `tp_unidade`,
  ADD COLUMN IF NOT EXISTS `co_tipo_estabelecimento` VARCHAR(5)    DEFAULT NULL AFTER `co_tipo_unidade`,
  ADD COLUMN IF NOT EXISTS `tp_gestao`               VARCHAR(5)    DEFAULT NULL AFTER `co_clientela`,
  ADD COLUMN IF NOT EXISTS `co_turno_atendimento`    VARCHAR(5)    DEFAULT NULL AFTER `tp_gestao`,
  ADD COLUMN IF NOT EXISTS `tp_estab_sempre_aberto`  VARCHAR(2)    DEFAULT NULL AFTER `co_turno_atendimento`,
  ADD COLUMN IF NOT EXISTS `nu_fax`                  VARCHAR(30)   DEFAULT NULL AFTER `nu_telefone`,
  ADD COLUMN IF NOT EXISTS `no_url`                  VARCHAR(255)  DEFAULT NULL AFTER `no_email`,
  ADD COLUMN IF NOT EXISTS `co_natureza_jur`         VARCHAR(10)   DEFAULT NULL AFTER `nu_longitude`,
  ADD COLUMN IF NOT EXISTS `co_natureza_juridica`    VARCHAR(10)   DEFAULT NULL AFTER `co_natureza_jur`,
  ADD COLUMN IF NOT EXISTS `st_conexao_internet`     VARCHAR(1)    DEFAULT NULL AFTER `co_natureza_juridica`,
  ADD COLUMN IF NOT EXISTS `nu_cpf_diretor`          VARCHAR(20)   DEFAULT NULL AFTER `st_conexao_internet`,
  ADD COLUMN IF NOT EXISTS `co_cpf_diretor_clinico`  VARCHAR(20)   DEFAULT NULL AFTER `nu_cpf_diretor`,
  ADD COLUMN IF NOT EXISTS `reg_diretor`             VARCHAR(30)   DEFAULT NULL AFTER `co_cpf_diretor_clinico`,
  ADD COLUMN IF NOT EXISTS `reg_diretor_clinico`     VARCHAR(30)   DEFAULT NULL AFTER `reg_diretor`,
  ADD COLUMN IF NOT EXISTS `co_motivo_desabilitacao` VARCHAR(5)    DEFAULT NULL AFTER `reg_diretor_clinico`,
  ADD COLUMN IF NOT EXISTS `competencia`             VARCHAR(6)    DEFAULT NULL AFTER `dt_atualizacao`;

-- Adicionar índice de competência se não existir
ALTER TABLE `cnes_estabelecimentos`
  ADD INDEX IF NOT EXISTS `idx_competencia` (`competencia`);

-- ─── cnes_equipamentos ───────────────────────────────────────────────────────

ALTER TABLE `cnes_equipamentos`
  ADD COLUMN IF NOT EXISTS `co_cnes`             VARCHAR(10)  DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `no_equipamento`      VARCHAR(255) DEFAULT NULL AFTER `co_equipamento`,
  ADD COLUMN IF NOT EXISTS `co_tipo_equipamento` VARCHAR(5)   DEFAULT NULL AFTER `no_equipamento`,
  ADD COLUMN IF NOT EXISTS `no_tipo_equipamento` VARCHAR(100) DEFAULT NULL AFTER `co_tipo_equipamento`,
  ADD COLUMN IF NOT EXISTS `qt_sus`              INT          DEFAULT 0   AFTER `tp_sus`,
  ADD COLUMN IF NOT EXISTS `competencia`         VARCHAR(6)   DEFAULT NULL AFTER `dt_atualizacao`,
  ADD COLUMN IF NOT EXISTS `fabricante`          VARCHAR(150) DEFAULT NULL AFTER `competencia`,
  ADD COLUMN IF NOT EXISTS `modelo`              VARCHAR(150) DEFAULT NULL AFTER `fabricante`,
  ADD COLUMN IF NOT EXISTS `ano_instalacao`      YEAR         DEFAULT NULL AFTER `modelo`,
  ADD COLUMN IF NOT EXISTS `observacoes`         TEXT         DEFAULT NULL AFTER `ano_instalacao`;

-- ─── cnes_profissionais ──────────────────────────────────────────────────────

ALTER TABLE `cnes_profissionais`
  ADD COLUMN IF NOT EXISTS `co_cnes`            VARCHAR(10)  DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `no_profissional`    VARCHAR(255) DEFAULT NULL AFTER `co_unidade`,
  ADD COLUMN IF NOT EXISTS `no_cbo`             VARCHAR(100) DEFAULT NULL AFTER `co_cbo`,
  ADD COLUMN IF NOT EXISTS `no_conselho_classe` VARCHAR(50)  DEFAULT NULL AFTER `co_conselho_classe`,
  ADD COLUMN IF NOT EXISTS `nu_registro`        VARCHAR(30)  DEFAULT NULL AFTER `no_conselho_classe`,
  ADD COLUMN IF NOT EXISTS `sg_uf_crm`          VARCHAR(2)   DEFAULT NULL AFTER `nu_registro`,
  ADD COLUMN IF NOT EXISTS `ind_vinculacao`     VARCHAR(5)   DEFAULT NULL AFTER `sg_uf_crm`,
  ADD COLUMN IF NOT EXISTS `qt_carga_horaria_amb` DECIMAL(5,2) DEFAULT NULL AFTER `ind_vinculacao`,
  ADD COLUMN IF NOT EXISTS `qt_carga_horaria_hosp` DECIMAL(5,2) DEFAULT NULL AFTER `qt_carga_horaria_amb`,
  ADD COLUMN IF NOT EXISTS `qt_carga_horaria_outros` DECIMAL(5,2) DEFAULT NULL AFTER `qt_carga_horaria_hosp`,
  ADD COLUMN IF NOT EXISTS `tp_sus`             VARCHAR(2)   DEFAULT NULL AFTER `qt_carga_horaria_outros`,
  ADD COLUMN IF NOT EXISTS `tp_vinculo`         VARCHAR(5)   DEFAULT NULL AFTER `tp_sus`,
  ADD COLUMN IF NOT EXISTS `dt_atualizacao`     DATE         DEFAULT NULL AFTER `tp_vinculo`,
  ADD COLUMN IF NOT EXISTS `competencia`        VARCHAR(6)   DEFAULT NULL AFTER `dt_atualizacao`,
  ADD COLUMN IF NOT EXISTS `email`              VARCHAR(255) DEFAULT NULL AFTER `competencia`,
  ADD COLUMN IF NOT EXISTS `contato`            VARCHAR(100) DEFAULT NULL AFTER `email`;

-- ─── cnes_importacoes (criar se não existir) ─────────────────────────────────

CREATE TABLE IF NOT EXISTS `cnes_importacoes` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `competencia`     VARCHAR(6)   DEFAULT NULL,
  `uf_filtro`       VARCHAR(2)   DEFAULT NULL,
  `total_estab`     INT          DEFAULT 0,
  `total_equip`     INT          DEFAULT 0,
  `total_prof`      INT          DEFAULT 0,
  `status`          ENUM('em_andamento','concluido','erro') DEFAULT 'em_andamento',
  `erro_msg`        TEXT         DEFAULT NULL,
  `iniciado_em`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `concluido_em`    TIMESTAMP    NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Verificação final ───────────────────────────────────────────────────────
SELECT 'Migration ALTER TABLE executada com sucesso!' AS resultado;
