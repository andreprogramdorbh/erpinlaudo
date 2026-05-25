<?php
/**
 * ERP InLaudo — Aba Histórico do Fornecedor
 * Exibe pedidos de compra, movimentações de entrada e resumo financeiro.
 */
$fornecedor    = $fornecedor    ?? null;
$historico     = $historico     ?? [];
$pedidos       = $historico['pedidos']       ?? [];
$movimentacoes = $historico['movimentacoes'] ?? [];
$resumo        = $historico['resumo']        ?? [];
?>

<div class="historico-fornecedor">

    <!-- Resumo em cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="text-primary mb-1"><i class="fas fa-shopping-cart fa-2x"></i></div>
                <div class="fs-4 fw-bold"><?php echo (int)($resumo['total_pedidos'] ?? 0); ?></div>
                <div class="text-muted small">Pedidos de Compra</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="text-success mb-1"><i class="fas fa-boxes fa-2x"></i></div>
                <div class="fs-4 fw-bold"><?php echo (int)($resumo['total_movimentacoes'] ?? 0); ?></div>
                <div class="text-muted small">Entradas de Estoque</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="text-warning mb-1"><i class="fas fa-dollar-sign fa-2x"></i></div>
                <div class="fs-4 fw-bold">
                    R$ <?php echo number_format((float)($resumo['valor_total_comprado'] ?? 0), 2, ',', '.'); ?>
                </div>
                <div class="text-muted small">Total Comprado</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="text-info mb-1"><i class="fas fa-calendar-check fa-2x"></i></div>
                <div class="fs-4 fw-bold">
                    <?php echo !empty($resumo['ultima_compra']) ? date('d/m/Y', strtotime($resumo['ultima_compra'])) : '—'; ?>
                </div>
                <div class="text-muted small">Última Compra</div>
            </div>
        </div>
    </div>

    <!-- Pedidos de Compra -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-shopping-cart text-primary me-2"></i>
                Pedidos de Compra
            </h5>
            <?php if (!empty($fornecedor->id)): ?>
            <a href="/estoque/pedidos-compra/create?fornecedor_id=<?php echo (int)$fornecedor->id; ?>"
               class="btn btn-sm btn-primary">
                <i class="fas fa-plus me-1"></i> Novo Pedido
            </a>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($pedidos)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    Nenhum pedido de compra encontrado para este fornecedor.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Número</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Itens</th>
                                <th class="text-end">Valor Total</th>
                                <th>NF-e</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($pedido->numero ?? ''); ?></strong></td>
                                <td><?php echo !empty($pedido->data_pedido) ? date('d/m/Y', strtotime($pedido->data_pedido)) : '—'; ?></td>
                                <td>
                                    <?php
                                    $statusMap = [
                                        'rascunho'              => ['secondary', 'Rascunho'],
                                        'enviado'               => ['info',      'Enviado'],
                                        'confirmado'            => ['primary',   'Confirmado'],
                                        'parcialmente_recebido' => ['warning',   'Parc. Recebido'],
                                        'recebido'              => ['success',   'Recebido'],
                                        'cancelado'             => ['danger',    'Cancelado'],
                                    ];
                                    $st = $statusMap[$pedido->status ?? ''] ?? ['secondary', $pedido->status ?? ''];
                                    ?>
                                    <span class="badge bg-<?php echo $st[0]; ?>"><?php echo $st[1]; ?></span>
                                </td>
                                <td><?php echo (int)($pedido->total_itens ?? 0); ?></td>
                                <td class="text-end">
                                    R$ <?php echo number_format((float)($pedido->valor_total ?? 0), 2, ',', '.'); ?>
                                </td>
                                <td>
                                    <?php if (!empty($pedido->nfe_numero)): ?>
                                        <span class="badge bg-light text-dark border">
                                            NF <?php echo htmlspecialchars($pedido->nfe_numero); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/estoque/pedidos-compra/<?php echo (int)$pedido->id; ?>"
                                       class="btn btn-sm btn-outline-primary" title="Ver pedido">
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
    </div>

    <!-- Movimentações de Estoque -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-boxes text-success me-2"></i>
                Entradas de Estoque (NF-e ou Manual)
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($movimentacoes)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    Nenhuma movimentação de entrada encontrada para este fornecedor.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Data</th>
                                <th>Produto</th>
                                <th>Origem</th>
                                <th>NF-e</th>
                                <th class="text-end">Qtd</th>
                                <th class="text-end">Valor Unit.</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimentacoes as $mov): ?>
                            <tr>
                                <td><?php echo !empty($mov->created_at) ? date('d/m/Y', strtotime($mov->created_at)) : '—'; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($mov->produto_nome ?? ''); ?></strong>
                                    <?php if (!empty($mov->produto_codigo)): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($mov->produto_codigo); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $origemMap = [
                                        'manual'        => ['secondary', 'Manual'],
                                        'xml_nfe'       => ['info',      'XML NF-e'],
                                        'pedido_compra' => ['primary',   'Pedido Compra'],
                                    ];
                                    $ori = $origemMap[$mov->origem ?? ''] ?? ['secondary', $mov->origem ?? ''];
                                    ?>
                                    <span class="badge bg-<?php echo $ori[0]; ?>"><?php echo $ori[1]; ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($mov->nfe_numero)): ?>
                                        <span class="text-muted small">NF <?php echo htmlspecialchars($mov->nfe_numero); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php echo number_format((float)($mov->quantidade ?? 0), 2, ',', '.'); ?>
                                    <?php echo htmlspecialchars($mov->unidade ?? 'UN'); ?>
                                </td>
                                <td class="text-end">
                                    R$ <?php echo number_format((float)($mov->preco_unitario ?? 0), 2, ',', '.'); ?>
                                </td>
                                <td class="text-end">
                                    <strong>R$ <?php echo number_format((float)($mov->valor_total ?? 0), 2, ',', '.'); ?></strong>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
