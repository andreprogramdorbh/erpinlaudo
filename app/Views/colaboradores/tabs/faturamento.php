<?php
/**
 * Aba: Faturamento do Colaborador (Contas a Receber vinculadas)
 */
use App\Core\UI;
$faturamentos = $faturamentos ?? [];

// Totais
$totalAberto    = 0;
$totalRecebido  = 0;
$totalCancelado = 0;
foreach ($faturamentos as $f) {
    $v = (float)($f->valor ?? 0);
    if ($f->status === 'aberta')    $totalAberto    += $v;
    if ($f->status === 'recebida')  $totalRecebido  += $v;
    if ($f->status === 'cancelada') $totalCancelado += $v;
}
?>

<!-- Resumo -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="text-muted small fw-bold mb-1">EM ABERTO</div>
                <div class="h5 fw-bold text-warning mb-0">R$ <?php echo number_format($totalAberto, 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="text-muted small fw-bold mb-1">RECEBIDO</div>
                <div class="h5 fw-bold text-success mb-0">R$ <?php echo number_format($totalRecebido, 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="text-muted small fw-bold mb-1">CANCELADO</div>
                <div class="h5 fw-bold text-danger mb-0">R$ <?php echo number_format($totalCancelado, 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Listagem -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="mb-0 fw-bold">
            <i class="fas fa-file-invoice-dollar text-primary me-2"></i>
            Contas a Receber Vinculadas
        </h5>
        <small class="text-muted">
            Para vincular uma conta a receber a este colaborador, edite a conta e selecione o colaborador no campo correspondente.
        </small>
    </div>
    <div class="card-body p-0">
        <?php
        $statusLabels = [
            'aberta'    => '<span class="badge bg-warning text-dark">Em Aberto</span>',
            'recebida'  => '<span class="badge bg-success">Recebida</span>',
            'cancelada' => '<span class="badge bg-danger">Cancelada</span>',
        ];
        $headers     = ['Descrição', 'Cliente', 'Vencimento', 'Recebimento', 'Valor', 'Status', 'Ações'];
        $rowRenderer = function ($f) use ($statusLabels) {
            $desc     = htmlspecialchars($f->descricao ?? '');
            $cliente  = htmlspecialchars($f->cliente_nome ?? '—');
            $venc     = $f->data_vencimento  ? date('d/m/Y', strtotime($f->data_vencimento))  : '—';
            $receb    = $f->data_recebimento ? date('d/m/Y', strtotime($f->data_recebimento)) : '—';
            $valor    = 'R$ ' . number_format((float)($f->valor ?? 0), 2, ',', '.');
            $status   = $statusLabels[$f->status] ?? $f->status;
            $link     = '<a href="/financeiro/receber/edit/' . (int)$f->id . '" class="btn btn-sm btn-outline-primary" title="Ver conta"><i class="fas fa-external-link-alt"></i></a>';
            return '<tr>'
                . '<td>' . $desc . '</td>'
                . '<td>' . $cliente . '</td>'
                . '<td>' . $venc . '</td>'
                . '<td>' . $receb . '</td>'
                . '<td class="fw-bold">' . $valor . '</td>'
                . '<td>' . $status . '</td>'
                . '<td class="text-end">' . $link . '</td>'
                . '</tr>';
        };
        UI::render('table', [
            'headers'      => $headers,
            'items'        => $faturamentos,
            'rowRenderer'  => $rowRenderer,
            'emptyMessage' => 'Nenhuma conta a receber vinculada a este colaborador.',
        ]);
        ?>
    </div>
</div>
