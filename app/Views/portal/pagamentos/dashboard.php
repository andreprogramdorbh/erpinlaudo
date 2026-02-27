<?php
$totalContas    = ($statusTotais['aberta'] + $statusTotais['recebida'] + $statusTotais['cancelada']);
$totalValorGeral= ($statusValores['aberta'] + $statusValores['recebida']);
$pctPago        = $totalValorGeral > 0 ? round(($statusValores['recebida'] / $totalValorGeral) * 100, 1) : 0;
$hoje           = date('Y-m-d');

// Prepara JSON para Chart.js
$jsStatusLabels  = json_encode(['Em Aberto', 'Pagas', 'Canceladas']);
$jsStatusCounts  = json_encode([$statusTotais['aberta'], $statusTotais['recebida'], $statusTotais['cancelada']]);
$jsStatusValores = json_encode([$statusValores['aberta'], $statusValores['recebida'], $statusValores['cancelada']]);
$jsMeioLabels    = json_encode($meioLabels);
$jsMeioCounts    = json_encode($meioCounts);
$jsMeioValores   = json_encode($meioValores);
$jsMesesLabels   = json_encode($mesesLabels);
$jsMensalAberta  = json_encode($mensalAberta);
$jsMensalRecebida= json_encode($mensalRecebida);
?>

<div class="portal-page-header">
    <div>
        <h1 class="portal-page-title"><i class="fa fa-chart-pie me-2"></i>Meu Financeiro</h1>
        <p class="portal-page-subtitle">Visão geral dos seus pagamentos</p>
    </div>
    <a href="/portal/contas-a-pagar" class="portal-btn portal-btn-primary portal-btn-sm">
        <i class="fa fa-file-invoice-dollar me-1"></i> Ver Contas
    </a>
</div>

