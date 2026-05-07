<?php
$statusMap = [
    'rascunho'    => ['label' => 'Rascunho',    'class' => 'portal-badge-secondary'],
    'processando' => ['label' => 'Processando', 'class' => 'portal-badge-info'],
    'concluido'   => ['label' => 'Concluída',   'class' => 'portal-badge-primary'],
    'faturado'    => ['label' => 'Faturada',    'class' => 'portal-badge-success'],
    'erro'        => ['label' => 'Erro',        'class' => 'portal-badge-danger'],
];
?>
<style>
.portal-badge-secondary { background:#f3f4f6; color:#374151; border-radius:20px; padding:3px 10px; font-size:.78rem; font-weight:600; display:inline-block; }
.portal-badge-info      { background:#dbeafe; color:#1d4ed8; border-radius:20px; padding:3px 10px; font-size:.78rem; font-weight:600; display:inline-block; }
.portal-badge-primary   { background:#ede9fe; color:#6d28d9; border-radius:20px; padding:3px 10px; font-size:.78rem; font-weight:600; display:inline-block; }
.portal-badge-success   { background:#dcfce7; color:#15803d; border-radius:20px; padding:3px 10px; font-size:.78rem; font-weight:600; display:inline-block; }
.portal-badge-danger    { background:#fee2e2; color:#dc2626; border-radius:20px; padding:3px 10px; font-size:.78rem; font-weight:600; display:inline-block; }
.portal-badge-warning   { background:#fef9c3; color:#854d0e; border-radius:20px; padding:3px 10px; font-size:.78rem; font-weight:600; display:inline-block; }
.apuracao-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 14px;
    transition: box-shadow .2s;
}
.apuracao-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.apuracao-card-header { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px; margin-bottom:12px; }
.apuracao-numero { font-weight:700; font-size:1rem; color:#111827; }
.apuracao-contrato { font-size:.82rem; color:#6b7280; margin-top:2px; }
.apuracao-periodo { font-size:.82rem; color:#6b7280; }
.apuracao-stats { display:flex; gap:20px; flex-wrap:wrap; margin-top:10px; }
.apuracao-stat { display:flex; flex-direction:column; }
.apuracao-stat-label { font-size:.75rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.04em; }
.apuracao-stat-value { font-size:.95rem; font-weight:700; color:#111827; margin-top:2px; }
.apuracao-stat-value.valor { color:#1d4ed8; }
.apuracao-footer { display:flex; justify-content:space-between; align-items:center; margin-top:14px; padding-top:12px; border-top:1px solid #f3f4f6; }
.btn-ver-apuracao { background:#1d4ed8; color:#fff; border:none; border-radius:8px; padding:7px 16px; font-size:.85rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:background .2s; }
.btn-ver-apuracao:hover { background:#1e40af; color:#fff; }
.portal-filter-bar { background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:14px 18px; margin-bottom:20px; }
.portal-filter-bar form { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
.portal-filter-bar .form-group { display:flex; flex-direction:column; gap:4px; }
.portal-filter-bar label { font-size:.8rem; font-weight:600; color:#374151; }
.portal-filter-bar select,
.portal-filter-bar input { border:1px solid #d1d5db; border-radius:7px; padding:6px 10px; font-size:.85rem; background:#fff; color:#111827; }
.portal-filter-bar .btn-filtrar { background:#1d4ed8; color:#fff; border:none; border-radius:7px; padding:7px 16px; font-size:.85rem; font-weight:600; cursor:pointer; }
.portal-filter-bar .btn-limpar { background:#f3f4f6; color:#374151; border:1px solid #d1d5db; border-radius:7px; padding:7px 14px; font-size:.85rem; font-weight:600; text-decoration:none; }
.empty-state { text-align:center; padding:48px 20px; color:#6b7280; }
.empty-state i { font-size:3rem; color:#d1d5db; margin-bottom:16px; }
.empty-state h3 { font-size:1.1rem; font-weight:600; color:#374151; margin-bottom:8px; }
</style>

<div class="portal-page-header">
    <div>
        <h1 class="portal-page-title"><i class="fa fa-chart-bar me-2"></i>Minhas Apurações</h1>
        <p class="portal-page-subtitle">Acompanhe o faturamento e os relatórios das suas apurações</p>
    </div>
</div>

<?php if (!empty($_GET['error'])): ?>
<div class="portal-alert portal-alert-danger mb-3">
    <i class="fa fa-exclamation-circle me-2"></i>
    <?php
    $erros = ['nao_autorizado' => 'Acesso não autorizado a esta apuração.'];
    echo htmlspecialchars($erros[$_GET['error']] ?? 'Ocorreu um erro. Tente novamente.');
    ?>
</div>
<?php endif; ?>

<!-- Cards de resumo -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="portal-summary-card">
            <div class="portal-summary-icon text-primary"><i class="fa fa-chart-bar"></i></div>
            <div class="portal-summary-info">
                <div class="portal-summary-value"><?php echo $totalApuracoes; ?></div>
                <div class="portal-summary-label">Total</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="portal-summary-card">
            <div class="portal-summary-icon text-success"><i class="fa fa-check-circle"></i></div>
            <div class="portal-summary-info">
                <div class="portal-summary-value"><?php echo $totalFaturadas; ?></div>
                <div class="portal-summary-label">Faturadas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="portal-summary-card">
            <div class="portal-summary-icon text-warning"><i class="fa fa-stethoscope"></i></div>
            <div class="portal-summary-info">
                <div class="portal-summary-value"><?php echo number_format($totalExames); ?></div>
                <div class="portal-summary-label">Exames</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="portal-summary-card">
            <div class="portal-summary-icon text-info"><i class="fa fa-dollar-sign"></i></div>
            <div class="portal-summary-info">
                <div class="portal-summary-value" style="font-size:.95rem">R$ <?php echo number_format($totalValorVenda, 2, ',', '.'); ?></div>
                <div class="portal-summary-label">Valor Total</div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="portal-filter-bar">
    <form method="GET" action="/portal/apuracoes">
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="">Todos</option>
                <option value="concluido"  <?php echo $filtroStatus === 'concluido'  ? 'selected' : ''; ?>>Concluída</option>
                <option value="faturado"   <?php echo $filtroStatus === 'faturado'   ? 'selected' : ''; ?>>Faturada</option>
                <option value="processando"<?php echo $filtroStatus === 'processando'? 'selected' : ''; ?>>Processando</option>
                <option value="rascunho"   <?php echo $filtroStatus === 'rascunho'   ? 'selected' : ''; ?>>Rascunho</option>
            </select>
        </div>
        <div class="form-group">
            <label>Período (mês)</label>
            <input type="month" name="periodo" value="<?php echo htmlspecialchars($filtroPeriodo); ?>">
        </div>
        <button type="submit" class="btn-filtrar"><i class="fa fa-filter me-1"></i> Filtrar</button>
        <?php if ($filtroStatus !== '' || $filtroPeriodo !== ''): ?>
        <a href="/portal/apuracoes" class="btn-limpar">Limpar</a>
        <?php endif; ?>
    </form>
</div>

<!-- Lista de apurações -->
<?php if (empty($apuracoes)): ?>
<div class="empty-state">
    <i class="fa fa-chart-bar d-block"></i>
    <h3>Nenhuma apuração encontrada</h3>
    <p>Não há apurações <?php echo $filtroStatus !== '' || $filtroPeriodo !== '' ? 'com os filtros selecionados' : 'disponíveis para o seu cadastro'; ?>.</p>
    <?php if ($filtroStatus !== '' || $filtroPeriodo !== ''): ?>
    <a href="/portal/apuracoes" class="btn-ver-apuracao mt-2">Ver todas</a>
    <?php endif; ?>
</div>
<?php else: ?>
<?php foreach ($apuracoes as $ap):
    $valorExibir = (float)(($ap->valor_venda_total ?? 0) > 0 ? $ap->valor_venda_total : $ap->valor_total);
    $statusInfo  = $statusMap[$ap->status] ?? ['label' => ucfirst($ap->status), 'class' => 'portal-badge-secondary'];
    $periodoStr  = '';
    if (!empty($ap->periodo_inicio) && !empty($ap->periodo_fim)) {
        $periodoStr = date('d/m/Y', strtotime($ap->periodo_inicio)) . ' → ' . date('d/m/Y', strtotime($ap->periodo_fim));
    } elseif (!empty($ap->periodo_inicio)) {
        $periodoStr = 'A partir de ' . date('d/m/Y', strtotime($ap->periodo_inicio));
    }
?>
<div class="apuracao-card">
    <div class="apuracao-card-header">
        <div>
            <div class="apuracao-numero">
                <i class="fa fa-chart-bar me-2 text-primary"></i>
                Apuração <?php echo htmlspecialchars($ap->numero); ?>
            </div>
            <?php if (!empty($ap->contrato_nome)): ?>
            <div class="apuracao-contrato">
                <i class="fa fa-file-contract me-1"></i>
                <?php echo htmlspecialchars($ap->contrato_nome); ?>
                <?php if (!empty($ap->contrato_numero)): ?>
                    — Nº <?php echo htmlspecialchars($ap->contrato_numero); ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px">
            <span class="<?php echo $statusInfo['class']; ?>"><?php echo $statusInfo['label']; ?></span>
            <?php if ($periodoStr): ?>
            <span class="apuracao-periodo"><i class="fa fa-calendar me-1"></i><?php echo $periodoStr; ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="apuracao-stats">
        <div class="apuracao-stat">
            <span class="apuracao-stat-label">Total de Exames</span>
            <span class="apuracao-stat-value"><?php echo number_format((int)($ap->total_exames ?? 0)); ?></span>
        </div>
        <div class="apuracao-stat">
            <span class="apuracao-stat-label">Normais</span>
            <span class="apuracao-stat-value"><?php echo number_format((int)($ap->total_normal ?? 0)); ?></span>
        </div>
        <div class="apuracao-stat">
            <span class="apuracao-stat-label">Urgências</span>
            <span class="apuracao-stat-value"><?php echo number_format((int)($ap->total_urgencia ?? 0)); ?></span>
        </div>
        <div class="apuracao-stat">
            <span class="apuracao-stat-label">Valor</span>
            <span class="apuracao-stat-value valor">R$ <?php echo number_format($valorExibir, 2, ',', '.'); ?></span>
        </div>
        <div class="apuracao-stat">
            <span class="apuracao-stat-label">Gerada em</span>
            <span class="apuracao-stat-value" style="font-size:.85rem"><?php echo date('d/m/Y', strtotime($ap->created_at)); ?></span>
        </div>
    </div>

    <div class="apuracao-footer">
        <span style="font-size:.8rem;color:#9ca3af">
            <?php if ($ap->status === 'faturado'): ?>
            <i class="fa fa-check-circle text-success me-1"></i> Faturamento gerado
            <?php elseif ($ap->status === 'concluido'): ?>
            <i class="fa fa-clock text-warning me-1"></i> Aguardando faturamento
            <?php else: ?>
            <i class="fa fa-info-circle text-muted me-1"></i> <?php echo $statusInfo['label']; ?>
            <?php endif; ?>
        </span>
        <a href="/portal/apuracoes/<?php echo $ap->id; ?>" class="btn-ver-apuracao">
            <i class="fa fa-eye"></i> Ver Relatório
        </a>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
