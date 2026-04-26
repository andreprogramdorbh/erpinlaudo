<?php
use App\Core\UI;
use App\Core\View;

$isFaturado = ($apuracao->status ?? '') === 'faturado';
$isConcluido = ($apuracao->status ?? '') === 'concluido';

$stClass = ['rascunho' => 'secondary', 'processando' => 'warning', 'concluido' => 'success', 'faturado' => 'primary', 'erro' => 'danger'];
$stLabel = ['rascunho' => 'Rascunho', 'processando' => 'Processando', 'concluido' => 'Conclu&iacute;do', 'faturado' => 'Faturado', 'erro' => 'Erro'];

$valorVenda = (float)(($apuracao->valor_venda_total ?? 0) > 0 ? $apuracao->valor_venda_total : $apuracao->valor_total);
$valorCusto = (float)($apuracao->valor_total ?? 0);
$margem     = $valorVenda > 0 && $valorCusto > 0 ? (($valorVenda - $valorCusto) / $valorVenda) * 100 : 0;

$actions = [
    ['text' => 'Voltar', 'link' => '/faturamento/apuracao-cliente', 'icon' => 'fas fa-arrow-left', 'class' => 'btn-outline-secondary'],
];

if (!$isFaturado && ($apuracao->status ?? '') !== 'rascunho') {
    $actions[] = [
        'text'       => 'Recalcular Valores',
        'link'       => '#',
        'icon'       => 'fas fa-sync-alt',
        'class'      => 'btn-outline-warning',
        'attributes' => 'onclick="abrirModalRecalcular()" id="btn-recalcular"',
    ];
}

if ($isConcluido) {
    $actions[] = [
        'text'       => 'Faturar &mdash; Gerar NF-e / Conta a Receber',
        'link'       => '#',
        'icon'       => 'fas fa-file-invoice-dollar',
        'class'      => 'btn-success',
        'attributes' => 'onclick="abrirModalFaturar()" id="btn-faturar"',
    ];
}

UI::sectionHeader(
    'Apura&ccedil;&atilde;o Cliente ' . htmlspecialchars($apuracao->numero),
    'Cliente: ' . htmlspecialchars($apuracao->cliente_nome ?? '&mdash;'),
    $actions
);
?>

<?php if ($isFaturado): ?>
<div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
    <i class="fas fa-lock me-3 fs-5"></i>
    <div>
        <strong>Apuração Faturada &mdash; Somente Leitura.</strong>
        Esta apuração já gerou uma <strong>Conta a Receber</strong> no financeiro e não pode ser alterada ou recalculada.
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     CABEÇALHO DA APURAÇÃO
     ============================================================ -->
