-- ============================================================
-- MÓDULO CNES GLOBAL — InLaudo ERP
-- Migration: 2026-03-25_cnes_global.sql
-- Tabelas: cnes_estabelecimentos, cnes_equipamentos, cnes_profissionais
-- ============================================================

-- --------------------------------------------------------
-- Tabela principal de estabelecimentos CNES
-- Fonte: tbEstabelecimento202602.csv
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cnes_estabelecimentos` (
  `id`                    INT(11) NOT NULL AUTO_INCREMENT,
  `co_unidade`            VARCHAR(20) NOT NULL COMMENT 'Código único da unidade (CO_UNIDADE)',
  `co_cnes`               VARCHAR(10) NOT NULL COMMENT 'Número CNES do estabelecimento',
  `nu_cnpj`               VARCHAR(20) DEFAULT NULL COMMENT 'CNPJ do estabelecimento',
  `nu_cnpj_mantenedora`   VARCHAR(20) DEFAULT NULL COMMENT 'CNPJ da mantenedora',
  `tp_pfpj`               TINYINT(1) DEFAULT NULL COMMENT '1=PF, 3=PJ',
  `no_razao_social`       VARCHAR(255) NOT NULL COMMENT 'Razão Social',
  `no_fantasia`           VARCHAR(255) DEFAULT NULL COMMENT 'Nome Fantasia',
  `no_fantasia_abrev`     VARCHAR(100) DEFAULT NULL COMMENT 'Nome Fantasia Abreviado',
  `tp_unidade`            VARCHAR(5) DEFAULT NULL COMMENT 'Tipo de Unidade (código)',
  `no_logradouro`         VARCHAR(255) DEFAULT NULL COMMENT 'Logradouro',
  `nu_endereco`           VARCHAR(20) DEFAULT NULL COMMENT 'Número do endereço',
  `no_complemento`        VARCHAR(100) DEFAULT NULL COMMENT 'Complemento',
  `no_bairro`             VARCHAR(100) DEFAULT NULL COMMENT 'Bairro',
  `co_cep`                VARCHAR(10) DEFAULT NULL COMMENT 'CEP',
  `co_municipio_gestor`   VARCHAR(10) DEFAULT NULL COMMENT 'Código IBGE do município',
  `co_estado_gestor`      VARCHAR(2) DEFAULT NULL COMMENT 'UF',
  `nu_telefone`           VARCHAR(30) DEFAULT NULL COMMENT 'Telefone',
  `nu_fax`                VARCHAR(30) DEFAULT NULL COMMENT 'Fax',
  `no_email`              VARCHAR(255) DEFAULT NULL COMMENT 'E-mail',
  `no_url`                VARCHAR(255) DEFAULT NULL COMMENT 'Website',
  `nu_latitude`           VARCHAR(20) DEFAULT NULL COMMENT 'Latitude',
  `nu_longitude`          VARCHAR(20) DEFAULT NULL COMMENT 'Longitude',
  `co_natureza_jur`       VARCHAR(10) DEFAULT NULL COMMENT 'Código Natureza Jurídica',
  `tp_gestao`             VARCHAR(5) DEFAULT NULL COMMENT 'Tipo de Gestão (M=Municipal, E=Estadual, D=Dupla)',
  `co_atividade`          VARCHAR(5) DEFAULT NULL COMMENT 'Código Atividade',
  `co_clientela`          VARCHAR(5) DEFAULT NULL COMMENT 'Código Clientela',
  `co_turno_atendimento`  VARCHAR(5) DEFAULT NULL COMMENT 'Turno de Atendimento',
  `st_conexao_internet`   VARCHAR(1) DEFAULT NULL COMMENT 'Conexão Internet (S/N)',
  `tp_estab_sempre_aberto` VARCHAR(1) DEFAULT NULL COMMENT 'Sempre Aberto (S/N)',
  `nu_cpf_diretor`        VARCHAR(20) DEFAULT NULL COMMENT 'CPF do Diretor Clínico',
  `reg_diretor`           VARCHAR(30) DEFAULT NULL COMMENT 'Registro do Diretor',
  `dt_atualizacao`        VARCHAR(20) DEFAULT NULL COMMENT 'Data de atualização no CNES',
  -- Campos extras para enriquecer o cadastro no ERP
  `cnes_importado_em`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de importação para o ERP',
  `cliente_id`            INT(11) DEFAULT NULL COMMENT 'FK para clientes — preenchido ao importar como cliente',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_co_cnes` (`co_cnes`),
  KEY `idx_co_unidade` (`co_unidade`),
  KEY `idx_co_estado` (`co_estado_gestor`),
  KEY `idx_co_municipio` (`co_municipio_gestor`),
  KEY `idx_no_razao` (`no_razao_social`(100)),
  KEY `idx_nu_cnpj` (`nu_cnpj`),
  KEY `idx_cliente_id` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Estabelecimentos CNES — importado da base pública DATASUS';

