-- ============================================================
-- ERP InLaudo — Proteção contra cadastros duplicados
-- Migration: 2026-05-25_unique_constraints_anti_duplicatas.sql
-- Compatível com MySQL 5.6+ e MariaDB 5.5+
-- Execute via phpMyAdmin no servidor HostGator
-- ============================================================
-- Estratégia: PROCEDURE que verifica se o índice já existe
-- antes de criá-lo (seguro para re-executar).
-- ============================================================

DROP PROCEDURE IF EXISTS sp_add_unique_idx;

DELIMITER $$

CREATE PROCEDURE sp_add_unique_idx(
    IN p_table      VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_columns    TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM   information_schema.STATISTICS
        WHERE  TABLE_SCHEMA = DATABASE()
          AND  TABLE_NAME   = p_table
          AND  INDEX_NAME   = p_index_name
    ) THEN
        SET @sql = CONCAT(
            'ALTER TABLE `', p_table, '` ',
            'ADD UNIQUE INDEX `', p_index_name, '` (', p_columns, ')'
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- 1. CLIENTES
--    Regra: mesmo usuario_id não pode ter dois registros com
--    o mesmo cpf_cnpj (documento não nulo).
--    Campo: cpf_cnpj + usuario_id
-- ============================================================
CALL sp_add_unique_idx(
    'clientes',
    'uq_clientes_cpf_cnpj_usuario',
    '`cpf_cnpj`, `usuario_id`'
);

-- ============================================================
-- 2. FORNECEDORES
--    Regra: mesmo usuario_id não pode ter dois fornecedores com
--    o mesmo documento (CNPJ/CPF não nulo).
--    Campo: documento + usuario_id
-- ============================================================
CALL sp_add_unique_idx(
    'fornecedores',
    'uq_fornecedores_documento_usuario',
    '`documento`, `usuario_id`'
);

-- ============================================================
-- 3. COLABORADORES
--    Regra: mesmo usuario_id não pode ter dois colaboradores com
--    o mesmo cpf_cnpj.
--    Campo: cpf_cnpj + usuario_id
--    (medicos já têm UNIQUE KEY uk_medicos_usuario_crm)
-- ============================================================
CALL sp_add_unique_idx(
    'colaboradores',
    'uq_colaboradores_cpf_cnpj_usuario',
    '`cpf_cnpj`, `usuario_id`'
);

-- ============================================================
-- 4. MEDICOS — CPF único por usuario
--    (CRM já é único via uk_medicos_usuario_crm)
-- ============================================================
CALL sp_add_unique_idx(
    'medicos',
    'uq_medicos_cpf_usuario',
    '`cpf`, `usuario_id`'
);

-- ============================================================
-- 5. PLANO DE CONTAS
--    Regra: mesmo usuario_id não pode ter dois planos com o
--    mesmo código.
--    Campo: codigo + usuario_id
-- ============================================================
CALL sp_add_unique_idx(
    'plano_contas',
    'uq_plano_contas_codigo_usuario',
    '`codigo`, `usuario_id`'
);

-- ============================================================
-- 6. PRODUTOS (est_produtos / produtos)
--    Já possui UNIQUE KEY uq_produto_codigo_usuario.
--    Adicionamos proteção por nome + usuario_id para evitar
--    nomes duplicados no mesmo tenant.
--    (opcional — comentado por padrão; descomente se necessário)
-- ============================================================
-- CALL sp_add_unique_idx(
--     'produtos',
--     'uq_produtos_nome_usuario',
--     '`nome`, `usuario_id`'
-- );

-- ============================================================
-- 7. USERS (sistema)
--    Já possui UNIQUE na coluna email pelo schema inicial.
--    Nenhuma alteração necessária.
-- ============================================================

DROP PROCEDURE IF EXISTS sp_add_unique_idx;

-- ============================================================
-- FIM DA MIGRATION
-- ============================================================
