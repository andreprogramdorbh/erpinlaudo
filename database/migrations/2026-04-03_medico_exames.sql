-- ============================================================
-- Migration: Vínculo Médico ↔ Tabela de Exames (Serviços/Exames do Médico)
-- Data: 2026-04-03
-- Compatível com: MariaDB 10.0+ (CREATE TABLE IF NOT EXISTS)
-- Descrição: Permite definir valores específicos por médico para
--            cada exame/modalidade, sobrepondo os valores da tabela
--            de preços durante o cálculo de apuração.
-- ============================================================

CREATE TABLE IF NOT EXISTS medico_exames (
    id               INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    usuario_id       INT UNSIGNED  NOT NULL,
    medico_id        INT UNSIGNED  NOT NULL,
    tabela_exame_id  INT UNSIGNED  NOT NULL,

    -- Valores específicos do médico (override dos valores da tabela)
    valor_rotina     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    valor_urgencia   DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    -- 0 = usa valores da tabela de exames; 1 = usa valores customizados acima
    usa_valor_custom TINYINT(1)    NOT NULL DEFAULT 0,

    observacoes      TEXT          NULL,

    -- MariaDB: TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP é seguro desde 5.6+
    -- ON UPDATE CURRENT_TIMESTAMP também é suportado nativamente no MariaDB
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_medico_exame         (medico_id, tabela_exame_id),
    INDEX      idx_medico_exames_usuario (usuario_id),
    INDEX      idx_medico_exames_medico  (medico_id),

    CONSTRAINT fk_medico_exames_medico
        FOREIGN KEY (medico_id)       REFERENCES medicos(id)       ON DELETE CASCADE,
    CONSTRAINT fk_medico_exames_tabela
        FOREIGN KEY (tabela_exame_id) REFERENCES tabela_exames(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
