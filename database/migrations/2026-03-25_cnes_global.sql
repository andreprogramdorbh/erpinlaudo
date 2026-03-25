-- ============================================================
-- MÓDULO CNES GLOBAL — InLaudo ERP
-- Migration: 2026-03-25_cnes_global.sql  (v3)
-- Compatível com: database/importar_cnes.php
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- --------------------------------------------------------
-- Tabela principal de estabelecimentos CNES
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cnes_estabelecimentos` (
  `id`                      INT(11)       NOT NULL AUTO_INCREMENT,
  `co_unidade`              VARCHAR(30)   NOT NULL  COMMENT 'Código único da unidade (CO_UNIDADE)',
  `co_cnes`                 VARCHAR(10)   NOT NULL  COMMENT 'Número CNES (7 dígitos)',
  `nu_cnpj`                 VARCHAR(20)   DEFAULT NULL,
  `nu_cnpj_mantenedora`     VARCHAR(20)   DEFAULT NULL,
  `tp_pfpj`                 VARCHAR(2)    DEFAULT NULL COMMENT 'PF ou PJ',
  `no_razao_social`         VARCHAR(255)  NOT NULL,
  `no_fantasia`             VARCHAR(255)  DEFAULT NULL,
  `no_fantasia_abrev`       VARCHAR(100)  DEFAULT NULL,
  `tp_unidade`              VARCHAR(5)    DEFAULT NULL,
  `co_tipo_unidade`         VARCHAR(10)   DEFAULT NULL,
  `co_tipo_estabelecimento` VARCHAR(5)    DEFAULT NULL,
  `co_atividade`            VARCHAR(5)    DEFAULT NULL,
  `co_clientela`            VARCHAR(5)    DEFAULT NULL,
  `tp_gestao`               VARCHAR(5)    DEFAULT NULL COMMENT 'M=Municipal, E=Estadual, D=Dupla',
  `co_turno_atendimento`    VARCHAR(5)    DEFAULT NULL,
  `tp_estab_sempre_aberto`  VARCHAR(2)    DEFAULT NULL,
  `no_logradouro`           VARCHAR(255)  DEFAULT NULL,
  `nu_endereco`             VARCHAR(20)   DEFAULT NULL,
  `no_complemento`          VARCHAR(100)  DEFAULT NULL,
  `no_bairro`               VARCHAR(100)  DEFAULT NULL,
  `co_cep`                  VARCHAR(10)   DEFAULT NULL,
  `co_estado_gestor`        VARCHAR(2)    DEFAULT NULL COMMENT 'UF',
  `co_municipio_gestor`     VARCHAR(10)   DEFAULT NULL COMMENT 'Código IBGE',
  `nu_telefone`             VARCHAR(30)   DEFAULT NULL,
  `nu_fax`                  VARCHAR(30)   DEFAULT NULL,
  `no_email`                VARCHAR(255)  DEFAULT NULL,
  `no_url`                  VARCHAR(255)  DEFAULT NULL,
  `nu_latitude`             VARCHAR(20)   DEFAULT NULL,
  `nu_longitude`            VARCHAR(20)   DEFAULT NULL,
  `co_natureza_jur`         VARCHAR(10)   DEFAULT NULL,
  `co_natureza_juridica`    VARCHAR(10)   DEFAULT NULL COMMENT 'Alias para co_natureza_jur',
  `st_conexao_internet`     VARCHAR(1)    DEFAULT NULL,
  `nu_cpf_diretor`          VARCHAR(20)   DEFAULT NULL,
  `co_cpf_diretor_clinico`  VARCHAR(20)   DEFAULT NULL COMMENT 'Alias para nu_cpf_diretor',
  `reg_diretor`             VARCHAR(30)   DEFAULT NULL,
  `reg_diretor_clinico`     VARCHAR(30)   DEFAULT NULL COMMENT 'Alias para reg_diretor',
  `co_motivo_desabilitacao` VARCHAR(5)    DEFAULT NULL,
  `dt_atualizacao`          DATE          DEFAULT NULL,
  `competencia`             VARCHAR(6)    DEFAULT NULL COMMENT 'AAAAMM',
  -- Campos extras ERP
  `cliente_id`              INT(11)       DEFAULT NULL COMMENT 'FK clientes',
  `created_at`              TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`              TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_co_cnes`    (`co_cnes`),
  UNIQUE KEY `uk_co_unidade` (`co_unidade`),
  KEY `idx_co_estado`        (`co_estado_gestor`),
  KEY `idx_co_municipio`     (`co_municipio_gestor`),
  KEY `idx_no_razao`         (`no_razao_social`(100)),
  KEY `idx_nu_cnpj`          (`nu_cnpj`),
  KEY `idx_cliente_id`       (`cliente_id`),
  KEY `idx_competencia`      (`competencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Estabelecimentos CNES — importado da base pública DATASUS';

