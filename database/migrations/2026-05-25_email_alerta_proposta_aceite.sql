-- ============================================================
-- ERP InLaudo — Alerta de E-mail: Proposta Aceita
-- Migration: 2026-05-25_email_alerta_proposta_aceite.sql
-- Execute via phpMyAdmin no servidor HostGator
-- ============================================================

-- Alerta: Proposta aceita pelo cliente (disparo imediato)
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao,
     antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc,
     assunto_template, corpo_template, ativo)
VALUES (
    0,
    'crm_proposta_aceita',
    'crm',
    'Proposta Aceita pelo Cliente',
    'Dispara um e-mail para o responsável e o admin quando o cliente aceita e assina a proposta via link público.',
    0,
    'unico',
    '00:00:00',
    '["vendedor","admin"]',
    NULL,
    'Proposta {{numero}} aceita por {{cliente_nome}}',
    '<p>Prezado(a) <strong>{{responsavel}}</strong>,</p>
<p>A proposta abaixo foi <strong style="color:#16a34a">aceita e assinada</strong> pelo cliente:</p>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;font-family:Arial,sans-serif">
  <tr style="background:#f0fdf4"><td><strong>Proposta</strong></td><td>{{numero}} — {{titulo}}</td></tr>
  <tr><td><strong>Cliente</strong></td><td>{{cliente_nome}}</td></tr>
  <tr style="background:#f0fdf4"><td><strong>Valor Total</strong></td><td>R$ {{total}}</td></tr>
  <tr><td><strong>Aceito por</strong></td><td>{{aceito_por_nome}}</td></tr>
  <tr style="background:#f0fdf4"><td><strong>Data/Hora</strong></td><td>{{data_aceite}}</td></tr>
  <tr><td><strong>IP do Assinante</strong></td><td>{{ip_assinante}}</td></tr>
</table>
<p style="margin-top:16px">
  <a href="{{link_proposta}}"
     style="display:inline-block;background:#1a56db;color:#fff;font-weight:700;
            padding:12px 24px;border-radius:6px;text-decoration:none">
    Ver Proposta no ERP
  </a>
</p>
<p style="color:#6b7280;font-size:12px">Acesse o ERP para gerar o Pedido de Venda com base nesta proposta.</p>',
    1
);

-- Alerta: Proposta recusada pelo cliente
INSERT IGNORE INTO email_alertas
    (usuario_id, codigo, modulo, nome, descricao,
     antecedencia_dias, frequencia, hora_disparo,
     destinatarios, cc,
     assunto_template, corpo_template, ativo)
VALUES (
    0,
    'crm_proposta_recusada',
    'crm',
    'Proposta Recusada pelo Cliente',
    'Dispara um e-mail para o responsável quando o cliente recusa a proposta via link público.',
    0,
    'unico',
    '00:00:00',
    '["vendedor","admin"]',
    NULL,
    'Proposta {{numero}} recusada por {{cliente_nome}}',
    '<p>Prezado(a) <strong>{{responsavel}}</strong>,</p>
<p>A proposta abaixo foi <strong style="color:#dc2626">recusada</strong> pelo cliente:</p>
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;font-family:Arial,sans-serif">
  <tr style="background:#fef2f2"><td><strong>Proposta</strong></td><td>{{numero}} — {{titulo}}</td></tr>
  <tr><td><strong>Cliente</strong></td><td>{{cliente_nome}}</td></tr>
  <tr style="background:#fef2f2"><td><strong>Motivo</strong></td><td>{{motivo_recusa}}</td></tr>
  <tr><td><strong>Data</strong></td><td>{{data_recusa}}</td></tr>
</table>
<p style="margin-top:16px">
  <a href="{{link_proposta}}"
     style="display:inline-block;background:#1a56db;color:#fff;font-weight:700;
            padding:12px 24px;border-radius:6px;text-decoration:none">
    Ver Proposta no ERP
  </a>
</p>',
    1
);
