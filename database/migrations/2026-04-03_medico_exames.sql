-- Tabela de vínculo entre médico e exames da tabela de preços
-- Permite definir valores específicos por médico para cada exame/modalidade
CREATE TABLE IF NOT EXISTS medico_exames (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED NOT NULL,
    medico_id       INT UNSIGNED NOT NULL,
    tabela_exame_id INT UNSIGNED NOT NULL,
    -- Valores específicos do médico (override dos valores da tabela)
    valor_rotina    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    valor_urgencia  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    -- Indica se usa valor da tabela (0) ou valor customizado (1)
    usa_valor_custom TINYINT(1) NOT NULL DEFAULT 0,
    observacoes     TEXT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_medico_exames_medico
        FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE,
    CONSTRAINT fk_medico_exames_tabela
        FOREIGN KEY (tabela_exame_id) REFERENCES tabela_exames(id) ON DELETE CASCADE,
    UNIQUE KEY uk_medico_exame (medico_id, tabela_exame_id),
    INDEX idx_medico_exames_usuario (usuario_id),
    INDEX idx_medico_exames_medico (medico_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