-- --------------------------------------------------------
-- Tabela de equipamentos por estabelecimento
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cnes_equipamentos` (
  `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
  `co_cnes`             VARCHAR(10)  DEFAULT NULL,
  `co_unidade`          VARCHAR(30)  NOT NULL,
  `co_equipamento`      VARCHAR(5)   NOT NULL,
  `no_equipamento`      VARCHAR(255) DEFAULT NULL COMMENT 'Nome do equipamento (do dicionário)',
  `co_tipo_equipamento` VARCHAR(5)   DEFAULT NULL,
  `no_tipo_equipamento` VARCHAR(100) DEFAULT NULL COMMENT 'Nome do tipo (do dicionário)',
  `qt_existente`        INT          DEFAULT 0,
  `qt_uso`              INT          DEFAULT 0,
  `tp_sus`              VARCHAR(2)   DEFAULT NULL,
  `qt_sus`              INT          DEFAULT 0,
  `dt_atualizacao`      DATE         DEFAULT NULL,
  `competencia`         VARCHAR(6)   DEFAULT NULL,
  -- Campos extras inseridos pelo usuário
  `fabricante`          VARCHAR(150) DEFAULT NULL,
  `modelo`              VARCHAR(150) DEFAULT NULL,
  `ano_instalacao`      YEAR         DEFAULT NULL,
  `observacoes`         TEXT         DEFAULT NULL,
  `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_unidade_equip` (`co_unidade`, `co_equipamento`),
  KEY `idx_co_unidade_equip`   (`co_unidade`),
  KEY `idx_co_tipo`            (`co_tipo_equipamento`),
  KEY `idx_co_equipamento`     (`co_equipamento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Equipamentos por estabelecimento CNES';

-- --------------------------------------------------------
-- Tabela de profissionais por estabelecimento
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cnes_profissionais` (
  `id`                      INT(11)      NOT NULL AUTO_INCREMENT,
  `co_cnes`                 VARCHAR(10)  DEFAULT NULL,
  `co_unidade`              VARCHAR(30)  NOT NULL,
  `co_profissional_sus`     VARCHAR(40)  DEFAULT NULL,
  `no_profissional`         VARCHAR(255) NOT NULL DEFAULT 'Profissional',
  `co_cbo`                  VARCHAR(10)  DEFAULT NULL,
  `no_cbo`                  VARCHAR(255) DEFAULT NULL COMMENT 'Descrição do CBO',
  `co_conselho_classe`      VARCHAR(5)   DEFAULT NULL,
  `no_conselho_classe`      VARCHAR(50)  DEFAULT NULL COMMENT 'Nome do conselho',
  `nu_registro`             VARCHAR(30)  DEFAULT NULL COMMENT 'Número de registro no conselho',
  `nu_registro_conselho`    VARCHAR(30)  DEFAULT NULL COMMENT 'Alias para nu_registro',
  `sg_uf_crm`               VARCHAR(2)   DEFAULT NULL COMMENT 'UF do registro',
  `sg_uf_conselho`          VARCHAR(2)   DEFAULT NULL COMMENT 'Alias para sg_uf_crm',
  `tp_sus_nao_sus`          VARCHAR(2)   DEFAULT NULL,
  `ind_vinculacao`          VARCHAR(2)   DEFAULT NULL,
  `qt_carga_horaria_amb`    INT          DEFAULT 0,
  `qt_carga_horaria_outros` INT          DEFAULT 0,
  `situacao`                ENUM('ativo','inativo') DEFAULT 'ativo',
  `dt_atualizacao`          DATE         DEFAULT NULL,
  `competencia`             VARCHAR(6)   DEFAULT NULL,
  -- Campos extras inseridos pelo usuário
  `email`                   VARCHAR(255) DEFAULT NULL,
  `contato`                 VARCHAR(30)  DEFAULT NULL,
  `observacoes`             TEXT         DEFAULT NULL,
  `created_at`              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_unidade_prof` (`co_unidade`, `co_profissional_sus`, `co_cbo`(10)),
  KEY `idx_co_unidade_prof`   (`co_unidade`),
  KEY `idx_co_cbo`            (`co_cbo`),
  KEY `idx_no_profissional`   (`no_profissional`(100)),
  KEY `idx_situacao`          (`situacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Profissionais de saúde por estabelecimento CNES';

-- --------------------------------------------------------
-- Tabela de controle de importações CNES
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cnes_importacoes` (
  `id`             INT(11)   NOT NULL AUTO_INCREMENT,
  `competencia`    VARCHAR(6) NOT NULL COMMENT 'AAAAMM',
  `status`         ENUM('processando','concluido','erro') DEFAULT 'processando',
  `total_estab`    INT DEFAULT 0,
  `total_equip`    INT DEFAULT 0,
  `total_prof`     INT DEFAULT 0,
  `log`            TEXT DEFAULT NULL,
  `usuario_id`     INT(11) DEFAULT NULL,
  `iniciado_em`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `concluido_em`   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_competencia` (`competencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Histórico de importações da base CNES';

-- --------------------------------------------------------
-- Domínio: tipos de equipamento
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cnes_dom_tipo_equipamento` (
  `co_tipo`  VARCHAR(5)   NOT NULL,
  `no_tipo`  VARCHAR(100) NOT NULL,
  PRIMARY KEY (`co_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `cnes_dom_tipo_equipamento` (`co_tipo`, `no_tipo`) VALUES
('1',  'Diagnóstico por Imagem'),
('2',  'Infraestrutura'),
('3',  'Manutenção da Vida'),
('4',  'Odontológico'),
('5',  'Óptico'),
('6',  'Outros'),
('7',  'Radioterapia'),
('8',  'Reabilitação'),
('9',  'Terapia Renal Substitutiva'),
('10', 'Laboratório');

-- --------------------------------------------------------
-- Domínio: equipamentos (dicionário oficial DATASUS)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cnes_dom_equipamentos` (
  `co_equipamento`  VARCHAR(5)   NOT NULL,
  `no_equipamento`  VARCHAR(255) NOT NULL,
  `co_tipo`         VARCHAR(5)   DEFAULT NULL,
  PRIMARY KEY (`co_equipamento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `cnes_dom_equipamentos` (`co_equipamento`, `no_equipamento`, `co_tipo`) VALUES
('01', 'Aparelho de Raio-X até 100mA', '1'),
('02', 'Aparelho de Raio-X de 100 a 500mA', '1'),
('03', 'Aparelho de Raio-X acima de 500mA', '1'),
('04', 'Aparelho de Raio-X com Fluoroscopia', '1'),
('05', 'Mamógrafo', '1'),
('06', 'Tomógrafo Computadorizado', '1'),
('07', 'Ressonância Magnética', '1'),
('08', 'Aparelho de Ultrassonografia', '1'),
('09', 'Aparelho de Densitometria Óssea', '1'),
('10', 'Cintilógrafo (Câmara Gama)', '1'),
('11', 'Aparelho de PET-CT', '1'),
('12', 'Aparelho de Hemodinâmica', '1'),
('13', 'Arco Cirúrgico (Intensificador de Imagem)', '1'),
('14', 'Aparelho de Raio-X Odontológico Periapical', '4'),
('15', 'Aparelho de Raio-X Odontológico Panorâmico', '4'),
('16', 'Aparelho de Raio-X Odontológico Cefalométrico', '4'),
('17', 'Eletroencefalógrafo', '6'),
('18', 'Eletrocardiógrafo', '6'),
('19', 'Ergômetro (Esteira/Bicicleta)', '6'),
('20', 'Holter', '6'),
('21', 'Monitor Multiparâmetro', '3'),
('22', 'Ventilador Pulmonar', '3'),
('23', 'Bomba de Infusão', '3'),
('24', 'Desfibrilador', '3'),
('25', 'Bisturi Elétrico', '6'),
('26', 'Fototerapia Neonatal', '3'),
('27', 'Incubadora Neonatal', '3'),
('28', 'Berço Aquecido', '3'),
('29', 'Cadeira de Hemodiálise', '9'),
('30', 'Rim Artificial', '9'),
('31', 'Autoclave', '2'),
('32', 'Câmara Hiperbárica', '6'),
('33', 'Acelerador Linear', '7'),
('34', 'Bomba de Cobalto', '7'),
('35', 'Braquiterapia', '7'),
('36', 'Simulador de Radioterapia', '7'),
('37', 'Equipamento de Oftalmologia', '5'),
('38', 'Equipamento de Reabilitação Física', '8'),
('39', 'Equipamento Odontológico Básico', '4'),
('40', 'Analisador Bioquímico', '10'),
('41', 'Analisador Hematológico', '10'),
('42', 'Centrífuga Laboratorial', '10'),
('43', 'Microscópio', '10'),
('44', 'Outros Equipamentos', '6');

-- --------------------------------------------------------
-- Domínio: CBO (principais para saúde)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cnes_dom_cbo` (
  `co_cbo`  VARCHAR(10)  NOT NULL,
  `no_cbo`  VARCHAR(255) NOT NULL,
  PRIMARY KEY (`co_cbo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `cnes_dom_cbo` (`co_cbo`, `no_cbo`) VALUES
('223505', 'Médico Clínico'),
('223208', 'Médico Radiologista e Diagnóstico por Imagem'),
('223212', 'Médico Ultrassonografista'),
('223216', 'Médico em Medicina Nuclear'),
('223220', 'Médico Radioterapeuta'),
('223224', 'Médico Neurorradiologista'),
('223228', 'Médico Hemodinamicista'),
('223810', 'Médico Cardiologista'),
('223905', 'Médico Cirurgião Geral'),
('223910', 'Médico Cirurgião Cardiovascular'),
('223915', 'Médico Cirurgião de Cabeça e Pescoço'),
('223920', 'Médico Cirurgião do Aparelho Digestivo'),
('223925', 'Médico Cirurgião Plástico'),
('223930', 'Médico Cirurgião Torácico'),
('223935', 'Médico Cirurgião Vascular'),
('223550', 'Médico Ginecologista e Obstetra'),
('223415', 'Médico Ortopedista e Traumatologista'),
('223710', 'Médico Pediatra'),
('223565', 'Médico Neurologista'),
('223605', 'Médico Psiquiatra'),
('223215', 'Médico Anestesiologista'),
('223115', 'Médico Infectologista'),
('223120', 'Médico Nefrologista'),
('223125', 'Médico Pneumologista'),
('223130', 'Médico Reumatologista'),
('223135', 'Médico Endocrinologista'),
('223140', 'Médico Hematologista'),
('223145', 'Médico Oncologista'),
('223150', 'Médico Gastroenterologista'),
('223155', 'Médico Dermatologista'),
('223160', 'Médico Urologista'),
('223165', 'Médico Oftalmologista'),
('223170', 'Médico Otorrinolaringologista'),
('223175', 'Médico Proctologista'),
('223185', 'Médico Mastologista'),
('223190', 'Médico Geriatra'),
('223195', 'Médico Intensivista'),
('223200', 'Médico de Família e Comunidade'),
('223205', 'Médico do Trabalho'),
('223210', 'Médico Sanitarista'),
('223225', 'Médico Patologista'),
('223230', 'Médico Patologista Clínico'),
('223235', 'Médico Geneticista'),
('223240', 'Médico Imunologista'),
('223245', 'Médico Alergologista'),
('223250', 'Médico Fisiatra'),
('225103', 'Médico Veterinário'),
('222105', 'Enfermeiro'),
('322205', 'Técnico de Enfermagem'),
('322230', 'Auxiliar de Enfermagem'),
('251510', 'Psicólogo Clínico'),
('322110', 'Técnico em Radiologia e Imagenologia'),
('322120', 'Técnico em Tomografia'),
('322130', 'Técnico em Ressonância Magnética'),
('322140', 'Técnico em Ultrassonografia'),
('322150', 'Técnico em Medicina Nuclear'),
('322160', 'Técnico em Radioterapia'),
('224110', 'Fisioterapeuta Geral'),
('224120', 'Fisioterapeuta Respiratória'),
('224130', 'Fisioterapeuta Neurofuncional'),
('224140', 'Fisioterapeuta Ortopédica'),
('226305', 'Farmacêutico'),
('226310', 'Farmacêutico Bioquímico'),
('226315', 'Farmacêutico Hospitalar'),
('226320', 'Farmacêutico Industrial'),
('239415', 'Nutricionista'),
('251605', 'Assistente Social'),
('322405', 'Técnico em Laboratório'),
('322410', 'Auxiliar de Laboratório'),
('322505', 'Técnico em Hemoterapia'),
('322605', 'Técnico em Saúde Bucal'),
('322705', 'Técnico em Óptica e Optometria'),
('322805', 'Técnico em Prótese Dentária');

-- --------------------------------------------------------
-- Domínio: Conselhos de Classe
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cnes_dom_conselho` (
  `co_conselho`  VARCHAR(5)  NOT NULL,
  `no_conselho`  VARCHAR(50) NOT NULL,
  PRIMARY KEY (`co_conselho`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `cnes_dom_conselho` (`co_conselho`, `no_conselho`) VALUES
('01', 'CRM'),
('02', 'CRO'),
('03', 'CRF'),
('04', 'COREN'),
('05', 'CRN'),
('06', 'CREFITO'),
('07', 'CRP'),
('08', 'CRESS'),
('09', 'CRFa'),
('10', 'CRBM'),
('11', 'CRBio'),
('12', 'CRMV'),
('13', 'CRQ'),
('14', 'CREA'),
('15', 'CRC'),
('16', 'OAB'),
('17', 'CRTR'),
('18', 'CRFA'),
('19', 'COFFITO'),
('99', 'Outro');

SET foreign_key_checks = 1;
