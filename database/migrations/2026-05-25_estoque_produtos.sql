-- ============================================================
-- MÓDULO ESTOQUE / PRODUTOS — ERP InLaudo
-- Migration: 2026-05-25
-- ============================================================

-- ─── 1. TABELA PRINCIPAL: produtos ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `produtos` (
  `id`                      INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`              INT NOT NULL                    COMMENT 'Dono/tenant do registro',
  `codigo`                  VARCHAR(30)  NOT NULL           COMMENT 'Código incremental ex: PRD-00001',
  `tipo`                    ENUM('produto','servico')       NOT NULL DEFAULT 'produto',
  `categoria`               ENUM(
                              'equipamento_medico',
                              'equipamento_hospitalar',
                              'consumivel',
                              'reagente',
                              'software',
                              'servico_manutencao',
                              'servico_instalacao',
                              'servico_treinamento',
                              'servico_consultoria',
                              'acessorio',
                              'peca_reposicao',
                              'outro'
                            ) NOT NULL DEFAULT 'equipamento_medico',
  `nome`                    VARCHAR(255) NOT NULL,
  `nome_tecnico`            VARCHAR(255) DEFAULT NULL       COMMENT 'Nome técnico/científico do produto',
  `descricao_curta`         VARCHAR(500) DEFAULT NULL       COMMENT 'Resumo para listagens e propostas',
  `descricao_completa`      TEXT         DEFAULT NULL       COMMENT 'Descrição detalhada para catálogo',
  `modelo`                  VARCHAR(150) DEFAULT NULL,
  `marca`                   VARCHAR(150) DEFAULT NULL,
  `fabricante_id`           INT          DEFAULT NULL       COMMENT 'FK fornecedores.id',
  `fabricante_nome`         VARCHAR(255) DEFAULT NULL       COMMENT 'Cache do nome do fabricante',
  `pais_origem`             VARCHAR(100) DEFAULT NULL,
  `ncm`                     VARCHAR(10)  DEFAULT NULL       COMMENT 'Nomenclatura Comum do Mercosul',
  `anvisa_registro`         VARCHAR(50)  DEFAULT NULL       COMMENT 'Número de registro ANVISA',
  `anvisa_classe`           ENUM('I','II','III','IV') DEFAULT NULL COMMENT 'Classe de risco ANVISA',
  `anvisa_validade`         DATE         DEFAULT NULL       COMMENT 'Validade do registro ANVISA',
  `unidade_medida`          VARCHAR(20)  NOT NULL DEFAULT 'UN' COMMENT 'UN, KG, L, M, CX, KIT…',
  `unidade_compra`          VARCHAR(20)  DEFAULT NULL       COMMENT 'Unidade de compra (ex: CX c/ 10)',
  `fator_conversao`         DECIMAL(10,4) DEFAULT 1.0000   COMMENT 'Qtd de UN por unidade de compra',

  -- ─── Preços ───────────────────────────────────────────────────────────────
  `preco_custo`             DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `preco_custo_medio`       DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Custo médio ponderado',
  `despesas_acessorias`     DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Frete, seguro, impostos de entrada',
  `custo_total`             DECIMAL(15,4) GENERATED ALWAYS AS (`preco_custo` + `despesas_acessorias`) STORED,
  `markup_percentual`       DECIMAL(10,4) NOT NULL DEFAULT 0.0000 COMMENT 'Markup sobre custo total (%)',
  `preco_venda`             DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Preço de venda praticado',
  `preco_minimo_venda`      DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Piso de venda (não vender abaixo)',
  `preco_sugerido`          DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Calculado: custo_total * (1 + markup/100)',
  `margem_lucro_bruta`      DECIMAL(10,4) GENERATED ALWAYS AS (
                              CASE WHEN `preco_venda` > 0
                                   THEN ((`preco_venda` - `preco_custo`) / `preco_venda`) * 100
                                   ELSE 0 END
                            ) STORED                       COMMENT 'Margem bruta % sobre preço de venda',
  `margem_lucro_liquida`    DECIMAL(10,4) NOT NULL DEFAULT 0.0000 COMMENT 'Margem após impostos/comissões (manual)',
  `impostos_percentual`     DECIMAL(10,4) NOT NULL DEFAULT 0.0000 COMMENT 'Alíquota total de impostos sobre venda (%)',
  `moeda`                   VARCHAR(3)    NOT NULL DEFAULT 'BRL',

  -- ─── Estoque ──────────────────────────────────────────────────────────────
  `controla_estoque`        TINYINT(1)   NOT NULL DEFAULT 1,
  `estoque_atual`           DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `estoque_minimo`          DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Gatilho de alerta de reposição',
  `estoque_maximo`          DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `ponto_reposicao`         DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Qtd para disparar pedido de compra',
  `lead_time_dias`          INT           NOT NULL DEFAULT 0 COMMENT 'Prazo médio de entrega do fornecedor (dias)',
  `localizacao_estoque`     VARCHAR(100) DEFAULT NULL       COMMENT 'Prateleira, corredor, depósito',

  -- ─── Validade ─────────────────────────────────────────────────────────────
  `controla_validade`       TINYINT(1)   NOT NULL DEFAULT 0,
  `alerta_validade_dias`    INT           NOT NULL DEFAULT 90 COMMENT 'Dias antes do vencimento para alertar',
  `lote_obrigatorio`        TINYINT(1)   NOT NULL DEFAULT 0,

  -- ─── Depreciação (obrigatório para equipamentos) ──────────────────────────
  `controla_depreciacao`    TINYINT(1)   NOT NULL DEFAULT 0,
  `vida_util_meses`         INT           DEFAULT NULL       COMMENT 'Vida útil em meses (ex: 60 = 5 anos)',
  `valor_residual`          DECIMAL(15,4) DEFAULT 0.0000    COMMENT 'Valor ao final da vida útil',
  `metodo_depreciacao`      ENUM('linear','soma_digitos','unidades_produzidas') DEFAULT 'linear',
  `depreciacao_mensal`      DECIMAL(15,4) DEFAULT 0.0000    COMMENT 'Valor mensal calculado',
  `alerta_substituicao_meses` INT         DEFAULT NULL      COMMENT 'Meses antes do fim da vida útil para sugerir troca',

  -- ─── Dimensões / Logística ────────────────────────────────────────────────
  `peso_kg`                 DECIMAL(10,4) DEFAULT NULL,
  `altura_cm`               DECIMAL(10,4) DEFAULT NULL,
  `largura_cm`              DECIMAL(10,4) DEFAULT NULL,
  `profundidade_cm`         DECIMAL(10,4) DEFAULT NULL,
  `voltagem`                ENUM('110V','220V','bivolt','DC','N/A') DEFAULT NULL,
  `potencia_w`              DECIMAL(10,2) DEFAULT NULL,

  -- ─── Garantia e Suporte ───────────────────────────────────────────────────
  `garantia_meses`          INT           NOT NULL DEFAULT 0,
  `garantia_estendida_meses` INT          NOT NULL DEFAULT 0,
  `assistencia_tecnica`     VARCHAR(255) DEFAULT NULL       COMMENT 'Nome/contato da assistência técnica',
  `manual_url`              VARCHAR(500) DEFAULT NULL,
  `ficha_tecnica_url`       VARCHAR(500) DEFAULT NULL,

  -- ─── Inteligência / IA ────────────────────────────────────────────────────
  `palavras_chave`          VARCHAR(500) DEFAULT NULL       COMMENT 'Tags para busca e IA',
  `publico_alvo`            VARCHAR(255) DEFAULT NULL       COMMENT 'Ex: UTI, Laboratório, Clínica Geral',
  `indicacoes_uso`          TEXT         DEFAULT NULL       COMMENT 'Indicações clínicas',
  `contraindicacoes`        TEXT         DEFAULT NULL,
  `diferenciais`            TEXT         DEFAULT NULL       COMMENT 'Diferenciais competitivos',
  `concorrentes`            VARCHAR(500) DEFAULT NULL       COMMENT 'Produtos concorrentes (para IA de precificação)',
  `score_venda`             TINYINT UNSIGNED DEFAULT 0      COMMENT '0-100: score de facilidade de venda (IA)',
  `ciclo_venda_dias`        INT           DEFAULT NULL      COMMENT 'Ciclo médio de venda em dias',
  `taxa_conversao`          DECIMAL(5,2)  DEFAULT NULL      COMMENT '% de propostas que viram venda',
  `ultima_venda_em`         DATE          DEFAULT NULL,
  `total_vendido`           DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Quantidade total vendida (histórico)',
  `receita_total`           DECIMAL(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Receita total gerada',

  -- ─── Imagem e Mídia ───────────────────────────────────────────────────────
  `imagem_principal`        VARCHAR(500) DEFAULT NULL,
  `imagens_adicionais`      TEXT         DEFAULT NULL       COMMENT 'JSON array de paths',
  `video_url`               VARCHAR(500) DEFAULT NULL,
  `catalogo_pdf_url`        VARCHAR(500) DEFAULT NULL,

  -- ─── Status e Controle ────────────────────────────────────────────────────
  `status`                  ENUM('ativo','inativo','descontinuado','em_homologacao') NOT NULL DEFAULT 'ativo',
  `visivel_proposta`        TINYINT(1)   NOT NULL DEFAULT 1 COMMENT 'Aparece na seleção de itens de proposta',
  `visivel_catalogo`        TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Aparece no catálogo público',
  `requer_instalacao`       TINYINT(1)   NOT NULL DEFAULT 0,
  `requer_treinamento`      TINYINT(1)   NOT NULL DEFAULT 0,
  `requer_anvisa`           TINYINT(1)   NOT NULL DEFAULT 0,
  `observacoes_internas`    TEXT         DEFAULT NULL,
  `created_at`              DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`              DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY `uq_produto_codigo_usuario` (`codigo`, `usuario_id`),
  INDEX `idx_produto_usuario`   (`usuario_id`),
  INDEX `idx_produto_tipo`      (`tipo`),
  INDEX `idx_produto_categoria` (`categoria`),
  INDEX `idx_produto_status`    (`status`),
  INDEX `idx_produto_fabricante`(`fabricante_id`),
  INDEX `idx_produto_anvisa`    (`anvisa_registro`),
  CONSTRAINT `fk_produto_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de produtos e serviços do ERP InLaudo';

-- ─── 2. COMPONENTES DO PRODUTO ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `produto_componentes` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `produto_id`          INT NOT NULL                        COMMENT 'Produto pai',
  `componente_id`       INT NOT NULL                        COMMENT 'FK produtos.id (componente)',
  `usuario_id`          INT NOT NULL,
  `quantidade`          DECIMAL(15,4) NOT NULL DEFAULT 1.0000,
  `obrigatorio`         TINYINT(1)   NOT NULL DEFAULT 1     COMMENT 'Componente obrigatório na venda',
  `vendido_separado`    TINYINT(1)   NOT NULL DEFAULT 1     COMMENT 'Pode ser vendido separadamente',
  `preco_venda_proprio` DECIMAL(15,4) DEFAULT NULL          COMMENT 'Preço quando vendido como componente (NULL = usa preco_venda do produto)',
  `desconto_composicao` DECIMAL(10,4) NOT NULL DEFAULT 0.0000 COMMENT 'Desconto % quando vendido como parte do kit',
  `ordem`               TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `observacoes`         VARCHAR(500) DEFAULT NULL,
  `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_pc_produto`     (`produto_id`),
  INDEX `idx_pc_componente`  (`componente_id`),
  INDEX `idx_pc_usuario`     (`usuario_id`),
  CONSTRAINT `fk_pc_produto`
    FOREIGN KEY (`produto_id`)    REFERENCES `produtos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_componente`
    FOREIGN KEY (`componente_id`) REFERENCES `produtos`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pc_usuario`
    FOREIGN KEY (`usuario_id`)    REFERENCES `users`(`id`)    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Componentes/peças que compõem um produto (BOM simplificado)';

-- ─── 3. REGRAS DE COMISSÃO POR PRODUTO ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `produto_comissoes` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `produto_id`          INT NOT NULL,
  `usuario_id`          INT NOT NULL,
  `colaborador_id`      INT DEFAULT NULL                    COMMENT 'NULL = regra global para todos os colaboradores',
  `descricao`           VARCHAR(255) NOT NULL,
  `tipo`                ENUM('percentual_venda','valor_fixo','percentual_margem','percentual_lucro') NOT NULL DEFAULT 'percentual_venda',
  `valor`               DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `meta_minima`         DECIMAL(15,4) DEFAULT NULL          COMMENT 'Valor mínimo de venda para acionar a comissão',
  `meta_maxima`         DECIMAL(15,4) DEFAULT NULL          COMMENT 'Valor máximo (acima disso, usa próxima faixa)',
  `escalonado`          TINYINT(1)   NOT NULL DEFAULT 0     COMMENT 'Se 1, aplica faixas progressivas',
  `vigencia_inicio`     DATE         DEFAULT NULL,
  `vigencia_fim`        DATE         DEFAULT NULL,
  `ativo`               TINYINT(1)   NOT NULL DEFAULT 1,
  `observacoes`         TEXT         DEFAULT NULL,
  `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_pcom_produto`     (`produto_id`),
  INDEX `idx_pcom_colaborador` (`colaborador_id`),
  INDEX `idx_pcom_usuario`     (`usuario_id`),
  CONSTRAINT `fk_pcom_produto`
    FOREIGN KEY (`produto_id`)     REFERENCES `produtos`(`id`)       ON DELETE CASCADE,
  CONSTRAINT `fk_pcom_usuario`
    FOREIGN KEY (`usuario_id`)     REFERENCES `users`(`id`)          ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Regras de comissionamento por produto/serviço';

-- ─── 4. MOVIMENTAÇÕES DE ESTOQUE ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `produto_movimentacoes` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `produto_id`          INT NOT NULL,
  `usuario_id`          INT NOT NULL,
  `tipo`                ENUM(
                          'entrada_compra',
                          'entrada_devolucao',
                          'entrada_ajuste',
                          'saida_venda',
                          'saida_uso_interno',
                          'saida_perda',
                          'saida_ajuste',
                          'transferencia'
                        ) NOT NULL,
  `quantidade`          DECIMAL(15,4) NOT NULL,
  `saldo_anterior`      DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `saldo_posterior`     DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `preco_unitario`      DECIMAL(15,4) DEFAULT NULL,
  `lote`                VARCHAR(100) DEFAULT NULL,
  `data_validade`       DATE         DEFAULT NULL,
  `numero_serie`        VARCHAR(100) DEFAULT NULL,
  `documento_ref`       VARCHAR(100) DEFAULT NULL           COMMENT 'NF, pedido, proposta etc.',
  `observacoes`         VARCHAR(500) DEFAULT NULL,
  `usuario_responsavel` INT          DEFAULT NULL           COMMENT 'Usuário que realizou a movimentação',
  `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pmov_produto`  (`produto_id`),
  INDEX `idx_pmov_usuario`  (`usuario_id`),
  INDEX `idx_pmov_tipo`     (`tipo`),
  INDEX `idx_pmov_created`  (`created_at`),
  CONSTRAINT `fk_pmov_produto`
    FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pmov_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `users`(`id`)    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Histórico de movimentações de estoque';

-- ─── 5. LOTES / RASTREABILIDADE ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `produto_lotes` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `produto_id`          INT NOT NULL,
  `usuario_id`          INT NOT NULL,
  `numero_lote`         VARCHAR(100) NOT NULL,
  `numero_serie`        VARCHAR(100) DEFAULT NULL,
  `data_fabricacao`     DATE         DEFAULT NULL,
  `data_validade`       DATE         DEFAULT NULL,
  `quantidade_entrada`  DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `quantidade_atual`    DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `preco_custo_lote`    DECIMAL(15,4) DEFAULT NULL,
  `fornecedor_id`       INT          DEFAULT NULL,
  `nota_fiscal`         VARCHAR(100) DEFAULT NULL,
  `status`              ENUM('disponivel','reservado','vencido','descartado') NOT NULL DEFAULT 'disponivel',
  `observacoes`         TEXT         DEFAULT NULL,
  `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_plote_produto`   (`produto_id`),
  INDEX `idx_plote_usuario`   (`usuario_id`),
  INDEX `idx_plote_validade`  (`data_validade`),
  INDEX `idx_plote_status`    (`status`),
  CONSTRAINT `fk_plote_produto`
    FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_plote_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `users`(`id`)    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Controle de lotes e rastreabilidade de produtos';

-- ─── 6. HISTÓRICO DE PREÇOS ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `produto_historico_precos` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `produto_id`          INT NOT NULL,
  `usuario_id`          INT NOT NULL,
  `preco_custo`         DECIMAL(15,4) NOT NULL,
  `preco_venda`         DECIMAL(15,4) NOT NULL,
  `markup_percentual`   DECIMAL(10,4) NOT NULL,
  `motivo`              VARCHAR(255) DEFAULT NULL,
  `usuario_responsavel` INT          DEFAULT NULL,
  `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_php_produto`  (`produto_id`),
  CONSTRAINT `fk_php_produto`
    FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Histórico de alterações de preço do produto';

-- ─── 7. CONTADOR DE CÓDIGO ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `produto_codigo_seq` (
  `usuario_id`  INT NOT NULL PRIMARY KEY,
  `ultimo_seq`  INT NOT NULL DEFAULT 0,
  CONSTRAINT `fk_pcs_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sequência de código por tenant para geração incremental de código de produto';
