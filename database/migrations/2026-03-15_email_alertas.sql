-- =============================================================================
-- ERP InLaudo — Sistema de Alertas por E-mail
-- Migration: 2026-03-15_email_alertas.sql
-- =============================================================================

-- Tabela principal de configuração de alertas
CREATE TABLE IF NOT EXISTS email_alertas (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id       INT UNSIGNED NOT NULL,

    -- Identificação do alerta
    codigo           VARCHAR(80)  NOT NULL COMMENT 'Slug único ex: financeiro_contas_vencer_3d',
    modulo           ENUM('financeiro','faturamento','crm') NOT NULL,
    nome             VARCHAR(120) NOT NULL COMMENT 'Nome legível do alerta',
    descricao        TEXT         NULL     COMMENT 'Descrição detalhada do disparo',

    -- Configuração de disparo
    antecedencia_dias TINYINT UNSIGNED NOT NULL DEFAULT 3
        COMMENT 'Dias antes do vencimento para disparar (0 = no dia, negativo = após)',
    frequencia       ENUM('unico','diario','semanal') NOT NULL DEFAULT 'diario'
        COMMENT 'Frequência de reenvio enquanto a condição persistir',
    hora_disparo     TIME NOT NULL DEFAULT '08:00:00'
        COMMENT 'Hora do dia para processar o alerta (via cron)',

    -- Destinatários
    destinatarios    TEXT NOT NULL
        COMMENT 'JSON array de e-mails: ["email1","email2"] ou tokens: ["responsavel","vendedor","admin"]',
    cc               TEXT NULL
        COMMENT 'JSON array de e-mails em cópia',

    -- Conteúdo do e-mail
    assunto_template VARCHAR(255) NOT NULL
        COMMENT 'Template do assunto com variáveis: {cliente}, {valor}, {dias}, {vencimento}',
    corpo_template   LONGTEXT NOT NULL
        COMMENT 'Template HTML do corpo com variáveis dinâmicas',

    -- Status
    ativo            TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_disparo   DATETIME NULL,
    total_disparos   INT UNSIGNED NOT NULL DEFAULT 0,

    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_alerta_usuario_codigo (usuario_id, codigo),
    INDEX idx_alerta_modulo (modulo),
    INDEX idx_alerta_ativo (ativo),
    INDEX idx_alerta_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de log de disparos
CREATE TABLE IF NOT EXISTS email_alertas_log (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alerta_id    INT UNSIGNED NOT NULL,
    usuario_id   INT UNSIGNED NOT NULL,
    destinatario VARCHAR(255) NOT NULL,
    assunto      VARCHAR(255) NOT NULL,
    status       ENUM('enviado','falha','ignorado') NOT NULL DEFAULT 'enviado',
    erro         TEXT NULL,
    referencia   VARCHAR(120) NULL COMMENT 'ID do registro que gerou o alerta (ex: conta_pagar:42)',
    disparado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_log_alerta (alerta_id),
    INDEX idx_log_usuario (usuario_id),
    INDEX idx_log_data (disparado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- SEEDS: Padrões de alerta por módulo
-- Inseridos com usuario_id = 0 (padrão do sistema — serão clonados por usuário)
-- =============================================================================

-- -----------------------------------------------------------------------
-- MÓDULO: FINANCEIRO
-- -----------------------------------------------------------------------

-- 1. Contas a Receber vencendo em 3 dias
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'financeiro_receber_vencer_3d',
    'financeiro',
    'Contas a Receber — Vencendo em 3 dias',
    'Alerta enviado 3 dias antes do vencimento de contas a receber em aberto. Ideal para acionar o setor financeiro e o cliente com antecedência.',
    3, 'unico', '08:00:00',
    '["admin","financeiro"]',
    NULL,
    'Alerta: Conta a Receber vencendo em {dias} dia(s) — {cliente}',
    '<p>Prezado(a),</p>
<p>Este é um alerta automático do <strong>ERP InLaudo</strong>.</p>
<p>A conta a receber abaixo vence em <strong>{dias} dia(s)</strong>:</p>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">
  <tr><td><strong>Cliente</strong></td><td>{cliente}</td></tr>
  <tr><td><strong>Descrição</strong></td><td>{descricao}</td></tr>
  <tr><td><strong>Valor</strong></td><td>R$ {valor}</td></tr>
  <tr><td><strong>Vencimento</strong></td><td>{vencimento}</td></tr>
</table>
<p>Acesse o ERP para tomar as providências necessárias.</p>',
    1
);

-- 2. Contas a Receber vencidas (em atraso)
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'financeiro_receber_atraso',
    'financeiro',
    'Contas a Receber — Em Atraso',
    'Alerta diário para contas a receber vencidas e não pagas. Disparado a partir do dia seguinte ao vencimento.',
    -1, 'diario', '09:00:00',
    '["admin","financeiro"]',
    NULL,
    'URGENTE: Conta a Receber em atraso — {cliente} ({dias} dia(s))',
    '<p>Prezado(a),</p>
<p>A conta a receber abaixo está <strong>em atraso há {dias} dia(s)</strong>:</p>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">
  <tr><td><strong>Cliente</strong></td><td>{cliente}</td></tr>
  <tr><td><strong>Descrição</strong></td><td>{descricao}</td></tr>
  <tr><td><strong>Valor</strong></td><td>R$ {valor}</td></tr>
  <tr><td><strong>Vencimento</strong></td><td>{vencimento}</td></tr>
  <tr><td><strong>Dias em atraso</strong></td><td><span style="color:red"><strong>{dias}</strong></span></td></tr>
</table>
<p>Providencie a cobrança imediatamente.</p>',
    1
);

-- 3. Contas a Pagar vencendo em 3 dias
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'financeiro_pagar_vencer_3d',
    'financeiro',
    'Contas a Pagar — Vencendo em 3 dias',
    'Alerta enviado 3 dias antes do vencimento de contas a pagar em aberto. Evita multas e juros por atraso.',
    3, 'unico', '08:00:00',
    '["admin","financeiro"]',
    NULL,
    'Alerta: Conta a Pagar vencendo em {dias} dia(s) — {fornecedor}',
    '<p>Prezado(a),</p>
<p>A conta a pagar abaixo vence em <strong>{dias} dia(s)</strong>:</p>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">
  <tr><td><strong>Fornecedor</strong></td><td>{fornecedor}</td></tr>
  <tr><td><strong>Descrição</strong></td><td>{descricao}</td></tr>
  <tr><td><strong>Valor</strong></td><td>R$ {valor}</td></tr>
  <tr><td><strong>Vencimento</strong></td><td>{vencimento}</td></tr>
</table>
<p>Providencie o pagamento para evitar multas e juros.</p>',
    1
);