-- --------------------------------------------------------
-- Tabela de equipamentos por estabelecimento
-- Fonte: rlEstabEquipamento202602.csv
-- Campos extras: fabricante, modelo, ano_instalacao (inseridos pelo usuário)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cnes_equipamentos` (
  `id`                INT(11) NOT NULL AUTO_INCREMENT,
  `co_unidade`        VARCHAR(20) NOT NULL COMMENT 'FK para cnes_estabelecimentos.co_unidade',
  `co_equipamento`    VARCHAR(5) NOT NULL COMMENT 'Código do equipamento CNES',
  `no_equipamento`    VARCHAR(255) DEFAULT NULL COMMENT 'Descrição do equipamento (preenchida por lookup)',
  `co_tipo_equipamento` VARCHAR(5) DEFAULT NULL COMMENT 'Código tipo equipamento (1=Diagnóstico por Imagem, etc.)',
  `no_tipo_equipamento` VARCHAR(100) DEFAULT NULL COMMENT 'Descrição do tipo',
  `qt_existente`      INT DEFAULT 0 COMMENT 'Quantidade existente',
  `qt_uso`            INT DEFAULT 0 COMMENT 'Quantidade em uso',
  `tp_sus`            VARCHAR(2) DEFAULT NULL COMMENT 'Atende SUS (1=Sim, 2=Não)',
  `qt_sus`            INT DEFAULT 0 COMMENT 'Quantidade disponível para SUS',
  `dt_atualizacao`    VARCHAR(20) DEFAULT NULL COMMENT 'Data de atualização no CNES',
  -- Campos extras inseridos pelo usuário no ERP
  `fabricante`        VARCHAR(150) DEFAULT NULL COMMENT 'Fabricante do equipamento',
  `modelo`            VARCHAR(150) DEFAULT NULL COMMENT 'Modelo do equipamento',
  `ano_instalacao`    YEAR DEFAULT NULL COMMENT 'Ano de instalação',
  `observacoes`       TEXT DEFAULT NULL COMMENT 'Observações adicionais',
  `atualizado_em`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_co_unidade_equip` (`co_unidade`),
  KEY `idx_co_tipo` (`co_tipo_equipamento`),
  KEY `idx_co_equipamento` (`co_equipamento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Equipamentos por estabelecimento CNES';

-- --------------------------------------------------------
-- Tabela de profissionais por estabelecimento
-- Fonte: tbDadosProfissionalSus202602.csv + tbCargaHorariaSus202602.csv
-- Campos extras: email e contato (inseridos pelo usuário)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cnes_profissionais` (
  `id`                    INT(11) NOT NULL AUTO_INCREMENT,
  `co_unidade`            VARCHAR(20) NOT NULL COMMENT 'FK para cnes_estabelecimentos.co_unidade',
  `co_profissional_sus`   VARCHAR(40) DEFAULT NULL COMMENT 'Código interno CNES do profissional',
  `no_profissional`       VARCHAR(255) NOT NULL COMMENT 'Nome do profissional',
  `co_cbo`                VARCHAR(10) DEFAULT NULL COMMENT 'Código CBO da ocupação',
  `no_cbo`                VARCHAR(255) DEFAULT NULL COMMENT 'Descrição CBO',
  `co_conselho_classe`    VARCHAR(5) DEFAULT NULL COMMENT 'Código conselho (CRM, CRN, etc.)',
  `no_conselho_classe`    VARCHAR(50) DEFAULT NULL COMMENT 'Sigla do conselho',
  `nu_registro`           VARCHAR(30) DEFAULT NULL COMMENT 'Número de registro no conselho',
  `sg_uf_crm`             VARCHAR(2) DEFAULT NULL COMMENT 'UF do registro',
  `tp_sus_nao_sus`        VARCHAR(2) DEFAULT NULL COMMENT 'Atende SUS (S/N)',
  `ind_vinculacao`        VARCHAR(10) DEFAULT NULL COMMENT 'Indicador de vínculo',
  `qt_carga_horaria_amb`  INT DEFAULT 0 COMMENT 'Carga horária ambulatorial',
  `qt_carga_horaria_outros` INT DEFAULT 0 COMMENT 'Carga horária outros',
  `situacao`              ENUM('ativo','inativo') DEFAULT 'ativo' COMMENT 'Situação do profissional',
  -- Campos extras inseridos pelo usuário no ERP
  `email`                 VARCHAR(255) DEFAULT NULL COMMENT 'E-mail do profissional (inserido pelo usuário)',
  `contato`               VARCHAR(30) DEFAULT NULL COMMENT 'Telefone/WhatsApp do profissional',
  `observacoes`           TEXT DEFAULT NULL COMMENT 'Observações adicionais',
  `atualizado_em`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_co_unidade_prof` (`co_unidade`),
  KEY `idx_co_cbo` (`co_cbo`),
  KEY `idx_no_profissional` (`no_profissional`(100)),
  KEY `idx_situacao` (`situacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Profissionais de saúde por estabelecimento CNES';

-- --------------------------------------------------------
-- Tabela de domínio: descrição dos equipamentos CNES
-- Baseada no dicionário oficial DATASUS
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cnes_dom_equipamentos` (
  `co_equipamento`    VARCHAR(5) NOT NULL,
  `no_equipamento`    VARCHAR(255) NOT NULL,
  `co_tipo`           VARCHAR(5) DEFAULT NULL,
  PRIMARY KEY (`co_equipamento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Domínio de equipamentos CNES';

-- --------------------------------------------------------
-- Tabela de domínio: tipos de equipamento
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cnes_dom_tipo_equipamento` (
  `co_tipo`           VARCHAR(5) NOT NULL,
  `no_tipo`           VARCHAR(100) NOT NULL,
  PRIMARY KEY (`co_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Domínio de tipos de equipamento CNES';

-- --------------------------------------------------------
-- Inserir domínios de tipos de equipamento (oficial DATASUS)
-- --------------------------------------------------------
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
-- Inserir domínio de equipamentos de Diagnóstico por Imagem (tipo 1)
-- Baseado no dicionário oficial CNES/DATASUS
-- --------------------------------------------------------
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
-- Inserir domínio de CBO (principais para saúde)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cnes_dom_cbo` (
  `co_cbo`    VARCHAR(10) NOT NULL,
  `no_cbo`    VARCHAR(255) NOT NULL,
  PRIMARY KEY (`co_cbo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Domínio CBO — Classificação Brasileira de Ocupações (saúde)';

INSERT IGNORE INTO `cnes_dom_cbo` (`co_cbo`, `no_cbo`) VALUES
('223505', 'Médico Clínico'),
('223208', 'Médico Radiologista'),
('223293', 'Médico Cardiologista'),
('223272', 'Médico Neurologista'),
('223280', 'Médico Ortopedista e Traumatologista'),
('223265', 'Médico Ginecologista e Obstetra'),
('223248', 'Médico Pediatra'),
('223320', 'Médico Cirurgião Geral'),
('223405', 'Médico de Família e Comunidade'),
('223415', 'Médico do Trabalho'),
('223505', 'Médico Clínico Geral'),
('222105', 'Médico Veterinário'),
('225103', 'Farmacêutico'),
('225125', 'Farmacêutico Bioquímico'),
('225170', 'Farmacêutico Hospitalar'),
('226305', 'Fisioterapeuta Geral'),
('226310', 'Fisioterapeuta Respiratório'),
('226315', 'Fisioterapeuta Neurofuncional'),
('226320', 'Fisioterapeuta Ortopédico'),
('223810', 'Enfermeiro'),
('223815', 'Enfermeiro de UTI'),
('223820', 'Enfermeiro Obstétrico'),
('322205', 'Técnico de Enfermagem'),
('322210', 'Técnico de Enfermagem de UTI'),
('322415', 'Técnico em Radiologia'),
('322420', 'Tecnólogo em Radiologia'),
('322230', 'Auxiliar de Enfermagem'),
('251510', 'Psicólogo Clínico'),
('251515', 'Psicólogo Hospitalar'),
('223905', 'Nutricionista'),
('223910', 'Nutricionista Clínico'),
('223605', 'Fonoaudiólogo'),
('223705', 'Terapeuta Ocupacional'),
('224105', 'Odontólogo Geral'),
('224110', 'Odontólogo Especialista'),
('515105', 'Agente Comunitário de Saúde'),
('515305', 'Agente de Combate às Endemias'),
('322105', 'Técnico em Laboratório'),
('322110', 'Técnico em Análises Clínicas'),
('322115', 'Técnico em Patologia Clínica'),
('322305', 'Técnico em Prótese Dentária'),
('322310', 'Técnico em Saúde Bucal'),
('322505', 'Técnico em Óptica'),
('322605', 'Técnico em Ortopedia');

-- --------------------------------------------------------
-- Inserir domínio de conselhos de classe
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cnes_dom_conselho` (
  `co_conselho`   VARCHAR(5) NOT NULL,
  `no_conselho`   VARCHAR(50) NOT NULL,
  PRIMARY KEY (`co_conselho`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Domínio de Conselhos de Classe CNES';

INSERT IGNORE INTO `cnes_dom_conselho` (`co_conselho`, `no_conselho`) VALUES
('01', 'CRM'),
('02', 'CRO'),
('03', 'CRF'),
('04', 'COREN'),
('05', 'CRN'),
('06', 'CRP'),
('07', 'CREFITO'),
('08', 'CRFa'),
('09', 'CRESS'),
('10', 'CRMV'),
('11', 'CRBio'),
('12', 'CRBM'),
('13', 'CRTR'),
('14', 'CRQ'),
('15', 'CFO'),
('16', 'CFF'),
('17', 'CFMV'),
('18', 'CFP'),
('19', 'CREFONO'),
('20', 'COFFITO'),
('99', 'Outro');
