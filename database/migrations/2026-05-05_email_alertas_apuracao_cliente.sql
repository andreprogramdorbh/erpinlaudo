-- =============================================================================
-- Migration: Alertas de e-mail para Apuração de Cliente
-- Data: 2026-05-05
-- Descrição: Adiciona os alertas de e-mail disparados ao faturar apuração
--            de cliente — notificação ao cliente com valores de venda e PDF.
-- =============================================================================

-- Atualiza o ENUM do campo modulo para incluir 'faturamento_cliente' se necessário
-- (o módulo 'faturamento' já existe, usamos ele para manter compatibilidade)

-- 1. Alerta: Apuração de Cliente Faturada — Notificação ao Cliente
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'apuracao_cliente_faturada',
    'faturamento',
    'Apuração de Cliente — Faturamento Gerado',
    'Notificação enviada ao cliente quando a apuração de serviços é faturada, informando o valor total, período de referência, total de exames e data de vencimento. O PDF da apuração é anexado ao e-mail.',
    0, 'unico', '00:00:00',
    '["cliente"]',
    '["admin"]',
    'ERP InLaudo — Apuração de Serviços Faturada {numero_apuracao} | R$ {valor}',
    '<p>Prezado(a) <strong>{cliente_nome}</strong>,</p>
<p>Informamos que a <strong>Apuração de Serviços</strong> referente ao período <strong>{periodo}</strong> foi <strong style="color:#1a56db;">concluída e faturada</strong>.</p>
<table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;background:#f8fafc;border-radius:8px;margin:16px 0;">
  <tr style="background:#1a56db;color:#fff;">
    <td colspan="2" style="padding:12px 16px;font-weight:700;font-size:15px;border-radius:6px 6px 0 0;">Resumo da Apuração {numero_apuracao}</td>
  </tr>
  <tr style="border-bottom:1px solid #e5e7eb;">
    <td style="padding:10px 16px;color:#6b7280;width:40%;">Período de Referência</td>
    <td style="padding:10px 16px;">{periodo}</td>
  </tr>
  <tr style="border-bottom:1px solid #e5e7eb;background:#fff;">
    <td style="padding:10px 16px;color:#6b7280;">Total de Exames</td>
    <td style="padding:10px 16px;">{total_exames} exame(s)</td>
  </tr>
  <tr style="border-bottom:1px solid #e5e7eb;">
    <td style="padding:10px 16px;color:#6b7280;">Valor Total</td>
    <td style="padding:10px 16px;font-size:20px;font-weight:700;color:#1a56db;">R$&nbsp;{valor}</td>
  </tr>
  <tr style="background:#f3f4f6;">
    <td style="padding:10px 16px;color:#6b7280;">Vencimento</td>
    <td style="padding:10px 16px;font-weight:600;">{vencimento}</td>
  </tr>
</table>
<p>O detalhamento completo dos exames está disponível no link abaixo. O PDF da apuração também está anexado a este e-mail.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{link_apuracao}"
     style="background:#1a56db;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
    Ver Apuração Completa
  </a>
</p>
<p style="color:#6b7280;font-size:13px;">Em caso de dúvidas sobre os valores, entre em contato com nossa equipe financeira.</p>',
    1
);
