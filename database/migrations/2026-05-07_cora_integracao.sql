-- =============================================================================
-- Migration: Integração Cora — Boletos Registrados
-- Data: 2026-05-07
-- Descrição: Adiciona suporte à integração Cora para emissão de boletos.
--            1. Coluna cora_invoice_id na tabela contas_receber
--            2. Coluna cora_boleto_url para armazenar o link do boleto
--            3. Coluna cora_boleto_pdf para armazenar o PDF do boleto
--            4. Registro na tabela integracoes para o provider 'cora'
-- =============================================================================

-- 1. Adiciona colunas Cora na tabela contas_receber
ALTER TABLE `contas_receber`
    ADD COLUMN IF NOT EXISTS `cora_invoice_id`  VARCHAR(100) NULL DEFAULT NULL COMMENT 'ID da fatura gerada na Cora' AFTER `asaas_subscription_id`,
    ADD COLUMN IF NOT EXISTS `cora_boleto_url`  VARCHAR(500) NULL DEFAULT NULL COMMENT 'URL do boleto gerado pela Cora' AFTER `cora_invoice_id`,
    ADD COLUMN IF NOT EXISTS `cora_boleto_pdf`  VARCHAR(500) NULL DEFAULT NULL COMMENT 'URL do PDF do boleto Cora' AFTER `cora_boleto_url`,
    ADD COLUMN IF NOT EXISTS `cora_pix_qrcode`  TEXT NULL DEFAULT NULL COMMENT 'QR Code Pix gerado pela Cora' AFTER `cora_boleto_pdf`;

-- 2. Índice para busca por cora_invoice_id (usado no webhook)
CREATE INDEX IF NOT EXISTS `idx_contas_receber_cora_invoice_id`
    ON `contas_receber` (`cora_invoice_id`);

-- 3. Cria diretório de certificados (executado via PHP — apenas documentação)
-- Os certificados são armazenados em: storage/cora/certs/
-- Permissões: 0700 (diretório) e 0600 (arquivos)

-- 4. Verifica se a tabela integracoes tem suporte ao provider 'cora'
-- (A tabela integracoes já existe e suporta qualquer nome de provider via campo nome)
-- Nenhuma alteração estrutural necessária na tabela integracoes.

-- =============================================================================
-- ROLLBACK (se necessário):
-- ALTER TABLE `contas_receber`
--     DROP COLUMN IF EXISTS `cora_invoice_id`,
--     DROP COLUMN IF EXISTS `cora_boleto_url`,
--     DROP COLUMN IF EXISTS `cora_boleto_pdf`,
--     DROP COLUMN IF EXISTS `cora_pix_qrcode`;
-- DROP INDEX IF EXISTS `idx_contas_receber_cora_invoice_id` ON `contas_receber`;
-- =============================================================================
