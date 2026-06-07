<?php
/**
 * ERP InLaudo - Aba Histórico do Cliente
 * Auditoria completa: Propostas CRM, Pedidos de Venda e Ordens de Serviço
 */
$clienteId  = $cliente->id ?? null;
$propostas  = $historicoPropostas  ?? [];
$pedidos    = $historicoPedidos    ?? [];
$ordens     = $historicoOrdens     ?? [];

// Totalizadores
$totalPropostas = count($propostas);
$totalPedidos   = count($pedidos);
$totalOrdens    = count($ordens);
$valorPropostas = array_sum(array_map(fn($p) => (float)($p->valor_total ?? 0), $propostas));
$valorPedidos   = array_sum(array_map(fn($p) => (float)($p->valor_total ?? 0), $pedidos));

// Helpers de badge de status
function badgeStatus(string $status, string $tipo = 'proposta'): string {
    $map = [
        // Propostas
        'gerada'       => ['bg-secondary',  'Gerada'],
        'enviada'      => ['bg-info text-dark', 'Enviada'],
        'visualizada'  => ['bg-primary',    'Visualizada'],
        'aceita'       => ['bg-success',    'Aceita'],
        'recusada'     => ['bg-danger',     'Recusada'],
        'cancelada'    => ['bg-danger',     'Cancelada'],
        'expirada'     => ['bg-warning text-dark', 'Expirada'],
        // Pedidos
        'pendente'     => ['bg-secondary',  'Pendente'],
        'confirmado'   => ['bg-primary',    'Confirmado'],
        'faturado'     => ['bg-success',    'Faturado'],
        'entregue'     => ['bg-success',    'Entregue'],
        // O.S
        'aberta'       => ['bg-warning text-dark', 'Aberta'],
        'em_andamento' => ['bg-info text-dark', 'Em Andamento'],
        'concluida'    => ['bg-success',    'Concluída'],
        'faturada'     => ['bg-success',    'Faturada'],
        'cancelada'    => ['bg-danger',     'Cancelada'],
    ];
    $cfg = $map[$status] ?? ['bg-secondary', ucfirst($status)];
    return "<span class=\"badge {$cfg[0]}\">{$cfg[1]}</span>";
}
?>

<!-- KPIs do histórico -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-primary"><?= $totalPropostas ?></div>
            <div class="text-muted small">Propostas</div>
            <div class="text-success small fw-semibold">R$ <?= number_format($valorPropostas, 2, ',', '.') ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-success"><?= $totalPedidos ?></div>
            <div class="text-muted small">Pedidos de Venda</div>
            <div class="text-success small fw-semibold">R$ <?= number_format($valorPedidos, 2, ',', '.') ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-warning"><?= $totalOrdens ?></div>
            <div class="text-muted small">Ordens de Serviço</div>
            <div class="text-muted small">—</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-info"><?= $totalPropostas + $totalPedidos + $totalOrdens ?></div>
            <div class="text-muted small">Total de Interações</div>
            <div class="text-success small fw-semibold">R$ <?= number_format($valorPropostas + $valorPedidos, 2, ',', '.') ?></div>
        </div>
    </div>
</div>

<!-- Tabs internas do histórico -->
<ul class="nav nav-tabs mb-3" id="histTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#histPropostas" type="button">
            <i class="fas fa-file-alt me-1"></i> Propostas
            <?php if ($totalPropostas): ?><span class="badge bg-primary ms-1"><?= $totalPropostas ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#histPedidos" type="button">
            <i class="fas fa-shopping-cart me-1"></i> Pedidos de Venda
            <?php if ($totalPedidos): ?><span class="badge bg-success ms-1"><?= $totalPedidos ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#histOrdens" type="button">
            <i class="fas fa-wrench me-1"></i> Ordens de Serviço
            <?php if ($totalOrdens): ?><span class="badge bg-warning text-dark ms-1"><?= $totalOrdens ?></span><?php endif; ?>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#histTimeline" type="button">
            <i class="fas fa-stream me-1"></i> Timeline Completa
        </button>
    </li>
</ul>

