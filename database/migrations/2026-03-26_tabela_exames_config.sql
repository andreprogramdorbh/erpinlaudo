-- ============================================================
-- Migration: Configuração da Tabela de Exames (preços, impostos, TAGs DICOM)
-- Data: 2026-03-26
-- Compatível com: MariaDB 10.0.2+ (ADD COLUMN IF NOT EXISTS)
-- ============================================================

ALTER TABLE tabela_exames
  ADD COLUMN IF NOT EXISTS nivel                   INT          NULL DEFAULT NULL AFTER valor_padrao,
  ADD COLUMN IF NOT EXISTS perc_rotina             DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER nivel,
  ADD COLUMN IF NOT EXISTS perc_urgencia           DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER perc_rotina,
  ADD COLUMN IF NOT EXISTS valor_rotina            DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER perc_urgencia,
  ADD COLUMN IF NOT EXISTS valor_urgencia          DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER valor_rotina,
  ADD COLUMN IF NOT EXISTS imposto_icms            DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER valor_urgencia,
  ADD COLUMN IF NOT EXISTS imposto_ipi             DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER imposto_icms,
  ADD COLUMN IF NOT EXISTS imposto_pis_cofins      DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER imposto_ipi,
  ADD COLUMN IF NOT EXISTS imposto_simples         DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER imposto_pis_cofins,
  ADD COLUMN IF NOT EXISTS custo_comissao          DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER imposto_simples,
  ADD COLUMN IF NOT EXISTS custo_mao_obra_direta   DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER custo_comissao,
  ADD COLUMN IF NOT EXISTS custo_mao_obra_indireta DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER custo_mao_obra_direta,
  ADD COLUMN IF NOT EXISTS margem_lucro            DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER custo_mao_obra_indireta,
  ADD COLUMN IF NOT EXISTS preco_custo             DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER margem_lucro,
  ADD COLUMN IF NOT EXISTS preco_venda             DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER preco_custo;

-- Tabela para as TAGs DICOM vinculadas ao exame
CREATE TABLE IF NOT EXISTS tabela_exames_tags (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exame_id   INT UNSIGNED  NOT NULL,
    tag_nome   VARCHAR(100)  NOT NULL,
    tag_valor  VARCHAR(255)  NOT NULL,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tabela_exames_tags_exame (exame_id),
    CONSTRAINT fk_tabela_exames_tags_exame
        FOREIGN KEY (exame_id) REFERENCES tabela_exames(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
