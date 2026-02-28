<?php
$etapaConfig = [
    'qualificacao' => ['label' => 'Qualificação', 'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'fa-star'],
    'proposta'     => ['label' => 'Proposta',     'color' => '#0284c7', 'bg' => '#e0f2fe', 'icon' => 'fa-file-alt'],
    'negociacao'   => ['label' => 'Negociação',   'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'fa-handshake'],
    'fechamento'   => ['label' => 'Fechamento',   'color' => '#059669', 'bg' => '#d1fae5', 'icon' => 'fa-check-circle'],
];
?>
<style>
/* ===== Layout do Funil ===== */
.funil-page{padding:1.5rem}
.funil-topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.funil-title{font-size:1.5rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:.6rem}
.funil-actions{display:flex;gap:.5rem}

/* KPI bar */
.funil-kpi-bar{display:flex;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap}
.funil-kpi{flex:1;min-width:140px;background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:.875rem 1rem;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.funil-kpi-label{font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem}
.funil-kpi-val{font-size:1.35rem;font-weight:700;color:#1e293b}
.funil-kpi-sub{font-size:.7rem;color:#94a3b8}

/* Board Kanban */
.kanban-board{display:flex;gap:1rem;overflow-x:auto;padding-bottom:1rem;min-height:500px}
.kanban-col{flex:0 0 280px;display:flex;flex-direction:column;border-radius:.75rem;overflow:hidden;border:1px solid #e2e8f0}
.kanban-col-header{padding:.875rem 1rem;display:flex;align-items:center;justify-content:space-between}
.kanban-col-title{font-size:.875rem;font-weight:700;display:flex;align-items:center;gap:.5rem}
.kanban-col-count{font-size:.7rem;padding:.2em .6em;border-radius:10px;font-weight:600;background:rgba(0,0,0,.08)}
.kanban-col-valor{font-size:.7rem;opacity:.75;margin-top:.1rem}
.kanban-cards{flex:1;padding:.5rem;min-height:200px;transition:background .2s}
.kanban-cards.drag-over{background:rgba(0,82,155,.06);border-radius:.5rem}

/* Cards */
.kanban-card{background:#fff;border:1px solid #e2e8f0;border-radius:.625rem;padding:.875rem;margin-bottom:.625rem;cursor:grab;box-shadow:0 1px 3px rgba(0,0,0,.06);transition:box-shadow .2s,transform .15s;position:relative}
.kanban-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.12);transform:translateY(-1px)}
.kanban-card.dragging{opacity:.5;cursor:grabbing;box-shadow:0 8px 24px rgba(0,0,0,.18)}
.kanban-card-title{font-size:.875rem;font-weight:600;color:#1e293b;margin-bottom:.35rem;line-height:1.3}
.kanban-card-contact{font-size:.75rem;color:#64748b;margin-bottom:.5rem;display:flex;align-items:center;gap:.35rem}
.kanban-card-footer{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.35rem}
.kanban-card-valor{font-size:.8125rem;font-weight:700;color:#059669}
.kanban-card-date{font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:.25rem}
.kanban-card-prob{height:4px;background:#e2e8f0;border-radius:2px;margin-top:.5rem;overflow:hidden}
.kanban-card-prob-fill{height:100%;border-radius:2px;background:linear-gradient(90deg,#f59e0b,#10b981)}
.kanban-card-actions{position:absolute;top:.5rem;right:.5rem;display:none;gap:.25rem}
.kanban-card:hover .kanban-card-actions{display:flex}
.kanban-card-actions a{width:22px;height:22px;border-radius:4px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:.65rem;color:#64748b;text-decoration:none}
.kanban-card-actions a:hover{background:#00529B;color:#fff}

/* Empty column */
.kanban-empty{text-align:center;padding:2rem 1rem;color:#cbd5e1;font-size:.8125rem}
.kanban-empty i{font-size:1.75rem;display:block;margin-bottom:.5rem}

/* Leads summary */
.leads-summary{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem 1.25rem;margin-top:1.5rem;display:flex;gap:1.5rem;flex-wrap:wrap;align-items:center}
.leads-summary-title{font-size:.8125rem;font-weight:600;color:#64748b;margin-right:.5rem}
.leads-badge{font-size:.75rem;padding:.3em .75em;border-radius:20px;font-weight:600}
</style>

<div class="funil-page">

  <!-- Top bar -->
  <div class="funil-topbar">
    <div class="funil-title">
      <i class="fas fa-filter" style="color:#00529B"></i>
      Funil de Vendas
    </div>
    <div class="funil-actions">
      <a href="/crm/leads/create" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-user-plus me-1"></i> Novo Lead
      </a>
      <a href="/crm/oportunidades/create" class="btn btn-sm btn-success">
        <i class="fas fa-plus me-1"></i> Nova Oportunidade
      </a>
      <a href="/crm/oportunidades" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-list me-1"></i> Lista
      </a>
    </div>
  </div>

  <!-- KPIs -->
  <div class="funil-kpi-bar">
    <?php foreach ($etapaConfig as $key => $cfg): ?>
    <?php $r = $resumo[$key] ?? ['total' => 0, 'valor_total' => 0]; ?>
    <div class="funil-kpi">
      <div class="funil-kpi-label" style="color:<?php echo $cfg['color']; ?>">
        <i class="fas <?php echo $cfg['icon']; ?> me-1"></i><?php echo $cfg['label']; ?>
      </div>
      <div class="funil-kpi-val"><?php echo $r['total']; ?></div>
      <div class="funil-kpi-sub">R$ <?php echo number_format($r['valor_total'], 2, ',', '.'); ?></div>
    </div>
    <?php endforeach; ?>
    <?php
      $totalOps   = array_sum(array_column($resumo, 'total'));
      $totalValor = array_sum(array_column($resumo, 'valor_total'));
    ?>
    <div class="funil-kpi" style="border-color:#00529B">
      <div class="funil-kpi-label" style="color:#00529B"><i class="fas fa-chart-bar me-1"></i>Total Pipeline</div>
      <div class="funil-kpi-val"><?php echo $totalOps; ?></div>
      <div class="funil-kpi-sub">R$ <?php echo number_format($totalValor, 2, ',', '.'); ?></div>
    </div>
  </div>

  <!-- Board Kanban -->
  <div class="kanban-board" id="kanban-board">
    <?php foreach ($etapaConfig as $etapaKey => $cfg): ?>
    <?php $cards = $colunas[$etapaKey] ?? []; ?>
    <div class="kanban-col" id="col-<?php echo $etapaKey; ?>" style="border-top:3px solid <?php echo $cfg['color']; ?>">
      <div class="kanban-col-header" style="background:<?php echo $cfg['bg']; ?>">
        <div>
          <div class="kanban-col-title" style="color:<?php echo $cfg['color']; ?>">
            <i class="fas <?php echo $cfg['icon']; ?>"></i>
            <?php echo $cfg['label']; ?>
            <span class="kanban-col-count"><?php echo count($cards); ?></span>
          </div>
          <?php if (!empty($resumo[$etapaKey]['valor_total'])): ?>
          <div class="kanban-col-valor" style="color:<?php echo $cfg['color']; ?>">
            R$ <?php echo number_format($resumo[$etapaKey]['valor_total'], 2, ',', '.'); ?>
          </div>
          <?php endif; ?>
        </div>
        <a href="/crm/oportunidades/create" class="btn btn-sm" style="background:<?php echo $cfg['color']; ?>;color:#fff;padding:.2rem .5rem;font-size:.7rem;border-radius:.4rem" title="Nova oportunidade nesta etapa">
          <i class="fas fa-plus"></i>
        </a>
      </div>

      <div class="kanban-cards" data-etapa="<?php echo $etapaKey; ?>"
           ondragover="onDragOver(event)" ondrop="onDrop(event)" ondragleave="onDragLeave(event)">

        <?php if (empty($cards)): ?>
        <div class="kanban-empty">
          <i class="fas fa-inbox"></i>
          Nenhuma oportunidade
        </div>
        <?php endif; ?>

        <?php foreach ($cards as $op): ?>
        <?php $prob = (int)($op->probabilidade_sucesso ?? 0); ?>
        <div class="kanban-card" draggable="true" id="card-<?php echo $op->id; ?>"
             data-id="<?php echo $op->id; ?>" data-etapa="<?php echo $etapaKey; ?>"
             ondragstart="onDragStart(event)">

          <!-- Ações rápidas -->
          <div class="kanban-card-actions">
            <a href="/crm/oportunidades/edit/<?php echo $op->id; ?>" title="Editar">
              <i class="fas fa-edit"></i>
            </a>
          </div>

          <div class="kanban-card-title"><?php echo htmlspecialchars($op->titulo_oportunidade); ?></div>

          <?php if ($op->nome_contato): ?>
          <div class="kanban-card-contact">
            <i class="fas fa-building"></i>
            <?php echo htmlspecialchars($op->nome_contato); ?>
          </div>
          <?php endif; ?>

          <div class="kanban-card-footer">
            <span class="kanban-card-valor">
              <?php echo $op->valor_estimado ? 'R$ ' . number_format($op->valor_estimado, 2, ',', '.') : '—'; ?>
            </span>
            <?php if ($op->data_fechamento_prevista): ?>
            <span class="kanban-card-date">
              <i class="fas fa-calendar"></i>
              <?php echo date('d/m/Y', strtotime($op->data_fechamento_prevista)); ?>
            </span>
            <?php endif; ?>
          </div>

          <?php if ($prob > 0): ?>
          <div class="kanban-card-prob">
            <div class="kanban-card-prob-fill" style="width:<?php echo $prob; ?>%" title="Probabilidade: <?php echo $prob; ?>%"></div>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>

      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Resumo de Leads -->
  <div class="leads-summary">
    <span class="leads-summary-title"><i class="fas fa-users me-1"></i> Leads:</span>
    <?php
    $leadsConfig = [
        'novo'        => ['label' => 'Novos',        'color' => 'secondary'],
        'contatado'   => ['label' => 'Contatados',   'color' => 'info'],
        'qualificado' => ['label' => 'Qualificados', 'color' => 'success'],
        'descartado'  => ['label' => 'Descartados',  'color' => 'danger'],
    ];
    foreach ($leadsConfig as $k => $cfg):
        $count = $leadsCount[$k] ?? 0;
        if ($count === 0 && $k === 'descartado') continue;
    ?>
    <a href="/crm/leads?status=<?php echo $k; ?>" class="leads-badge bg-<?php echo $cfg['color']; ?>-subtle text-<?php echo $cfg['color']; ?> text-decoration-none">
      <?php echo $cfg['label']; ?>: <?php echo $count; ?>
    </a>
    <?php endforeach; ?>
    <a href="/crm/leads" class="btn btn-sm btn-outline-primary ms-auto">
      <i class="fas fa-users me-1"></i> Gerenciar Leads
    </a>
  </div>

</div>

<script>
// ===== Drag and Drop Kanban =====
let draggedId   = null;
let draggedEtapa = null;

function onDragStart(e) {
  draggedId    = e.currentTarget.dataset.id;
  draggedEtapa = e.currentTarget.dataset.etapa;
  e.currentTarget.classList.add('dragging');
  e.dataTransfer.effectAllowed = 'move';
}

document.querySelectorAll('.kanban-card').forEach(card => {
  card.addEventListener('dragend', () => card.classList.remove('dragging'));
});

function onDragOver(e) {
  e.preventDefault();
  e.dataTransfer.dropEffect = 'move';
  e.currentTarget.classList.add('drag-over');
}

function onDragLeave(e) {
  e.currentTarget.classList.remove('drag-over');
}

function onDrop(e) {
  e.preventDefault();
  const col      = e.currentTarget;
  const novaEtapa = col.dataset.etapa;
  col.classList.remove('drag-over');

  if (!draggedId || novaEtapa === draggedEtapa) return;

  // Move o card visualmente
  const card = document.getElementById('card-' + draggedId);
  if (!card) return;

  // Remove empty state se existir
  const empty = col.querySelector('.kanban-empty');
  if (empty) empty.remove();

  col.appendChild(card);
  card.dataset.etapa = novaEtapa;
  draggedEtapa = novaEtapa;

  // Atualiza contadores
  atualizarContadores();

  // Persiste via AJAX
  const form = new FormData();
  form.append('id',    draggedId);
  form.append('etapa', novaEtapa);
  form.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

  fetch('/crm/oportunidades/mover', { method: 'POST', body: form })
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        alert('Erro ao mover oportunidade. Recarregue a página.');
      }
    })
    .catch(() => {
      // Falha silenciosa — a mudança visual já ocorreu
    });
}

function atualizarContadores() {
  document.querySelectorAll('.kanban-cards').forEach(col => {
    const etapa = col.dataset.etapa;
    const count = col.querySelectorAll('.kanban-card').length;
    const header = document.getElementById('col-' + etapa);
    if (header) {
      const badge = header.querySelector('.kanban-col-count');
      if (badge) badge.textContent = count;
    }
  });
}
</script>

<?php require_once dirname(__DIR__, 2) . '/layout/erp_footer.php'; ?>
