<?php
$esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$fmt = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$fmtN = fn($v, $d=2) => number_format((float)$v, $d, ',', '.');

$tipoLabels = [
    'entrada'          => ['label' => 'Entrada',          'color' => '#059669', 'bg' => '#d1fae5', 'icon' => 'fa-arrow-down'],
    'saida'            => ['label' => 'Saída',            'color' => '#dc2626', 'bg' => '#fee2e2', 'icon' => 'fa-arrow-up'],
    'ajuste'           => ['label' => 'Ajuste',           'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'fa-sliders-h'],
    'devolucao_compra' => ['label' => 'Dev. Compra',      'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'fa-undo'],
    'devolucao_venda'  => ['label' => 'Dev. Venda',       'color' => '#1d4ed8', 'bg' => '#dbeafe', 'icon' => 'fa-undo-alt'],
    'transferencia'    => ['label' => 'Transferência',    'color' => '#7c3aed', 'bg' => '#ede9fe', 'icon' => 'fa-exchange-alt'],
    'perda'            => ['label' => 'Perda',            'color' => '#dc2626', 'bg' => '#fee2e2', 'icon' => 'fa-trash'],
    'inventario'       => ['label' => 'Inventário',       'color' => '#374151', 'bg' => '#f3f4f6', 'icon' => 'fa-clipboard-list'],
];
$origemLabels = [
    'manual'         => 'Manual',
    'xml_nfe'        => 'XML NF-e',
    'pedido_compra'  => 'Pedido de Compra',
    'pedido_venda'   => 'Pedido de Venda',
    'ajuste_sistema' => 'Ajuste Sistema',
];

$tl = $tipoLabels[$mov->tipo] ?? ['label' => $mov->tipo, 'color' => '#374151', 'bg' => '#f3f4f6', 'icon' => 'fa-exchange-alt'];
$total = (float)$mov->quantidade * (float)$mov->preco_unitario;
?>
<style>
.detail-card { background:#fff; border-radius:12px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,.07); margin-bottom:20px; }
.detail-title { font-size:13px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; margin-bottom:12px; }
.detail-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f3f4f6; }
.detail-row:last-child { border-bottom:none; }
.detail-label { color:#6b7280; font-size:13px; }
.detail-value { font-weight:600; font-size:13px; }
</style>

<!-- Cabeçalho -->
<div class="detail-card" style="background:linear-gradient(135deg,<?= $tl['color'] ?>,<?= $tl['color'] ?>cc);color:#fff;">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width:56px;height:56px;border-radius:14px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:24px;">
                <i class="fas <?= $tl['icon'] ?>"></i>
            </div>
            <div>
                <h4 class="mb-1 text-white"><?= $tl['label'] ?> #<?= $mov->id ?></h4>
                <div style="opacity:.85;font-size:14px;">
                    <?= date('d/m/Y H:i', strtotime($mov->created_at)) ?>
                    &nbsp;·&nbsp; <?= $origemLabels[$mov->origem] ?? $esc($mov->origem) ?>
                </div>
            </div>
        </div>
        <div class="text-end">
            <div style="font-size:28px;font-weight:800;"><?= $fmt($total) ?></div>
            <div style="opacity:.85;font-size:13px;"><?= $fmtN($mov->quantidade) ?> <?= $esc($mov->unidade ?? 'UN') ?> × <?= $fmt($mov->preco_unitario ?? 0) ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Produto -->
    <div class="col-12 col-md-6">
        <div class="detail-card">
            <div class="detail-title"><i class="fas fa-box me-2"></i>Produto</div>
            <div class="detail-row">
                <span class="detail-label">Código</span>
                <span class="detail-value"><?= $esc($mov->produto_codigo ?? '—') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Nome</span>
                <span class="detail-value"><?= $esc($mov->produto_nome ?? '—') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Unidade</span>
                <span class="detail-value"><?= $esc($mov->unidade ?? 'UN') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Estoque após mov.</span>
                <span class="detail-value"><?= $fmtN($mov->estoque_apos ?? 0) ?></span>
            </div>
        </div>
    </div>

    <!-- Rastreabilidade -->
    <div class="col-12 col-md-6">
        <div class="detail-card">
            <div class="detail-title"><i class="fas fa-barcode me-2"></i>Rastreabilidade</div>
            <div class="detail-row">
                <span class="detail-label">Lote</span>
                <span class="detail-value"><?= $esc($mov->lote ?? '—') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Data de Fabricação</span>
                <span class="detail-value"><?= $mov->data_fabricacao ? date('d/m/Y', strtotime($mov->data_fabricacao)) : '—' ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Data de Validade</span>
                <span class="detail-value"><?= $mov->data_validade ? date('d/m/Y', strtotime($mov->data_validade)) : '—' ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Localização</span>
                <span class="detail-value"><?= $esc($mov->localizacao ?? '—') ?></span>
            </div>
        </div>
    </div>

    <!-- Motivo e Observações -->
    <div class="col-12">
        <div class="detail-card">
            <div class="detail-title"><i class="fas fa-comment-alt me-2"></i>Motivo e Observações</div>
            <div class="detail-row">
                <span class="detail-label">Motivo</span>
                <span class="detail-value"><?= $esc($mov->motivo ?? '—') ?></span>
            </div>
            <?php if (!empty($mov->observacoes)): ?>
            <div class="detail-row">
                <span class="detail-label">Observações</span>
                <span class="detail-value"><?= $esc($mov->observacoes) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($mov->nfe_chave)): ?>
            <div class="detail-row">
                <span class="detail-label">Chave NF-e</span>
                <span class="detail-value font-monospace" style="font-size:11px;"><?= $esc($mov->nfe_chave) ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <span class="detail-label">Registrado por</span>
                <span class="detail-value"><?= $esc($mov->usuario_nome ?? '—') ?></span>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <a href="/estoque/movimentacoes" class="btn btn-outline-secondary rounded-pill px-4">
        <i class="fas fa-arrow-left me-2"></i>Voltar
    </a>
    <a href="/estoque/produtos/<?= $mov->produto_id ?>" class="btn btn-outline-primary rounded-pill px-4">
        <i class="fas fa-box me-2"></i>Ver Produto
    </a>
</div>