<!-- KPIs principais -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="pf-kpi-card pf-kpi-blue">
            <div class="pf-kpi-icon"><i class="fa fa-list-alt"></i></div>
            <div class="pf-kpi-body">
                <div class="pf-kpi-value"><?php echo $totalContas; ?></div>
                <div class="pf-kpi-label">Total de Contas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pf-kpi-card pf-kpi-green">
            <div class="pf-kpi-icon"><i class="fa fa-check-circle"></i></div>
            <div class="pf-kpi-body">
                <div class="pf-kpi-value"><?php echo $statusTotais['recebida']; ?></div>
                <div class="pf-kpi-label">Pagas</div>
                <div class="pf-kpi-sub">R$ <?php echo number_format($statusValores['recebida'], 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pf-kpi-card <?php echo $statusTotais['aberta'] > 0 ? 'pf-kpi-yellow' : 'pf-kpi-green'; ?>">
            <div class="pf-kpi-icon"><i class="fa fa-clock"></i></div>
            <div class="pf-kpi-body">
                <div class="pf-kpi-value"><?php echo $statusTotais['aberta']; ?></div>
                <div class="pf-kpi-label">Em Aberto</div>
                <div class="pf-kpi-sub">R$ <?php echo number_format($statusValores['aberta'], 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pf-kpi-card <?php echo ($vencidas->total ?? 0) > 0 ? 'pf-kpi-red' : 'pf-kpi-green'; ?>">
            <div class="pf-kpi-icon"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="pf-kpi-body">
                <div class="pf-kpi-value"><?php echo (int)($vencidas->total ?? 0); ?></div>
                <div class="pf-kpi-label">Vencidas</div>
                <?php if (($vencidas->valor_total ?? 0) > 0): ?>
                <div class="pf-kpi-sub">R$ <?php echo number_format((float)$vencidas->valor_total, 2, ',', '.'); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Barra de progresso de pagamentos -->
<?php if ($totalValorGeral > 0): ?>
<div class="pf-progress-section mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="fw-semibold small">Progresso de Pagamentos</span>
        <span class="fw-bold text-success"><?php echo $pctPago; ?>% pago</span>
    </div>
    <div class="pf-progress-bar-bg">
        <div class="pf-progress-bar-fill" style="width: <?php echo min(100, $pctPago); ?>%"></div>
    </div>
    <div class="d-flex justify-content-between mt-1">
        <span class="small text-muted">R$ <?php echo number_format($statusValores['recebida'], 2, ',', '.'); ?> pago</span>
        <span class="small text-muted">R$ <?php echo number_format($totalValorGeral, 2, ',', '.'); ?> total</span>
    </div>
</div>
<?php endif; ?>

<!-- Gráficos: Pizza (status) + Pizza (meio) -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="pf-chart-card">
            <div class="pf-chart-header">
                <i class="fa fa-chart-pie me-2 text-primary"></i>
                <span>Distribuição por Status</span>
            </div>
            <div class="pf-chart-body">
                <?php if ($totalContas === 0): ?>
                    <div class="pf-chart-empty"><i class="fa fa-chart-pie fa-2x text-muted mb-2 d-block"></i>Sem dados ainda</div>
                <?php else: ?>
                    <div class="pf-chart-canvas-wrap">
                        <canvas id="chartStatus"></canvas>
                    </div>
                    <div class="pf-chart-legend" id="legendStatus"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="pf-chart-card">
            <div class="pf-chart-header">
                <i class="fa fa-credit-card me-2 text-success"></i>
                <span>Formas de Pagamento (Pagas)</span>
            </div>
            <div class="pf-chart-body">
                <?php if (empty($meioLabels)): ?>
                    <div class="pf-chart-empty"><i class="fa fa-credit-card fa-2x text-muted mb-2 d-block"></i>Nenhum pagamento realizado ainda</div>
                <?php else: ?>
                    <div class="pf-chart-canvas-wrap">
                        <canvas id="chartMeio"></canvas>
                    </div>
                    <div class="pf-chart-legend" id="legendMeio"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Gráfico de barras: Evolução mensal -->
<div class="pf-chart-card mb-4">
    <div class="pf-chart-header">
        <i class="fa fa-chart-bar me-2 text-warning"></i>
        <span>Evolução Mensal — Últimos 12 Meses</span>
    </div>
    <div class="pf-chart-body">
        <div class="pf-chart-canvas-wrap pf-chart-wide">
            <canvas id="chartMensal"></canvas>
        </div>
    </div>
</div>

<!-- Tabela resumo por meio de pagamento -->
<?php if (!empty($meioLabels)): ?>
<div class="pf-chart-card mb-4">
    <div class="pf-chart-header">
        <i class="fa fa-table me-2 text-info"></i>
        <span>Resumo por Forma de Pagamento</span>
    </div>
    <div class="pf-chart-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Forma</th>
                        <th class="text-center">Qtd.</th>
                        <th class="text-end pe-3">Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meioLabels as $i => $label): ?>
                    <tr>
                        <td class="ps-3"><?php echo htmlspecialchars($label); ?></td>
                        <td class="text-center"><?php echo $meioCounts[$i]; ?></td>
                        <td class="text-end pe-3 fw-semibold text-success">
                            R$ <?php echo number_format($meioValores[$i], 2, ',', '.'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* KPI Cards */
.pf-kpi-card{display:flex;align-items:center;gap:.875rem;padding:.875rem 1rem;border-radius:var(--portal-radius);border:1px solid var(--portal-border);background:var(--portal-surface);box-shadow:var(--portal-shadow)}
.pf-kpi-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0}
.pf-kpi-blue .pf-kpi-icon{background:#dbeafe;color:#1d4ed8}
.pf-kpi-green .pf-kpi-icon{background:#d1fae5;color:#065f46}
.pf-kpi-yellow .pf-kpi-icon{background:#fef3c7;color:#92400e}
.pf-kpi-red .pf-kpi-icon{background:#fee2e2;color:#991b1b}
.pf-kpi-value{font-size:1.375rem;font-weight:700;line-height:1.2}
.pf-kpi-label{font-size:.75rem;color:var(--portal-muted)}
.pf-kpi-sub{font-size:.7rem;color:var(--portal-muted)}

/* Barra de progresso */
.pf-progress-section{background:var(--portal-surface);border:1px solid var(--portal-border);border-radius:var(--portal-radius);padding:1rem 1.25rem}
.pf-progress-bar-bg{height:12px;background:#e5e7eb;border-radius:6px;overflow:hidden}
.pf-progress-bar-fill{height:100%;background:linear-gradient(90deg,#10b981,#059669);border-radius:6px;transition:width .6s ease}

/* Chart cards */
.pf-chart-card{background:var(--portal-surface);border:1px solid var(--portal-border);border-radius:var(--portal-radius);overflow:hidden;box-shadow:var(--portal-shadow)}
.pf-chart-header{display:flex;align-items:center;padding:.875rem 1.25rem;border-bottom:1px solid var(--portal-border);font-weight:600;font-size:.9375rem}
.pf-chart-body{padding:1.25rem}
.pf-chart-canvas-wrap{position:relative;height:240px;width:100%}
.pf-chart-wide{height:280px}
.pf-chart-empty{text-align:center;padding:3rem 1rem;color:var(--portal-muted);font-size:.875rem}
.pf-chart-legend{display:flex;flex-wrap:wrap;gap:.5rem .75rem;margin-top:1rem;justify-content:center}
.pf-legend-item{display:flex;align-items:center;gap:6px;font-size:.8125rem}
.pf-legend-dot{width:12px;height:12px;border-radius:3px;flex-shrink:0}
</style>

<!-- Chart.js via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
'use strict';

// Paleta de cores
var COLORS_STATUS = ['#f59e0b','#10b981','#6b7280'];
var COLORS_MEIO   = ['#6366f1','#f59e0b','#10b981','#3b82f6','#ef4444','#8b5cf6','#14b8a6'];
var COLOR_ABERTA  = 'rgba(245,158,11,0.7)';
var COLOR_RECEBIDA= 'rgba(16,185,129,0.85)';

// Formata moeda BR
function fmtBRL(v){return'R$ '+parseFloat(v).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});}

// Cria legenda customizada
function criarLegenda(containerId, labels, colors, values){
    var el=document.getElementById(containerId);
    if(!el)return;
    el.innerHTML='';
    labels.forEach(function(lbl,i){
        var item=document.createElement('div');item.className='pf-legend-item';
        var dot=document.createElement('div');dot.className='pf-legend-dot';dot.style.background=colors[i]||'#ccc';
        var txt=document.createElement('span');txt.textContent=lbl+(values?(' — '+fmtBRL(values[i])):'');
        item.appendChild(dot);item.appendChild(txt);el.appendChild(item);
    });
}

// Opções comuns para pizza
var piOpts={
    responsive:true,maintainAspectRatio:false,
    plugins:{legend:{display:false},tooltip:{callbacks:{label:function(ctx){return' '+ctx.label+': '+fmtBRL(ctx.raw);}}}}
};

// ---------------------------------------------------------------
// Gráfico 1 — Pizza: Status
// ---------------------------------------------------------------
var statusLabels  = <?php echo $jsStatusLabels; ?>;
var statusValores = <?php echo $jsStatusValores; ?>;
var ctxStatus = document.getElementById('chartStatus');
if(ctxStatus){
    new Chart(ctxStatus,{
        type:'doughnut',
        data:{labels:statusLabels,datasets:[{data:statusValores,backgroundColor:COLORS_STATUS,borderWidth:2,borderColor:'#fff',hoverOffset:6}]},
        options:piOpts
    });
    criarLegenda('legendStatus',statusLabels,COLORS_STATUS,statusValores);
}

// ---------------------------------------------------------------
// Gráfico 2 — Pizza: Meio de Pagamento
// ---------------------------------------------------------------
var meioLabels  = <?php echo $jsMeioLabels; ?>;
var meioValores = <?php echo $jsMeioValores; ?>;
var ctxMeio = document.getElementById('chartMeio');
if(ctxMeio&&meioLabels.length>0){
    new Chart(ctxMeio,{
        type:'doughnut',
        data:{labels:meioLabels,datasets:[{data:meioValores,backgroundColor:COLORS_MEIO.slice(0,meioLabels.length),borderWidth:2,borderColor:'#fff',hoverOffset:6}]},
        options:piOpts
    });
    criarLegenda('legendMeio',meioLabels,COLORS_MEIO,meioValores);
}

// ---------------------------------------------------------------
// Gráfico 3 — Barras: Evolução Mensal
// ---------------------------------------------------------------
var mesesLabels   = <?php echo $jsMesesLabels; ?>;
var mensalAberta  = <?php echo $jsMensalAberta; ?>;
var mensalRecebida= <?php echo $jsMensalRecebida; ?>;
var ctxMensal = document.getElementById('chartMensal');
if(ctxMensal){
    new Chart(ctxMensal,{
        type:'bar',
        data:{
            labels:mesesLabels,
            datasets:[
                {label:'Em Aberto',data:mensalAberta,backgroundColor:COLOR_ABERTA,borderRadius:4,borderSkipped:false},
                {label:'Pagas',data:mensalRecebida,backgroundColor:COLOR_RECEBIDA,borderRadius:4,borderSkipped:false}
            ]
        },
        options:{
            responsive:true,maintainAspectRatio:false,
            plugins:{
                legend:{position:'top',labels:{boxWidth:14,font:{size:12}}},
                tooltip:{callbacks:{label:function(ctx){return' '+ctx.dataset.label+': '+fmtBRL(ctx.raw);}}}
            },
            scales:{
                x:{grid:{display:false},ticks:{font:{size:11}}},
                y:{beginAtZero:true,ticks:{callback:function(v){return'R$ '+v.toLocaleString('pt-BR');}},grid:{color:'rgba(0,0,0,.05)'}}
            }
        }
    });
}

})();
</script>
