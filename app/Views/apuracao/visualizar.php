<?php
use App\Core\UI;

$tipo    = $apuracao->tipo ?? 'prestador';
$backUrl = $tipo === 'prestador' ? '/faturamento/apuracao-prestador' : '/faturamento/apuracao-cliente';

$stClass = ['rascunho' => 'secondary', 'processando' => 'warning', 'concluido' => 'success', 'faturado' => 'primary', 'erro' => 'danger'];
$stLabel = ['rascunho' => 'Rascunho', 'processando' => 'Processando', 'concluido' => 'Conclu&iacute;do', 'faturado' => 'Faturado', 'erro' => 'Erro'];

$actions = [
    ['text' => 'Voltar', 'link' => $backUrl, 'icon' => 'fas fa-arrow-left', 'class' => 'btn-outline-secondary'],
];
if (($apuracao->status ?? '') === 'concluido') {
    $actions[] = [
        'text'  => 'Faturar Apura&ccedil;&atilde;o',
        'link'  => '/faturamento/apuracao-prestador/faturar/' . $apuracao->id,
        'icon'  => 'fas fa-file-invoice-dollar',
        'class' => 'btn-success',
    ];
}
UI::sectionHeader(
    'Apura&ccedil;&atilde;o ' . htmlspecialchars($apuracao->numero),
    ($tipo === 'prestador'
        ? 'Prestador: ' . htmlspecialchars($apuracao->medico_nome ?? '&mdash;')
        : 'Cliente: '   . htmlspecialchars($apuracao->cliente_nome ?? '&mdash;')),
    $actions
);
?>

<!-- ============================================================
     CABEÇALHO DA APURAÇÃO
     ============================================================ -->
