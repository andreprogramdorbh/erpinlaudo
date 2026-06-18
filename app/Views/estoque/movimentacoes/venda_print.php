<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedido de Venda <?= htmlspecialchars($pedido->numero ?? '') ?></title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Segoe UI',Arial,sans-serif;font-size:11pt;color:#1e293b;background:#fff;padding:20px}
  .page{max-width:820px;margin:0 auto;background:#fff}

  /* Botões de ação (não imprimíveis) */
  .no-print{margin-bottom:18px;display:flex;gap:10px}
  .btn-print{background:#1a56db;color:#fff;border:none;padding:9px 20px;border-radius:6px;font-size:10pt;cursor:pointer;display:flex;align-items:center;gap:6px}
  .btn-back{background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;padding:9px 16px;border-radius:6px;font-size:10pt;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px}

  /* Header do documento */
  .doc-header{display:flex;align-items:flex-start;justify-content:space-between;border-bottom:3px solid #1a56db;padding-bottom:14px;margin-bottom:16px}
  .logo-area{display:flex;align-items:center;gap:12px}
  .logo-area img{max-height:52px;max-width:120px;object-fit:contain}
  .logo-area .empresa-info h1{font-size:14pt;font-weight:800;color:#1a56db;line-height:1.2}
  .logo-area .empresa-info p{font-size:8.5pt;color:#64748b;line-height:1.5}
  .doc-ref{text-align:right}
  .doc-ref .doc-type{font-size:8.5pt;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
  .doc-ref .doc-num{font-size:18pt;font-weight:800;color:#1a56db;line-height:1.1}
  .doc-ref .doc-status{display:inline-block;padding:.25em .9em;border-radius:20px;font-size:8.5pt;font-weight:700;margin-top:4px}
  .status-rascunho{background:#f1f5f9;color:#64748b}
  .status-confirmado{background:#dbeafe;color:#1d4ed8}
  .status-entregue{background:#dcfce7;color:#166534}
  .status-faturado{background:#f0fdf4;color:#15803d}
  .status-cancelado{background:#fee2e2;color:#991b1b}
  .doc-ref .doc-date{font-size:8.5pt;color:#64748b;margin-top:3px}

  /* Seções */
  .section{margin-bottom:14px;border:1px solid #e2e8f0;border-radius:6px;overflow:hidden}
  .section-title{background:#f1f5f9;padding:6px 12px;font-size:8.5pt;font-weight:700;color:#374151;border-bottom:1px solid #e2e8f0;text-transform:uppercase;letter-spacing:.05em}
  .section-body{padding:10px 12px}

  /* Grid de dados */
  .data-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px 20px}
  .data-grid.cols3{grid-template-columns:1fr 1fr 1fr}
  .data-grid.cols4{grid-template-columns:1fr 1fr 1fr 1fr}
  .data-item label{display:block;font-size:7.5pt;color:#64748b;font-weight:600;margin-bottom:1px;text-transform:uppercase;letter-spacing:.03em}
  .data-item span{font-size:10pt;color:#1e293b;font-weight:500}

  /* Tabela de itens */
  table.items{width:100%;border-collapse:collapse;font-size:9.5pt}
  table.items th{background:#f1f5f9;padding:6px 8px;text-align:left;font-size:8pt;font-weight:700;color:#374151;border-bottom:2px solid #e2e8f0}
  table.items td{padding:5px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top}
  table.items tr:last-child td{border-bottom:none}
  table.items .text-right{text-align:right}
  table.items .text-center{text-align:center}
  table.items .subtotal-row td{background:#f8fafc;border-top:1px solid #e2e8f0;font-size:9pt;color:#374151}
  table.items .total-row td{font-weight:700;background:#eff6ff;border-top:2px solid #bfdbfe;font-size:10.5pt;color:#1d4ed8}
  table.items .total-row td:last-child{color:#16a34a}

  /* Tabela de parcelas */
  table.parcelas{width:100%;border-collapse:collapse;font-size:9.5pt}
  table.parcelas th{background:#f1f5f9;padding:6px 8px;text-align:left;font-size:8pt;font-weight:700;color:#374151;border-bottom:2px solid #e2e8f0}
  table.parcelas td{padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
  table.parcelas tr:last-child td{border-bottom:none}
  table.parcelas .text-right{text-align:right}
  table.parcelas .text-center{text-align:center}
  .parcela-num{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#dbeafe;color:#1d4ed8;font-size:8pt;font-weight:700}
  .badge-forma{display:inline-block;padding:.2em .7em;border-radius:12px;font-size:8pt;font-weight:600}
  .forma-pix{background:#dcfce7;color:#166534}
  .forma-boleto{background:#fef3c7;color:#92400e}
  .forma-dinheiro{background:#f0fdf4;color:#15803d}
  .forma-cartao_credito{background:#ede9fe;color:#5b21b6}
  .forma-cartao_debito{background:#e0e7ff;color:#3730a3}
  .forma-transferencia{background:#cffafe;color:#0e7490}
  .forma-cheque{background:#fce7f3;color:#9d174d}
  .forma-outros{background:#f1f5f9;color:#374151}
  .status-aberta{color:#d97706;font-weight:600}
  .status-recebida{color:#16a34a;font-weight:600}
  .status-cancelada{color:#dc2626;font-weight:600}
  .status-vencida{color:#dc2626;font-weight:700}

  /* Resumo financeiro */
  .resumo-fin{display:flex;gap:16px;flex-wrap:wrap;margin-top:10px}
  .resumo-card{flex:1;min-width:150px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:10px 14px;text-align:center}
  .resumo-card label{display:block;font-size:7.5pt;color:#64748b;font-weight:600;text-transform:uppercase;margin-bottom:4px}
  .resumo-card .val{font-size:13pt;font-weight:800;color:#1e293b}
  .resumo-card .val.green{color:#16a34a}
  .resumo-card .val.blue{color:#1d4ed8}
  .resumo-card .val.orange{color:#d97706}

  /* Alerta sem parcelas */
  .alert-info{background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:10px 14px;font-size:9.5pt;color:#1d4ed8;margin-top:6px}

  /* Assinaturas */
  .assinaturas{display:flex;gap:40px;margin-top:28px;padding-top:16px;border-top:1px solid #e2e8f0}
  .assinatura{flex:1;text-align:center}
  .assinatura .linha{border-top:1px solid #94a3b8;margin-bottom:4px;margin-top:36px}
  .assinatura p{font-size:8.5pt;color:#64748b}

  /* Rodapé */
  .doc-footer{margin-top:16px;padding-top:8px;border-top:1px solid #e2e8f0;text-align:center;font-size:8pt;color:#94a3b8}

  @media print{
    body{padding:0}
    .no-print{display:none!important}
    @page{margin:14mm 12mm}
    .section{break-inside:avoid}
  }
</style>
</head>
<body>
<?php
$pedido   = $pedido   ?? null;
$parcelas = $parcelas ?? [];
$empresa  = $empresa  ?? null;

$statusLabel = match($pedido->status ?? '') {
    'rascunho'   => 'Rascunho',
    'confirmado' => 'Confirmado',
    'entregue'   => 'Entregue',
    'faturado'   => 'Faturado',
    'cancelado'  => 'Cancelado',
    default      => ucfirst($pedido->status ?? ''),
};
$statusClass = 'status-' . ($pedido->status ?? 'rascunho');

$formaLabel = function(string $f): string {
    return match($f) {
        'pix'             => 'PIX',
        'boleto'          => 'Boleto',
        'dinheiro'        => 'Dinheiro',
        'cartao_credito'  => 'Cartão Crédito',
        'cartao_debito'   => 'Cartão Débito',
        'transferencia'   => 'Transferência',
        'cheque'          => 'Cheque',
        default           => ucfirst(str_replace('_', ' ', $f)),
    };
};

$formaClass = function(string $f): string {
    return 'forma-' . $f;
};

$statusParcelaLabel = function(string $s, string $venc): string {
    if ($s === 'recebida') return 'Recebida';
    if ($s === 'cancelada') return 'Cancelada';
    if ($s === 'aberta' && $venc < date('Y-m-d')) return 'Vencida';
    return 'Em Aberto';
};

$statusParcelaClass = function(string $s, string $venc): string {
    if ($s === 'recebida') return 'status-recebida';
    if ($s === 'cancelada') return 'status-cancelada';
    if ($s === 'aberta' && $venc < date('Y-m-d')) return 'status-vencida';
    return 'status-aberta';
};

$fmt = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$fmtDate = fn($d) => $d ? date('d/m/Y', strtotime($d)) : '—';

// Totais das parcelas
$totalParcelas  = array_sum(array_column($parcelas, 'valor'));
$totalRecebido  = array_sum(array_map(fn($p) => $p->status === 'recebida' ? $p->valor : 0, $parcelas));
$totalEmAberto  = $totalParcelas - $totalRecebido;

$logoPath = '';
if ($empresa && !empty($empresa->logo_path)) {
    $logoPath = '/' . ltrim($empresa->logo_path, '/');
}
?>

<!-- Botões de ação (não imprimíveis) -->
<div class="no-print">
  <button class="btn-print" onclick="window.print()">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z"/></svg>
    Imprimir / Salvar PDF
  </button>
  <a class="btn-back" href="/estoque/vendas/<?= $pedido->id ?>">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    ← Voltar
  </a>
</div>

<div class="page">

  <!-- CABEÇALHO -->
  <div class="doc-header">
    <div class="logo-area">
      <?php if ($logoPath): ?>
        <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo">
      <?php endif; ?>
      <div class="empresa-info">
        <h1><?= htmlspecialchars($empresa->razao_social ?? $empresa->nome_fantasia ?? 'EMPRESA') ?></h1>
        <p><?= htmlspecialchars($empresa->nome_fantasia ?? '') ?></p>
        <?php if (!empty($empresa->cnpj)): ?>
          <p>CNPJ: <?= htmlspecialchars($empresa->cnpj) ?></p>
        <?php endif; ?>
        <?php if (!empty($empresa->endereco)): ?>
          <p><?= htmlspecialchars($empresa->endereco) ?><?= !empty($empresa->cidade) ? ' — ' . htmlspecialchars($empresa->cidade) . ' - ' . htmlspecialchars($empresa->estado ?? '') : '' ?></p>
        <?php endif; ?>
        <?php if (!empty($empresa->telefone)): ?>
          <p>Tel: <?= htmlspecialchars($empresa->telefone) ?></p>
        <?php endif; ?>
        <?php if (!empty($empresa->email)): ?>
          <p><?= htmlspecialchars($empresa->email) ?></p>
        <?php endif; ?>
      </div>
    </div>
    <div class="doc-ref">
      <div class="doc-type">Pedido de Venda</div>
      <div class="doc-num"><?= htmlspecialchars($pedido->numero ?? '') ?></div>
      <div><span class="doc-status <?= $statusClass ?>"><?= $statusLabel ?></span></div>
      <div class="doc-date">Data: <?= $fmtDate($pedido->data_pedido ?? null) ?></div>
    </div>
  </div>

  <!-- DADOS DO CLIENTE -->
  <div class="section">
    <div class="section-title">Dados do Cliente</div>
    <div class="section-body">
      <div class="data-grid cols3">
        <div class="data-item">
          <label>Nome / Razão Social</label>
          <span><?= htmlspecialchars($pedido->cliente_nome ?? '—') ?></span>
        </div>
        <div class="data-item">
          <label>CPF / CNPJ</label>
          <span><?= htmlspecialchars($pedido->cliente_cpf_cnpj ?? '—') ?></span>
        </div>
        <div class="data-item">
          <label>Telefone</label>
          <span><?= htmlspecialchars($pedido->cliente_telefone ?? '—') ?></span>
        </div>
        <div class="data-item">
          <label>E-mail</label>
          <span><?= htmlspecialchars($pedido->cliente_email ?? '—') ?></span>
        </div>
        <div class="data-item">
          <label>Data do Pedido</label>
          <span><?= $fmtDate($pedido->data_pedido ?? null) ?></span>
        </div>
        <div class="data-item">
          <label>Forma de Pagamento</label>
          <span><?= htmlspecialchars($pedido->forma_pagamento ?? '—') ?></span>
        </div>
      </div>
      <?php if (!empty($pedido->endereco_entrega)): ?>
      <div style="margin-top:8px">
        <div class="data-item">
          <label>Endereço de Entrega</label>
          <span><?= htmlspecialchars($pedido->endereco_entrega) ?></span>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ITENS DO PEDIDO -->
  <div class="section">
    <div class="section-title">Itens do Pedido</div>
    <div class="section-body" style="padding:0">
      <table class="items">
        <thead>
          <tr>
            <th>Produto / Descrição</th>
            <th class="text-center">Qtd</th>
            <th class="text-center">Unid.</th>
            <th class="text-right">Preço Unit. (R$)</th>
            <th class="text-right">Desc. %</th>
            <th class="text-right">Total (R$)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($pedido->itens ?? []) as $item): ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($item->descricao ?? $item->produto_nome ?? '—') ?></strong>
              <?php if (!empty($item->codigo)): ?>
                <br><span style="font-size:8pt;color:#64748b"><?= htmlspecialchars($item->codigo) ?></span>
              <?php endif; ?>
            </td>
            <td class="text-center"><?= number_format((float)($item->quantidade ?? 0), 3, ',', '.') ?></td>
            <td class="text-center"><?= htmlspecialchars($item->unidade ?? 'UN') ?></td>
            <td class="text-right"><?= number_format((float)($item->preco_venda ?? 0), 2, ',', '.') ?></td>
            <td class="text-center"><?= number_format((float)($item->desconto_percentual ?? 0), 1, ',', '.') ?>%</td>
            <td class="text-right"><strong><?= number_format((float)($item->total ?? 0), 2, ',', '.') ?></strong></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="subtotal-row">
            <td colspan="5" class="text-right">Subtotal Produtos:</td>
            <td class="text-right"><?= $fmt($pedido->subtotal ?? $pedido->valor_total ?? 0) ?></td>
          </tr>
          <?php if (!empty($pedido->frete) && (float)$pedido->frete > 0): ?>
          <tr class="subtotal-row">
            <td colspan="5" class="text-right">Frete:</td>
            <td class="text-right"><?= $fmt($pedido->frete) ?></td>
          </tr>
          <?php endif; ?>
          <?php if (!empty($pedido->desconto_total) && (float)$pedido->desconto_total > 0): ?>
          <tr class="subtotal-row">
            <td colspan="5" class="text-right">Desconto:</td>
            <td class="text-right" style="color:#dc2626">- <?= $fmt($pedido->desconto_total) ?></td>
          </tr>
          <?php endif; ?>
          <tr class="total-row">
            <td colspan="5" class="text-right">TOTAL GERAL:</td>
            <td class="text-right"><?= $fmt($pedido->valor_total ?? 0) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <!-- CONDIÇÕES DE PAGAMENTO / PARCELAMENTO -->
  <div class="section">
    <div class="section-title">Condições de Pagamento</div>
    <div class="section-body">
      <?php if (empty($parcelas)): ?>
        <div class="alert-info">
          Nenhuma parcela registrada para este pedido. As condições de pagamento foram definidas como: <strong><?= htmlspecialchars($pedido->condicao_pagamento ?? $pedido->forma_pagamento ?? 'À vista') ?></strong>.
        </div>
      <?php else: ?>
        <table class="parcelas">
          <thead>
            <tr>
              <th class="text-center">#</th>
              <th>Descrição</th>
              <th>Forma de Pagamento</th>
              <th class="text-center">Vencimento</th>
              <th class="text-right">Valor (R$)</th>
              <th class="text-center">Status</th>
              <?php if (array_filter($parcelas, fn($p) => !empty($p->data_recebimento))): ?>
              <th class="text-center">Recebido em</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php $temDataReceb = (bool)array_filter($parcelas, fn($p) => !empty($p->data_recebimento)); ?>
            <?php foreach ($parcelas as $i => $parcela): ?>
            <tr>
              <td class="text-center">
                <span class="parcela-num"><?= ($parcela->numero_parcela ?? ($i + 1)) ?></span>
              </td>
              <td><?= htmlspecialchars($parcela->descricao ?? 'Parcela ' . ($i + 1)) ?></td>
              <td>
                <?php $forma = $parcela->meio_pagamento ?? ''; ?>
                <span class="badge-forma <?= $formaClass($forma) ?>"><?= $formaLabel($forma) ?></span>
              </td>
              <td class="text-center"><?= $fmtDate($parcela->data_vencimento ?? null) ?></td>
              <td class="text-right"><strong><?= $fmt($parcela->valor ?? 0) ?></strong></td>
              <td class="text-center">
                <?php
                  $st = $parcela->status ?? 'aberta';
                  $venc = $parcela->data_vencimento ?? date('Y-m-d');
                  echo '<span class="' . $statusParcelaClass($st, $venc) . '">' . $statusParcelaLabel($st, $venc) . '</span>';
                ?>
              </td>
              <?php if ($temDataReceb): ?>
              <td class="text-center"><?= $fmtDate($parcela->data_recebimento ?? null) ?></td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr style="background:#f8fafc;font-weight:700;border-top:2px solid #e2e8f0">
              <td colspan="4" class="text-right" style="padding:7px 8px">Total das Parcelas:</td>
              <td class="text-right" style="padding:7px 8px"><?= $fmt($totalParcelas) ?></td>
              <td colspan="<?= $temDataReceb ? 2 : 1 ?>"></td>
            </tr>
          </tfoot>
        </table>

        <!-- Resumo financeiro das parcelas -->
        <div class="resumo-fin" style="margin-top:12px">
          <div class="resumo-card">
            <label>Total do Pedido</label>
            <div class="val blue"><?= $fmt($pedido->valor_total ?? 0) ?></div>
          </div>
          <div class="resumo-card">
            <label>Total Parcelado</label>
            <div class="val"><?= $fmt($totalParcelas) ?></div>
          </div>
          <div class="resumo-card">
            <label>Já Recebido</label>
            <div class="val green"><?= $fmt($totalRecebido) ?></div>
          </div>
          <div class="resumo-card">
            <label>Em Aberto</label>
            <div class="val orange"><?= $fmt($totalEmAberto) ?></div>
          </div>
          <div class="resumo-card">
            <label>Parcelas</label>
            <div class="val"><?= count($parcelas) ?>x</div>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($pedido->observacoes)): ?>
      <div style="margin-top:12px;padding:10px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;font-size:9.5pt;color:#92400e">
        <strong>Observações:</strong> <?= nl2br(htmlspecialchars($pedido->observacoes)) ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- RESUMO FINANCEIRO GERAL -->
  <div class="section">
    <div class="section-title">Resumo Financeiro</div>
    <div class="section-body">
      <div class="data-grid cols4">
        <div class="data-item">
          <label>Subtotal Produtos</label>
          <span><?= $fmt($pedido->subtotal ?? $pedido->valor_total ?? 0) ?></span>
        </div>
        <div class="data-item">
          <label>Frete</label>
          <span><?= $fmt($pedido->frete ?? 0) ?></span>
        </div>
        <div class="data-item">
          <label>Desconto</label>
          <span style="color:#dc2626">- <?= $fmt($pedido->desconto_total ?? 0) ?></span>
        </div>
        <div class="data-item">
          <label>Total Geral</label>
          <span style="font-size:13pt;font-weight:800;color:#16a34a"><?= $fmt($pedido->valor_total ?? 0) ?></span>
        </div>
        <?php if (!empty($pedido->margem_total) && (float)$pedido->margem_total != 0): ?>
        <div class="data-item">
          <label>Margem Bruta</label>
          <span style="color:#16a34a"><?= $fmt($pedido->margem_total) ?></span>
        </div>
        <div class="data-item">
          <label>Margem %</label>
          <span style="color:#16a34a"><?= number_format((float)($pedido->margem_percentual ?? 0), 1, ',', '.') ?>%</span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ASSINATURAS -->
  <div class="assinaturas">
    <div class="assinatura">
      <div class="linha"></div>
      <p>Responsável</p>
    </div>
    <div class="assinatura">
      <div class="linha"></div>
      <p>Cliente</p>
    </div>
    <div class="assinatura">
      <div class="linha"></div>
      <p>Data</p>
    </div>
  </div>

  <!-- RODAPÉ -->
  <div class="doc-footer">
    Documento gerado em <?= date('d/m/Y \à\s H:i') ?> — <?= htmlspecialchars($empresa->razao_social ?? '') ?>
    <?php if (!empty($empresa->cnpj)): ?> — CNPJ: <?= htmlspecialchars($empresa->cnpj) ?><?php endif; ?>
  </div>

</div><!-- /page -->
</body>
</html>
