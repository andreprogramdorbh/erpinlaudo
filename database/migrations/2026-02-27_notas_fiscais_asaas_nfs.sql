-- ============================================================
-- Migration: Campos de NF-s via Asaas na tabela notas_fiscais
-- Data: 2026-02-27
-- Compatível com: MariaDB 10.0.2+ (ADD COLUMN IF NOT EXISTS)
--                 MariaDB 10.1.4+ (CREATE INDEX IF NOT EXISTS)
-- Descrição: Adiciona suporte à emissão de NF-s via API Asaas,
--            rastreamento de origem (manual vs asaas) e vínculo
--            com contas a receber.
-- ============================================================

ALTER TABLE notas_fiscais
    -- ID da NF no Asaas (ex: inv_00000000000)
    ADD COLUMN IF NOT EXISTS asaas_invoice_id  VARCHAR(60)                    NULL AFTER xml_path,

    -- Origem da emissão: 'manual' = anexo manual, 'asaas' = emitida via API
    ADD COLUMN IF NOT EXISTS origem_emissao    ENUM('manual','asaas') NOT NULL DEFAULT 'manual' AFTER asaas_invoice_id,

    -- Vínculo com a conta a receber que originou a NF
    ADD COLUMN IF NOT EXISTS conta_receber_id  INT UNSIGNED                   NULL AFTER origem_emissao,

    -- URL do PDF da NF gerada pelo Asaas
    ADD COLUMN IF NOT EXISTS asaas_pdf_url     VARCHAR(500)                   NULL AFTER conta_receber_id,

    -- Status da NF no Asaas (SCHEDULED, SYNCHRONIZED, AUTHORIZED, ERROR, CANCELED, etc.)
    ADD COLUMN IF NOT EXISTS asaas_status      VARCHAR(40)                    NULL AFTER asaas_pdf_url,

    -- Descrição do serviço municipal usada na emissão
    ADD COLUMN IF NOT EXISTS servico_descricao VARCHAR(255)                   NULL AFTER asaas_status,

    -- Código do serviço municipal (municipalServiceCode)
    ADD COLUMN IF NOT EXISTS servico_codigo    VARCHAR(20)                    NULL AFTER servico_descricao,

    -- ID do serviço municipal no Asaas (municipalServiceId)
    ADD COLUMN IF NOT EXISTS servico_id_asaas  VARCHAR(20)                    NULL AFTER servico_codigo,

    -- Observações da NF enviadas ao Asaas
    ADD COLUMN IF NOT EXISTS observacoes_nf    TEXT                           NULL AFTER servico_id_asaas;

-- Índices criados separadamente com IF NOT EXISTS para idempotência
CREATE INDEX IF NOT EXISTS idx_nf_asaas_invoice_id ON notas_fiscais (asaas_invoice_id);
CREATE INDEX IF NOT EXISTS idx_nf_conta_receber_id  ON notas_fiscais (conta_receber_id);
CREATE INDEX IF NOT EXISTS idx_nf_origem_emissao    ON notas_fiscais (origem_emissao);

-- Atualiza registros existentes: todos os que não têm asaas_invoice_id são 'manual'
UPDATE notas_fiscais SET origem_emissao = 'manual' WHERE asaas_invoice_id IS NULL;
