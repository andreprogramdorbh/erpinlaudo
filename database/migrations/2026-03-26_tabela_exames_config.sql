-- Adicionar novas colunas na tabela_exames
ALTER TABLE tabela_exames
ADD COLUMN nivel INT NULL DEFAULT NULL AFTER valor_padrao,
ADD COLUMN perc_rotina DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER nivel,
ADD COLUMN perc_urgencia DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER perc_rotina,
ADD COLUMN valor_rotina DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER perc_urgencia,
ADD COLUMN valor_urgencia DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER valor_rotina,
ADD COLUMN imposto_icms DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER valor_urgencia,
ADD COLUMN imposto_ipi DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER imposto_icms,
ADD COLUMN imposto_pis_cofins DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER imposto_ipi,
ADD COLUMN imposto_simples DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER imposto_pis_cofins,
ADD COLUMN custo_comissao DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER imposto_simples,
ADD COLUMN custo_mao_obra_direta DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER custo_comissao,
ADD COLUMN custo_mao_obra_indireta DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER custo_mao_obra_direta,
ADD COLUMN margem_lucro DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER custo_mao_obra_indireta,
ADD COLUMN preco_custo DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER margem_lucro,
ADD COLUMN preco_venda DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER preco_custo;

-- Criar tabela para as TAGs DICOM vinculadas ao exame
CREATE TABLE IF NOT EXISTS tabela_exames_tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exame_id INT UNSIGNED NOT NULL,
    tag_nome VARCHAR(100) NOT NULL,
    tag_valor VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tabela_exames_tags_exame
        FOREIGN KEY (exame_id) REFERENCES tabela_exames(id)
        ON DELETE CASCADE,
    INDEX idx_tabela_exames_tags_exame (exame_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