-- 4. Contas a Pagar em atraso
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'financeiro_pagar_atraso',
    'financeiro',
    'Contas a Pagar — Em Atraso',
    'Alerta diário para contas a pagar vencidas e não quitadas. Risco de multa, juros e protesto.',
    -1, 'diario', '09:00:00',
    '["admin","financeiro"]',
    NULL,
    'URGENTE: Conta a Pagar em atraso — {fornecedor} ({dias} dia(s))',
    '<p>Prezado(a),</p>
<p>A conta a pagar abaixo está <strong>em atraso há {dias} dia(s)</strong> e ainda não foi quitada:</p>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">
  <tr><td><strong>Fornecedor</strong></td><td>{fornecedor}</td></tr>
  <tr><td><strong>Descrição</strong></td><td>{descricao}</td></tr>
  <tr><td><strong>Valor</strong></td><td>R$ {valor}</td></tr>
  <tr><td><strong>Vencimento</strong></td><td>{vencimento}</td></tr>
  <tr><td><strong>Dias em atraso</strong></td><td><span style="color:red"><strong>{dias}</strong></span></td></tr>
</table>
<p>Regularize o pagamento imediatamente para evitar protesto.</p>',
    1
);

-- 5. Resumo diário financeiro
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'financeiro_resumo_diario',
    'financeiro',
    'Resumo Financeiro Diário',
    'Relatório diário consolidado com contas a vencer nos próximos 7 dias e contas em atraso.',
    0, 'diario', '07:00:00',
    '["admin"]',
    NULL,
    'Resumo Financeiro do Dia — {data}',
    '<p>Prezado(a),</p>
<p>Segue o resumo financeiro de <strong>{data}</strong>:</p>
<h4>Contas a Receber</h4>
<ul>
  <li>Vencendo hoje: <strong>R$ {receber_hoje}</strong></li>
  <li>Vencendo nos próximos 7 dias: <strong>R$ {receber_7d}</strong></li>
  <li>Em atraso: <strong style="color:red">R$ {receber_atraso}</strong></li>