<div class="row g-4 mb-4">

    <!-- Dados gerais -->
    <div class="col-lg-7">
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
                        <small class="text-muted d-block mb-1">Status</small>
                        <span class="badge bg-<?php echo $stClass[$apuracao->status] ?? 'secondary'; ?>">
                            <?php echo $stLabel[$apuracao->status] ?? ucfirst($apuracao->status); ?>
                        </span>
                        <?php if ($isFaturado): ?>
                            <span class="badge bg-light text-dark border ms-1"><i class="fas fa-lock me-1"></i>Somente Leitura</span>
                        <?php endif; ?>
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
                        <small class="text-muted d-block mb-1">Cliente</small>
                        <span class="fw-semibold"><?php echo htmlspecialchars($apuracao->cliente_nome ?? '&mdash;'); ?></span>
                    </div>

                    <div class="col-md-4">
                        <small class="text-muted d-block mb-1">Contrato</small>
                        <span class="fw-semibold"><?php echo htmlspecialchars($apuracao->contrato_nome ?? '&mdash;'); ?></span>
                        <?php if (!empty($apuracao->contrato_numero)): ?>
                            <small class="text-muted ms-1">(<?php echo htmlspecialchars($apuracao->contrato_numero); ?>)</small>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <small class="text-muted d-block mb-1">Gerado em</small>
                        <span class="fw-semibold"><?php echo date('d/m/Y H:i', strtotime($apuracao->created_at ?? 'now')); ?></span>
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

                </div>
            </div>
        </div>
    </div>

    <!-- Resumo financeiro — destaque no valor de VENDA -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-pie me-2 text-success"></i>Resumo Financeiro</h6>
            </div>
            <div class="card-body p-4">

                <!-- Contadores de exames -->
                <div class="row g-2 mb-3 text-center">
                    <div class="col-4">
                        <div class="fw-bold fs-4 text-primary" id="resumo-total-exames"><?php echo number_format((int)$apuracao->total_exames); ?></div>
                        <small class="text-muted">Total Exames</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-4 text-success" id="resumo-total-normal"><?php echo number_format((int)$apuracao->total_normal); ?></div>
                        <small class="text-muted">Normal</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-4 text-danger" id="resumo-total-urgencia"><?php echo number_format((int)$apuracao->total_urgencia); ?></div>
                        <small class="text-muted">Urg&ecirc;ncia</small>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Valor de custo (informativo) -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small"><i class="fas fa-hand-holding-usd me-1"></i>Custo (Prestador)</span>
                    <span class="text-warning fw-semibold" id="resumo-valor-total">R$&nbsp;<?php echo number_format($valorCusto, 2, ',', '.'); ?></span>
                </div>

                <!-- Valor de venda — destaque principal -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold"><i class="fas fa-tag me-1 text-success"></i>Valor de Venda (Cliente)</span>
                    <span class="fw-bold text-success fs-4" id="resumo-valor-venda">R$&nbsp;<?php echo number_format($valorVenda, 2, ',', '.'); ?></span>
                </div>

                <!-- Margem -->
                <?php if ($valorVenda > 0 && $valorCusto > 0): ?>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small"><i class="fas fa-percent me-1"></i>Margem</span>
                    <span class="badge bg-<?php echo $margem >= 0 ? 'success' : 'danger'; ?> fs-6" id="resumo-margem">
                        <?php echo number_format($margem, 1, ',', '.'); ?>%
                    </span>
                </div>
                <?php endif; ?>

                <?php if ($isConcluido): ?>
                <hr class="my-3">
                <div class="d-grid">
                    <button class="btn btn-success" onclick="abrirModalFaturar()">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Faturar &mdash; Gerar Conta a Receber
                    </button>
                </div>
                <?php endif; ?>

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
                                <th class="text-end pe-3">Valor Venda</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($resumoModal as $rm): ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($rm->modalidade ?? '&mdash;'); ?></span>
                                </td>
                                <td class="text-center fw-bold"><?php echo number_format((int)$rm->total); ?></td>
                                <td class="text-center text-success fw-semibold"><?php echo number_format((int)$rm->total_normal); ?></td>
                                <td class="text-center text-danger fw-semibold"><?php echo number_format((int)$rm->total_urgencia); ?></td>
                                <td class="text-end pe-3 small text-success fw-semibold">R$&nbsp;<?php echo number_format((float)($rm->valor_venda ?? $rm->valor), 2, ',', '.'); ?></td>
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
                                <th class="text-end pe-3">Valor Venda</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($resumoMedico as $rm): ?>
                            <tr>
                                <td class="ps-3 small">
                                    <?php echo htmlspecialchars($rm->medico ?? '&mdash;'); ?>
                                    <?php if (!empty($rm->medico_crm)): ?>
                                        <br><small class="text-muted">CRM <?php echo htmlspecialchars($rm->medico_crm); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold"><?php echo number_format((int)$rm->total); ?></td>
                                <td class="text-end pe-3 small text-success fw-semibold">R$&nbsp;<?php echo number_format((float)($rm->valor_venda ?? $rm->valor), 2, ',', '.'); ?></td>
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
                                <th class="text-end pe-3">Valor Venda</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($resumoUnidade as $ru): ?>
                            <tr>
                                <td class="ps-3 small fw-semibold"><?php echo htmlspecialchars($ru->unidade ?? 'Sem unidade'); ?></td>
                                <td class="text-center fw-bold"><?php echo number_format((int)$ru->total); ?></td>
                                <td class="text-center text-success fw-semibold"><?php echo number_format((int)$ru->total_normal); ?></td>
                                <td class="text-center text-danger fw-semibold"><?php echo number_format((int)$ru->total_urgencia); ?></td>
                                <td class="text-end pe-3 small text-success fw-semibold">R$&nbsp;<?php echo number_format((float)($ru->valor_venda ?? $ru->valor), 2, ',', '.'); ?></td>
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
     ITENS DA APURAÇÃO — com valor de VENDA em destaque
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
                        <th class="text-end">Custo</th>
                        <th class="text-end">Valor Venda</th>
                        <th class="text-center pe-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($itens as $i => $item):
                    $isUrgencia = stripos($item->prioridade ?? '', 'urgent') !== false
                               || ($item->tipo_prioridade ?? '') === 'urgencia';
                    $semMatch   = ($item->status_item ?? '') === 'sem_match';
                    $valorVendaItem = (float)($item->valor_calculado_venda ?? $item->valor_calculado ?? 0);
                    $valorCustoItem = (float)($item->valor_calculado ?? 0);
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
                        <!-- Custo (informativo, menor destaque) -->
                        <td class="text-end small text-muted">
                            <?php if ($valorCustoItem > 0): ?>
                                R$&nbsp;<?php echo number_format($valorCustoItem, 2, ',', '.'); ?>
                            <?php else: ?>
                                <span class="text-warning">—</span>
                            <?php endif; ?>
                        </td>
                        <!-- Valor de Venda (destaque principal) -->
                        <td class="text-end small fw-bold text-success">
                            <?php if ($valorVendaItem > 0): ?>
                                R$&nbsp;<?php echo number_format($valorVendaItem, 2, ',', '.'); ?>
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

