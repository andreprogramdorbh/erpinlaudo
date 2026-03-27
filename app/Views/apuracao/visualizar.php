<?php
use App\Core\UI;

$tipo = $apuracao->tipo ?? 'prestador';
$backUrl = $tipo === 'prestador' ? '/faturamento/apuracao-prestador' : '/faturamento/apuracao-cliente';

$actions = [
    ['text' => 'Voltar', 'link' => $backUrl, 'icon' => 'fas fa-arrow-left', 'class' => 'btn-outline-secondary'],
];
UI::sectionHeader(
    'Apuração ' . htmlspecialchars($apuracao->numero),
    ($tipo === 'prestador' ? 'Prestador: ' . htmlspecialchars($apuracao->medico_nome ?? '—') : 'Cliente: ' . htmlspecialchars($apuracao->cliente_nome ?? '—')),
    $actions
);
?>

<!-- CABEÇALHO DA APURAÇÃO -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Número</small>
                        <span class="badge bg-secondary font-monospace fs-6"><?php echo htmlspecialchars($apuracao->numero); ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Tipo</small>
                        <span class="badge bg-<?php echo $tipo === 'prestador' ? 'info' : 'warning'; ?> text-dark">
                            <i class="fas fa-<?php echo $tipo === 'prestador' ? 'user-md' : 'building'; ?> me-1"></i>
                            <?php echo $tipo === 'prestador' ? 'Prestador' : 'Cliente'; ?>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Status</small>
                        <?php
                        $stClass = ['rascunho' => 'secondary', 'processando' => 'warning', 'concluido' => 'success', 'faturado' => 'primary', 'erro' => 'danger'];
                        $stLabel = ['rascunho' => 'Rascunho', 'processando' => 'Processando', 'concluido' => 'Concluído', 'faturado' => 'Faturado', 'erro' => 'Erro'];
                        ?>
                        <span class="badge bg-<?php echo $stClass[$apuracao->status] ?? 'secondary'; ?> fs-6">
                            <?php echo $stLabel[$apuracao->status] ?? ucfirst($apuracao->status); ?>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Período</small>
                        <span class="fw-semibold">
                            <?php
                            if ($apuracao->periodo_inicio && $apuracao->periodo_fim) {
                                echo date('d/m/Y', strtotime($apuracao->periodo_inicio)) . ' → ' . date('d/m/Y', strtotime($apuracao->periodo_fim));
                            } else {
                                echo '—';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Contrato</small>
                        <span class="fw-semibold"><?php echo htmlspecialchars($apuracao->contrato_nome ?? '—'); ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Gerado em</small>
                        <span class="fw-semibold"><?php echo date('d/m/Y H:i', strtotime($apuracao->created_at ?? 'now')); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Totais -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3 text-muted">Resumo Financeiro</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total de Exames</span>
                    <span class="fw-bold"><?php echo $apuracao->total_exames; ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Normal</span>
                    <span class="badge bg-success"><?php echo $apuracao->total_normal; ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Urgência</span>
                    <span class="badge bg-danger"><?php echo $apuracao->total_urgencia; ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="fw-bold">Valor Total</span>
                    <span class="fw-bold text-success fs-5">R$ <?php echo number_format((float)$apuracao->valor_total, 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RESUMO POR MODALIDADE -->
<?php if (!empty($resumoModal)): ?>
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2 text-primary"></i>Por Modalidade</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Modalidade</th>
                                <th class="text-center">Qtd</th>
                                <th class="text-center">Normal</th>
                                <th class="text-center">Urgência</th>
                                <th class="text-end pe-3">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($resumoModal as $rm): ?>
                            <tr>
                                <td class="ps-3"><span class="badge bg-secondary"><?php echo htmlspecialchars($rm->modalidade); ?></span></td>
                                <td class="text-center fw-bold"><?php echo $rm->total; ?></td>
                                <td class="text-center"><?php echo $rm->total_normal; ?></td>
                                <td class="text-center"><?php echo $rm->total_urgencia; ?></td>
                                <td class="text-end pe-3">R$ <?php echo number_format((float)$rm->valor, 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($resumoMedico)): ?>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-user-md me-2 text-info"></i>Por Médico</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Médico</th>
                                <th class="text-center">Qtd</th>
                                <th class="text-end pe-3">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($resumoMedico as $rm): ?>
                            <tr>
                                <td class="ps-3 small"><?php echo htmlspecialchars($rm->medico); ?></td>
                                <td class="text-center fw-bold"><?php echo $rm->total; ?></td>
                                <td class="text-end pe-3">R$ <?php echo number_format((float)$rm->valor, 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ITENS DA APURAÇÃO -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-list me-2 text-secondary"></i>Itens da Apuração</h6>
        <span class="badge bg-primary"><?php echo count($itens); ?> itens</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($itens)): ?>
            <div class="text-center py-4 text-muted">Nenhum item encontrado.</div>
        <?php else: ?>
        <div class="table-responsive" style="max-height:500px;overflow-y:auto;">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Médico</th>
                        <th>Modalidade</th>
                        <th>Exame</th>
                        <th>Paciente</th>
                        <th class="text-center">Prioridade</th>
                        <th>Data Conclusão</th>
                        <th class="text-end">Valor Pago</th>
                        <th class="text-end pe-3">Valor Venda</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($itens as $i => $item): ?>
                    <tr>
                        <td class="ps-3 text-muted small"><?php echo $i + 1; ?></td>
                        <td class="small"><?php echo htmlspecialchars($item->medico_nome ?? '—'); ?></td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($item->modalidade ?? '—'); ?></span></td>
                        <td class="small"><?php echo htmlspecialchars($item->study_description ?? '—'); ?></td>
                        <td class="small"><?php echo htmlspecialchars($item->paciente_nome ?? '—'); ?></td>
                        <td class="text-center">
                            <?php if (stripos($item->prioridade ?? '', 'urgent') !== false || ($item->prioridade ?? '') === 'U'): ?>
                                <span class="badge bg-danger">Urgência</span>
                            <?php else: ?>
                                <span class="badge bg-success">Normal</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?php echo $item->data_conclusao ? date('d/m/Y H:i', strtotime($item->data_conclusao)) : '—'; ?></td>
                        <td class="text-end small">
                            <?php if ($item->valor_calculado > 0): ?>
                                R$ <?php echo number_format((float)$item->valor_calculado, 2, ',', '.'); ?>
                            <?php else: ?>
                                <span class="text-warning small">Sem tabela</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-3 small">
                            <?php if ($item->valor_venda > 0): ?>
                                R$ <?php echo number_format((float)$item->valor_venda, 2, ',', '.'); ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
