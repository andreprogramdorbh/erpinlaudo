<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>O.S <?= htmlspecialchars($os->numero ?? '') ?></title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Segoe UI',Arial,sans-serif;font-size:11pt;color:#1e293b;background:#fff;padding:20px}
  .page{max-width:800px;margin:0 auto;background:#fff}
  /* Header */
  .doc-header{display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid #1a56db;padding-bottom:12px;margin-bottom:16px}
  .doc-header .logo-area h1{font-size:18pt;font-weight:800;color:#1a56db}
  .doc-header .logo-area p{font-size:9pt;color:#64748b}
  .doc-header .os-number{text-align:right}
  .doc-header .os-number .num{font-size:16pt;font-weight:700;color:#1a56db}
  .doc-header .os-number .tipo{font-size:9pt;padding:.2em .7em;border-radius:20px;font-weight:600}
  .tipo-preventiva{background:#dcfce7;color:#166534}
  .tipo-corretiva{background:#fee2e2;color:#991b1b}
  /* Seções */
  .section{margin-bottom:14px;border:1px solid #e2e8f0;border-radius:6px;overflow:hidden}
  .section-title{background:#f1f5f9;padding:6px 12px;font-size:9pt;font-weight:700;color:#374151;border-bottom:1px solid #e2e8f0;text-transform:uppercase;letter-spacing:.05em}
  .section-body{padding:10px 12px}
  /* Grid de dados */
  .data-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px 16px}
  .data-grid.cols3{grid-template-columns:1fr 1fr 1fr}
  .data-item label{display:block;font-size:8pt;color:#64748b;font-weight:600;margin-bottom:1px}
  .data-item span{font-size:10pt;color:#1e293b;font-weight:500}
  /* Tabela de itens */
  table.items{width:100%;border-collapse:collapse;font-size:9.5pt}
  table.items th{background:#f1f5f9;padding:5px 8px;text-align:left;font-size:8.5pt;font-weight:700;color:#374151;border-bottom:2px solid #e2e8f0}
  table.items td{padding:5px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top}
  table.items tr:last-child td{border-bottom:none}
  table.items .text-right{text-align:right}
  table.items .total-row td{font-weight:700;background:#f8fafc;border-top:2px solid #e2e8f0}
  /* Próximas trocas */
  .prox-table{width:100%;border-collapse:collapse;font-size:9pt}
  .prox-table th{background:#dbeafe;color:#1d4ed8;padding:4px 8px;font-size:8pt;font-weight:700}
  .prox-table td{padding:4px 8px;border-bottom:1px solid #e2e8f0}
  /* Evolução */
  .evolucao-box{background:#f0f9ff;border:1px solid #bae6fd;border-radius:4px;padding:8px 10px;font-size:9.5pt;line-height:1.5;white-space:pre-wrap}
  /* Assinaturas */
  .assinaturas{display:flex;gap:40px;margin-top:24px;padding-top:16px;border-top:1px solid #e2e8f0}
  .assinatura{flex:1;text-align:center}
  .assinatura .linha{border-top:1px solid #94a3b8;margin-bottom:4px;margin-top:32px}
  .assinatura p{font-size:8.5pt;color:#64748b}
  /* Rodapé */
  .doc-footer{margin-top:16px;padding-top:8px;border-top:1px solid #e2e8f0;text-align:center;font-size:8pt;color:#94a3b8}
  /* Status badge */
  .status-badge{display:inline-block;padding:.2em .8em;border-radius:20px;font-size:8.5pt;font-weight:600}
  .status-aberta{background:#dbeafe;color:#1d4ed8}
  .status-em_andamento{background:#cffafe;color:#0e7490}
  .status-concluida{background:#dcfce7;color:#166534}
  .status-faturada{background:#f1f5f9;color:#1e293b}
  .status-cancelada{background:#fee2e2;color:#991b1b}
  /* Alerta de próximas trocas */
  .alert-prox{background:#fffbeb;border:1px solid #fde68a;border-radius:4px;padding:6px 10px;font-size:8.5pt;color:#92400e;margin-bottom:8px}
  @media print{
    body{padding:0}
    .no-print{display:none!important}
    @page{margin:15mm 12mm}
  }
</style>
</head>
<body>
<?php
$os        = $os        ?? null;
$trocas    = $trocas    ?? [];
$historico = $historico ?? [];
$empresa   = $empresa   ?? null;

$tipoLabel = $os->tipo === 'preventiva' ? 'Preventiva' : 'Corretiva';
$tipoClass = 'tipo-' . ($os->tipo ?? 'corretiva');
$statusClass = 'status-' . ($os->status ?? 'aberta');
$statusLabel = match($os->status ?? '') {
    'aberta'          => 'Aberta',
    'em_andamento'    => 'Em Andamento',
    'aguardando_peca' => 'Aguardando Peça',
    'concluida'       => 'Concluída',
    'faturada'        => 'Faturada',
    'cancelada'       => 'Cancelada',
    default           => ucfirst($os->status ?? ''),
};

// Itens com próxima troca
$trocasComProx = array_filter($trocas, fn($t) => !empty($t->data_proxima_troca));
?>

<div class="page">

  <!-- Botões de ação (não imprimem) -->
  <div class="no-print" style="margin-bottom:16px;display:flex;gap:8px">
    <button onclick="window.print()" style="padding:6px 16px;background:#1a56db;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:10pt">
      🖨 Imprimir / Salvar PDF
    </button>
    <a href="/manutencao/ordens/<?= $os->id ?>" style="padding:6px 16px;background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;border-radius:4px;text-decoration:none;font-size:10pt">
      ← Voltar
    </a>
  </div>

  <!-- Cabeçalho -->
  <div class="doc-header">
    <div class="logo-area">
      <h1><?= htmlspecialchars($empresa->razao_social ?? $empresa->nome ?? 'EMPRESA') ?></h1>
      <?php if (!empty($empresa->cnpj)): ?><p>CNPJ: <?= htmlspecialchars($empresa->cnpj) ?></p><?php endif; ?>
      <?php if (!empty($empresa->telefone)): ?><p>Tel: <?= htmlspecialchars($empresa->telefone) ?></p><?php endif; ?>
      <?php if (!empty($empresa->email)): ?><p><?= htmlspecialchars($empresa->email) ?></p><?php endif; ?>
    </div>
    <div class="os-number">
      <div style="font-size:8pt;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em">Ordem de Serviço</div>
      <div class="num"><?= htmlspecialchars($os->numero) ?></div>
      <div style="margin-top:4px">
        <span class="tipo <?= $tipoClass ?>"><?= $tipoLabel ?></span>
        &nbsp;
        <span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
      </div>
      <div style="font-size:8.5pt;color:#64748b;margin-top:4px">
        Abertura: <?= date('d/m/Y', strtotime($os->data_abertura)) ?>
        <?php if (!empty($os->data_previsao)): ?>
        — Previsão: <?= date('d/m/Y', strtotime($os->data_previsao)) ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Dados do Cliente -->
  <div class="section">
    <div class="section-title">Dados do Cliente</div>
    <div class="section-body">
      <div class="data-grid">
        <div class="data-item"><label>Nome / Razão Social</label><span><?= htmlspecialchars($os->cliente_nome) ?></span></div>
        <div class="data-item"><label>CPF / CNPJ</label><span><?= htmlspecialchars($os->cliente_cpf_cnpj ?? '-') ?></span></div>
        <div class="data-item"><label>E-mail</label><span><?= htmlspecialchars($os->cliente_email ?? '-') ?></span></div>
        <div class="data-item"><label>Telefone</label><span><?= htmlspecialchars($os->cliente_telefone ?? '-') ?></span></div>
        <div class="data-item"><label>Endereço</label><span><?= htmlspecialchars($os->cliente_endereco ?? '-') ?></span></div>
        <div class="data-item"><label>Cidade / UF</label><span><?= htmlspecialchars(($os->cliente_cidade ?? '') . ($os->cliente_estado ? ' - ' . $os->cliente_estado : '')) ?: '-' ?></span></div>
      </div>
    </div>
  </div>

  <!-- Dados do Equipamento -->
  <div class="section">
    <div class="section-title">Equipamento / Produto</div>
    <div class="section-body">
      <div class="data-grid cols3">
        <div class="data-item"><label>Produto / Equipamento</label><span><?= htmlspecialchars($os->produto_nome ?? '-') ?></span></div>
        <div class="data-item"><label>Código</label><span><?= htmlspecialchars($os->produto_codigo ?? '-') ?></span></div>
        <div class="data-item"><label>Número de Série</label><span style="font-weight:700"><?= htmlspecialchars($os->numero_serie ?? '-') ?></span></div>
        <div class="data-item"><label>Marca</label><span><?= htmlspecialchars($os->marca ?? '-') ?></span></div>
        <div class="data-item"><label>Modelo</label><span><?= htmlspecialchars($os->modelo ?? '-') ?></span></div>
        <div class="data-item"><label>Vida Útil</label><span><?= $os->vida_util_meses ? $os->vida_util_meses . ' meses' : '-' ?></span></div>
      </div>
    </div>
  </div>

  <!-- Dados do Chamado -->
  <div class="section">
    <div class="section-title">Dados do Chamado</div>
    <div class="section-body">
      <div class="data-grid" style="margin-bottom:8px">
        <div class="data-item"><label>Tipo de Manutenção</label><span><?= $tipoLabel ?></span></div>
        <div class="data-item"><label>Técnico Responsável</label><span><?= htmlspecialchars($os->tecnico_responsavel ?? '-') ?></span></div>
      </div>
      <div class="data-item" style="margin-bottom:6px">
        <label>Motivo do Chamado</label>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:6px 8px;font-size:10pt;white-space:pre-wrap"><?= htmlspecialchars($os->motivo_chamado) ?></div>
      </div>
      <?php if (!empty($os->descricao_servico)): ?>
      <div class="data-item">
        <label>Descrição do Serviço</label>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:6px 8px;font-size:10pt;white-space:pre-wrap"><?= htmlspecialchars($os->descricao_servico) ?></div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Evolução da Manutenção -->
  <?php if (!empty($os->evolucao)): ?>
  <div class="section">
    <div class="section-title">Evolução da Manutenção</div>
    <div class="section-body">
      <div class="evolucao-box"><?= htmlspecialchars($os->evolucao) ?></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Itens Trocados / Serviços -->
  <?php if (!empty($trocas)): ?>
  <div class="section">
    <div class="section-title">Itens Trocados / Serviços Realizados</div>
    <div class="section-body" style="padding:0">
      <table class="items">
        <thead>
          <tr>
            <th>Código</th>
            <th>Descrição</th>
            <th>Unid.</th>
            <th class="text-right">Qtd</th>
            <th class="text-right">Unit. (R$)</th>
            <th class="text-right">Total (R$)</th>
            <th>Vida Útil</th>
            <th>Próx. Troca</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($trocas as $t): ?>
          <tr>
            <td><?= htmlspecialchars($t->produto_codigo ?? '-') ?></td>
            <td><?= htmlspecialchars($t->descricao) ?><?php if (!empty($t->observacoes)): ?><br><span style="font-size:8pt;color:#64748b"><?= htmlspecialchars($t->observacoes) ?></span><?php endif; ?></td>
            <td><?= htmlspecialchars($t->unidade ?? 'UN') ?></td>
            <td class="text-right"><?= number_format((float)$t->quantidade, 3, ',', '.') ?></td>
            <td class="text-right"><?= number_format((float)$t->preco_unitario, 2, ',', '.') ?></td>
            <td class="text-right"><?= number_format((float)$t->preco_total, 2, ',', '.') ?></td>
            <td><?= $t->vida_util_meses ? $t->vida_util_meses . ' m' : '-' ?></td>
            <td><?= !empty($t->data_proxima_troca) ? date('d/m/Y', strtotime($t->data_proxima_troca)) : '-' ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td colspan="5" class="text-right">Valor Peças:</td>
            <td class="text-right">R$ <?= number_format((float)$os->valor_pecas, 2, ',', '.') ?></td>
            <td colspan="2"></td>
          </tr>
          <tr class="total-row">
            <td colspan="5" class="text-right">Valor do Serviço:</td>
            <td class="text-right">R$ <?= number_format((float)$os->valor_servico, 2, ',', '.') ?></td>
            <td colspan="2"></td>
          </tr>
          <tr class="total-row" style="font-size:11pt">
            <td colspan="5" class="text-right" style="color:#166534">TOTAL GERAL:</td>
            <td class="text-right" style="color:#166534">R$ <?= number_format((float)$os->valor_total, 2, ',', '.') ?></td>
            <td colspan="2"></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Próximas Trocas Recomendadas -->
  <?php if (!empty($trocasComProx)): ?>
  <div class="section">
    <div class="section-title" style="background:#dbeafe;color:#1d4ed8">📅 Próximas Trocas Recomendadas</div>
    <div class="section-body" style="padding:8px 0">
      <div class="alert-prox">
        ⚠️ Atenção: Os itens abaixo possuem prazo de troca recomendado. Realize a manutenção preventiva nas datas indicadas para garantir o funcionamento adequado do equipamento.
      </div>
      <table class="prox-table">
        <thead>
          <tr>
            <th>Item / Peça</th>
            <th>Número de Série</th>
            <th>Vida Útil</th>
            <th>Data da Troca</th>
            <th>Próxima Troca</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($trocasComProx as $t): ?>
          <tr>
            <td><?= htmlspecialchars($t->descricao) ?></td>
            <td><?= htmlspecialchars($os->numero_serie ?? '-') ?></td>
            <td><?= $t->vida_util_meses ? $t->vida_util_meses . ' meses' : '-' ?></td>
            <td><?= date('d/m/Y', strtotime($os->data_abertura)) ?></td>
            <td style="font-weight:700;color:#1d4ed8"><?= date('d/m/Y', strtotime($t->data_proxima_troca)) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Observações -->
  <?php if (!empty($os->observacoes)): ?>
  <div class="section">
    <div class="section-title">Observações</div>
    <div class="section-body">
      <p style="font-size:10pt;white-space:pre-wrap"><?= htmlspecialchars($os->observacoes) ?></p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Assinaturas -->
  <div class="assinaturas">
    <div class="assinatura">
      <div class="linha"></div>
      <p><strong>Técnico Responsável</strong></p>
      <p><?= htmlspecialchars($os->tecnico_responsavel ?? '') ?></p>
    </div>
    <div class="assinatura">
      <div class="linha"></div>
      <p><strong>Cliente</strong></p>
      <p><?= htmlspecialchars($os->cliente_nome) ?></p>
    </div>
    <div class="assinatura">
      <div class="linha"></div>
      <p><strong>Data</strong></p>
      <p>____/____/________</p>
    </div>
  </div>

  <!-- Rodapé -->
  <div class="doc-footer">
    <p><?= htmlspecialchars($empresa->razao_social ?? '') ?><?php if (!empty($empresa->cnpj)): ?> — CNPJ: <?= htmlspecialchars($empresa->cnpj) ?><?php endif; ?></p>
    <p>Documento gerado em <?= date('d/m/Y \à\s H:i') ?> — <?= htmlspecialchars($os->numero) ?></p>
  </div>

</div>
</body>
</html>
