CREATE TABLE IF NOT EXISTS especialidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    especialidade VARCHAR(150) NOT NULL,
    subespecialidade VARCHAR(150) NULL,
    rqe VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_especialidades_usuario
        FOREIGN KEY (usuario_id) REFERENCES users(id)
        ON DELETE CASCADE,
    INDEX idx_especialidades_usuario (usuario_id),
    INDEX idx_especialidades_nome (especialidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS medicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    crm VARCHAR(50) NOT NULL,
    uf_crm CHAR(2) NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefone VARCHAR(30) NOT NULL,
    especialidade_id INT UNSIGNED NULL,
    subespecialidade VARCHAR(150) NULL,
    rqe VARCHAR(50) NULL,
    assinatura_digital VARCHAR(255) NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_medicos_usuario
        FOREIGN KEY (usuario_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_medicos_especialidade
        FOREIGN KEY (especialidade_id) REFERENCES especialidades(id)
        ON DELETE SET NULL,
    UNIQUE KEY uk_medicos_usuario_crm (usuario_id, crm, uf_crm),
    INDEX idx_medicos_usuario (usuario_id),
    INDEX idx_medicos_especialidade (especialidade_id),
    INDEX idx_medicos_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tabela_exames (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome_exame VARCHAR(255) NOT NULL,
    modalidade ENUM('TC', 'RM', 'RX', 'US') NOT NULL,
    valor_padrao DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tabela_exames_usuario
        FOREIGN KEY (usuario_id) REFERENCES users(id)
        ON DELETE CASCADE,
    INDEX idx_tabela_exames_usuario (usuario_id),
    INDEX idx_tabela_exames_modalidade (modalidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
