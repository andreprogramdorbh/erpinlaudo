<?php
use App\Core\UI;
use App\Core\View;

$tipo       = $apuracao->tipo ?? 'prestador';
$backUrl    = $tipo === 'prestador' ? '/faturamento/apuracao-prestador' : '/faturamento/apuracao-cliente';
$isFaturado = ($apuracao->status ?? '') === 'faturado';

$stClass = ['rascunho' => 'secondary', 'processando' => 'warning', 'concluido' => 'success', 'faturado' => 'primary', 'erro' => 'danger'];
$stLabel = ['rascunho' => 'Rascunho', 'processando' => 'Processando', 'concluido' => 'Conclu&iacute;do', 'faturado' => 'Faturado', 'erro' => 'Erro'];

$actions = [
    ['text' => 'Voltar', 'link' => $backUrl, 'icon' => 'fas fa-arrow-left', 'class' => 'btn-outline-secondary'],
];

if (!$isFaturado && ($apuracao->status ?? '') !== 'rascunho') {
    // Botão Recalcular — visível para concluido e erro (não para faturado)
    $actions[] = [
        'text'       => 'Recalcular Valores',
        'link'       => '#',
        'icon'       => 'fas fa-sync-alt',
        'class'      => 'btn-outline-warning',
        'attributes' => 'onclick="abrirModalRecalcular()" id="btn-recalcular"',
    ];
}

if (($apuracao->status ?? '') === 'concluido') {
    $actions[] = [
        'text'  => 'Faturar Apura&ccedil;&atilde;o',
        'link'  => '/faturamento/apuracao/faturar/' . $apuracao->id,
        'icon'  => 'fas fa-file-invoice-dollar',
        'class' => 'btn-success',
    ];
}

