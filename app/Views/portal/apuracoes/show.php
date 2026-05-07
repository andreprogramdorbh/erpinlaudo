<?php
$statusMap = [
    'rascunho'    => ['label' => 'Rascunho',    'class' => 'portal-badge-secondary'],
    'processando' => ['label' => 'Processando', 'class' => 'portal-badge-info'],
    'concluido'   => ['label' => 'Concluída',   'class' => 'portal-badge-primary'],
    'faturado'    => ['label' => 'Faturada',    'class' => 'portal-badge-success'],
    'erro'        => ['label' => 'Erro',        'class' => 'portal-badge-danger'],
];
$statusContaMap = [
    'aberta'    => ['label' => 'Em Aberto', 'class' => 'portal-badge-warning'],
    'recebida'  => ['label' => 'Pago',      'class' => 'portal-badge-success'],
    'cancelada' => ['label' => 'Cancelada', 'class' => 'portal-badge-danger'],
];
$valorExibir = (float)(($apuracao->valor_venda_total ?? 0) > 0 ? $apuracao->valor_venda_total : $apuracao->valor_total);
$statusInfo  = $statusMap[$apuracao->status] ?? ['label' => ucfirst($apuracao->status), 'class' => 'portal-badge-secondary'];
$periodoStr  = '';
if (!empty($apuracao->periodo_inicio) && !empty($apuracao->periodo_fim)) {
    $periodoStr = date('d/m/Y', strtotime($apuracao->periodo_inicio)) . ' → ' . date('d/m/Y', strtotime($apuracao->periodo_fim));
}
?>
<style>
.portal-badge-secondary { background:#f3f4f6; color:#374151; border-radius:20px; padding:3px 10px; font-size:.78rem; font-weight:600; display:inline-block; }
.portal-badge-info      { background:#dbeafe; color:#1d4ed8; border-radius:20px; padding:3px 10px; font-size:.78rem; font-weight:600; display:inline-block; }
.portal-badge-primary   { background:#ede9fe; color:#6d28d9; border-radius:20px; padding:3px 10px; font-size:.78rem; font-weight:600; display:inline-block; }
.portal-badge-success   { background:#dcfce7; color:#15803d; border-radius:20px; padding:3px 10px; font-size:.78rem; font-weight:600; display:inline-block; }
.portal-badge-danger    { background:#fee2e2; color:#dc2626; border-radius:20px; padding:3px 10px; font-size:.78rem; font-weight:600; display:inline-block; }
.portal-badge-warning   { background:#fef9c3; color:#854d0e; border-radius:20px; padding:3px 10px; font-size:.78rem; font-weight:600; display:inline-block; }
.apuracao-header-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 20px;
}
.apuracao-header-top { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:16px; }
.apuracao-header-title { font-size:1.25rem; font-weight:700; color:#111827; }
.apuracao-header-sub { font-size:.85rem; color:#6b7280; margin-top:4px; }
.apuracao-kpis { display:flex; gap:24px; flex-wrap:wrap; padding-top:16px; border-top:1px solid #f3f4f6; }
.kpi-item { display:flex; flex-direction:column; }
.kpi-label { font-size:.75rem; color:#9ca3af; text-transform:uppercase; letter-spacing:.04em; }
.kpi-value { font-size:1.1rem; font-weight:700; color:#111827; margin-top:2px; }
.kpi-value.valor { color:#1d4ed8; font-size:1.2rem; }
.section-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 16px;
}
.section-card-title { font-size:.95rem; font-weight:700; color:#374151; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.section-card-title i { color:#6d28d9; }
.portal-table { width:100%; border-collapse:collapse; font-size:.875rem; }
.portal-table th { background:#f9fafb; color:#374151; font-weight:600; padding:10px 12px; text-align:left; border-bottom:2px solid #e5e7eb; font-size:.8rem; text-transform:uppercase; letter-spacing:.04em; }
.portal-table td { padding:10px 12px; border-bottom:1px solid #f3f4f6; color:#111827; vertical-align:middle; }
.portal-table tr:last-child td { border-bottom:none; }
.portal-table tr:hover td { background:#f9fafb; }
.portal-table .text-right { text-align:right; }
.portal-table .text-center { text-align:center; }
.total-row td { font-weight:700; background:#f0fdf4 !important; color:#15803d; border-top:2px solid #bbf7d0; }
.conta-vinculada-card {
    background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%);
    border: 1px solid #bfdbfe;
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 16px;
}
.conta-vinculada-title { font-size:.95rem; font-weight:700; color:#1d4ed8; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
.conta-vinculada-info { display:flex; gap:24px; flex-wrap:wrap; }
.conta-info-item { display:flex; flex-direction:column; }
.conta-info-label { font-size:.75rem; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; }
.conta-info-value { font-size:.95rem; font-weight:600; color:#111827; margin-top:2px; }
.btn-back { background:#f3f4f6; color:#374151; border:1px solid #d1d5db; border-radius:8px; padding:8px 16px; font-size:.85rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
.btn-back:hover { background:#e5e7eb; color:#111827; }
.btn-pagar-conta { background:#1d4ed8; color:#fff; border:none; border-radius:8px; padding:8px 18px; font-size:.85rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
.btn-pagar-conta:hover { background:#1e40af; color:#fff; }
</style>

<!-- Navegação -->
<div class="mb-3">
    <a href="/portal/apuracoes" class="btn-back">
        <i class="fa fa-arrow-left"></i> Voltar para Apurações
    </a>
</div>

<!-- Cabeçalho da apuração -->
<div class="apuracao-header-card">
    <div class="apuracao-header-top">
        <div>
            <div class="apuracao-header-title">
                <i class="fa fa-chart-bar me-2 text-primary"></i>
                Apuração <?php echo htmlspecialchars($apuracao->numero); ?>
            </div>
            <?php if (!empty($apuracao->contrato_nome)): ?>
            <div class="apuracao-header-sub">
                <i class="fa fa-file-contract me-1"></i>
                <?php echo htmlspecialchars($apuracao->contrato_nome); ?>
                <?php if (!empty($apuracao->contrato_numero)): ?>
                    — Nº <?php echo htmlspecialchars($apuracao->contrato_numero); ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($periodoStr): ?>
            <div class="apuracao-header-sub mt-1">
                <i class="fa fa-calendar me-1"></i> <?php echo $periodoStr; ?>
            </div>
            <?php endif; ?>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px">
            <span class="<?php echo $statusInfo['class']; ?>"><?php echo $statusInfo['label']; ?></span>
            <span style="font-size:.8rem;color:#9ca3af">
                Gerada em <?php echo date('d/m/Y', strtotime($apuracao->created_at)); ?>
            </span>
        </div>
    </div>

    <div class="apuracao-kpis">
        <div class="kpi-item">
            <span class="kpi-label">Total de Exames</span>
            <span class="kpi-value"><?php echo number_format((int)($apuracao->total_exames ?? 0)); ?></span>
        </div>
        <div class="kpi-item">
            <span class="kpi-label">Normais</span>
            <span class="kpi-value"><?php echo number_format((int)($apuracao->total_normal ?? 0)); ?></span>
        </div>
        <div class="kpi-item">
            <span class="kpi-label">Urgências</span>
            <span class="kpi-value"><?php echo number_format((int)($apuracao->total_urgencia ?? 0)); ?></span>
        </div>
        <div class="kpi-item">
            <span class="kpi-label">Valor Total</span>
            <span class="kpi-value valor">R$ <?php echo number_format($valorExibir, 2, ',', '.'); ?></span>
        </div>
    </div>
</div>

<!-- Conta a receber vinculada (se existir) -->
<?php if ($contaVinculada): ?>
<?php
$contaStatus = $statusContaMap[$contaVinculada->status] ?? ['label' => ucfirst($contaVinculada->status), 'class' => 'portal-badge-secondary'];
$hoje = date('Y-m-d');
$vencida = $contaVinculada->status === 'aberta' && $contaVinculada->data_vencimento < $hoje;
?>
<div class="conta-vinculada-card">
    <div class="conta-vinculada-title">
        <i class="fa fa-file-invoice-dollar"></i>
        Cobrança Vinculada a esta Apuração
    </div>
    <div class="conta-vinculada-info">
        <div class="conta-info-item">
            <span class="conta-info-label">Valor</span>
            <span class="conta-info-value" style="color:#1d4ed8">R$ <?php echo number_format((float)$contaVinculada->valor, 2, ',', '.'); ?></span>
        </div>
        <div class="conta-info-item">
            <span class="conta-info-label">Vencimento</span>
            <span class="conta-info-value <?php echo $vencida ? 'text-danger' : ''; ?>">
                <?php echo date('d/m/Y', strtotime($contaVinculada->data_vencimento)); ?>
                <?php if ($vencida): ?><span style="color:#dc2626;font-size:.78rem"> (Vencida)</span><?php endif; ?>
            </span>
        </div>
        <div class="conta-info-item">
            <span class="conta-info-label">Status</span>
            <span class="<?php echo $contaStatus['class']; ?>"><?php echo $contaStatus['label']; ?></span>
        </div>
        <?php if ($contaVinculada->status === 'recebida' && !empty($contaVinculada->data_recebimento)): ?>
        <div class="conta-info-item">
            <span class="conta-info-label">Pago em</span>
            <span class="conta-info-value"><?php echo date('d/m/Y', strtotime($contaVinculada->data_recebimento)); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($contaVinculada->status === 'aberta'): ?>
        <div class="conta-info-item" style="margin-left:auto;align-self:center">
            <a href="/portal/contas-a-pagar" class="btn-pagar-conta">
                <i class="fa fa-credit-card"></i> Ir para Pagamento
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Resumo por Modalidade -->
<?php if (!empty($resumoModalidade)): ?>
<div class="section-card">
    <div class="section-card-title">
        <i class="fa fa-th-list"></i> Resumo por Modalidade
    </div>
    <div style="overflow-x:auto">
        <table class="portal-table">
            <thead>
                <tr>
                    <th>Modalidade</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Normais</th>
                    <th class="text-center">Urgências</th>
                    <th class="text-right">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalMod = 0; $totalModValor = 0;
                foreach ($resumoModalidade as $row):
                    $v = (float)(($row->valor_venda ?? 0) > 0 ? $row->valor_venda : $row->valor);
                    $totalMod += (int)$row->total;
                    $totalModValor += $v;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row->modalidade ?? '—'); ?></td>
                    <td class="text-center"><?php echo number_format((int)$row->total); ?></td>
                    <td class="text-center"><?php echo number_format((int)($row->total_normal ?? 0)); ?></td>
                    <td class="text-center"><?php echo number_format((int)($row->total_urgencia ?? 0)); ?></td>
                    <td class="text-right" style="font-weight:600;color:#1d4ed8">R$ <?php echo number_format($v, 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td><strong>Total</strong></td>
                    <td class="text-center"><strong><?php echo number_format($totalMod); ?></strong></td>
                    <td class="text-center">—</td>
                    <td class="text-center">—</td>
                    <td class="text-right"><strong>R$ <?php echo number_format($totalModValor, 2, ',', '.'); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Resumo por Médico -->
<?php if (!empty($resumoMedico)): ?>
<div class="section-card">
    <div class="section-card-title">
        <i class="fa fa-user-md"></i> Resumo por Médico / Prestador
    </div>
    <div style="overflow-x:auto">
        <table class="portal-table">
            <thead>
                <tr>
                    <th>Médico</th>
                    <th>CRM</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Normais</th>
                    <th class="text-center">Urgências</th>
                    <th class="text-right">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalMed = 0; $totalMedValor = 0;
                foreach ($resumoMedico as $row):
                    $v = (float)(($row->valor_venda ?? 0) > 0 ? $row->valor_venda : $row->valor);
                    $totalMed += (int)$row->total;
                    $totalMedValor += $v;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row->medico ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($row->medico_crm ?? '—'); ?></td>
                    <td class="text-center"><?php echo number_format((int)$row->total); ?></td>
                    <td class="text-center"><?php echo number_format((int)($row->total_normal ?? 0)); ?></td>
                    <td class="text-center"><?php echo number_format((int)($row->total_urgencia ?? 0)); ?></td>
                    <td class="text-right" style="font-weight:600;color:#1d4ed8">R$ <?php echo number_format($v, 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2"><strong>Total</strong></td>
                    <td class="text-center"><strong><?php echo number_format($totalMed); ?></strong></td>
                    <td class="text-center">—</td>
                    <td class="text-center">—</td>
                    <td class="text-right"><strong>R$ <?php echo number_format($totalMedValor, 2, ',', '.'); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Resumo por Unidade -->
<?php if (!empty($resumoUnidade)): ?>
<div class="section-card">
    <div class="section-card-title">
        <i class="fa fa-hospital"></i> Resumo por Unidade
    </div>
    <div style="overflow-x:auto">
        <table class="portal-table">
            <thead>
                <tr>
                    <th>Unidade</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Normais</th>
                    <th class="text-center">Urgências</th>
                    <th class="text-right">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalUni = 0; $totalUniValor = 0;
                foreach ($resumoUnidade as $row):
                    $v = (float)(($row->valor_venda ?? 0) > 0 ? $row->valor_venda : $row->valor);
                    $totalUni += (int)$row->total;
                    $totalUniValor += $v;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row->unidade ?? '—'); ?></td>
                    <td class="text-center"><?php echo number_format((int)$row->total); ?></td>
                    <td class="text-center"><?php echo number_format((int)($row->total_normal ?? 0)); ?></td>
                    <td class="text-center"><?php echo number_format((int)($row->total_urgencia ?? 0)); ?></td>
                    <td class="text-right" style="font-weight:600;color:#1d4ed8">R$ <?php echo number_format($v, 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td><strong>Total</strong></td>
                    <td class="text-center"><strong><?php echo number_format($totalUni); ?></strong></td>
                    <td class="text-center">—</td>
                    <td class="text-center">—</td>
                    <td class="text-right"><strong>R$ <?php echo number_format($totalUniValor, 2, ',', '.'); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Rodapé -->
<div style="text-align:center;margin-top:20px">
    <a href="/portal/apuracoes" class="btn-back">
        <i class="fa fa-arrow-left"></i> Voltar para Apurações
    </a>
</div>
