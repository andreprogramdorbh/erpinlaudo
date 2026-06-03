<?php
$esc  = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$fmt  = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$fmtN = fn($v, $dec = 0) => number_format((float)$v, $dec, ',', '.');

$statusConfig = [
    'rascunho'   => ['label' => 'Rascunho',   'bg' => '#f3f4f6', 'color' => '#374151'],
    'confirmado' => ['label' => 'Confirmado',  'bg' => '#dbeafe', 'color' => '#1e40af'],
    'recebido'   => ['label' => 'Recebido',    'bg' => '#d1fae5', 'color' => '#065f46'],
    'cancelado'  => ['label' => 'Cancelado',   'bg' => '#fee2e2', 'color' => '#991b1b'],
];

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>
<style>
.kpi-card { border-radius:12px; padding:20px 24px; display:flex; align-items:center; gap:16px; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,.07); }
.kpi-icon { width:52px; height:52px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
.kpi-val  { font-size:26px; font-weight:700; line-height:1; }
.kpi-lbl  { font-size:12px; color:#6b7280; margin-top:3px; }
.badge-status { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.pc-table th { background:#f8fafc; font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#6b7280; font-weight:600; padding:10px 14px; }
.pc-table td { padding:12px 14px; vertical-align:middle; }
.pc-table tr:hover td { background:#f9fafb; }
.filtro-bar { background:#fff; border-radius:12px; padding:16px 20px; box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:20px; }
</style>

<?php if ($success === 'criado'): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> Pedido de compra criado com sucesso!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php elseif ($success === 'atualizado'): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> Pedido de compra atualizado com sucesso!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php elseif ($success === 'excluido'): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> Pedido de compra excluído com sucesso!
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
            <div class="kpi-icon" style="background:#dbeafe;">
                <i class="fas fa-file-alt" style="color:#1e40af;"></i>
            </div>
            <div>
                <div class="kpi-val" style="color:#1e40af;"><?= $fmtN($kpis->total ?? 0) ?></div>
                <div class="kpi-lbl">Total de Pedidos</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fef3c7;">
                <i class="fas fa-clock" style="color:#d97706;"></i>
            </div>
            <div>
                <div class="kpi-val" style="color:#d97706;"><?= $fmtN($kpis->confirmado ?? 0) ?></div>
                <div class="kpi-lbl">Confirmados</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#d1fae5;">
                <i class="fas fa-check-circle" style="color:#059669;"></i>
            </div>
            <div>
                <div class="kpi-val" style="color:#059669;"><?= $fmtN($kpis->recebido ?? 0) ?></div>
                <div class="kpi-lbl">Recebidos</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#ede9fe;">
                <i class="fas fa-dollar-sign" style="color:#7c3aed;"></i>
            </div>
            <div>
                <div class="kpi-val" style="color:#7c3aed; font-size:18px;"><?= $fmt($kpis->valor_mes ?? 0) ?></div>
                <div class="kpi-lbl">Valor no Mês</div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="filtro-bar">
    <form method="GET" action="/estoque/compras" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-semibold text-muted mb-1">Pesquisar</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-start-0"
                    placeholder="Nº pedido, fornecedor ou NF-e..."
                    value="<?= $esc($filtros['q'] ?? '') ?>">
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($statusConfig as $val => $cfg): ?>
                <option value="<?= $val ?>" <?= ($filtros['status'] ?? '') === $val ? 'selected' : '' ?>>
                    <?= $cfg['label'] ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Data Início</label>
            <input type="date" name="data_inicio" class="form-control form-control-sm"
                value="<?= $esc($filtros['data_inicio'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Data Fim</label>
            <input type="date" name="data_fim" class="form-control form-control-sm"
                value="<?= $esc($filtros['data_fim'] ?? '') ?>">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-fill">
                <i class="fas fa-filter me-1"></i> Filtrar
            </button>
            <a href="/estoque/compras" class="btn btn-outline-secondary btn-sm" title="Limpar filtros">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Cabeçalho da tabela com botão Novo -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold text-muted mb-0">
        <?= count($pedidos ?? []) ?> pedido(s) encontrado(s)
    </h6>
    <a href="/estoque/compras/create" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i> Novo Pedido
    </a>
</div>

<!-- Tabela -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (!empty($pedidos)): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 pc-table">
                <thead>
                    <tr>
                        <th>Nº PEDIDO</th>
                        <th>DATA</th>
                        <th>FORNECEDOR</th>
                        <th>NF-e</th>
                        <th class="text-center">ITENS</th>
                        <th class="text-end">VALOR TOTAL</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center">AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $p):
                        $st  = $p->status ?? 'rascunho';
                        $cfg = $statusConfig[$st] ?? $statusConfig['rascunho'];
                    ?>
                    <tr>
                        <td>
                            <a href="/estoque/compras/<?= (int)$p->id ?>" class="fw-bold text-primary text-decoration-none">
                                <?= $esc($p->numero ?? '#' . $p->id) ?>
                            </a>
                        </td>
                        <td class="text-nowrap">
                            <?= !empty($p->data_pedido) ? date('d/m/Y', strtotime($p->data_pedido)) : '—' ?>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= $esc($p->fornecedor_nome ?? '—') ?></div>
                            <?php if (!empty($p->fornecedor_cnpj)): ?>
                            <div class="small text-muted"><?= $esc($p->fornecedor_cnpj) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small">
                            <?= $esc($p->nfe_numero ?? '—') ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border"><?= (int)($p->total_itens ?? 0) ?></span>
                        </td>
                        <td class="text-end fw-bold">
                            <?= $fmt($p->valor_total ?? 0) ?>
                        </td>
                        <td class="text-center">
                            <span class="badge-status"
                                style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;">
                                <?= $cfg['label'] ?>
                            </span>
                        </td>
                        <td class="text-center text-nowrap">
                            <a href="/estoque/compras/<?= (int)$p->id ?>"
                               class="text-secondary me-2" title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (in_array($st, ['rascunho', 'confirmado'])): ?>
                            <a href="/estoque/compras/<?= (int)$p->id ?>/edit"
                               class="text-primary me-2" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($st === 'rascunho'): ?>
                            <a href="#" class="text-danger" title="Excluir"
                               onclick="confirmarExclusao(<?= (int)$p->id ?>, '<?= $esc($p->numero ?? $p->id) ?>'); return false;">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-3x text-muted mb-3 d-block"></i>
            <p class="text-muted mb-1">Nenhum pedido de compra encontrado.</p>
            <a href="/estoque/compras/create" class="btn btn-primary btn-sm mt-2">
                <i class="fas fa-plus me-1"></i> Criar Primeiro Pedido
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Excluir Pedido</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-0">
                    Deseja excluir o pedido <strong id="modalPedidoNum"></strong>?
                    Esta ação não pode ser desfeita.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <form id="formExcluir" method="POST" action="" class="d-inline">
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash me-1"></i> Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id, numero) {
    document.getElementById('modalPedidoNum').textContent = numero;
    document.getElementById('formExcluir').action = '/estoque/compras/' + id + '/delete';
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}
</script>