<?php if (!$isFaturado): ?>
<!-- ============================================================
     MODAL DE CONFIRMAÇÃO — FATURAR
     ============================================================ -->
<div class="modal fade" id="modalFaturar" tabindex="-1" aria-labelledby="modalFaturarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom bg-success text-white">
                <h5 class="modal-title fw-bold" id="modalFaturarLabel">
                    <i class="fas fa-file-invoice-dollar me-2"></i>Confirmar Faturamento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success border-0 mb-3">
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="text-muted small">Apuração</div>
                            <div class="fw-bold"><?php echo htmlspecialchars($apuracao->numero); ?></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Valor a Cobrar</div>
                            <div class="fw-bold text-success fs-5">R$&nbsp;<?php echo number_format($valorVenda, 2, ',', '.'); ?></div>
                        </div>
                    </div>
                </div>
                <p class="mb-2">
                    Ao confirmar, o sistema irá:
                </p>
                <ul class="mb-3">
                    <li>Gerar uma <strong>Conta a Receber</strong> no módulo Financeiro com o valor de venda</li>
                    <li>Vincular ao cliente <strong><?php echo htmlspecialchars($apuracao->cliente_nome ?? '—'); ?></strong></li>
                    <li>Definir vencimento em <strong>30 dias</strong></li>
                    <li>Alterar o status da apuração para <strong>Faturado</strong></li>
                </ul>
                <div class="alert alert-warning border-0 small mb-0">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Esta ação <strong>não pode ser desfeita</strong>. Confirme apenas se os valores estão corretos.
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <a href="/faturamento/apuracao/faturar/<?php echo (int)$apuracao->id; ?>" class="btn btn-success">
                    <i class="fas fa-check me-1"></i>Confirmar e Faturar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL DE CONFIRMAÇÃO — RECALCULAR
     ============================================================ -->
