-- Migration: Adicionar colunas individuais à tabela layout_exames
-- Problema: O PerfilController foi redesenhado para salvar cada coluna do mapeamento
--           como campo individual (separador, col_medico, col_crm, etc.) em vez de
--           usar apenas mapeamento_json. A tabela não tinha essas colunas, causando
--           o erro "Call to undefined method LayoutExame::allByUser()" ao acessar /perfil.
--
-- Nota: O Model LayoutExame.php já executa esses ALTER TABLE automaticamente via
--       garantirColunas() no construtor. Esta migration serve como registro formal
--       e para execução manual em ambientes onde o PHP não tenha permissão de DDL.
--
-- Seguro para executar múltiplas vezes (IF NOT EXISTS / IGNORE).

ALTER TABLE `layout_exames`
    ADD COLUMN IF NOT EXISTS `separador`             VARCHAR(5)   NOT NULL DEFAULT ';',
    ADD COLUMN IF NOT EXISTS `linha_cabecalho`       TINYINT      NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `col_medico`            VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `col_crm`               VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `col_modalidade`        VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `col_study_description` VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `col_prioridade`        VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `col_data_conclusao`    VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `col_paciente`          VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `col_paciente_id`       VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `col_unidade`           VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `col_accession`         VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `col_convenio`          VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `col_valor_exame`       VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `col_revisor`           VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `col_data_revisao`      VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `valores_urgencia`      VARCHAR(255) DEFAULT 'URGENTE,U,URGENT',
    ADD COLUMN IF NOT EXISTS `formato_data`          VARCHAR(50)  DEFAULT 'd/m/Y H:i';