</ul>
<h4>Contas a Pagar</h4>
<ul>
  <li>Vencendo hoje: <strong>R$ {pagar_hoje}</strong></li>
  <li>Vencendo nos próximos 7 dias: <strong>R$ {pagar_7d}</strong></li>
  <li>Em atraso: <strong style="color:red">R$ {pagar_atraso}</strong></li>
</ul>
<p>Acesse o ERP para detalhes completos.</p>',
    0
);

-- -----------------------------------------------------------------------
-- MÓDULO: FATURAMENTO
-- -----------------------------------------------------------------------

-- 6. Fatura emitida (confirmação ao cliente)
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'faturamento_fatura_emitida',
    'faturamento',
    'Fatura Emitida — Notificação ao Cliente',
    'Notificação automática enviada ao cliente no momento da emissão de uma nova fatura.',
    0, 'unico', '00:00:00',
    '["cliente"]',
    '["admin"]',
    'Nova Fatura Emitida — {numero_fatura} | ERP InLaudo',
    '<p>Prezado(a) <strong>{cliente}</strong>,</p>
<p>Informamos que uma nova fatura foi emitida em seu nome:</p>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">
  <tr><td><strong>Número</strong></td><td>{numero_fatura}</td></tr>
  <tr><td><strong>Descrição</strong></td><td>{descricao}</td></tr>
  <tr><td><strong>Valor</strong></td><td>R$ {valor}</td></tr>
  <tr><td><strong>Vencimento</strong></td><td>{vencimento}</td></tr>
</table>
<p>Em caso de dúvidas, entre em contato conosco.</p>',
    1
);

-- 7. Fatura vencendo em 2 dias
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'faturamento_fatura_vencer_2d',
    'faturamento',
    'Fatura — Lembrete de Vencimento (2 dias)',
    'Lembrete enviado ao cliente 2 dias antes do vencimento da fatura.',
    2, 'unico', '08:00:00',
    '["cliente"]',
    '["admin","financeiro"]',
    'Lembrete: Sua fatura vence em {dias} dia(s) — {numero_fatura}',
    '<p>Prezado(a) <strong>{cliente}</strong>,</p>
<p>Este é um lembrete de que sua fatura vence em <strong>{dias} dia(s)</strong>:</p>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">
  <tr><td><strong>Número</strong></td><td>{numero_fatura}</td></tr>
  <tr><td><strong>Valor</strong></td><td>R$ {valor}</td></tr>
  <tr><td><strong>Vencimento</strong></td><td>{vencimento}</td></tr>
</table>
<p>Efetue o pagamento para evitar juros e multas.</p>',
    1
);

-- 8. Fatura em atraso
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'faturamento_fatura_atraso',
    'faturamento',
    'Fatura — Em Atraso (cobrança ao cliente)',
    'Cobrança automática enviada ao cliente quando a fatura está vencida e não paga.',
    -1, 'diario', '09:00:00',
    '["cliente","admin"]',
    '["financeiro"]',
    'AVISO: Fatura em atraso — {numero_fatura} ({dias} dia(s) vencida)',
    '<p>Prezado(a) <strong>{cliente}</strong>,</p>
<p>Identificamos que a fatura abaixo está <strong>em atraso há {dias} dia(s)</strong>:</p>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">
  <tr><td><strong>Número</strong></td><td>{numero_fatura}</td></tr>
  <tr><td><strong>Valor Original</strong></td><td>R$ {valor}</td></tr>
  <tr><td><strong>Vencimento</strong></td><td>{vencimento}</td></tr>
  <tr><td><strong>Dias em atraso</strong></td><td><span style="color:red"><strong>{dias}</strong></span></td></tr>
</table>
<p>Regularize seu débito o quanto antes para evitar restrições.</p>',
    1
);

-- -----------------------------------------------------------------------
-- MÓDULO: CRM
-- -----------------------------------------------------------------------

-- 9. Lead sem contato há X dias
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'crm_lead_sem_contato',
    'crm',
    'Lead — Sem Contato há 7 dias',
    'Alerta ao vendedor quando um lead ativo não recebe nenhuma interação há 7 dias.',
    -7, 'diario', '08:30:00',
    '["vendedor","admin"]',
    NULL,
    'Alerta CRM: Lead sem contato há {dias} dia(s) — {lead}',
    '<p>Prezado(a) <strong>{vendedor}</strong>,</p>
