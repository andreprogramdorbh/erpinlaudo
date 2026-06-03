<?php
$esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$fmt = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$fmtQ = fn($v) => number_format((float)$v, 3, ',', '.');

$statusConfig = [
    'rascunho'   => ['label' => 'Rascunho',   'bg' => '#f3f4f6', 'color' => '#374151', 'icon' => 'fa-pencil-alt'],
    'confirmado' => ['label' => 'Confirmado',  'bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => 'fa-check'],
    'recebido'   => ['label' => 'Recebido',    'bg' => '#d1fae5', 'color' => '#065f46', 'icon' => 'fa-check-double'],
    'cancelado'  => ['label' => 'Cancelado',   'bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'fa-times'],
];

$st  = $pedido->status ?? 'rascunho';
$cfg = $statusConfig[$st] ?? $statusConfig['rascunho'];
$success = $_GET['success'] ?? '';
?>
<style>
.info-card { background:#fff; border-radius:12px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:16px; }
.info-label { font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#9ca3af; font-weight:600; }
.info-value { font-size:14px; color:#111827; font-weight:500; margin-top:2px; }
.itens-table th { background:#f8fafc; font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:#6b7280; font-weight:600; padding:10px 14px; }
.itens-table td { padding:11px 14px; vertical-align:middle; }
.badge-status { display:inline-block; padding:5px 14px; border-radius:20px; font-size:12px; font-weight:600; }
.timeline-item { display:flex; gap:12px; padding:10px 0; border-bottom:1px solid #f3f4f6; }
.timeline-item:last-child { border-bottom:none; }
.timeline-dot { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:12px; }
</style>

<?php if ($success === 'recebido'): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i> Pedido marcado como recebido e estoque atualizado!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php elseif ($success === 'cancelado'): ?>
<div class="alert alert-warning alert-dismissible fade show">
    <i class="fas fa-ban me-2"></i> Pedido cancelado com sucesso.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Cabeçalho -->
<div class="d-flex align-items-start gap-3 mb-4">
    <div style="width:52px;height:52px;border-radius:12px;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="fas fa-shopping-cart" style="color:#1e40af;font-size:22px;"></i>
    </div>
    <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <h4 class="mb-0">Pedido de Compra <?= $esc($pedido->numero ?? '#' . $pedido->id) ?></h4>
            <span class="badge-status" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;">
                <i class="fas <?= $cfg['icon'] ?> me-1"></i><?= $cfg['label'] ?>
            </span>
        </div>
        <small class="text-muted">
            Criado em <?= !empty($pedido->created_at) ? date('d/m/Y H:i', strtotime($pedido->created_at)) : '—' ?>
        </small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (in_array($st, ['rascunho', 'confirmado'])): ?>
        <a href="/estoque/compras/<?= (int)$pedido->id ?>/edit" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-edit me-1"></i> Editar
        </a>
        <?php endif; ?>
        <?php if ($st === 'confirmado'): ?>
        <form method="POST" action="/estoque/compras/<?= (int)$pedido->id ?>/receber" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= $esc($_SESSION['csrf_token'] ?? '') ?>">
            <button type="submit" class="btn btn-success btn-sm"
                onclick="return confirm('Confirmar recebimento e dar entrada no estoque?')">
                <i class="fas fa-check-double me-1"></i> Confirmar Recebimento
            </button>
        </form>
        <?php endif; ?>
        <?php if (in_array($st, ['rascunho', 'confirmado'])): ?>
        <form method="POST" action="/estoque/compras/<?= (int)$pedido->id ?>/cancelar" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= $esc($_SESSION['csrf_token'] ?? '') ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm"
                onclick="return confirm('Cancelar este pedido de compra?')">
                <i class="fas fa-ban me-1"></i> Cancelar
            </button>
        </form>
        <?php endif; ?>
        <a href="/estoque/compras" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
    </div>
</div>

<div class="row g-3">
    <!-- Coluna principal -->
    <div class="col-lg-8">
        <!-- Dados do fornecedor -->
        <div class="info-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-truck me-2 text-muted"></i>Fornecedor</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="info-label">Nome</div>
                    <div class="info-value"><?= $esc($pedido->fornecedor_nome ?? '—') ?></div>
                </div>
                <div class="col-md-3">
                    <div class="info-label">CNPJ / CPF</div>
                    <div class="info-value"><?= $esc($pedido->fornecedor_cnpj ?? '—') ?></div>
                </div>
                <div class="col-md-3">
                    <div class="info-label">NF-e</div>
                    <div class="info-value"><?= $esc($pedido->nfe_numero ?? '—') ?></div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Data do Pedido</div>
                    <div class="info-value">
                        <?= !empty($pedido->data_pedido) ? date('d/m/Y', strtotime($pedido->data_pedido)) : '—' ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Previsão de Entrega</div>
                    <div class="info-value">
                        <?= !empty($pedido->data_previsao) ? date('d/m/Y', strtotime($pedido->data_previsao)) : '—' ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-label">Condição de Pagamento</div>
                    <div class="info-value"><?= $esc($pedido->condicao_pagamento ?? '—') ?></div>
                </div>
                <?php if (!empty($pedido->observacoes)): ?>
                <div class="col-12">
                    <div class="info-label">Observações</div>
                    <div class="info-value"><?= $esc($pedido->observacoes) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Itens -->
        <div class="info-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-list me-2 text-muted"></i>Itens do Pedido</h6>
            <?php if (!empty($pedido->itens)): ?>
            <div class="table-responsive">
                <table class="table itens-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>PRODUTO / DESCRIÇÃO</th>
                            <th class="text-center">QTD</th>
                            <th class="text-center">UNID.</th>
                            <th class="text-end">PREÇO UNIT.</th>
                            <th class="text-end">TOTAL</th>
                            <th>LOTE / VALIDADE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedido->itens as $item): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= $esc($item->descricao ?? $item->produto_nome ?? '—') ?></div>
                                <?php if (!empty($item->produto_nome) && !empty($item->descricao) && $item->descricao !== $item->produto_nome): ?>
                                <div class="small text-muted"><?= $esc($item->produto_nome) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= $fmtQ($item->quantidade ?? 0) ?></td>
                            <td class="text-center text-muted"><?= $esc($item->unidade ?? 'UN') ?></td>
                            <td class="text-end"><?= $fmt($item->preco_unitario ?? 0) ?></td>
                            <td class="text-end fw-bold"><?= $fmt($item->valor_total ?? ($item->quantidade * $item->preco_unitario)) ?></td>
                            <td class="small text-muted">
                                <?php if (!empty($item->lote)): ?>
                                <div>Lote: <?= $esc($item->lote) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item->data_validade)): ?>
                                <div>Val: <?= date('d/m/Y', strtotime($item->data_validade)) ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted small mb-0">Nenhum item registrado.</p>
            <?php endif; ?>
        </div>

        <!-- Movimentações geradas -->
        <?php if (!empty($movs)): ?>
        <div class="info-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-exchange-alt me-2 text-muted"></i>Movimentações de Estoque Geradas</h6>
            <?php foreach ($movs as $m): ?>
            <div class="timeline-item">
                <div class="timeline-dot" style="background:#d1fae5;">
                    <i class="fas fa-arrow-down" style="color:#059669;"></i>
                </div>
                <div>
                    <div class="fw-semibold small"><?= $esc($m->produto_nome ?? 'Produto #' . $m->produto_id) ?></div>
                    <div class="small text-muted">
                        Entrada de <?= $fmtQ($m->quantidade) ?> <?= $esc($m->unidade ?? 'UN') ?>
                        — <?= !empty($m->created_at) ? date('d/m/Y H:i', strtotime($m->created_at)) : '—' ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Coluna lateral — totais -->
    <div class="col-lg-4">
        <div class="info-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-calculator me-2 text-muted"></i>Resumo Financeiro</h6>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">Subtotal Produtos:</span>
                <span><?= $fmt($pedido->valor_produtos ?? 0) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">Frete:</span>
                <span><?= $fmt($pedido->valor_frete ?? 0) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span class="text-muted small">Desconto:</span>
                <span class="text-danger">- <?= $fmt($pedido->valor_desconto ?? 0) ?></span>
            </div>
            <div class="d-flex justify-content-between border-top pt-3">
                <span class="fw-bold">TOTAL:</span>
                <strong style="font-size:20px;color:#1e40af;"><?= $fmt($pedido->valor_total ?? 0) ?></strong>
            </div>
        </div>
    </div>
</div>
