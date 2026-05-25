<?php
$fmt  = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$fmtN = fn($v, $dec=2) => number_format((float)$v, $dec, ',', '.');
$esc  = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$tipoLabels = [
    'entrada' => ['label' => 'Entrada',  'class' => 'badge-entrada'],
    'saida'   => ['label' => 'Saída',    'class' => 'badge-saida'],
    'ajuste'  => ['label' => 'Ajuste',   'class' => 'badge-ajuste'],
    'devolucao_compra' => ['label' => 'Dev. Compra', 'class' => 'badge-warning'],
    'devolucao_venda'  => ['label' => 'Dev. Venda',  'class' => 'badge-info'],
    'transferencia'    => ['label' => 'Transferência','class' => 'badge-purple'],
    'perda'            => ['label' => 'Perda',        'class' => 'badge-danger'],
    'inventario'       => ['label' => 'Inventário',   'class' => 'badge-secondary'],
];
$origemLabels = [
    'manual'        => 'Manual',
    'xml_nfe'       => 'XML NF-e',
    'pedido_compra' => 'Pedido de Compra',
    'pedido_venda'  => 'Pedido de Venda',
    'ajuste_sistema'=> 'Ajuste Sistema',
];
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>
<style>
.kpi-card { border-radius:12px; padding:20px 24px; display:flex; align-items:center; gap:16px; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,.07); }
.kpi-icon { width:52px; height:52px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
.kpi-val  { font-size:26px; font-weight:700; line-height:1; }
.kpi-lbl  { font-size:12px; color:#6b7280; margin-top:3px; }
.badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.badge-entrada   { background:#d1fae5; color:#065f46; }
.badge-saida     { background:#fee2e2; color:#991b1b; }
.badge-ajuste    { background:#fef3c7; color:#92400e; }
.badge-warning   { background:#fef3c7; color:#92400e; }
.badge-info      { background:#dbeafe; color:#1e40af; }
.badge-purple    { background:#ede9fe; color:#5b21b6; }
.badge-danger    { background:#fee2e2; color:#991b1b; }
.badge-secondary { background:#f3f4f6; color:#374151; }
.mov-table th { background:#f8fafc; font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#6b7280; font-weight:600; padding:10px 14px; }
.mov-table td { padding:12px 14px; vertical-align:middle; }
.mov-table tr:hover td { background:#f9fafb; }
.filtro-bar { background:#fff; border-radius:12px; padding:16px 20px; box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:20px; }
.btn-entrada { background:#059669; color:#fff; border:none; }
.btn-entrada:hover { background:#047857; color:#fff; }
.btn-saida { background:#dc2626; color:#fff; border:none; }
.btn-saida:hover { background:#b91c1c; color:#fff; }
.qty-entrada { color:#059669; font-weight:700; }
.qty-saida   { color:#dc2626; font-weight:700; }
.qty-ajuste  { color:#d97706; font-weight:700; }
</style>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>
    <?= $success === 'registrado' ? 'Movimentação registrada com sucesso!' : 'Operação realizada com sucesso!' ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php elseif ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i> Ocorreu um erro. Tente novamente.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#d1fae5;">
                <i class="fas fa-arrow-down" style="color:#059669;"></i>
            </div>
            <div>
                <div class="kpi-val" style="color:#059669;"><?= $fmtN($kpis->total_entradas ?? 0, 0) ?></div>
                <div class="kpi-lbl">Entradas no período</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fee2e2;">
                <i class="fas fa-arrow-up" style="color:#dc2626;"></i>
            </div>
            <div>
                <div class="kpi-val" style="color:#dc2626;"><?= $fmtN($kpis->total_saidas ?? 0, 0) ?></div>
                <div class="kpi-lbl">Saídas no período</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#dbeafe;">
                <i class="fas fa-dollar-sign" style="color:#1d4ed8;"></i>
            </div>
            <div>
                <div class="kpi-val" style="color:#1d4ed8;"><?= $fmt($kpis->valor_entradas ?? 0) ?></div>
                <div class="kpi-lbl">Valor entradas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#ede9fe;">
                <i class="fas fa-chart-line" style="color:#7c3aed;"></i>
            </div>
            <div>
                <div class="kpi-val" style="color:#7c3aed;"><?= $fmt($kpis->valor_saidas ?? 0) ?></div>
                <div class="kpi-lbl">Valor saídas</div>
            </div>
        </div>
    </div>
</div>

<!-- Ações rápidas -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="/estoque/movimentacoes/create?tipo=entrada" class="btn btn-entrada rounded-pill px-4">
        <i class="fas fa-arrow-down me-2"></i>Nova Entrada
    </a>
    <a href="/estoque/movimentacoes/create?tipo=saida" class="btn btn-saida rounded-pill px-4">
        <i class="fas fa-arrow-up me-2"></i>Nova Saída
    </a>
    <a href="/estoque/movimentacoes/importar-xml" class="btn btn-outline-primary rounded-pill px-4">
        <i class="fas fa-file-code me-2"></i>Importar XML NF-e
    </a>
    <a href="/estoque/compras" class="btn btn-outline-secondary rounded-pill px-4">
        <i class="fas fa-shopping-cart me-2"></i>Pedidos de Compra
    </a>
    <a href="/estoque/vendas" class="btn btn-outline-secondary rounded-pill px-4">
        <i class="fas fa-store me-2"></i>Pedidos de Venda
    </a>
</div>

<!-- Filtros -->
<div class="filtro-bar">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Buscar</label>
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Produto, lote, NF-e..." value="<?= $esc($filtros['q']) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Tipo</label>
            <select name="tipo" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($tipoLabels as $k => $v): ?>
                <option value="<?= $k ?>" <?= $filtros['tipo'] === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Origem</label>
            <select name="origem" class="form-select form-select-sm">
                <option value="">Todas</option>
                <?php foreach ($origemLabels as $k => $v): ?>
                <option value="<?= $k ?>" <?= $filtros['origem'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label mb-1" style="font-size:12px;font-weight:600;">De</label>
            <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?= $esc($filtros['data_inicio']) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Até</label>
            <input type="date" name="data_fim" class="form-control form-control-sm" value="<?= $esc($filtros['data_fim']) ?>">
        </div>
        <div class="col-12 col-md-1">
            <button type="submit" class="btn btn-primary btn-sm w-100">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </form>
</div>

<!-- Tabela -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mov-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Data/Hora</th>
                        <th>Tipo</th>
                        <th>Produto</th>
                        <th>Qtd</th>
                        <th>Preço Unit.</th>
                        <th>Total</th>
                        <th>Origem</th>
                        <th>Lote</th>
                        <th>Motivo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($movimentacoes)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-5 text-muted">
                            <i class="fas fa-exchange-alt fa-2x mb-3 d-block" style="opacity:.3;"></i>
                            Nenhuma movimentação encontrada no período.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movimentacoes as $m): ?>
                    <?php
                        $tl     = $tipoLabels[$m->tipo] ?? ['label' => $m->tipo, 'class' => 'badge-secondary'];
                        $qtyClass = $m->tipo === 'entrada' ? 'qty-entrada' : ($m->tipo === 'saida' ? 'qty-saida' : 'qty-ajuste');
                        $sinal    = $m->tipo === 'entrada' ? '+' : ($m->tipo === 'saida' ? '-' : '±');
                        $total    = (float)$m->quantidade * (float)$m->preco_unitario;
                    ?>
                    <tr>
                        <td><small class="text-muted"><?= $m->id ?></small></td>
                        <td>
                            <div style="font-size:13px;"><?= date('d/m/Y', strtotime($m->created_at)) ?></div>
                            <small class="text-muted"><?= date('H:i', strtotime($m->created_at)) ?></small>
                        </td>
                        <td><span class="badge <?= $tl['class'] ?>"><?= $tl['label'] ?></span></td>
                        <td>
                            <div style="font-size:13px;font-weight:600;"><?= $esc($m->produto_nome ?? '—') ?></div>
                            <small class="text-muted"><?= $esc($m->produto_codigo ?? '') ?></small>
                        </td>
                        <td class="<?= $qtyClass ?>"><?= $sinal . $fmtN($m->quantidade) ?> <?= $esc($m->unidade ?? '') ?></td>
                        <td><?= $fmt($m->preco_unitario ?? 0) ?></td>
                        <td><?= $fmt($total) ?></td>
                        <td><small><?= $origemLabels[$m->origem] ?? $esc($m->origem) ?></small></td>
                        <td><small class="text-muted"><?= $esc($m->lote ?? '—') ?></small></td>
                        <td><small class="text-muted" title="<?= $esc($m->motivo ?? '') ?>"><?= mb_strimwidth($m->motivo ?? '—', 0, 30, '…') ?></small></td>
                        <td>
                            <a href="/estoque/movimentacoes/<?= $m->id ?>" class="btn btn-sm btn-outline-secondary" title="Ver detalhes">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
