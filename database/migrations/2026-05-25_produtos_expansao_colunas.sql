-- ============================================================
-- ERP InLaudo — Expansão da tabela produtos
-- Migration: 2026-05-25_produtos_expansao_colunas.sql
-- Execute via phpMyAdmin no servidor HostGator
-- Compatível com MySQL 5.6+ (sem IF NOT EXISTS em ADD COLUMN)
-- ============================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_expand_produtos $$
CREATE PROCEDURE sp_expand_produtos()
BEGIN
    -- nome_tecnico
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'nome_tecnico') THEN
        ALTER TABLE `produtos` ADD COLUMN `nome_tecnico` VARCHAR(255) DEFAULT NULL AFTER `nome`;
    END IF;
    -- descricao_curta
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'descricao_curta') THEN
        ALTER TABLE `produtos` ADD COLUMN `descricao_curta` VARCHAR(500) DEFAULT NULL AFTER `nome_tecnico`;
    END IF;
    -- descricao_completa
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'descricao_completa') THEN
        ALTER TABLE `produtos` ADD COLUMN `descricao_completa` TEXT DEFAULT NULL AFTER `descricao_curta`;
    END IF;
    -- modelo
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'modelo') THEN
        ALTER TABLE `produtos` ADD COLUMN `modelo` VARCHAR(100) DEFAULT NULL;
    END IF;
    -- marca
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'marca') THEN
        ALTER TABLE `produtos` ADD COLUMN `marca` VARCHAR(100) DEFAULT NULL;
    END IF;
    -- fabricante_id
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'fabricante_id') THEN
        ALTER TABLE `produtos` ADD COLUMN `fabricante_id` INT DEFAULT NULL;
    END IF;
    -- fabricante_nome
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'fabricante_nome') THEN
        ALTER TABLE `produtos` ADD COLUMN `fabricante_nome` VARCHAR(255) DEFAULT NULL;
    END IF;
    -- pais_origem
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'pais_origem') THEN
        ALTER TABLE `produtos` ADD COLUMN `pais_origem` VARCHAR(100) DEFAULT NULL;
    END IF;
    -- ncm
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'ncm') THEN
        ALTER TABLE `produtos` ADD COLUMN `ncm` VARCHAR(20) DEFAULT NULL;
    END IF;
    -- anvisa_registro
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'anvisa_registro') THEN
        ALTER TABLE `produtos` ADD COLUMN `anvisa_registro` VARCHAR(50) DEFAULT NULL;
    END IF;
    -- anvisa_classe
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'anvisa_classe') THEN
        ALTER TABLE `produtos` ADD COLUMN `anvisa_classe` ENUM('I','II','III','IV') DEFAULT NULL;
    END IF;
    -- anvisa_validade
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'anvisa_validade') THEN
        ALTER TABLE `produtos` ADD COLUMN `anvisa_validade` DATE DEFAULT NULL;
    END IF;
    -- unidade_compra
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'unidade_compra') THEN
        ALTER TABLE `produtos` ADD COLUMN `unidade_compra` VARCHAR(20) DEFAULT NULL;
    END IF;
    -- fator_conversao
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'fator_conversao') THEN
        ALTER TABLE `produtos` ADD COLUMN `fator_conversao` DECIMAL(10,4) NOT NULL DEFAULT 1.0000;
    END IF;
    -- preco_custo_medio
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'preco_custo_medio') THEN
        ALTER TABLE `produtos` ADD COLUMN `preco_custo_medio` DECIMAL(15,4) NOT NULL DEFAULT 0.0000;
    END IF;
    -- despesas_acessorias
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'despesas_acessorias') THEN
        ALTER TABLE `produtos` ADD COLUMN `despesas_acessorias` DECIMAL(15,4) NOT NULL DEFAULT 0.0000;
    END IF;
    -- preco_minimo_venda
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'preco_minimo_venda') THEN
        ALTER TABLE `produtos` ADD COLUMN `preco_minimo_venda` DECIMAL(15,4) NOT NULL DEFAULT 0.0000;
    END IF;
    -- preco_sugerido
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'preco_sugerido') THEN
        ALTER TABLE `produtos` ADD COLUMN `preco_sugerido` DECIMAL(15,4) NOT NULL DEFAULT 0.0000;
    END IF;
    -- margem_lucro_liquida
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'margem_lucro_liquida') THEN
        ALTER TABLE `produtos` ADD COLUMN `margem_lucro_liquida` DECIMAL(10,4) NOT NULL DEFAULT 0.0000;
    END IF;
    -- impostos_percentual
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'impostos_percentual') THEN
        ALTER TABLE `produtos` ADD COLUMN `impostos_percentual` DECIMAL(10,4) NOT NULL DEFAULT 0.0000;
    END IF;
    -- moeda
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'moeda') THEN
        ALTER TABLE `produtos` ADD COLUMN `moeda` VARCHAR(3) NOT NULL DEFAULT 'BRL';
    END IF;
    -- estoque_minimo
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'estoque_minimo') THEN
        ALTER TABLE `produtos` ADD COLUMN `estoque_minimo` DECIMAL(15,4) NOT NULL DEFAULT 0.0000;
    END IF;
    -- estoque_maximo
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'estoque_maximo') THEN
        ALTER TABLE `produtos` ADD COLUMN `estoque_maximo` DECIMAL(15,4) NOT NULL DEFAULT 0.0000;
    END IF;
    -- ponto_reposicao
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'ponto_reposicao') THEN
        ALTER TABLE `produtos` ADD COLUMN `ponto_reposicao` DECIMAL(15,4) NOT NULL DEFAULT 0.0000;
    END IF;
    -- lead_time_dias
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'lead_time_dias') THEN
        ALTER TABLE `produtos` ADD COLUMN `lead_time_dias` INT NOT NULL DEFAULT 0;
    END IF;
    -- localizacao_estoque
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'localizacao_estoque') THEN
        ALTER TABLE `produtos` ADD COLUMN `localizacao_estoque` VARCHAR(100) DEFAULT NULL;
    END IF;
    -- controla_validade
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'controla_validade') THEN
        ALTER TABLE `produtos` ADD COLUMN `controla_validade` TINYINT(1) NOT NULL DEFAULT 0;
    END IF;
    -- alerta_validade_dias
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'alerta_validade_dias') THEN
        ALTER TABLE `produtos` ADD COLUMN `alerta_validade_dias` INT NOT NULL DEFAULT 90;
    END IF;
    -- lote_obrigatorio
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'lote_obrigatorio') THEN
        ALTER TABLE `produtos` ADD COLUMN `lote_obrigatorio` TINYINT(1) NOT NULL DEFAULT 0;
    END IF;
    -- controla_depreciacao
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'controla_depreciacao') THEN
        ALTER TABLE `produtos` ADD COLUMN `controla_depreciacao` TINYINT(1) NOT NULL DEFAULT 0;
    END IF;
    -- vida_util_meses
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'vida_util_meses') THEN
        ALTER TABLE `produtos` ADD COLUMN `vida_util_meses` INT DEFAULT NULL;
    END IF;
    -- valor_residual
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'valor_residual') THEN
        ALTER TABLE `produtos` ADD COLUMN `valor_residual` DECIMAL(15,4) DEFAULT 0.0000;
    END IF;
    -- metodo_depreciacao
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'metodo_depreciacao') THEN
        ALTER TABLE `produtos` ADD COLUMN `metodo_depreciacao` ENUM('linear','soma_digitos','unidades_produzidas') DEFAULT 'linear';
    END IF;
    -- depreciacao_mensal
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'depreciacao_mensal') THEN
        ALTER TABLE `produtos` ADD COLUMN `depreciacao_mensal` DECIMAL(15,4) DEFAULT 0.0000;
    END IF;
    -- alerta_substituicao_meses
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'alerta_substituicao_meses') THEN
        ALTER TABLE `produtos` ADD COLUMN `alerta_substituicao_meses` INT DEFAULT NULL;
    END IF;
    -- peso_kg
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'peso_kg') THEN
        ALTER TABLE `produtos` ADD COLUMN `peso_kg` DECIMAL(10,4) DEFAULT NULL;
    END IF;
    -- altura_cm
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'altura_cm') THEN
        ALTER TABLE `produtos` ADD COLUMN `altura_cm` DECIMAL(10,4) DEFAULT NULL;
    END IF;
    -- largura_cm
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'largura_cm') THEN
        ALTER TABLE `produtos` ADD COLUMN `largura_cm` DECIMAL(10,4) DEFAULT NULL;
    END IF;
    -- profundidade_cm
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'profundidade_cm') THEN
        ALTER TABLE `produtos` ADD COLUMN `profundidade_cm` DECIMAL(10,4) DEFAULT NULL;
    END IF;
    -- voltagem
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'voltagem') THEN
        ALTER TABLE `produtos` ADD COLUMN `voltagem` ENUM('110V','220V','bivolt','DC','N/A') DEFAULT NULL;
    END IF;
    -- potencia_w
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'potencia_w') THEN
        ALTER TABLE `produtos` ADD COLUMN `potencia_w` DECIMAL(10,2) DEFAULT NULL;
    END IF;
    -- garantia_meses
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'garantia_meses') THEN
        ALTER TABLE `produtos` ADD COLUMN `garantia_meses` INT NOT NULL DEFAULT 0;
    END IF;
    -- garantia_estendida_meses
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'garantia_estendida_meses') THEN
        ALTER TABLE `produtos` ADD COLUMN `garantia_estendida_meses` INT NOT NULL DEFAULT 0;
    END IF;
    -- assistencia_tecnica
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'assistencia_tecnica') THEN
        ALTER TABLE `produtos` ADD COLUMN `assistencia_tecnica` VARCHAR(255) DEFAULT NULL;
    END IF;
    -- manual_url
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'manual_url') THEN
        ALTER TABLE `produtos` ADD COLUMN `manual_url` VARCHAR(500) DEFAULT NULL;
    END IF;
    -- ficha_tecnica_url
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'ficha_tecnica_url') THEN
        ALTER TABLE `produtos` ADD COLUMN `ficha_tecnica_url` VARCHAR(500) DEFAULT NULL;
    END IF;
    -- palavras_chave
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'palavras_chave') THEN
        ALTER TABLE `produtos` ADD COLUMN `palavras_chave` VARCHAR(500) DEFAULT NULL;
    END IF;
    -- publico_alvo
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'publico_alvo') THEN
        ALTER TABLE `produtos` ADD COLUMN `publico_alvo` VARCHAR(255) DEFAULT NULL;
    END IF;
    -- indicacoes_uso
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'indicacoes_uso') THEN
        ALTER TABLE `produtos` ADD COLUMN `indicacoes_uso` TEXT DEFAULT NULL;
    END IF;
    -- contraindicacoes
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'contraindicacoes') THEN
        ALTER TABLE `produtos` ADD COLUMN `contraindicacoes` TEXT DEFAULT NULL;
    END IF;
    -- diferenciais
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'diferenciais') THEN
        ALTER TABLE `produtos` ADD COLUMN `diferenciais` TEXT DEFAULT NULL;
    END IF;
    -- concorrentes
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'concorrentes') THEN
        ALTER TABLE `produtos` ADD COLUMN `concorrentes` VARCHAR(500) DEFAULT NULL;
    END IF;
    -- ciclo_venda_dias
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'ciclo_venda_dias') THEN
        ALTER TABLE `produtos` ADD COLUMN `ciclo_venda_dias` INT DEFAULT NULL;
    END IF;
    -- video_url
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'video_url') THEN
        ALTER TABLE `produtos` ADD COLUMN `video_url` VARCHAR(500) DEFAULT NULL;
    END IF;
    -- catalogo_pdf_url
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'catalogo_pdf_url') THEN
        ALTER TABLE `produtos` ADD COLUMN `catalogo_pdf_url` VARCHAR(500) DEFAULT NULL;
    END IF;
    -- visivel_proposta
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'visivel_proposta') THEN
        ALTER TABLE `produtos` ADD COLUMN `visivel_proposta` TINYINT(1) NOT NULL DEFAULT 1;
    END IF;
    -- visivel_catalogo
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'visivel_catalogo') THEN
        ALTER TABLE `produtos` ADD COLUMN `visivel_catalogo` TINYINT(1) NOT NULL DEFAULT 0;
    END IF;
    -- requer_instalacao
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'requer_instalacao') THEN
        ALTER TABLE `produtos` ADD COLUMN `requer_instalacao` TINYINT(1) NOT NULL DEFAULT 0;
    END IF;
    -- requer_treinamento
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'requer_treinamento') THEN
        ALTER TABLE `produtos` ADD COLUMN `requer_treinamento` TINYINT(1) NOT NULL DEFAULT 0;
    END IF;
    -- requer_anvisa
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'requer_anvisa') THEN
        ALTER TABLE `produtos` ADD COLUMN `requer_anvisa` TINYINT(1) NOT NULL DEFAULT 0;
    END IF;
    -- observacoes_internas
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'observacoes_internas') THEN
        ALTER TABLE `produtos` ADD COLUMN `observacoes_internas` TEXT DEFAULT NULL;
    END IF;
    -- imagem_principal
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produtos' AND COLUMN_NAME = 'imagem_principal') THEN
        ALTER TABLE `produtos` ADD COLUMN `imagem_principal` VARCHAR(500) DEFAULT NULL;
    END IF;
    -- produto_historico_precos
    IF NOT EXISTS (SELECT 1 FROM information_schema.TABLES
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'produto_historico_precos') THEN
        CREATE TABLE `produto_historico_precos` (
            `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `produto_id`           INT UNSIGNED NOT NULL,
            `usuario_id`           INT UNSIGNED NOT NULL,
            `preco_custo`          DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
            `preco_venda`          DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
            `markup_percentual`    DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
            `motivo`               VARCHAR(255) DEFAULT NULL,
            `usuario_responsavel`  VARCHAR(150) DEFAULT NULL,
            `criado_em`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_php_produto` (`produto_id`),
            INDEX `idx_php_usuario` (`usuario_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    END IF;
END $$

CALL sp_expand_produtos() $$
DROP PROCEDURE IF EXISTS sp_expand_produtos $$

DELIMITER ;
