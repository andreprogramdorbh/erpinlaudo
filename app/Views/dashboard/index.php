<?php
use App\Core\Auth;


// Helpers
function brl($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function pct($a,$b){ return $b > 0 ? round(($a/$b)*100,1) : 0; }
function mesLabel($ym){
    $meses=['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun',
            '07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];
    [$y,$m] = explode('-',$ym);
    return ($meses[$m]??$m).'/'.$y;
}

$nomeUsuario = $usuario->name ?? 'Usuário';
$saudacao = (date('H') < 12) ? 'Bom dia' : ((date('H') < 18) ? 'Boa tarde' : 'Boa noite');

// Resultado do mes
$recebidoMes = (float)($resultado->recebido_mes ?? 0);
$pagoMes     = (float)($resultado->pago_mes ?? 0);
$saldoMes    = $recebidoMes - $pagoMes;

// Evolucao mensal — prepara arrays para Chart.js
$evoLabels = $evoReceber = $evoPagar = [];
foreach (($evolucaoMensal ?? []) as $e) {
    $evoLabels[]  = mesLabel($e->mes);
    $evoReceber[] = (float)$e->receber;
    $evoPagar[]   = (float)$e->pagar;
}

// Faturamento mensal
$fatLabels = $fatValores = [];
foreach (($faturamentoMensal ?? []) as $f) {
    $fatLabels[]  = mesLabel($f->mes);
    $fatValores[] = (float)$f->valor;
}

// Funil
$funilNomes  = ['qualificacao'=>'Qualificação','proposta'=>'Proposta','negociacao'=>'Negociação','fechamento'=>'Fechamento'];
$funilLabels = $funilQtds = $funilValores = [];
foreach (($funilEtapas ?? []) as $fe) {
    $funilLabels[]  = $funilNomes[$fe->etapa_funil] ?? $fe->etapa_funil;
    $funilQtds[]    = (int)$fe->qtd;
    $funilValores[] = (float)$fe->valor;
}

// Interacoes — icones
$tipoIcon = [
    'email'           => ['fa-envelope',       '#3b82f6'],
    'telefone'        => ['fa-phone',           '#10b981'],
    'whatsapp'        => ['fa-whatsapp',        '#25D366'],
    'reuniao_presencial'=>['fa-handshake',      '#8b5cf6'],
    'reuniao_online'  => ['fa-video',           '#6366f1'],
    'visita_tecnica'  => ['fa-hard-hat',        '#f59e0b'],
    'proposta_enviada'=> ['fa-file-invoice',    '#0ea5e9'],
    'contrato_enviado'=> ['fa-file-signature',  '#ef4444'],
    'outro'           => ['fa-comment-dots',    '#64748b'],
];
?>
<style>
/* ===== Dashboard Profissional ===== */
.dash-wrap{padding:1.5rem;width:100%}

/* Saudacao */
.dash-greeting{background:linear-gradient(135deg,#00529B 0%,#0284c7 55%,#0ea5e9 100%);border-radius:.875rem;padding:1.5rem 2rem;color:#fff;display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;box-shadow:0 4px 20px rgba(0,82,155,.25)}
.dash-greeting h1{font-size:1.4rem;font-weight:700;margin:0 0 .25rem}
.dash-greeting p{opacity:.85;margin:0;font-size:.9rem}
.dash-greeting-date{text-align:right;font-size:.8rem;opacity:.8}
.dash-greeting-date strong{display:block;font-size:1.1rem;font-weight:700;opacity:1}

/* KPI Cards */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem}
@media(max-width:1100px){.kpi-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.kpi-grid{grid-template-columns:1fr}}
.kpi-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:.5rem;box-shadow:0 1px 3px rgba(0,0,0,.06);position:relative;overflow:hidden;transition:box-shadow .2s}
.kpi-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.1)}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.kpi-card.kpi-green::before{background:#10b981}
.kpi-card.kpi-blue::before{background:#3b82f6}
.kpi-card.kpi-red::before{background:#ef4444}
.kpi-card.kpi-orange::before{background:#f59e0b}
.kpi-card.kpi-purple::before{background:#8b5cf6}
.kpi-card.kpi-teal::before{background:#14b8a6}
.kpi-label{font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b;display:flex;align-items:center;gap:.4rem}
.kpi-value{font-size:1.6rem;font-weight:800;color:#0f172a;line-height:1}
.kpi-sub{font-size:.75rem;color:#94a3b8;display:flex;align-items:center;gap:.3rem}
.kpi-icon{position:absolute;right:1.25rem;top:50%;transform:translateY(-50%);font-size:2rem;opacity:.07;color:#0f172a}

/* Secao de graficos */
.dash-row{display:grid;gap:1rem;margin-bottom:1rem}
.dash-row-2{grid-template-columns:2fr 1fr}
.dash-row-3{grid-template-columns:1fr 1fr 1fr}
.dash-row-eq{grid-template-columns:1fr 1fr}
@media(max-width:1100px){.dash-row-2,.dash-row-3,.dash-row-eq{grid-template-columns:1fr}}

/* Card padrao */
.dash-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;box-shadow:0 1px 3px rgba(0,0,0,.06);overflow:hidden}
.dash-card-header{padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between}
.dash-card-title{font-size:.9375rem;font-weight:600;color:#1e293b;display:flex;align-items:center;gap:.5rem;margin:0}
.dash-card-subtitle{font-size:.75rem;color:#94a3b8;margin-top:.15rem}
.dash-card-body{padding:1.25rem 1.5rem}
.dash-card-body.no-pad{padding:0}

/* Tabelas */
.dash-table{width:100%;font-size:.8125rem;border-collapse:collapse}
.dash-table th{background:#f8fafc;color:#64748b;font-weight:600;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;padding:.6rem 1rem;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.dash-table td{padding:.65rem 1rem;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:middle}
.dash-table tr:last-child td{border-bottom:none}
.dash-table tr:hover td{background:#f8fafc}

/* Badges */
.badge-status{display:inline-flex;align-items:center;gap:.25rem;font-size:.7rem;padding:.25em .6em;border-radius:20px;font-weight:600}
.bs-green{background:#d1fae5;color:#065f46}
.bs-red{background:#fee2e2;color:#991b1b}
.bs-yellow{background:#fef3c7;color:#92400e}
.bs-blue{background:#dbeafe;color:#1e40af}
.bs-gray{background:#f1f5f9;color:#475569}

/* Funil visual */
.funnel-wrap{padding:1.25rem}
.funnel-step{display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem}
.funnel-bar-wrap{flex:1;background:#f1f5f9;border-radius:4px;height:28px;overflow:hidden;position:relative}
.funnel-bar{height:100%;border-radius:4px;display:flex;align-items:center;padding:0 .75rem;font-size:.75rem;font-weight:600;color:#fff;transition:width .6s ease}
.funnel-label{font-size:.8rem;font-weight:500;color:#475569;min-width:100px}
.funnel-meta{font-size:.75rem;color:#94a3b8;min-width:80px;text-align:right}

/* Interacoes */
.interacao-item{display:flex;align-items:flex-start;gap:.75rem;padding:.75rem 1.25rem;border-bottom:1px solid #f1f5f9}
.interacao-item:last-child{border-bottom:none}
.interacao-icon{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.8rem}
.interacao-body{flex:1;min-width:0}
.interacao-nome{font-size:.8125rem;font-weight:600;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.interacao-desc{font-size:.75rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.interacao-time{font-size:.7rem;color:#94a3b8;white-space:nowrap}

/* Alertas vencimento */
.alert-item{display:flex;align-items:center;gap:.75rem;padding:.7rem 1.25rem;border-bottom:1px solid #f1f5f9}
.alert-item:last-child{border-bottom:none}
.alert-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.alert-info{flex:1;min-width:0}
.alert-nome{font-size:.8rem;font-weight:500;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.alert-cliente{font-size:.72rem;color:#64748b}
.alert-valor{font-size:.8rem;font-weight:700;color:#0f172a;white-space:nowrap}
.alert-date{font-size:.72rem;color:#94a3b8;white-space:nowrap}

/* Resultado do mes */
.resultado-wrap{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;padding:1.25rem 1.5rem}
@media(max-width:700px){.resultado-wrap{grid-template-columns:1fr}}
.resultado-item{text-align:center;padding:1rem;border-radius:.5rem}
.resultado-item.ri-green{background:#f0fdf4}
.resultado-item.ri-red{background:#fef2f2}
.resultado-item.ri-blue{background:#eff6ff}
.resultado-label{font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin-bottom:.35rem}
.resultado-value{font-size:1.4rem;font-weight:800}
.ri-green .resultado-value{color:#16a34a}
.ri-red .resultado-value{color:#dc2626}
.ri-blue .resultado-value{color:#2563eb}

/* Saldo positivo/negativo */
.saldo-pos{color:#16a34a}
.saldo-neg{color:#dc2626}

/* Vazio */
.empty-state{text-align:center;padding:2rem;color:#94a3b8}
.empty-state i{font-size:2rem;margin-bottom:.5rem;display:block}
.empty-state p{font-size:.8rem;margin:0}
</style>

<div class="dash-wrap">

  <!-- Saudacao -->
  <div class="dash-greeting">
    <div>
      <h1><?php echo $saudacao; ?>, <?php echo htmlspecialchars(explode(' ', $nomeUsuario)[0]); ?>!</h1>
      <p>Aqui esta o resumo completo do seu negocio hoje.</p>
    </div>
    <div class="dash-greeting-date">
      <span>Hoje</span>
      <strong><?php echo date('d/m/Y'); ?></strong>
      <span><?php echo date('l', strtotime('today')); ?></span>
    </div>
  </div>

  <!-- KPIs principais -->
  <div class="kpi-grid">
    <div class="kpi-card kpi-green">
      <div class="kpi-label"><i class="fas fa-arrow-down"></i> A Receber</div>
      <div class="kpi-value"><?php echo brl($receber->total_aberto ?? 0); ?></div>
      <div class="kpi-sub"><span class="badge-status bs-green"><?php echo (int)($receber->qtd_aberto??0); ?> contas</span></div>
      <i class="fas fa-arrow-circle-down kpi-icon"></i>
    </div>
    <div class="kpi-card kpi-red">
      <div class="kpi-label"><i class="fas fa-arrow-up"></i> A Pagar</div>
      <div class="kpi-value"><?php echo brl($pagar->total_aberto ?? 0); ?></div>
      <div class="kpi-sub"><span class="badge-status bs-red"><?php echo (int)($pagar->qtd_aberto??0); ?> contas</span></div>
      <i class="fas fa-arrow-circle-up kpi-icon"></i>
    </div>
    <div class="kpi-card <?php echo $saldoMes >= 0 ? 'kpi-blue' : 'kpi-orange'; ?>">
      <div class="kpi-label"><i class="fas fa-balance-scale"></i> Saldo do Mes</div>
      <div class="kpi-value <?php echo $saldoMes >= 0 ? 'saldo-pos' : 'saldo-neg'; ?>"><?php echo brl(abs($saldoMes)); ?></div>
      <div class="kpi-sub"><?php echo $saldoMes >= 0 ? '<span class="badge-status bs-green">Positivo</span>' : '<span class="badge-status bs-red">Negativo</span>'; ?></div>
      <i class="fas fa-chart-line kpi-icon"></i>
    </div>
    <div class="kpi-card kpi-orange">
      <div class="kpi-label"><i class="fas fa-exclamation-triangle"></i> Vencido</div>
      <div class="kpi-value"><?php echo brl($receber->total_vencido ?? 0); ?></div>
      <div class="kpi-sub"><span class="badge-status bs-red"><?php echo (int)($receber->qtd_vencido??0); ?> em atraso</span></div>
      <i class="fas fa-clock kpi-icon"></i>
    </div>
    <div class="kpi-card kpi-teal">
      <div class="kpi-label"><i class="fas fa-file-invoice-dollar"></i> NFs Emitidas (mes)</div>
      <div class="kpi-value"><?php echo brl($nfs->valor_mes ?? 0); ?></div>
      <div class="kpi-sub"><span class="badge-status bs-blue"><?php echo (int)($nfs->nfs_mes??0); ?> notas</span></div>
      <i class="fas fa-file-invoice kpi-icon"></i>
    </div>
    <div class="kpi-card kpi-purple">
      <div class="kpi-label"><i class="fas fa-funnel-dollar"></i> Pipeline CRM</div>
      <div class="kpi-value"><?php echo brl($oportunidades->pipeline_valor ?? 0); ?></div>
      <div class="kpi-sub"><span class="badge-status bs-blue"><?php echo (int)($oportunidades->abertas??0); ?> oportunidades</span></div>
      <i class="fas fa-handshake kpi-icon"></i>
    </div>
    <div class="kpi-card kpi-blue">
      <div class="kpi-label"><i class="fas fa-users"></i> Clientes</div>
      <div class="kpi-value"><?php echo number_format((int)($clientes->total??0)); ?></div>
      <div class="kpi-sub"><?php if(($clientes->novos_mes??0)>0): ?><span class="badge-status bs-green">+<?php echo (int)$clientes->novos_mes; ?> este mes</span><?php endif; ?></div>
      <i class="fas fa-users kpi-icon"></i>
    </div>
    <div class="kpi-card kpi-green">
      <div class="kpi-label"><i class="fas fa-check-circle"></i> Recebido (mes)</div>
      <div class="kpi-value"><?php echo brl($recebidoMes); ?></div>
      <div class="kpi-sub"><span class="badge-status bs-green"><?php echo (int)($receber->qtd_recebido??0); ?> pagamentos</span></div>
      <i class="fas fa-check-double kpi-icon"></i>
    </div>
  </div>

  <!-- Resultado do mes -->
  <div class="dash-card mb-3">
    <div class="dash-card-header">
      <div>
        <p class="dash-card-title"><i class="fas fa-chart-pie text-primary"></i> Resultado do Mes — <?php echo mesLabel($mesAtual); ?></p>
        <div class="dash-card-subtitle">Comparativo entre entradas e saidas no mes atual</div>
      </div>
    </div>
    <div class="resultado-wrap">
      <div class="resultado-item ri-green">
        <div class="resultado-label">Entradas</div>
        <div class="resultado-value"><?php echo brl($recebidoMes); ?></div>
      </div>
      <div class="resultado-item ri-red">
        <div class="resultado-label">Saidas</div>
        <div class="resultado-value"><?php echo brl($pagoMes); ?></div>
      </div>
      <div class="resultado-item <?php echo $saldoMes >= 0 ? 'ri-blue' : 'ri-red'; ?>">
        <div class="resultado-label">Saldo</div>
        <div class="resultado-value <?php echo $saldoMes >= 0 ? 'saldo-pos' : 'saldo-neg'; ?>"><?php echo ($saldoMes >= 0 ? '+' : '-') . brl(abs($saldoMes)); ?></div>
      </div>
    </div>
  </div>

  <!-- Graficos: Evolucao financeira + Funil CRM -->
  <div class="dash-row dash-row-2 mb-3">
    <div class="dash-card">
      <div class="dash-card-header">
        <div>
          <p class="dash-card-title"><i class="fas fa-chart-bar text-primary"></i> Evolucao Financeira — 12 Meses</p>
          <div class="dash-card-subtitle">Contas a receber vs. contas a pagar por mes</div>
        </div>
      </div>
      <div class="dash-card-body">
        <canvas id="chartEvolucao" height="90"></canvas>
      </div>
    </div>
    <div class="dash-card">
      <div class="dash-card-header">
        <div>
          <p class="dash-card-title"><i class="fas fa-filter text-primary"></i> Funil de Vendas</p>
          <div class="dash-card-subtitle">Oportunidades abertas por etapa</div>
        </div>
        <a href="/crm/funil" class="btn btn-sm btn-outline-primary">Ver funil</a>
      </div>
      <div class="funnel-wrap">
        <?php
        $maxQtd = max(array_merge([1], $funilQtds));
        $cores  = ['#3b82f6','#8b5cf6','#f59e0b','#10b981'];
        foreach ($funilLabels as $i => $label):
            $pctBar = $maxQtd > 0 ? round(($funilQtds[$i]/$maxQtd)*100) : 0;
        ?>
        <div class="funnel-step">
          <div class="funnel-label"><?php echo $label; ?></div>
          <div class="funnel-bar-wrap">
            <div class="funnel-bar" style="width:<?php echo max($pctBar,5); ?>%;background:<?php echo $cores[$i%4]; ?>">
              <?php echo $funilQtds[$i]; ?>
            </div>
          </div>
          <div class="funnel-meta"><?php echo brl($funilValores[$i]); ?></div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($funilLabels)): ?>
        <div class="empty-state"><i class="fas fa-filter"></i><p>Nenhuma oportunidade aberta</p></div>
        <?php endif; ?>
        <!-- Resumo CRM -->
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem;padding-top:1rem;border-top:1px solid #f1f5f9">
          <span class="badge-status bs-blue">Leads: <?php echo (int)($leads->total??0); ?></span>
          <span class="badge-status bs-green">Ganhas: <?php echo (int)($oportunidades->ganhas??0); ?></span>
          <span class="badge-status bs-red">Perdidas: <?php echo (int)($oportunidades->perdidas??0); ?></span>
          <?php if(($oportunidades->prob_media??0)>0): ?>
          <span class="badge-status bs-yellow">Prob. media: <?php echo round($oportunidades->prob_media); ?>%</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Faturamento mensal + Leads CRM -->
  <div class="dash-row dash-row-eq mb-3">
    <div class="dash-card">
      <div class="dash-card-header">
        <div>
          <p class="dash-card-title"><i class="fas fa-file-invoice-dollar text-success"></i> Faturamento — Ultimos 6 Meses</p>
          <div class="dash-card-subtitle">Valor total de notas fiscais emitidas</div>
        </div>
        <a href="/faturamento/notas-fiscais" class="btn btn-sm btn-outline-success">Ver NFs</a>
      </div>
      <div class="dash-card-body">
        <canvas id="chartFaturamento" height="100"></canvas>
      </div>
    </div>
    <div class="dash-card">
      <div class="dash-card-header">
        <div>
          <p class="dash-card-title"><i class="fas fa-user-tag text-purple" style="color:#8b5cf6"></i> Status dos Leads</p>
          <div class="dash-card-subtitle">Distribuicao atual da base de leads</div>
        </div>
        <a href="/crm/leads" class="btn btn-sm btn-outline-primary">Ver leads</a>
      </div>
      <div class="dash-card-body" style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap">
        <canvas id="chartLeads" width="160" height="160" style="max-width:160px;flex-shrink:0"></canvas>
        <div style="flex:1;min-width:120px">
          <?php
          $leadData = [
            ['Novos',        $leads->novos??0,        '#3b82f6'],
            ['Qualificados', $leads->qualificados??0,  '#8b5cf6'],
            ['Oportunidade', $leads->oportunidades??0, '#f59e0b'],
            ['Convertidos',  $leads->convertidos??0,   '#10b981'],
            ['Perdidos',     $leads->perdidos??0,       '#ef4444'],
          ];
          foreach ($leadData as $ld):
          ?>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.4rem">
            <div style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;color:#475569">
              <span style="width:10px;height:10px;border-radius:50%;background:<?php echo $ld[2]; ?>;flex-shrink:0"></span>
              <?php echo $ld[0]; ?>
            </div>
            <strong style="font-size:.8rem;color:#1e293b"><?php echo (int)$ld[1]; ?></strong>
          </div>
          <?php endforeach; ?>
          <div style="margin-top:.75rem;padding-top:.75rem;border-top:1px solid #f1f5f9;font-size:.78rem;color:#64748b">
            Total: <strong style="color:#1e293b"><?php echo (int)($leads->total??0); ?></strong> leads
            <?php if(($leads->novos_mes??0)>0): ?> &bull; <span style="color:#10b981">+<?php echo (int)$leads->novos_mes; ?> este mes</span><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Alertas: Vencimentos + Contas em atraso -->
  <div class="dash-row dash-row-eq mb-3">
    <div class="dash-card">
      <div class="dash-card-header">
        <div>
          <p class="dash-card-title"><i class="fas fa-calendar-check text-warning" style="color:#f59e0b"></i> Proximos Vencimentos</p>
          <div class="dash-card-subtitle">Contas a receber nos proximos 7 dias</div>
        </div>
        <a href="/financeiro/contas-a-receber" class="btn btn-sm btn-outline-warning">Ver todas</a>
      </div>
      <div class="dash-card-body no-pad">
        <?php if(empty($proximosVencimentos)): ?>
        <div class="empty-state"><i class="fas fa-calendar-check"></i><p>Nenhum vencimento nos proximos 7 dias</p></div>
        <?php else: foreach($proximosVencimentos as $v):
          $diasRestantes = (int)((strtotime($v->data_vencimento) - strtotime('today')) / 86400);
          $dotColor = $diasRestantes <= 1 ? '#ef4444' : ($diasRestantes <= 3 ? '#f59e0b' : '#10b981');
        ?>
        <div class="alert-item">
          <div class="alert-dot" style="background:<?php echo $dotColor; ?>"></div>
          <div class="alert-info">
            <div class="alert-nome"><?php echo htmlspecialchars($v->descricao); ?></div>
            <div class="alert-cliente"><?php echo htmlspecialchars($v->cliente_nome ?? '—'); ?></div>
          </div>
          <div style="text-align:right">
            <div class="alert-valor"><?php echo brl($v->valor); ?></div>
            <div class="alert-date"><?php echo date('d/m/Y', strtotime($v->data_vencimento)); ?> <?php echo $diasRestantes === 0 ? '<span style="color:#ef4444">Hoje</span>' : ($diasRestantes === 1 ? '<span style="color:#f59e0b">Amanha</span>' : "em {$diasRestantes}d"); ?></div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <div class="dash-card">
      <div class="dash-card-header">
        <div>
          <p class="dash-card-title"><i class="fas fa-exclamation-circle text-danger" style="color:#ef4444"></i> Em Atraso</p>
          <div class="dash-card-subtitle">Contas a receber vencidas e nao pagas</div>
        </div>
        <span class="badge-status bs-red"><?php echo (int)($receber->qtd_vencido??0); ?> contas</span>
      </div>
      <div class="dash-card-body no-pad">
        <?php if(empty($contasVencidas)): ?>
        <div class="empty-state"><i class="fas fa-check-circle" style="color:#10b981"></i><p>Nenhuma conta em atraso!</p></div>
        <?php else: foreach($contasVencidas as $cv): ?>
        <div class="alert-item">
          <div class="alert-dot" style="background:#ef4444"></div>
          <div class="alert-info">
            <div class="alert-nome"><?php echo htmlspecialchars($cv->descricao); ?></div>
            <div class="alert-cliente"><?php echo htmlspecialchars($cv->cliente_nome ?? '—'); ?></div>
          </div>
          <div style="text-align:right">
            <div class="alert-valor"><?php echo brl($cv->valor); ?></div>
            <div class="alert-date" style="color:#ef4444"><?php echo (int)$cv->dias_atraso; ?>d em atraso</div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>

  <!-- Ultimas NFs + Ultimas interacoes CRM -->
  <div class="dash-row dash-row-eq mb-3">
    <div class="dash-card">
      <div class="dash-card-header">
        <div>
          <p class="dash-card-title"><i class="fas fa-file-alt text-success"></i> Ultimas Notas Fiscais</p>
        </div>
        <a href="/faturamento/notas-fiscais" class="btn btn-sm btn-outline-success">Ver todas</a>
      </div>
      <div class="dash-card-body no-pad">
        <?php if(empty($ultimasNfs)): ?>
        <div class="empty-state"><i class="fas fa-file-alt"></i><p>Nenhuma nota fiscal emitida</p></div>
        <?php else: ?>
        <table class="dash-table">
          <thead><tr><th>NF</th><th>Cliente</th><th>Valor</th><th>Data</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach($ultimasNfs as $nf):
            $nfBadge = $nf->status === 'emitida' ? 'bs-green' : ($nf->status === 'cancelada' ? 'bs-red' : 'bs-gray');
          ?>
          <tr>
            <td><strong>#<?php echo htmlspecialchars($nf->numero_nf ?? '—'); ?></strong></td>
            <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars($nf->cliente_nome ?? '—'); ?></td>
            <td><strong><?php echo brl($nf->valor_total); ?></strong></td>
            <td><?php echo $nf->data_emissao ? date('d/m/Y', strtotime($nf->data_emissao)) : '—'; ?></td>
            <td><span class="badge-status <?php echo $nfBadge; ?>"><?php echo ucfirst($nf->status); ?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
    <div class="dash-card">
      <div class="dash-card-header">
        <div>
          <p class="dash-card-title"><i class="fas fa-comments" style="color:#8b5cf6"></i> Ultimas Interacoes CRM</p>
        </div>
        <a href="/crm/leads" class="btn btn-sm btn-outline-primary">Ver CRM</a>
      </div>
      <div class="dash-card-body no-pad">
        <?php if(empty($ultimasInteracoes)): ?>
        <div class="empty-state"><i class="fas fa-comments"></i><p>Nenhuma interacao registrada</p></div>
        <?php else: foreach($ultimasInteracoes as $inter):
          $iconData = $tipoIcon[$inter->tipo_interacao] ?? ['fa-comment-dots','#64748b'];
          $timeAgo  = human_time_diff(strtotime($inter->created_at));
        ?>
        <div class="interacao-item">
          <div class="interacao-icon" style="background:<?php echo $iconData[1]; ?>20;color:<?php echo $iconData[1]; ?>">
            <i class="fas <?php echo $iconData[0]; ?>"></i>
          </div>
          <div class="interacao-body">
            <div class="interacao-nome"><?php echo htmlspecialchars($inter->nome_referencia ?? '—'); ?>
              <span class="badge-status <?php echo $inter->origem === 'lead' ? 'bs-blue' : 'bs-yellow'; ?>" style="font-size:.65rem;margin-left:.25rem"><?php echo ucfirst($inter->origem); ?></span>
            </div>
            <div class="interacao-desc"><?php echo htmlspecialchars(mb_substr($inter->descricao ?? '', 0, 60)); ?><?php echo strlen($inter->descricao??'')>60?'...':''; ?></div>
          </div>
          <div class="interacao-time"><?php echo $timeAgo; ?></div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>

</div>

<?php
function human_time_diff(int $ts): string {
    $diff = time() - $ts;
    if ($diff < 60)   return 'agora';
    if ($diff < 3600) return floor($diff/60).'min';
    if ($diff < 86400) return floor($diff/3600).'h';
    return floor($diff/86400).'d';
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Inter','Segoe UI',sans-serif";
Chart.defaults.color = '#64748b';

// --- Evolucao Financeira (Barras) ---
const evoLabels  = <?php echo json_encode($evoLabels); ?>;
const evoReceber = <?php echo json_encode($evoReceber); ?>;
const evoPagar   = <?php echo json_encode($evoPagar); ?>;

new Chart(document.getElementById('chartEvolucao'), {
  type: 'bar',
  data: {
    labels: evoLabels,
    datasets: [
      { label: 'A Receber', data: evoReceber, backgroundColor: 'rgba(16,185,129,.7)', borderColor: '#10b981', borderWidth: 1, borderRadius: 4 },
      { label: 'A Pagar',   data: evoPagar,   backgroundColor: 'rgba(239,68,68,.6)',  borderColor: '#ef4444', borderWidth: 1, borderRadius: 4 }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: true,
    plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: ctx => 'R$ ' + ctx.raw.toLocaleString('pt-BR',{minimumFractionDigits:2}) } } },
    scales: {
      x: { grid: { display: false } },
      y: { grid: { color: '#f1f5f9' }, ticks: { callback: v => 'R$' + (v>=1000 ? (v/1000).toFixed(0)+'k' : v) } }
    }
  }
});

// --- Faturamento (Linha) ---
const fatLabels  = <?php echo json_encode($fatLabels); ?>;
const fatValores = <?php echo json_encode($fatValores); ?>;

new Chart(document.getElementById('chartFaturamento'), {
  type: 'line',
  data: {
    labels: fatLabels,
    datasets: [{
      label: 'Faturamento (R$)', data: fatValores,
      borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.1)',
      borderWidth: 2.5, fill: true, tension: 0.4, pointRadius: 4, pointBackgroundColor: '#10b981'
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: true,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => 'R$ ' + ctx.raw.toLocaleString('pt-BR',{minimumFractionDigits:2}) } } },
    scales: {
      x: { grid: { display: false } },
      y: { grid: { color: '#f1f5f9' }, ticks: { callback: v => 'R$' + (v>=1000 ? (v/1000).toFixed(0)+'k' : v) } }
    }
  }
});

// --- Leads (Doughnut) ---
const leadLabels = ['Novos','Qualificados','Oportunidade','Convertidos','Perdidos'];
const leadData   = [<?php echo (int)($leads->novos??0); ?>,<?php echo (int)($leads->qualificados??0); ?>,<?php echo (int)($leads->oportunidades??0); ?>,<?php echo (int)($leads->convertidos??0); ?>,<?php echo (int)($leads->perdidos??0); ?>];
const leadColors = ['#3b82f6','#8b5cf6','#f59e0b','#10b981','#ef4444'];

new Chart(document.getElementById('chartLeads'), {
  type: 'doughnut',
  data: { labels: leadLabels, datasets: [{ data: leadData, backgroundColor: leadColors, borderWidth: 2, borderColor: '#fff' }] },
  options: {
    responsive: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ctx.label + ': ' + ctx.raw } } },
    cutout: '65%'
  }
});
</script>