<div class="modal fade" id="modalRecalcular" tabindex="-1" aria-labelledby="modalRecalcularLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalRecalcularLabel">
                    <i class="fas fa-sync-alt me-2 text-warning"></i>Recalcular Valores
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">
                    Esta ação irá <strong>recalcular todos os valores de venda</strong> dos itens desta apuração com base na
                    <strong>tabela de preços de venda atual</strong> do contrato, sem reimportar o arquivo.
                </p>
                <ul class="text-muted small mb-3">
                    <li>Os dados originais (médico, modalidade, datas, paciente) serão preservados.</li>
                    <li>Apenas os valores de venda calculados e o match de exame serão atualizados.</li>
                    <li>O status voltará para <strong>Concluído</strong> após o recálculo.</li>
                </ul>
                <div class="alert alert-warning border-0 mb-0 small">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Confirme apenas se a tabela de preços de venda foi atualizada e deseja aplicar os novos valores.
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-warning" id="btn-confirmar-recalcular" onclick="executarRecalculo()">
                    <i class="fas fa-sync-alt me-1"></i>Sim, Recalcular
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de resultado do recálculo -->
<div class="modal fade" id="modalResultadoRecalculo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-check-circle me-2 text-success"></i>Rec&aacute;lculo Conclu&iacute;do
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="resultado-recalculo-body">
                <!-- Preenchido via JS -->
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-refresh me-1"></i>Atualizar P&aacute;gina
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const APURACAO_ID = <?php echo (int) $apuracao->id; ?>;
const CSRF_TOKEN  = '<?php echo View::csrfToken(); ?>';

function abrirModalFaturar() {
    new bootstrap.Modal(document.getElementById('modalFaturar')).show();
}

function abrirModalRecalcular() {
    new bootstrap.Modal(document.getElementById('modalRecalcular')).show();
}

function executarRecalculo() {
    const btnConfirmar = document.getElementById('btn-confirmar-recalcular');
    btnConfirmar.disabled = true;
    btnConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Recalculando...';

    fetch('/faturamento/apuracao/recalcular/' + APURACAO_ID, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: 'csrf_token=' + encodeURIComponent(CSRF_TOKEN),
    })
    .then(r => r.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('modalRecalcular')).hide();

        if (data.success) {
            // Atualizar resumo na página
            const el = (id) => document.getElementById(id);
            if (el('resumo-total-exames'))  el('resumo-total-exames').textContent  = data.total_exames;
            if (el('resumo-total-normal'))  el('resumo-total-normal').textContent  = data.total_normal;
            if (el('resumo-total-urgencia')) el('resumo-total-urgencia').textContent = data.total_urgencia;
            if (el('resumo-valor-total'))   el('resumo-valor-total').innerHTML     = 'R$&nbsp;' + data.valor_total.replace('R$ ', '');
            if (el('resumo-valor-venda'))   el('resumo-valor-venda').innerHTML     = 'R$&nbsp;' + (data.valor_venda_total || data.valor_total).replace('R$ ', '');

            document.getElementById('resultado-recalculo-body').innerHTML = `
                <div class="row g-3 text-center mb-3">
                    <div class="col-4">
                        <div class="fw-bold fs-4">${data.total_exames}</div>
                        <small class="text-muted">Total Exames</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-4 text-success">${data.total_normal}</div>
                        <small class="text-muted">Normal</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-4 text-danger">${data.total_urgencia}</div>
                        <small class="text-muted">Urgência</small>
                    </div>
                </div>
                <div class="row g-2 text-center mb-3">
                    <div class="col-6">
                        <span class="fw-semibold text-warning">${data.valor_total}</span>
                        <div class="text-muted small">Custo (Prestador)</div>
                    </div>
                    <div class="col-6">
                        <span class="fw-bold text-success fs-4">${data.valor_venda_total || data.valor_total}</span>
                        <div class="text-muted small">Valor de Venda (Cliente)</div>
                    </div>
                </div>
                ${data.sem_match > 0 ? `<div class="alert alert-warning border-0 small"><i class="fas fa-exclamation-triangle me-1"></i>${data.sem_match} item(s) sem correspondência na tabela de exames.</div>` : ''}
                ${data.log && data.log !== 'Recálculo concluído sem erros.' ? `<details class="mt-2"><summary class="text-muted small">Ver log</summary><pre class="small mt-1 text-muted" style="max-height:120px;overflow-y:auto">${data.log}</pre></details>` : ''}
            `;
            new bootstrap.Modal(document.getElementById('modalResultadoRecalculo')).show();
        } else {
            alert('Erro ao recalcular: ' + (data.message || 'Erro desconhecido'));
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Sim, Recalcular';
        }
    })
    .catch(err => {
        alert('Erro de comunicação: ' + err.message);
        btnConfirmar.disabled = false;
        btnConfirmar.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Sim, Recalcular';
    });
}
</script>
<?php endif; ?>
