<?php
/**
 * Aba de Parcelas — Contas a Receber
 * Exibe todas as parcelas do grupo vinculado a esta conta.
 */
$conta          = $conta ?? null;
$parcelas       = $parcelas ?? [];
$resumoParcelas = $resumoParcelas ?? null;
$hoje           = date('Y-m-d');
?>

<style>
.parcelas-timeline { list-style: none; padding: 0; margin: 0; }
.parcela-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px; border-radius: 8px; margin-bottom: 6px;
    border: 1px solid #e5e7eb; background: #fff;
    transition: box-shadow .15s;
}
.parcela-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.parcela-item.atual { border-left: 4px solid #3b82f6; background: #eff6ff; }
.parcela-item.paga  { border-left: 4px solid #22c55e; background: #f0fdf4; opacity: .85; }
.parcela-item.atrasada { border-left: 4px solid #ef4444; background: #fef2f2; }
.parcela-num {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .85rem; flex-shrink: 0;
}
.parcela-num.paga     { background: #dcfce7; color: #16a34a; }
.parcela-num.atrasada { background: #fee2e2; color: #dc2626; }
.parcela-num.aberta   { background: #dbeafe; color: #2563eb; }
.parcela-num.atual    { background: #3b82f6; color: #fff; }
.parcela-info { flex: 1; min-width: 0; }
.parcela-desc { font-weight: 600; font-size: .92rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.parcela-venc { font-size: .8rem; color: #6b7280; }
.parcela-valor { font-weight: 700; font-size: 1rem; white-space: nowrap; }
.parcela-status-badge { font-size: .75rem; padding: 2px 8px; border-radius: 12px; white-space: nowrap; }
.parcela-link { color: #3b82f6; text-decoration: none; font-size: .8rem; white-space: nowrap; }
.parcela-link:hover { text-decoration: underline; }
.resumo-bar { height: 8px; border-radius: 4px; background: #e5e7eb; overflow: hidden; margin-top: 4px; }
.resumo-bar-fill { height: 100%; background: #22c55e; border-radius: 4px; transition: width .4s; }
</style>

<section class="form-section">
    <h3 class="form-section-title">
        <i class="fas fa-list-ol me-2"></i>
        Parcelas do Grupo
    </h3>

    <?php if (empty($parcelas)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Esta conta não possui parcelas vinculadas. Para gerar parcelas, crie uma nova conta com
            <strong>Recorrente = Sim</strong> e informe o <strong>Total de Parcelas</strong>.
        </div>
    <?php else: ?>

        <?php
        $total    = count($parcelas);
        $pagas    = 0;
        $vencidas = 0;
        $valorTotal = 0;
        $valorPago  = 0;
        foreach ($parcelas as $p) {
            $valorTotal += (float)($p->valor ?? 0);
            if (($p->status ?? '') === 'recebida') { $pagas++; $valorPago += (float)($p->valor ?? 0); }
            if (($p->status ?? '') === 'aberta' && ($p->data_vencimento ?? '') < $hoje) $vencidas++;
        }
        $pct = $total > 0 ? round(($pagas / $total) * 100) : 0;
        ?>

        <!-- Resumo -->
        <div class="row g-3 mb-4">
            <div class="col-sm-3">
                <div class="card border-0 bg-light text-center p-3">
                    <div class="fw-bold fs-4"><?php echo $total; ?></div>
                    <div class="text-muted small">Total de Parcelas</div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="card border-0 bg-success bg-opacity-10 text-center p-3">
                    <div class="fw-bold fs-4 text-success"><?php echo $pagas; ?></div>
                    <div class="text-muted small">Pagas</div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="card border-0 <?php echo $vencidas > 0 ? 'bg-danger bg-opacity-10' : 'bg-light'; ?> text-center p-3">
                    <div class="fw-bold fs-4 <?php echo $vencidas > 0 ? 'text-danger' : ''; ?>"><?php echo $vencidas; ?></div>
                    <div class="text-muted small">Vencidas</div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="card border-0 bg-primary bg-opacity-10 text-center p-3">
                    <div class="fw-bold fs-5 text-primary">R$ <?php echo number_format($valorTotal, 2, ',', '.'); ?></div>
                    <div class="text-muted small">Valor Total</div>
                </div>
            </div>
        </div>

        <!-- Barra de progresso -->
        <div class="mb-4">
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Progresso de pagamento</span>
                <span><?php echo $pct; ?>% pago (R$ <?php echo number_format($valorPago, 2, ',', '.'); ?>)</span>
            </div>
            <div class="resumo-bar">
                <div class="resumo-bar-fill" style="width:<?php echo $pct; ?>%"></div>
            </div>
        </div>

        <!-- Lista de parcelas -->
        <ul class="parcelas-timeline">
            <?php foreach ($parcelas as $p):
                $isAtual  = (int)($p->id ?? 0) === (int)($conta->id ?? 0);
                $isPaga   = ($p->status ?? '') === 'recebida';
                $isAtraso = !$isPaga && ($p->data_vencimento ?? '') < $hoje;
                $numParcela = (int)($p->numero_parcela ?? 0);
                $totalParcelas = (int)($p->total_parcelas ?? 0);

                if ($isAtual) $cls = 'atual';
                elseif ($isPaga) $cls = 'paga';
                elseif ($isAtraso) $cls = 'atrasada';
                else $cls = '';

                $numCls = $isAtual ? 'atual' : ($isPaga ? 'paga' : ($isAtraso ? 'atrasada' : 'aberta'));
            ?>
            <li class="parcela-item <?php echo $cls; ?>">
                <div class="parcela-num <?php echo $numCls; ?>">
                    <?php if ($isPaga): ?>
                        <i class="fas fa-check"></i>
                    <?php elseif ($isAtraso): ?>
                        <i class="fas fa-exclamation"></i>
                    <?php else: ?>
                        <?php echo $numParcela ?: '—'; ?>
                    <?php endif; ?>
                </div>

                <div class="parcela-info">
                    <div class="parcela-desc">
                        <?php echo htmlspecialchars($p->descricao ?? ''); ?>
                        <?php if ($numParcela && $totalParcelas): ?>
                            <span class="text-muted fw-normal">(<?php echo $numParcela; ?>/<?php echo $totalParcelas; ?>)</span>
                        <?php endif; ?>
                        <?php if ($isAtual): ?>
                            <span class="badge bg-primary ms-1" style="font-size:.7rem">Esta</span>
                        <?php endif; ?>
                    </div>
                    <div class="parcela-venc">
                        Vencimento: <?php echo $p->data_vencimento ? date('d/m/Y', strtotime($p->data_vencimento)) : '—'; ?>
                        <?php if ($isPaga && !empty($p->data_recebimento)): ?>
                            &nbsp;|&nbsp; Recebido em: <?php echo date('d/m/Y', strtotime($p->data_recebimento)); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="parcela-valor">
                    R$ <?php echo number_format((float)($p->valor ?? 0), 2, ',', '.'); ?>
                </div>

                <?php
                $badgeClass = match($p->status ?? 'aberta') {
                    'recebida' => 'bg-success',
                    'cancelada' => 'bg-secondary',
                    default => $isAtraso ? 'bg-danger' : 'bg-warning text-dark',
                };
                $badgeLabel = match($p->status ?? 'aberta') {
                    'recebida' => 'Paga',
                    'cancelada' => 'Cancelada',
                    default => $isAtraso ? 'Vencida' : 'Aberta',
                };
                ?>
                <span class="badge <?php echo $badgeClass; ?> parcela-status-badge"><?php echo $badgeLabel; ?></span>

                <?php if (!$isAtual): ?>
                <a href="/financeiro/contas-a-receber/edit/<?php echo (int)$p->id; ?>?tab=geral"
                   class="parcela-link" title="Abrir parcela">
                    <i class="fas fa-external-link-alt"></i>
                </a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="mt-3 text-muted small">
            <i class="fas fa-info-circle me-1"></i>
            Grupo de parcelas: <code><?php echo htmlspecialchars($conta->grupo_parcelas ?? '—'); ?></code>
        </div>

    <?php endif; ?>
</section>
