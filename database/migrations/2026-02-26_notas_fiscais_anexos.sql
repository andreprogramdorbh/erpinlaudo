-- ============================================================
-- Notas Fiscais — Tabela de Anexos
-- Criado em: 2026-02-26
-- Permite anexar arquivos (PDF, XML, JPG) às notas fiscais.
-- Os anexos ficam disponíveis para download no portal do cliente.
-- ============================================================

CREATE TABLE IF NOT EXISTS notas_fiscais_anexos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT NOT NULL COMMENT 'Tenant (usuário ERP dono da nota)',
    nota_fiscal_id  INT NOT NULL COMMENT 'ID da nota fiscal vinculada',
    file_path       VARCHAR(500) NOT NULL COMMENT 'Caminho relativo a BASE_PATH',
    original_name   VARCHAR(255) NOT NULL COMMENT 'Nome original do arquivo enviado',
    mime_type       VARCHAR(100) NULL COMMENT 'MIME type detectado pelo servidor',
    file_size       INT NULL COMMENT 'Tamanho em bytes',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nf_anexos_nota   (nota_fiscal_id),
    INDEX idx_nf_anexos_tenant (usuario_id),
    CONSTRAINT fk_nf_anexo_nota FOREIGN KEY (nota_fiscal_id)
        REFERENCES notas_fiscais(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