<div class="row g-4 mb-4">

    <!-- Dados gerais -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2 text-primary"></i>Dados da Apura&ccedil;&atilde;o</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">

                    <div class="col-md-4">
                        <small class="text-muted d-block mb-1">N&uacute;mero</small>
                        <span class="badge bg-secondary font-monospace"><?php echo htmlspecialchars($apuracao->numero); ?></span>
                    </div>

                    <div class="col-md-4">
                        <small class="text-muted d-block mb-1">Tipo</small>
                        <span class="badge bg-<?php echo $tipo === 'prestador' ? 'info' : 'warning'; ?> text-dark">
                            <i class="fas fa-<?php echo $tipo === 'prestador' ? 'user-md' : 'building'; ?> me-1"></i>
                            <?php echo $tipo === 'prestador' ? 'Prestador' : 'Cliente'; ?>
                        </span>
                    </div>

                    <div class="col-md-4">
                        <small class="text-muted d-block mb-1">Status</small>
                        <span class="badge bg-<?php echo $stClass[$apuracao->status] ?? 'secondary'; ?>">
                            <?php echo $stLabel[$apuracao->status] ?? ucfirst($apuracao->status); ?>
                        </span>
                    </div>

                    <div class="col-md-4">
                        <small class="text-muted d-block mb-1">Per&iacute;odo</small>
                        <span class="fw-semibold">
                            <?php
                            if (!empty($apuracao->periodo_inicio) && !empty($apuracao->periodo_fim)) {
                                echo date('d/m/Y', strtotime($apuracao->periodo_inicio))
                                   . ' &rarr; '
                                   . date('d/m/Y', strtotime($apuracao->periodo_fim));
                            } else {
                                echo '<span class="text-muted">&mdash;</span>';
                            }
                            ?>
                        </span>
                    </div>

                    <div class="col-md-4">
                        <small class="text-muted d-block mb-1">Contrato</small>
                        <span class="fw-semibold"><?php echo htmlspecialchars($apuracao->contrato_nome ?? '&mdash;'); ?></span>
                    </div>

                    <?php if (!empty($todasTagsDicom)): ?>
                    <div class="col-12">
                        <small class="text-muted d-block mb-1"><i class="fas fa-tag me-1"></i>Modalidades (TAGs DICOM cadastradas)</small>
                        <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($todasTagsDicom as $tagVal): ?>
                            <?php
                                $examesDestaTg = $tagDicomParaExame[$tagVal] ?? [];
                                $tooltipText   = implode(', ', $examesDestaTg);
                            ?>
                            <span class="badge bg-primary" title="Exame(s): <?php echo htmlspecialchars($tooltipText); ?>" data-bs-toggle="tooltip">
                                <?php echo htmlspecialchars($tagVal); ?>
                            </span>
                        <?php endforeach; ?>
                        </div>
                        <small class="text-muted mt-1 d-block">Passe o mouse sobre a tag para ver o exame vinculado</small>
                    </div>
                    <?php endif; ?>

                    <div class="col-md-4">
                        <small class="text-muted d-block mb-1">Gerado em</small>
                        <span class="fw-semibold"><?php echo date('d/m/Y H:i', strtotime($apuracao->created_at ?? 'now')); ?></span>
                    </div>

                    <?php if ($tipo === 'prestador' && !empty($apuracao->medico_nome)): ?>
                    <div class="col-md-4">
                        <small class="text-muted d-block mb-1">M&eacute;dico</small>
                        <span class="fw-semibold"><?php echo htmlspecialchars($apuracao->medico_nome); ?></span>
                        <?php if (!empty($apuracao->medico_crm)): ?>
                            <small class="text-muted ms-1">CRM <?php echo htmlspecialchars($apuracao->medico_crm); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php elseif ($tipo === 'cliente' && !empty($apuracao->cliente_nome)): ?>
                    <div class="col-md-4">
                        <small class="text-muted d-block mb-1">Cliente</small>
                        <span class="fw-semibold"><?php echo htmlspecialchars($apuracao->cliente_nome); ?></span>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <!-- Resumo financeiro -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-pie me-2 text-success"></i>Resumo Financeiro</h6>
            </div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Total de Exames</span>
                    <span class="fw-bold fs-5"><?php echo number_format((int)$apuracao->total_exames); ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted"><i class="fas fa-circle text-success me-1" style="font-size:.6rem"></i>Normal</span>
                    <span class="badge bg-success fs-6"><?php echo number_format((int)$apuracao->total_normal); ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted"><i class="fas fa-circle text-danger me-1" style="font-size:.6rem"></i>Urg&ecirc;ncia</span>
                    <span class="badge bg-danger fs-6"><?php echo number_format((int)$apuracao->total_urgencia); ?></span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Valor Total</span>
                    <span class="fw-bold text-success fs-4">R$&nbsp;<?php echo number_format((float)$apuracao->valor_total, 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     RESUMOS: Modalidade | Médico | Unidade
     ============================================================ -->
<?php if (!empty($resumoModal) || !empty($resumoMedico) || !empty($resumoUnidade)): ?>
<div class="row g-4 mb-4">

    <!-- Por Modalidade -->
    <?php if (!empty($resumoModal)): ?>
    <div class="col-md-<?php echo (!empty($resumoMedico) || !empty($resumoUnidade)) ? '4' : '12'; ?>">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2 text-primary"></i>Por Modalidade</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Modalidade</th>
                                <th class="text-center">Total</th>
                                <th class="text-center"><span class="badge bg-success">N</span></th>
                                <th class="text-center"><span class="badge bg-danger">U</span></th>
                                <th class="text-end pe-3">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($resumoModal as $rm): ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($rm->modalidade ?? '—'); ?></span>
                                </td>
                                <td class="text-center fw-bold"><?php echo number_format((int)$rm->total); ?></td>
                                <td class="text-center text-success fw-semibold"><?php echo number_format((int)$rm->total_normal); ?></td>
                                <td class="text-center text-danger fw-semibold"><?php echo number_format((int)$rm->total_urgencia); ?></td>
                                <td class="text-end pe-3 small">R$&nbsp;<?php echo number_format((float)$rm->valor, 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Por Médico -->
    <?php if (!empty($resumoMedico)): ?>
    <div class="col-md-<?php echo !empty($resumoModal) ? '4' : '6'; ?>">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-user-md me-2 text-info"></i>Por M&eacute;dico</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:260px;overflow-y:auto;">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3">M&eacute;dico</th>
                                <th class="text-center">Total</th>
                                <th class="text-end pe-3">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($resumoMedico as $rm): ?>
                            <tr>
                                <td class="ps-3 small">
                                    <?php echo htmlspecialchars($rm->medico ?? '—'); ?>
                                    <?php if (!empty($rm->medico_crm)): ?>
                                        <br><small class="text-muted">CRM <?php echo htmlspecialchars($rm->medico_crm); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold"><?php echo number_format((int)$rm->total); ?></td>
                                <td class="text-end pe-3 small">R$&nbsp;<?php echo number_format((float)$rm->valor, 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Por Unidade -->
    <?php if (!empty($resumoUnidade)): ?>
    <div class="col-md-<?php echo !empty($resumoModal) ? '4' : '6'; ?>">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-hospital me-2 text-warning"></i>Por Unidade</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:260px;overflow-y:auto;">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3">Unidade</th>
                                <th class="text-center">Total</th>
                                <th class="text-center"><span class="badge bg-success">N</span></th>
                                <th class="text-center"><span class="badge bg-danger">U</span></th>
                                <th class="text-end pe-3">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($resumoUnidade as $ru): ?>
                            <tr>
                                <td class="ps-3 small fw-semibold"><?php echo htmlspecialchars($ru->unidade ?? 'Sem unidade'); ?></td>
                                <td class="text-center fw-bold"><?php echo number_format((int)$ru->total); ?></td>
                                <td class="text-center text-success fw-semibold"><?php echo number_format((int)$ru->total_normal); ?></td>
                                <td class="text-center text-danger fw-semibold"><?php echo number_format((int)$ru->total_urgencia); ?></td>
                                <td class="text-end pe-3 small">R$&nbsp;<?php echo number_format((float)$ru->valor, 2, ',', '.'); ?></td>
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

<!-- ============================================================
     ITENS DA APURAÇÃO
     ============================================================ -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-list me-2 text-secondary"></i>Itens da Apura&ccedil;&atilde;o</h6>
        <span class="badge bg-primary"><?php echo count($itens); ?> itens</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($itens)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                Nenhum item encontrado.
            </div>
        <?php else: ?>
        <div class="table-responsive" style="max-height:550px;overflow-y:auto;">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Unidade</th>
                        <th>M&eacute;dico</th>
                        <th>Modalidade</th>
                        <th>Exame</th>
                        <th>Paciente</th>
                        <th class="text-center">Prioridade</th>
                        <th>Data Conclus&atilde;o</th>
                        <th class="text-end">Valor Calc.</th>
                        <th class="text-center pe-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($itens as $i => $item):
                    $isUrgencia = stripos($item->prioridade ?? '', 'urgent') !== false
                               || ($item->tipo_prioridade ?? '') === 'urgencia';
                    $semMatch   = ($item->status_item ?? '') === 'sem_match';
                ?>
                    <tr class="<?php echo $semMatch ? 'table-warning' : ''; ?>">
                        <td class="ps-3 text-muted small"><?php echo $i + 1; ?></td>
                        <td class="small">
                            <?php if (!empty($item->unidade)): ?>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($item->unidade); ?></span>
                            <?php else: ?>
                                <span class="text-muted">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?php echo htmlspecialchars($item->medico_nome ?? '&mdash;'); ?></td>
                        <td>
                            <?php
                                $modItem = strtoupper(trim($item->modalidade ?? ''));
                                $exameViaTag = !empty($tagDicomParaExame[$modItem])
                                    ? implode(', ', $tagDicomParaExame[$modItem])
                                    : null;
                            ?>
                            <span class="badge bg-secondary<?php echo $exameViaTag ? ' border border-primary' : ''; ?>"
                                  <?php if ($exameViaTag): ?>title="Tipo de Exame: <?php echo htmlspecialchars($exameViaTag); ?>" data-bs-toggle="tooltip"<?php endif; ?>>
                                <?php echo htmlspecialchars($item->modalidade ?? '&mdash;'); ?>
                            </span>
                            <?php if ($exameViaTag): ?>
                                <i class="fas fa-link text-primary ms-1" style="font-size:.65rem" title="TAG DICOM vinculada a: <?php echo htmlspecialchars($exameViaTag); ?>"></i>
                            <?php endif; ?>
                        </td>
                        <td class="small" style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                            title="<?php echo htmlspecialchars($item->study_description ?? ''); ?>">
                            <?php if (!empty($item->exame_nome_tabela)): ?>
                                <span class="text-success" title="Match: <?php echo htmlspecialchars($item->exame_nome_tabela); ?>">
                                    <i class="fas fa-check-circle me-1 text-success" style="font-size:.7rem"></i>
                                </span>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($item->study_description ?? '&mdash;'); ?>
                        </td>
                        <td class="small"><?php echo htmlspecialchars($item->paciente_nome ?? '&mdash;'); ?></td>
                        <td class="text-center">
                            <?php if ($isUrgencia): ?>
                                <span class="badge bg-danger">Urg&ecirc;ncia</span>
                            <?php else: ?>
                                <span class="badge bg-success">Normal</span>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <?php echo $item->data_conclusao
                                ? date('d/m/Y H:i', strtotime($item->data_conclusao))
                                : '&mdash;'; ?>
                        </td>
                        <td class="text-end small fw-semibold">
                            <?php if ((float)($item->valor_calculado ?? 0) > 0): ?>
                                R$&nbsp;<?php echo number_format((float)$item->valor_calculado, 2, ',', '.'); ?>
                            <?php else: ?>
                                <span class="text-warning">Sem tabela</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center pe-3">
                            <?php if ($semMatch): ?>
                                <span class="badge bg-warning text-dark" title="<?php echo htmlspecialchars($item->obs_item ?? ''); ?>">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Sem match
                                </span>
                            <?php elseif (($item->status_item ?? '') === 'ok'): ?>
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>OK</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($item->status_item ?? ''); ?></span>
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