<div class="tab-content" id="histTabsContent">

    <!-- Propostas -->
    <div class="tab-pane fade show active" id="histPropostas">
        <?php if (empty($propostas)): ?>
        <div class="text-center text-muted py-4">
            <i class="fas fa-file-alt fa-2x mb-2 d-block opacity-25"></i>
            Nenhuma proposta encontrada para este cliente.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Número</th>
                        <th>Título</th>
                        <th>Status</th>
                        <th>Valor Total</th>
                        <th>Validade</th>
                        <th>Data</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($propostas as $p): ?>
                    <tr>
                        <td><span class="fw-semibold text-primary"><?= htmlspecialchars($p->numero ?? '—') ?></span></td>
                        <td><?= htmlspecialchars($p->titulo ?? '—') ?></td>
                        <td><?= badgeStatus($p->status ?? 'gerada', 'proposta') ?></td>
                        <td class="fw-semibold">R$ <?= number_format((float)($p->valor_total ?? 0), 2, ',', '.') ?></td>
                        <td><?= !empty($p->validade_proposta) ? date('d/m/Y', strtotime($p->validade_proposta)) : '—' ?></td>
                        <td class="text-muted small"><?= !empty($p->created_at) ? date('d/m/Y H:i', strtotime($p->created_at)) : '—' ?></td>
                        <td class="text-end">
                            <a href="/crm/propostas/<?= $p->id ?>" class="btn btn-xs btn-outline-primary" title="Ver proposta">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="fw-bold text-end">Total:</td>
                        <td class="fw-bold text-success">R$ <?= number_format($valorPropostas, 2, ',', '.') ?></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pedidos de Venda -->
    <div class="tab-pane fade" id="histPedidos">
        <?php if (empty($pedidos)): ?>
        <div class="text-center text-muted py-4">
            <i class="fas fa-shopping-cart fa-2x mb-2 d-block opacity-25"></i>
            Nenhum pedido de venda encontrado para este cliente.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Número</th>
                        <th>Status</th>
                        <th>Itens</th>
                        <th>Valor Total</th>
                        <th>Faturamento</th>
                        <th>Data</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td><span class="fw-semibold text-success"><?= htmlspecialchars($p->numero ?? '—') ?></span></td>
                        <td><?= badgeStatus($p->status ?? 'pendente', 'pedido') ?></td>
                        <td class="text-center"><?= (int)($p->total_itens ?? 0) ?></td>
                        <td class="fw-semibold">R$ <?= number_format((float)($p->valor_total ?? 0), 2, ',', '.') ?></td>
                        <td><?= !empty($p->data_faturamento) ? date('d/m/Y', strtotime($p->data_faturamento)) : '—' ?></td>
                        <td class="text-muted small"><?= !empty($p->created_at) ? date('d/m/Y H:i', strtotime($p->created_at)) : '—' ?></td>
                        <td class="text-end">
                            <a href="/estoque/vendas/<?= $p->id ?>" class="btn btn-xs btn-outline-success" title="Ver pedido">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="fw-bold text-end">Total:</td>
                        <td class="fw-bold text-success">R$ <?= number_format($valorPedidos, 2, ',', '.') ?></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Ordens de Serviço -->
    <div class="tab-pane fade" id="histOrdens">
        <?php if (empty($ordens)): ?>
        <div class="text-center text-muted py-4">
            <i class="fas fa-wrench fa-2x mb-2 d-block opacity-25"></i>
            Nenhuma ordem de serviço encontrada para este cliente.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Número</th>
                        <th>Tipo</th>
                        <th>Equipamento</th>
                        <th>Status</th>
                        <th>Valor Total</th>
                        <th>Abertura</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordens as $os): ?>
                    <tr>
                        <td><span class="fw-semibold text-warning"><?= htmlspecialchars($os->numero ?? '—') ?></span></td>
                        <td>
                            <span class="badge <?= ($os->tipo ?? '') === 'preventiva' ? 'bg-info text-dark' : 'bg-danger' ?>">
                                <?= ucfirst($os->tipo ?? '—') ?>
                            </span>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($os->produto_nome ?? '—') ?></div>
                            <?php if (!empty($os->numero_serie)): ?>
                            <small class="text-muted">S/N: <?= htmlspecialchars($os->numero_serie) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= badgeStatus($os->status ?? 'aberta', 'os') ?></td>
                        <td class="fw-semibold">R$ <?= number_format((float)($os->valor_total ?? 0), 2, ',', '.') ?></td>
                        <td class="text-muted small"><?= !empty($os->data_abertura) ? date('d/m/Y', strtotime($os->data_abertura)) : '—' ?></td>
                        <td class="text-end">
                            <a href="/manutencao/ordens/<?= $os->id ?>" class="btn btn-xs btn-outline-warning" title="Ver O.S">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Timeline Completa -->
    <div class="tab-pane fade" id="histTimeline">
        <?php
        // Montar timeline unificada
        $timeline = [];
        foreach ($propostas as $p) {
            $timeline[] = [
                'data'  => $p->created_at ?? '',
                'tipo'  => 'proposta',
                'icon'  => 'fas fa-file-alt',
                'color' => 'primary',
                'titulo'=> 'Proposta ' . ($p->numero ?? ''),
                'desc'  => ($p->titulo ?? '') . ' — R$ ' . number_format((float)($p->valor_total ?? 0), 2, ',', '.'),
                'status'=> $p->status ?? '',
                'link'  => '/crm/propostas/' . $p->id,
            ];
        }
        foreach ($pedidos as $p) {
            $timeline[] = [
                'data'  => $p->created_at ?? '',
                'tipo'  => 'pedido',
                'icon'  => 'fas fa-shopping-cart',
                'color' => 'success',
                'titulo'=> 'Pedido ' . ($p->numero ?? ''),
                'desc'  => (int)($p->total_itens ?? 0) . ' item(ns) — R$ ' . number_format((float)($p->valor_total ?? 0), 2, ',', '.'),
                'status'=> $p->status ?? '',
                'link'  => '/estoque/vendas/' . $p->id,
            ];
        }
        foreach ($ordens as $os) {
            $timeline[] = [
                'data'  => $os->created_at ?? '',
                'tipo'  => 'os',
                'icon'  => 'fas fa-wrench',
                'color' => 'warning',
                'titulo'=> 'O.S ' . ($os->numero ?? ''),
                'desc'  => ucfirst($os->tipo ?? '') . ' — ' . ($os->produto_nome ?? ''),
                'status'=> $os->status ?? '',
                'link'  => '/manutencao/ordens/' . $os->id,
            ];
        }
        usort($timeline, fn($a, $b) => strcmp($b['data'], $a['data']));
        ?>
        <?php if (empty($timeline)): ?>
        <div class="text-center text-muted py-4">
            <i class="fas fa-stream fa-2x mb-2 d-block opacity-25"></i>
            Nenhuma interação registrada para este cliente.
        </div>
        <?php else: ?>
        <div class="timeline-hist">
            <?php foreach ($timeline as $item): ?>
            <div class="timeline-hist-item">
                <div class="timeline-hist-icon bg-<?= $item['color'] ?>">
                    <i class="<?= $item['icon'] ?> text-white"></i>
                </div>
                <div class="timeline-hist-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <a href="<?= $item['link'] ?>" class="fw-semibold text-decoration-none">
                                <?= htmlspecialchars($item['titulo']) ?>
                            </a>
                            <div class="text-muted small"><?= htmlspecialchars($item['desc']) ?></div>
                        </div>
                        <div class="text-end ms-3">
                            <?= badgeStatus($item['status'], $item['tipo']) ?>
                            <div class="text-muted small mt-1">
                                <?= !empty($item['data']) ? date('d/m/Y H:i', strtotime($item['data'])) : '—' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /tab-content -->

<style>
.timeline-hist { position: relative; padding-left: 2.5rem; }
.timeline-hist::before { content: ''; position: absolute; left: 1rem; top: 0; bottom: 0; width: 2px; background: #e2e8f0; }
.timeline-hist-item { position: relative; margin-bottom: 1.25rem; }
.timeline-hist-icon { position: absolute; left: -2rem; width: 2rem; height: 2rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .75rem; }
.timeline-hist-body { background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem; padding: .75rem 1rem; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
.btn-xs { padding: .2rem .45rem; font-size: .75rem; }
</style>
