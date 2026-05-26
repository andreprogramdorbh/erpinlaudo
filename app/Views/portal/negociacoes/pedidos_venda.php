<?php
/**
 * Portal do Cliente — Negociações > Pedidos de Venda
 */
$statusLabels = [
    'rascunho'   => ['label' => 'Rascunho',    'class' => 'bg-secondary'],
    'confirmado' => ['label' => 'Confirmado',   'class' => 'bg-primary'],
    'em_separacao' => ['label' => 'Em Separação', 'class' => 'bg-info text-dark'],
    'enviado'    => ['label' => 'Enviado',      'class' => 'bg-warning text-dark'],
    'entregue'   => ['label' => 'Entregue',     'class' => 'bg-success'],
    'cancelado'  => ['label' => 'Cancelado',    'class' => 'bg-danger'],
    'faturado'   => ['label' => 'Faturado',     'class' => 'bg-dark'],
];
?>
<div class="portal-page-header">
    <div>
        <h1 class="portal-page-title"><i class="fa fa-shopping-cart me-2 text-primary"></i>Meus Pedidos de Venda</h1>
        <p class="portal-page-subtitle">Acompanhe o status dos seus pedidos</p>
    </div>
</div>

<!-- Filtro de status -->
<div class="portal-info-card mb-4 p-3">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="text-muted small fw-semibold me-2">Filtrar por status:</span>
        <a href="/portal/negociacoes/pedidos-venda"
           class="btn btn-sm <?php echo empty($statusFiltro) ? 'btn-primary' : 'btn-outline-secondary'; ?>">
            Todos
        </a>
        <?php foreach ($statusLabels as $key => $info): ?>
        <a href="/portal/negociacoes/pedidos-venda?status=<?php echo $key; ?>"
           class="btn btn-sm <?php echo $statusFiltro === $key ? 'btn-primary' : 'btn-outline-secondary'; ?>">
            <?php echo $info['label']; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (empty($pedidos)): ?>
<div class="portal-info-card text-center py-5">
    <div style="font-size:48px;margin-bottom:12px">🛒</div>
    <h5 class="fw-semibold text-muted">Nenhum pedido encontrado</h5>
    <p class="text-muted small">Quando uma proposta for aceita e convertida em pedido, ele aparecerá aqui.</p>
</div>
<?php else: ?>
<div class="portal-info-card p-0" style="overflow:hidden">
    <div class="table-responsive">
        <table style="width:100%;border-collapse:collapse;font-size:.9rem">
            <thead style="background:#f8fafc">
                <tr>
                    <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Pedido</th>
                    <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Data</th>
                    <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Itens</th>
                    <th style="padding:12px 16px;text-align:right;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Valor Total</th>
                    <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Previsão Entrega</th>
                    <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Status</th>
                    <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Pagamento</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pedidos as $ped):
                $st    = $ped->status ?? 'confirmado';
                $badge = $statusLabels[$st] ?? ['label' => ucfirst($st), 'class' => 'bg-secondary'];
                $pagLabels = [
                    'pendente'  => ['label' => 'Pendente',  'class' => 'text-warning'],
                    'pago'      => ['label' => 'Pago',      'class' => 'text-success'],
                    'parcial'   => ['label' => 'Parcial',   'class' => 'text-info'],
                    'cancelado' => ['label' => 'Cancelado', 'class' => 'text-danger'],
                ];
                $pagSt  = $ped->status_pagamento ?? 'pendente';
                $pagBadge = $pagLabels[$pagSt] ?? ['label' => ucfirst($pagSt), 'class' => 'text-muted'];
            ?>
            <tr style="border-bottom:1px solid #f3f4f6">
                <td style="padding:12px 16px;font-weight:600;color:#1a56db">
                    <?php echo htmlspecialchars($ped->numero ?? '#' . $ped->id); ?>
                    <?php if (!empty($ped->proposta_numero)): ?>
                    <div class="text-muted small">Proposta: <?php echo htmlspecialchars($ped->proposta_numero); ?></div>
                    <?php endif; ?>
                </td>
                <td style="padding:12px 16px;text-align:center;color:#6b7280">
                    <?php echo !empty($ped->data_pedido) ? date('d/m/Y', strtotime($ped->data_pedido)) : '—'; ?>
                </td>
                <td style="padding:12px 16px;text-align:center;color:#6b7280">
                    <?php echo (int)($ped->total_itens ?? 0); ?>
                </td>
                <td style="padding:12px 16px;text-align:right;font-weight:600">
                    R$ <?php echo number_format((float)($ped->total ?? 0), 2, ',', '.'); ?>
                </td>
                <td style="padding:12px 16px;text-align:center;color:#6b7280">
                    <?php echo !empty($ped->previsao_entrega) ? date('d/m/Y', strtotime($ped->previsao_entrega)) : '—'; ?>
                </td>
                <td style="padding:12px 16px;text-align:center">
                    <span class="badge <?php echo $badge['class']; ?>"><?php echo $badge['label']; ?></span>
                </td>
                <td style="padding:12px 16px;text-align:center">
                    <span class="fw-semibold <?php echo $pagBadge['class']; ?>">
                        <?php echo $pagBadge['label']; ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