if (!empty($isSuperAdmin)) {
    $saUrl = '/faturamento/apuracao/superadmin-delete/' . $apuracao->id;
    $saNum = htmlspecialchars($apuracao->numero, ENT_QUOTES);
    $actions[] = [
        'text'       => '[SUPERADMIN] Excluir em Cascata',
        'link'       => '#',
        'icon'       => 'fas fa-skull-crossbones',
        'class'      => 'btn-danger',
        'attributes' => 'onclick="confirmarExclusaoSuperAdmin(\'' . $saUrl . '\', \'' . $saNum . '\')"',
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

<?php if ($isFaturado): ?>
<!-- Alerta de somente leitura -->
<div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
    <i class="fas fa-lock me-3 fs-5"></i>
    <div>
        <strong>Apuração Faturada — Somente Leitura.</strong>
        Esta apuração já gerou uma conta financeira e não pode ser alterada ou recalculada.
    </div>
</div>
<?php endif; ?>

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
                    <span class="fw-bold fs-5" id="resumo-total-exames"><?php echo number_format((int)$apuracao->total_exames); ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted"><i class="fas fa-circle text-success me-1" style="font-size:.6rem"></i>Normal</span>
                    <span class="badge bg-success fs-6" id="resumo-total-normal"><?php echo number_format((int)$apuracao->total_normal); ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted"><i class="fas fa-circle text-danger me-1" style="font-size:.6rem"></i>Urg&ecirc;ncia</span>
                    <span class="badge bg-danger fs-6" id="resumo-total-urgencia"><?php echo number_format((int)$apuracao->total_urgencia); ?></span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-muted small"><i class="fas fa-hand-holding-usd me-1"></i>Custo (Prestador)</span>
                    <span class="fw-semibold text-warning" id="resumo-valor-total">R$&nbsp;<?php echo number_format((float)$apuracao->valor_total, 2, ',', '.'); ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold small"><i class="fas fa-tag me-1"></i>Venda (Cliente)</span>
                    <span class="fw-bold text-success fs-5" id="resumo-valor-venda">R$&nbsp;<?php echo number_format((float)($apuracao->valor_venda_total ?? $apuracao->valor_total), 2, ',', '.'); ?></span>
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

<?php if (!$isFaturado): ?>
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
                    Esta ação irá <strong>recalcular todos os valores</strong> dos itens desta apuração com base na
                    <strong>tabela de preços atual</strong>, sem reimportar o arquivo.
                </p>
                <ul class="text-muted small mb-3">
                    <li>Os dados originais (médico, modalidade, datas, paciente) serão preservados.</li>
                    <li>Apenas os valores calculados e o match de exame serão atualizados.</li>
                    <li>O status voltará para <strong>Concluído</strong> após o recálculo.</li>
                </ul>
                <div class="alert alert-warning border-0 mb-0 small">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Confirme apenas se a tabela de preços foi atualizada e deseja aplicar os novos valores.
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

<!-- ============================================================
     MODAL DE RESULTADO — RECALCULAR
     ============================================================ -->
<div class="modal fade" id="modalResultadoRecalculo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-check-circle me-2 text-success"></i>Recálculo Concluído
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="resultado-recalculo-body">
                <!-- Preenchido via JS -->
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-refresh me-1"></i>Atualizar Página
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const APURACAO_ID   = <?php echo (int) $apuracao->id; ?>;
const CSRF_TOKEN    = '<?php echo View::csrfToken(); ?>';

function abrirModalRecalcular() {
    const modal = new bootstrap.Modal(document.getElementById('modalRecalcular'));
    modal.show();
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
        // Fechar modal de confirmação
        bootstrap.Modal.getInstance(document.getElementById('modalRecalcular')).hide();

        if (data.success) {
            // Atualizar resumo financeiro na página
            const el = (id) => document.getElementById(id);
            if (el('resumo-total-exames'))  el('resumo-total-exames').textContent  = data.total_exames;
            if (el('resumo-total-normal'))  el('resumo-total-normal').textContent  = data.total_normal;
            if (el('resumo-total-urgencia')) el('resumo-total-urgencia').textContent = data.total_urgencia;
            if (el('resumo-valor-total'))   el('resumo-valor-total').innerHTML     = 'R$&nbsp;' + data.valor_total.replace('R$ ', '');
            if (el('resumo-valor-venda') && data.valor_venda_total)  el('resumo-valor-venda').innerHTML  = 'R$&nbsp;' + data.valor_venda_total.replace('R$ ', '');

            // Exibir resultado
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
                        <small class="text-muted">Urg\u00eancia</small>
                    </div>
                </div>
                <div class="row g-2 text-center mb-3">
                    <div class="col-6">
                        <span class="fw-bold text-warning fs-4">${data.valor_total}</span>
                        <div class="text-muted small">Custo (Prestador)</div>
                    </div>
                    <div class="col-6">
                        <span class="fw-bold text-success fs-4">${data.valor_venda_total || data.valor_total}</span>
                        <div class="text-muted small">Venda (Cliente)</div>
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

// Exclusão em cascata exclusiva do SUPERADMIN
function confirmarExclusaoSuperAdmin(url, numero) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '⚠️ Exclusão Permanente (SUPERADMIN)',
            html: '<p>Você está prestes a excluir <strong>' + numero + '</strong> e <strong>todas as suas ramificações</strong>:</p>'
                + '<ul class="text-start small mt-2">'
                + '<li>Apuração principal e todos os itens</li>'
                + '<li>Sub-apurações de prestador vinculadas</li>'
                + '<li>Contas a Pagar geradas</li>'
                + '<li>Contas a Receber geradas</li>'
                + '</ul>'
                + '<p class="text-danger fw-bold mt-2">Esta ação é irreversível e não pode ser desfeita!</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-skull-crossbones me-1"></i> Excluir Tudo',
            cancelButtonText: 'Cancelar',
            focusCancel: true,
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    } else {
        if (confirm('[SUPERADMIN] Excluir ' + numero + ' e todas as ramificações?\n\nESTA AÇÃO É IRREVERSÍVEL!')) {
            window.location.href = url;
        }
    }
}
</script>
<?php endif; ?>
