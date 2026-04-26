-- Migration: suporte a múltiplos CRMs por médico (um por estado)
-- Cria tabela medico_crms para armazenar CRMs adicionais além do CRM principal
-- O CRM principal continua na tabela medicos (crm + uf_crm) para retrocompatibilidade

CREATE TABLE IF NOT EXISTS medico_crms (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medico_id   INT UNSIGNED NOT NULL,
    usuario_id  INT NOT NULL,
    crm         VARCHAR(50) NOT NULL,
    uf_crm      CHAR(2) NOT NULL,
    principal   TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = CRM principal (espelho do medicos.crm)',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_medico_crms_medico
        FOREIGN KEY (medico_id) REFERENCES medicos(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_medico_crms_usuario
        FOREIGN KEY (usuario_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uk_medico_crms_medico_uf (medico_id, uf_crm),
    INDEX idx_medico_crms_medico (medico_id),
    INDEX idx_medico_crms_usuario (usuario_id),
    INDEX idx_medico_crms_crm (crm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrar CRMs existentes da tabela medicos para medico_crms (CRM principal)
INSERT IGNORE INTO medico_crms (medico_id, usuario_id, crm, uf_crm, principal)
SELECT id, usuario_id, crm, uf_crm, 1
FROM medicos
WHERE crm IS NOT NULL AND crm != '' AND uf_crm IS NOT NULL AND uf_crm != '';
