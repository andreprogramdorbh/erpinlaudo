-- ============================================================
-- IMPORTAÇÃO CNES GLOBAL — BASE DATASUS/CNES 202602
-- Método: LOAD DATA LOCAL INFILE (phpMyAdmin / MySQL CLI)
-- Encoding fonte: ISO-8859-1 → UTF-8 (CHARACTER SET latin1)
-- Separador: ponto-e-vírgula (;), campos entre aspas duplas
-- ============================================================
-- INSTRUÇÕES:
--   1. Execute primeiro a migration: 2026-03-25_cnes_global.sql
--   2. Ajuste os caminhos abaixo para o caminho real dos CSVs no servidor
--   3. Execute via phpMyAdmin > SQL, ou via MySQL CLI:
--      mysql -u usuario -p --local-infile=1 nome_banco < importar_cnes_sql.sql
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;
SET sql_mode = '';
SET @caminho = '/tmp/cnes_base/';   -- ← AJUSTE ESTE CAMINHO SE NECESSÁRIO

-- ============================================================
-- TABELA TEMPORÁRIA PARA STAGING (evita duplicatas na importação)
-- ============================================================

DROP TABLE IF EXISTS _cnes_estab_staging;
CREATE TABLE _cnes_estab_staging (
    co_unidade       VARCHAR(30),
    co_cnes          VARCHAR(7),
    nu_cnpj_manten   VARCHAR(14),
    tp_pfpj          VARCHAR(1),
    nivel_dep        VARCHAR(1),
    no_razao_social  VARCHAR(255),
    no_fantasia      VARCHAR(255),
    no_logradouro    VARCHAR(255),
    nu_endereco      VARCHAR(10),
    no_complemento   VARCHAR(100),
    no_bairro        VARCHAR(100),
    co_cep           VARCHAR(8),
    co_regiao_saude  VARCHAR(4),
    co_micro_regiao  VARCHAR(6),
    co_distrito_san  VARCHAR(4),
    co_distrito_adm  VARCHAR(4),
    nu_telefone      VARCHAR(20),
    nu_fax           VARCHAR(20),
    no_email         VARCHAR(100),
    nu_cpf           VARCHAR(11),
    nu_cnpj          VARCHAR(14),
    co_atividade     VARCHAR(2),
    co_clientela     VARCHAR(2),
    nu_alvara        VARCHAR(20),
    dt_expedicao     VARCHAR(10),
    tp_orgao_exp     VARCHAR(2),
    dt_val_lic_sani  VARCHAR(10),
    tp_lic_sani      VARCHAR(1),
    tp_unidade       VARCHAR(2),
    co_turno_atend   VARCHAR(2),
    co_estado_gestor VARCHAR(2),
    co_municipio_ges VARCHAR(6),
    dt_atualizacao   VARCHAR(10),
    co_usuario       VARCHAR(50),
    co_cpf_diretor   VARCHAR(11),
    reg_diretor      VARCHAR(20),
    st_adesao_filant VARCHAR(1),
    co_motivo_desab  VARCHAR(2),
    no_url           VARCHAR(255),
    nu_latitude      VARCHAR(20),
    nu_longitude     VARCHAR(20),
    dt_atu_geo       VARCHAR(10),
    no_usuario_geo   VARCHAR(50),
    co_natureza_jur  VARCHAR(4),
    tp_sempre_aberto VARCHAR(1),
    st_gera_credito  VARCHAR(1),
    st_conexao_int   VARCHAR(1),
    co_tipo_unidade  VARCHAR(4),
    no_fantasia_abrev VARCHAR(30),
    tp_gestao        VARCHAR(1),
    dt_atu_origem    VARCHAR(10),
    co_tipo_estab    VARCHAR(2),
    co_ativ_principal VARCHAR(2),
    st_contrato_form VARCHAR(1),
    co_tipo_abrang   VARCHAR(1),
    st_coworking     VARCHAR(1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS _cnes_equip_staging;
CREATE TABLE _cnes_equip_staging (
    co_unidade       VARCHAR(30),
    co_equipamento   VARCHAR(4),
    co_tipo_equip    VARCHAR(4),
    qt_existente     INT,
    qt_uso           INT,
    tp_sus           VARCHAR(1),
    qt_sus           INT,
    dt_atualizacao   VARCHAR(10),
    co_usuario       VARCHAR(50),
    dt_atu_origem    VARCHAR(10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS _cnes_prof_staging;
CREATE TABLE _cnes_prof_staging (
    co_profissional  VARCHAR(32),
    co_cpf           VARCHAR(14),
    no_profissional  VARCHAR(255),
    co_cns           VARCHAR(20),
    dt_atualizacao   VARCHAR(10),
    co_usuario       VARCHAR(50),
    st_nmprof        VARCHAR(1),
    co_nacionalidade VARCHAR(3),
    co_seq_inclusao  VARCHAR(10),
    dt_atu_origem    VARCHAR(10),
    no_social        VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS _cnes_vinculo_staging;
CREATE TABLE _cnes_vinculo_staging (
    co_unidade       VARCHAR(30),
    co_profissional  VARCHAR(32),
    co_cbo           VARCHAR(6),
    tp_sus_nao_sus   VARCHAR(1),
    ind_vinculacao   VARCHAR(6),
    tp_terceiro_sih  VARCHAR(1),
    qt_ch_ambulat    INT,
    co_conselho      VARCHAR(2),
    nu_registro      VARCHAR(20),
    sg_uf_crm        VARCHAR(2),
    tp_preceptor     VARCHAR(1),
    tp_residente     VARCHAR(1),
    nu_cnpj_detalhe  VARCHAR(14),
    dt_atualizacao   VARCHAR(10),
    co_usuario       VARCHAR(50),
    dt_atu_origem    VARCHAR(10),
    qt_ch_outros     INT,
    qt_ch_hosp_sus   INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ETAPA 1: CARREGAR tbEstabelecimento (ISO-8859-1)
-- ============================================================
SELECT 'Carregando tbEstabelecimento...' AS status;

LOAD DATA LOCAL INFILE '/tmp/cnes_base/tbEstabelecimento202602.csv'
INTO TABLE _cnes_estab_staging
CHARACTER SET latin1
FIELDS TERMINATED BY ';'
OPTIONALLY ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(co_unidade, co_cnes, nu_cnpj_manten, tp_pfpj, nivel_dep,
 no_razao_social, no_fantasia, no_logradouro, nu_endereco, no_complemento,
 no_bairro, co_cep, co_regiao_saude, co_micro_regiao, co_distrito_san,
 co_distrito_adm, nu_telefone, nu_fax, no_email, nu_cpf,
 nu_cnpj, co_atividade, co_clientela, nu_alvara, dt_expedicao,
 tp_orgao_exp, dt_val_lic_sani, tp_lic_sani, tp_unidade, co_turno_atend,
 co_estado_gestor, co_municipio_ges, dt_atualizacao, co_usuario, co_cpf_diretor,
 reg_diretor, st_adesao_filant, co_motivo_desab, no_url, nu_latitude,
 nu_longitude, dt_atu_geo, no_usuario_geo, co_natureza_jur, tp_sempre_aberto,
 st_gera_credito, st_conexao_int, co_tipo_unidade, no_fantasia_abrev, tp_gestao,
 dt_atu_origem, co_tipo_estab, co_ativ_principal, st_contrato_form, co_tipo_abrang,
 st_coworking);

SELECT CONCAT('Registros carregados no staging: ', COUNT(*)) AS status FROM _cnes_estab_staging;

-- ============================================================
-- ETAPA 2: INSERIR/ATUALIZAR cnes_estabelecimentos (UPSERT)
-- ============================================================
SELECT 'Inserindo em cnes_estabelecimentos (UPSERT)...' AS status;

INSERT INTO cnes_estabelecimentos (
    co_cnes, co_unidade, nu_cnpj, nu_cnpj_mantenedora,
    no_razao_social, no_fantasia, no_fantasia_abrev,
    tp_unidade, co_tipo_unidade, co_tipo_estabelecimento,
    co_atividade, co_clientela, tp_gestao,
    no_logradouro, nu_endereco, no_complemento, no_bairro,
    co_cep, co_estado_gestor, co_municipio_gestor,
    nu_telefone, nu_fax, no_email, no_url,
    nu_latitude, nu_longitude,
    co_cpf_diretor_clinico, reg_diretor_clinico,
    co_natureza_juridica, st_conexao_internet,
    co_motivo_desabilitacao, dt_atualizacao,
    competencia, created_at, updated_at
)
SELECT
    TRIM(co_cnes),
    TRIM(co_unidade),
    TRIM(nu_cnpj),
    TRIM(nu_cnpj_manten),
    CONVERT(TRIM(no_razao_social) USING utf8mb4),
    CONVERT(TRIM(no_fantasia) USING utf8mb4),
    CONVERT(TRIM(no_fantasia_abrev) USING utf8mb4),
    TRIM(tp_unidade),
    TRIM(co_tipo_unidade),
    TRIM(co_tipo_estab),
    TRIM(co_atividade),
    TRIM(co_clientela),
    TRIM(tp_gestao),
    CONVERT(TRIM(no_logradouro) USING utf8mb4),
    TRIM(nu_endereco),
    CONVERT(TRIM(no_complemento) USING utf8mb4),
    CONVERT(TRIM(no_bairro) USING utf8mb4),
    TRIM(co_cep),
    TRIM(co_estado_gestor),
    TRIM(co_municipio_ges),
    TRIM(nu_telefone),
    TRIM(nu_fax),
    LOWER(TRIM(no_email)),
    TRIM(no_url),
    NULLIF(TRIM(nu_latitude), ''),
    NULLIF(TRIM(nu_longitude), ''),
    TRIM(co_cpf_diretor),
    TRIM(reg_diretor),
    TRIM(co_natureza_jur),
    TRIM(st_conexao_int),
    NULLIF(TRIM(co_motivo_desab), ''),
    CASE
        WHEN TRIM(dt_atualizacao) REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$'
        THEN STR_TO_DATE(TRIM(dt_atualizacao), '%d/%m/%Y')
        ELSE NULL
    END,
    '202602',
    NOW(),
    NOW()
FROM _cnes_estab_staging
ON DUPLICATE KEY UPDATE
    co_unidade              = VALUES(co_unidade),
    nu_cnpj                 = VALUES(nu_cnpj),
    nu_cnpj_mantenedora     = VALUES(nu_cnpj_mantenedora),
    no_razao_social         = VALUES(no_razao_social),
    no_fantasia             = VALUES(no_fantasia),
    no_fantasia_abrev       = VALUES(no_fantasia_abrev),
    tp_unidade              = VALUES(tp_unidade),
    co_tipo_unidade         = VALUES(co_tipo_unidade),
    co_tipo_estabelecimento = VALUES(co_tipo_estabelecimento),
    co_atividade            = VALUES(co_atividade),
    co_clientela            = VALUES(co_clientela),
    tp_gestao               = VALUES(tp_gestao),
    no_logradouro           = VALUES(no_logradouro),
    nu_endereco             = VALUES(nu_endereco),
    no_complemento          = VALUES(no_complemento),
    no_bairro               = VALUES(no_bairro),
    co_cep                  = VALUES(co_cep),
    co_estado_gestor        = VALUES(co_estado_gestor),
    co_municipio_gestor     = VALUES(co_municipio_gestor),
    nu_telefone             = VALUES(nu_telefone),
    nu_fax                  = VALUES(nu_fax),
    no_email                = VALUES(no_email),
    no_url                  = VALUES(no_url),
    nu_latitude             = VALUES(nu_latitude),
    nu_longitude            = VALUES(nu_longitude),
    co_cpf_diretor_clinico  = VALUES(co_cpf_diretor_clinico),
    reg_diretor_clinico     = VALUES(reg_diretor_clinico),
    co_natureza_juridica    = VALUES(co_natureza_juridica),
    st_conexao_internet     = VALUES(st_conexao_internet),
    co_motivo_desabilitacao = VALUES(co_motivo_desabilitacao),
    dt_atualizacao          = VALUES(dt_atualizacao),
    competencia             = VALUES(competencia),
    updated_at              = NOW();

SELECT CONCAT('Estabelecimentos inseridos/atualizados: ', ROW_COUNT()) AS status;

-- ============================================================
-- ETAPA 3: CARREGAR rlEstabEquipamento
-- ============================================================
SELECT 'Carregando rlEstabEquipamento...' AS status;

LOAD DATA LOCAL INFILE '/tmp/cnes_base/rlEstabEquipamento202602.csv'
INTO TABLE _cnes_equip_staging
CHARACTER SET utf8mb4
FIELDS TERMINATED BY ';'
OPTIONALLY ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(co_unidade, co_equipamento, co_tipo_equip, qt_existente, qt_uso,
 tp_sus, qt_sus, dt_atualizacao, co_usuario, dt_atu_origem);

SELECT CONCAT('Equipamentos no staging: ', COUNT(*)) AS status FROM _cnes_equip_staging;

-- ============================================================
-- ETAPA 4: INSERIR/ATUALIZAR cnes_equipamentos (UPSERT)
-- ============================================================
SELECT 'Inserindo em cnes_equipamentos (UPSERT)...' AS status;

INSERT INTO cnes_equipamentos (
    co_cnes, co_unidade, co_equipamento, co_tipo_equipamento,
    qt_existente, qt_uso, tp_sus, qt_sus,
    dt_atualizacao, competencia, created_at, updated_at
)
SELECT
    e.co_cnes,
    s.co_unidade,
    TRIM(s.co_equipamento),
    TRIM(s.co_tipo_equip),
    COALESCE(s.qt_existente, 0),
    COALESCE(s.qt_uso, 0),
    TRIM(s.tp_sus),
    COALESCE(s.qt_sus, 0),
    CASE
        WHEN TRIM(s.dt_atualizacao) REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$'
        THEN STR_TO_DATE(TRIM(s.dt_atualizacao), '%d/%m/%Y')
        ELSE NULL
    END,
    '202602',
    NOW(),
    NOW()
FROM _cnes_equip_staging s
INNER JOIN cnes_estabelecimentos e ON e.co_unidade = TRIM(s.co_unidade)
ON DUPLICATE KEY UPDATE
    qt_existente    = VALUES(qt_existente),
    qt_uso          = VALUES(qt_uso),
    tp_sus          = VALUES(tp_sus),
    qt_sus          = VALUES(qt_sus),
    dt_atualizacao  = VALUES(dt_atualizacao),
    competencia     = VALUES(competencia),
    updated_at      = NOW();

SELECT CONCAT('Equipamentos inseridos/atualizados: ', ROW_COUNT()) AS status;

-- ============================================================
-- ETAPA 5: CARREGAR tbDadosProfissionalSus
-- ============================================================
SELECT 'Carregando tbDadosProfissionalSus...' AS status;

LOAD DATA LOCAL INFILE '/tmp/cnes_base/tbDadosProfissionalSus202602.csv'
INTO TABLE _cnes_prof_staging
CHARACTER SET latin1
FIELDS TERMINATED BY ';'
OPTIONALLY ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(co_profissional, co_cpf, no_profissional, co_cns,
 dt_atualizacao, co_usuario, st_nmprof, co_nacionalidade,
 co_seq_inclusao, dt_atu_origem, no_social);

SELECT CONCAT('Profissionais no staging: ', COUNT(*)) AS status FROM _cnes_prof_staging;

-- ============================================================
-- ETAPA 6: CARREGAR tbCargaHorariaSus (vínculos)
-- ============================================================
SELECT 'Carregando tbCargaHorariaSus...' AS status;

LOAD DATA LOCAL INFILE '/tmp/cnes_base/tbCargaHorariaSus202602.csv'
INTO TABLE _cnes_vinculo_staging
CHARACTER SET utf8mb4
FIELDS TERMINATED BY ';'
OPTIONALLY ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(co_unidade, co_profissional, co_cbo, tp_sus_nao_sus,
 ind_vinculacao, tp_terceiro_sih, qt_ch_ambulat,
 co_conselho, nu_registro, sg_uf_crm, tp_preceptor,
 tp_residente, nu_cnpj_detalhe, dt_atualizacao, co_usuario,
 dt_atu_origem, qt_ch_outros, qt_ch_hosp_sus);

SELECT CONCAT('Vínculos no staging: ', COUNT(*)) AS status FROM _cnes_vinculo_staging;

-- ============================================================
-- ETAPA 7: INSERIR/ATUALIZAR cnes_profissionais (JOIN vínculos + dados)
-- ============================================================
SELECT 'Inserindo em cnes_profissionais (UPSERT)...' AS status;

INSERT INTO cnes_profissionais (
    co_cnes, co_unidade, co_profissional_sus, no_profissional,
    co_cbo, co_conselho_classe, nu_registro_conselho, sg_uf_conselho,
    tp_sus_nao_sus, dt_atualizacao, competencia, created_at, updated_at
)
SELECT
    est.co_cnes,
    v.co_unidade,
    TRIM(v.co_profissional),
    CONVERT(TRIM(p.no_profissional) USING utf8mb4),
    TRIM(v.co_cbo),
    TRIM(v.co_conselho),
    TRIM(v.nu_registro),
    TRIM(v.sg_uf_crm),
    TRIM(v.tp_sus_nao_sus),
    CASE
        WHEN TRIM(v.dt_atualizacao) REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$'
        THEN STR_TO_DATE(TRIM(v.dt_atualizacao), '%d/%m/%Y')
        ELSE NULL
    END,
    '202602',
    NOW(),
    NOW()
FROM _cnes_vinculo_staging v
INNER JOIN cnes_estabelecimentos est ON est.co_unidade = TRIM(v.co_unidade)
LEFT JOIN _cnes_prof_staging p ON p.co_profissional = TRIM(v.co_profissional)
ON DUPLICATE KEY UPDATE
    no_profissional         = VALUES(no_profissional),
    co_cbo                  = VALUES(co_cbo),
    co_conselho_classe      = VALUES(co_conselho_classe),
    nu_registro_conselho    = VALUES(nu_registro_conselho),
    sg_uf_conselho          = VALUES(sg_uf_conselho),
    tp_sus_nao_sus          = VALUES(tp_sus_nao_sus),
    dt_atualizacao          = VALUES(dt_atualizacao),
    competencia             = VALUES(competencia),
    updated_at              = NOW();

SELECT CONCAT('Profissionais inseridos/atualizados: ', ROW_COUNT()) AS status;

-- ============================================================
-- LIMPEZA DOS STAGING
-- ============================================================
DROP TABLE IF EXISTS _cnes_estab_staging;
DROP TABLE IF EXISTS _cnes_equip_staging;
DROP TABLE IF EXISTS _cnes_prof_staging;
DROP TABLE IF EXISTS _cnes_vinculo_staging;

SET foreign_key_checks = 1;

-- ============================================================
-- RESUMO FINAL
-- ============================================================
SELECT
    (SELECT COUNT(*) FROM cnes_estabelecimentos) AS total_estabelecimentos,
    (SELECT COUNT(*) FROM cnes_equipamentos)     AS total_equipamentos,
    (SELECT COUNT(*) FROM cnes_profissionais)    AS total_profissionais,
    (SELECT MAX(competencia) FROM cnes_estabelecimentos) AS ultima_competencia;