<p>O lead abaixo está <strong>sem registro de contato há {dias} dia(s)</strong>:</p>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">
  <tr><td><strong>Lead</strong></td><td>{lead}</td></tr>
  <tr><td><strong>Status</strong></td><td>{status_lead}</td></tr>
  <tr><td><strong>Último contato</strong></td><td>{ultimo_contato}</td></tr>
  <tr><td><strong>Próximo contato previsto</strong></td><td>{proximo_contato}</td></tr>
</table>
<p>Acesse o CRM e registre uma interação para manter o pipeline atualizado.</p>',
    1
);

-- 10. Próximo contato com lead vencendo hoje
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'crm_lead_proximo_contato_hoje',
    'crm',
    'Lead — Próximo Contato Agendado para Hoje',
    'Lembrete matinal ao vendedor sobre leads com próximo contato agendado para o dia.',
    0, 'unico', '07:30:00',
    '["vendedor"]',
    NULL,
    'Lembrete CRM: Você tem contato agendado hoje com {lead}',
    '<p>Bom dia, <strong>{vendedor}</strong>!</p>
<p>Você tem um contato agendado para <strong>hoje</strong> com o seguinte lead:</p>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">
  <tr><td><strong>Lead</strong></td><td>{lead}</td></tr>
  <tr><td><strong>Empresa</strong></td><td>{empresa}</td></tr>
  <tr><td><strong>Telefone</strong></td><td>{telefone}</td></tr>
  <tr><td><strong>Status</strong></td><td>{status_lead}</td></tr>
</table>
<p>Boa sorte na abordagem!</p>',
    1
);

-- 11. Oportunidade vencendo em 3 dias
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'crm_oportunidade_vencer_3d',
    'crm',
    'Oportunidade — Fechamento Previsto em 3 dias',
    'Alerta ao vendedor quando a data de fechamento prevista de uma oportunidade está a 3 dias.',
    3, 'unico', '08:00:00',
    '["vendedor","admin"]',
    NULL,
    'Alerta CRM: Oportunidade vence em {dias} dia(s) — {oportunidade}',
    '<p>Prezado(a) <strong>{vendedor}</strong>,</p>
<p>A oportunidade abaixo tem fechamento previsto em <strong>{dias} dia(s)</strong>:</p>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">
  <tr><td><strong>Oportunidade</strong></td><td>{oportunidade}</td></tr>
  <tr><td><strong>Lead/Cliente</strong></td><td>{lead}</td></tr>
  <tr><td><strong>Valor Estimado</strong></td><td>R$ {valor}</td></tr>
  <tr><td><strong>Probabilidade</strong></td><td>{probabilidade}%</td></tr>
  <tr><td><strong>Fechamento Previsto</strong></td><td>{data_fechamento}</td></tr>
  <tr><td><strong>Etapa do Funil</strong></td><td>{etapa}</td></tr>
</table>
<p>Atualize o status da oportunidade no CRM.</p>',
    1
);

-- 12. Oportunidade vencida sem fechamento
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'crm_oportunidade_vencida',
    'crm',
    'Oportunidade — Data de Fechamento Ultrapassada',
    'Alerta diário quando uma oportunidade aberta ultrapassou a data de fechamento prevista sem ser ganha ou perdida.',
    -1, 'diario', '09:00:00',
    '["vendedor","admin"]',
    NULL,
    'ATENÇÃO CRM: Oportunidade sem fechamento — {oportunidade} ({dias} dia(s) atrasada)',
    '<p>Prezado(a) <strong>{vendedor}</strong>,</p>
<p>A oportunidade abaixo ultrapassou a data de fechamento prevista há <strong>{dias} dia(s)</strong> e ainda está aberta:</p>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">
  <tr><td><strong>Oportunidade</strong></td><td>{oportunidade}</td></tr>
  <tr><td><strong>Lead/Cliente</strong></td><td>{lead}</td></tr>
  <tr><td><strong>Valor Estimado</strong></td><td>R$ {valor}</td></tr>
  <tr><td><strong>Fechamento Previsto</strong></td><td>{data_fechamento}</td></tr>
  <tr><td><strong>Dias em atraso</strong></td><td><span style="color:red"><strong>{dias}</strong></span></td></tr>
</table>
<p>Atualize o status para "Ganha" ou "Perdida" no CRM.</p>',
    1
);
