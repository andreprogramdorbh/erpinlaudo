-- =============================================================================
-- ERP InLaudo — Alertas de E-mail: Corpo Clínico
-- Migration: 2026-04-27_email_alertas_corpo_clinico.sql
-- =============================================================================
-- Adiciona o módulo 'corpo_clinico' ao ENUM da tabela email_alertas
-- (executa apenas se a coluna ainda não inclui o valor)
ALTER TABLE email_alertas
    MODIFY COLUMN modulo ENUM('financeiro','faturamento','crm','corpo_clinico') NOT NULL;

-- =============================================================================
-- SEEDS: Alertas padrão — Módulo Corpo Clínico
-- usuario_id = 0 → padrão do sistema (clonado por usuário ao ativar)
-- =============================================================================

-- 13. Provisionamento de Pagamento ao Médico (Conta a Pagar criada)
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'corpo_clinico_provisionamento_pagamento',
    'corpo_clinico',
    'Corpo Clínico — Provisionamento de Pagamento',
    'Notificação enviada ao médico quando uma conta a pagar é criada em seu nome, informando o valor provisionado e a data prevista de pagamento.',
    0, 'unico', '00:00:00',
    '["medico"]',
    '["admin"]',
    'ERP InLaudo — Provisionamento de Pagamento: R$ {valor} | {numero_apuracao}',
    '<p>Prezado(a) <strong>Dr(a). {medico_nome}</strong>,</p>
<p>Informamos que foi registrado um <strong>provisionamento de pagamento</strong> em seu nome no sistema <strong>ERP InLaudo</strong>.</p>
<table border="0" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;background:#f8fafc;border-radius:8px;margin:16px 0;">
  <tr style="background:#1a56db;color:#fff;">
    <td colspan="2" style="padding:10px 16px;font-weight:700;border-radius:6px 6px 0 0;">Detalhes do Provisionamento</td>
  </tr>
  <tr style="border-bottom:1px solid #e5e7eb;">
    <td style="padding:8px 16px;color:#6b7280;width:40%;">Apuração</td>
    <td style="padding:8px 16px;font-weight:600;">{numero_apuracao}</td>
  </tr>
  <tr style="border-bottom:1px solid #e5e7eb;background:#fff;">
    <td style="padding:8px 16px;color:#6b7280;">Período</td>
    <td style="padding:8px 16px;">{periodo}</td>
  </tr>
  <tr style="border-bottom:1px solid #e5e7eb;">
    <td style="padding:8px 16px;color:#6b7280;">Total de Exames</td>
    <td style="padding:8px 16px;">{total_exames} exame(s) — {total_normal} normal(is) / {total_urgencia} urgência(s)</td>
  </tr>
  <tr style="border-bottom:1px solid #e5e7eb;background:#fff;">
    <td style="padding:8px 16px;color:#6b7280;">Valor Provisionado</td>
    <td style="padding:8px 16px;font-size:18px;font-weight:700;color:#059669;">R$&nbsp;{valor}</td>
  </tr>
  <tr style="border-bottom:1px solid #e5e7eb;">
    <td style="padding:8px 16px;color:#6b7280;">Vencimento Previsto</td>
    <td style="padding:8px 16px;">{vencimento}</td>
  </tr>
  <tr style="background:#fff;">
    <td style="padding:8px 16px;color:#6b7280;">Descrição</td>
    <td style="padding:8px 16px;">{descricao}</td>
  </tr>
</table>
<p>Para visualizar os detalhes completos da sua apuração, acesse o link abaixo:</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{link_apuracao}"
     style="background:#1a56db;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Ver Apuração Completa
  </a>
</p>
<p style="color:#6b7280;font-size:13px;">Em caso de dúvidas sobre os valores, entre em contato com o setor financeiro.</p>',
    1
);

-- 14. Apuração de Prestador Concluída — Notificação ao Médico
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'corpo_clinico_apuracao_concluida',
    'corpo_clinico',
    'Corpo Clínico — Apuração de Prestador Concluída',
    'Notificação enviada ao médico quando a apuração de prestador é concluída (faturada), com link de acesso à visualização completa e PDF em anexo.',
    0, 'unico', '00:00:00',
    '["medico"]',
    '["admin"]',
    'ERP InLaudo — Sua Apuração {numero_apuracao} foi Concluída | R$ {valor}',
    '<p>Prezado(a) <strong>Dr(a). {medico_nome}</strong>,</p>
<p>Sua apuração de prestador referente ao período <strong>{periodo}</strong> foi <strong style="color:#059669;">concluída e faturada</strong> com sucesso.</p>
<table border="0" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;background:#f8fafc;border-radius:8px;margin:16px 0;">
  <tr style="background:#059669;color:#fff;">
    <td colspan="2" style="padding:10px 16px;font-weight:700;border-radius:6px 6px 0 0;">Resumo da Apuração</td>
  </tr>
  <tr style="border-bottom:1px solid #e5e7eb;">
    <td style="padding:8px 16px;color:#6b7280;width:40%;">Número</td>
    <td style="padding:8px 16px;font-weight:600;font-family:monospace;">{numero_apuracao}</td>
  </tr>
  <tr style="border-bottom:1px solid #e5e7eb;background:#fff;">
    <td style="padding:8px 16px;color:#6b7280;">Período</td>
    <td style="padding:8px 16px;">{periodo}</td>
  </tr>
  <tr style="border-bottom:1px solid #e5e7eb;">
    <td style="padding:8px 16px;color:#6b7280;">Total de Exames</td>
    <td style="padding:8px 16px;">{total_exames}</td>
  </tr>
  <tr style="border-bottom:1px solid #e5e7eb;background:#fff;">
    <td style="padding:8px 16px;color:#6b7280;">Normal</td>
    <td style="padding:8px 16px;"><span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:4px;">{total_normal}</span></td>
  </tr>
  <tr style="border-bottom:1px solid #e5e7eb;">
    <td style="padding:8px 16px;color:#6b7280;">Urgência</td>
    <td style="padding:8px 16px;"><span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:4px;">{total_urgencia}</span></td>
  </tr>
  <tr style="background:#fff;">
    <td style="padding:8px 16px;color:#6b7280;">Valor Total</td>
    <td style="padding:8px 16px;font-size:20px;font-weight:700;color:#059669;">R$&nbsp;{valor}</td>
  </tr>
</table>
<p>Você pode acessar e visualizar todos os detalhes da sua apuração pelo link abaixo. O PDF completo também está disponível para download diretamente na página.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{link_apuracao}"
     style="background:#059669;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;margin-right:8px;">
    Ver Apuração Online
  </a>
  <a href="{link_pdf}"
     style="background:#1a56db;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Baixar PDF
  </a>
</p>
<p style="color:#6b7280;font-size:13px;">Este e-mail foi enviado automaticamente. Em caso de divergências, entre em contato com o setor financeiro.</p>',
    1
);
