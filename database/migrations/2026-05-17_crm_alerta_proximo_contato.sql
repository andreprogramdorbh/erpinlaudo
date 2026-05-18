-- =============================================================================
-- ERP InLaudo — Alertas de Próximo Contato CRM
-- Migration: 2026-05-17_crm_alerta_proximo_contato.sql
-- =============================================================================
-- Alerta 1: Próximo contato em ATRASO — disparo DIÁRIO para dono + admin
-- Alerta 2: Próximo contato em 2 dias  — disparo ÚNICO para vendedor
-- =============================================================================

-- ── Alerta 1: Próximo Contato em Atraso (diário — dono + admin) ──────────────
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao, antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc, assunto_template, corpo_template, ativo)
VALUES (
    0,
    'crm_lead_proximo_contato_atraso',
    'crm',
    'Lead — Próximo Contato em Atraso',
    'Alerta diário disparado para o dono do lead e para o(s) admin(s) quando a data do próximo contato já passou e o lead ainda está ativo. Enviado todos os dias enquanto o atraso persistir.',
    -1,
    'diario',
    '08:00:00',
    '["vendedor","admin"]',
    NULL,
    'ATENÇÃO CRM: Próximo contato em atraso — {lead} ({dias} dia(s))',
    '<p>Prezado(a) <strong>{vendedor}</strong>,</p>
<p>O lead abaixo possui um <strong>próximo contato em atraso</strong> há <strong style="color:#dc2626">{dias} dia(s)</strong>:</p>
<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:14px;">
  <tr style="background:#f3f4f6;">
    <td style="width:40%;font-weight:bold;">Lead / Empresa</td>
    <td>{lead}</td>
  </tr>
  <tr>
    <td style="font-weight:bold;">Status</td>
    <td>{status_lead}</td>
  </tr>
  <tr style="background:#f3f4f6;">
    <td style="font-weight:bold;">Data Prevista do Contato</td>
    <td style="color:#dc2626;font-weight:bold;">{proximo_contato}</td>
  </tr>
  <tr>
    <td style="font-weight:bold;">Dias em Atraso</td>
    <td><span style="color:#dc2626;font-weight:bold;">{dias} dia(s)</span></td>
  </tr>
  <tr style="background:#f3f4f6;">
    <td style="font-weight:bold;">Telefone</td>
    <td>{telefone}</td>
  </tr>
  <tr>
    <td style="font-weight:bold;">Responsável Comercial</td>
    <td>{vendedor}</td>
  </tr>
</table>
<br>
<p style="background:#fef2f2;border-left:4px solid #dc2626;padding:12px 16px;border-radius:4px;">
  <strong>Ação necessária:</strong> Acesse o CRM, realize o contato e atualize a data do próximo follow-up.
</p>
<p style="color:#6b7280;font-size:13px;">Este alerta será enviado diariamente enquanto a data de próximo contato estiver em atraso.</p>',
    1
),

-- ── Alerta 2: Próximo Contato em 2 dias (único — vendedor) ───────────────────
(
    0,
    'crm_lead_proximo_contato_2d',
    'crm',
    'Lead — Próximo Contato em 2 Dias',
    'Lembrete enviado ao vendedor responsável quando faltam exatamente 2 dias para a data do próximo contato agendado. Enviado uma única vez.',
    2,
    'unico',
    '07:30:00',
    '["vendedor"]',
    NULL,
    'Lembrete CRM: Próximo contato em 2 dias — {lead}',
    '<p>Bom dia, <strong>{vendedor}</strong>!</p>
<p>Você tem um contato agendado para <strong style="color:#1d4ed8">daqui a 2 dias</strong> com o seguinte lead:</p>
<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:14px;">
  <tr style="background:#eff6ff;">
    <td style="width:40%;font-weight:bold;">Lead / Empresa</td>
    <td>{lead}</td>
  </tr>
  <tr>
    <td style="font-weight:bold;">Status</td>
    <td>{status_lead}</td>
  </tr>
  <tr style="background:#eff6ff;">
    <td style="font-weight:bold;">Data do Próximo Contato</td>
    <td style="color:#1d4ed8;font-weight:bold;">{proximo_contato}</td>
  </tr>
  <tr>
    <td style="font-weight:bold;">Telefone</td>
    <td>{telefone}</td>
  </tr>
  <tr style="background:#eff6ff;">
    <td style="font-weight:bold;">Responsável Comercial</td>
    <td>{vendedor}</td>
  </tr>
</table>
<br>
<p style="background:#eff6ff;border-left:4px solid #1d4ed8;padding:12px 16px;border-radius:4px;">
  <strong>Prepare-se:</strong> Revise as interações anteriores e defina sua abordagem antes do contato.
</p>',
    1
);
