<?php
// Helpers de status
$statusConfig = [
    'gerada'     => ['label' => 'Gerada',     'color' => 'secondary', 'icon' => 'fa-file-alt'],
    'enviada'    => ['label' => 'Enviada',    'color' => 'primary',   'icon' => 'fa-paper-plane'],
    'visualizada'=> ['label' => 'Visualizada','color' => 'info',      'icon' => 'fa-eye'],
    'aceita'     => ['label' => 'Aceita',     'color' => 'success',   'icon' => 'fa-check-circle'],
    'recusada'   => ['label' => 'Recusada',   'color' => 'danger',    'icon' => 'fa-times-circle'],
    'expirada'   => ['label' => 'Expirada',   'color' => 'warning',   'icon' => 'fa-clock'],
];

$propostas = $propostas ?? [];
$kpis      = $kpis      ?? [];
$filtros   = $filtros   ?? [];
$isAdmin   = $isAdmin   ?? false;
?>
<style>
.crm-kpi-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.crm-kpi{flex:1;min-width:150px;background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.crm-kpi-label{font-size:.75rem;color:#6b7280;margin-bottom:.25rem}
.crm-kpi-val{font-size:1.4rem;font-weight:700;color:#1e293b}
.crm-kpi-sub{font-size:.75rem;color:#94a3b8;margin-top:.1rem}
.crm-table-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.crm-table-header{display:flex;align-items:center;justify-content:space-between;padding:.875rem 1.25rem;border-bottom:1px solid #e2e8f0;flex-wrap:wrap;gap:.75rem}
.prop-row:hover{background:#f8fafc}
.prop-name{font-weight:600;color:#1e293b}
.prop-sub{font-size:.75rem;color:#64748b}
.action-btns{display:flex;gap:.35rem;justify-content:flex-end}
.action-btns .btn{padding:.25rem .55rem;font-size:.75rem;border-radius:.4rem}
.empty-state{text-align:center;padding:3.5rem 1rem;color:#94a3b8}
.empty-state i{font-size:3rem;margin-bottom:.75rem;display:block}
.badge-status{font-size:.7rem;padding:.3em .7em;border-radius:20px;font-weight:600}
.validade-cell{font-size:.8rem}
.validade-ok{color:#059669}
.validade-warn{color:#d97706}
.validade-exp{color:#dc2626}
</style>

<div class="container-fluid">

  <!-- KPIs -->
  <div class="crm-kpi-bar">
    <div class="crm-kpi">
      <div class="crm-kpi-label">Total de Propostas</div>
      <div class="crm-kpi-val"><?php echo (int)($kpis['total'] ?? 0); ?></div>
    </div>
    <div class="crm-kpi">
      <div class="crm-kpi-label"><span class="badge bg-secondary-subtle text-secondary">Geradas</span></div>
      <div class="crm-kpi-val"><?php echo (int)($kpis['geradas'] ?? 0); ?></div>
    </div>
    <div class="crm-kpi">
      <div class="crm-kpi-label"><span class="badge bg-primary-subtle text-primary">Enviadas</span></div>
      <div class="crm-kpi-val"><?php echo (int)($kpis['enviadas'] ?? 0); ?></div>
    </div>
    <div class="crm-kpi">
      <div class="crm-kpi-label"><span class="badge bg-success-subtle text-success">Aceitas</span></div>
      <div class="crm-kpi-val"><?php echo (int)($kpis['aceitas'] ?? 0); ?></div>
      <div class="crm-kpi-sub">R$ <?php echo number_format((float)($kpis['valor_aceito'] ?? 0), 2, ',', '.'); ?></div>
    </div>
    <div class="crm-kpi">
      <div class="crm-kpi-label">Pipeline Ativo</div>
      <div class="crm-kpi-val" style="color:#1a56db">R$ <?php echo number_format((float)($kpis['valor_pipeline'] ?? 0), 2, ',', '.'); ?></div>
    </div>
  </div>

  <!-- Tabela -->
  <div class="crm-table-card">
    <div class="crm-table-header">
      <div class="d-flex align-items-center gap-2">
        <i class="fas fa-file-contract text-primary"></i>
        <strong>Propostas Comerciais</strong>
      </div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <!-- Filtro de status -->
        <form method="GET" action="/crm/propostas" class="d-flex gap-2 align-items-center">
          <input type="text" name="busca" class="form-control form-control-sm" placeholder="Buscar cliente ou número..." value="<?php echo htmlspecialchars($filtros['busca'] ?? ''); ?>" style="width:200px">
          <select name="status" class="form-select form-select-sm" style="width:140px">
            <option value="">Todos os status</option>
            <?php foreach ($statusConfig as $k => $s): ?>
            <option value="<?php echo $k; ?>" <?php echo ($filtros['status'] ?? '') === $k ? 'selected' : ''; ?>>
              <?php echo $s['label']; ?>
            </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-search"></i></button>
          <?php if (!empty($filtros['status']) || !empty($filtros['busca'])): ?>
          <a href="/crm/propostas" class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></a>
          <?php endif; ?>
        </form>
        <a href="/crm/propostas/create" class="btn btn-sm btn-primary">
          <i class="fas fa-plus me-1"></i>Nova Proposta
        </a>
      </div>
    </div>

    <?php if (empty($propostas)): ?>
    <div class="empty-state">
      <i class="fas fa-file-contract"></i>
      <h5>Nenhuma proposta encontrada</h5>
      <p class="mb-3">Crie sua primeira proposta comercial para um cliente.</p>
      <a href="/crm/propostas/create" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nova Proposta
      </a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:120px">Número</th>
            <th>Cliente / Título</th>
            <th style="width:120px">Valor Total</th>
            <th style="width:110px">Status</th>
            <th style="width:110px">Validade</th>
            <th style="width:120px" class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($propostas as $p): ?>
          <?php
            $sc       = $statusConfig[$p->status] ?? $statusConfig['gerada'];
            $hoje     = new DateTime('today');
            $valDt    = !empty($p->validade_proposta) ? new DateTime($p->validade_proposta) : null;
            $valClass = 'validade-ok';
            $valLabel = $valDt ? date('d/m/Y', strtotime($p->validade_proposta)) : '—';
            if ($valDt) {
                $diff = (int) $hoje->diff($valDt)->days * ($valDt >= $hoje ? 1 : -1);
                if ($diff < 0)       $valClass = 'validade-exp';
                elseif ($diff <= 5)  $valClass = 'validade-warn';
            }
          ?>
          <tr class="prop-row">
            <td>
              <a href="/crm/propostas/<?php echo $p->id; ?>" class="fw-bold text-decoration-none text-primary">
                <?php echo htmlspecialchars($p->numero); ?>
              </a>
              <div class="prop-sub"><?php echo date('d/m/Y', strtotime($p->created_at)); ?></div>
            </td>
            <td>
              <div class="prop-name"><?php echo htmlspecialchars($p->cliente_nome); ?></div>
              <div class="prop-sub"><?php echo htmlspecialchars($p->titulo); ?></div>
            </td>
            <td>
              <strong>R$ <?php echo number_format((float)$p->total, 2, ',', '.'); ?></strong>
            </td>
            <td>
              <span class="badge bg-<?php echo $sc['color']; ?>-subtle text-<?php echo $sc['color']; ?> badge-status">
                <i class="fas <?php echo $sc['icon']; ?> me-1"></i><?php echo $sc['label']; ?>
              </span>
            </td>
            <td class="validade-cell <?php echo $valClass; ?>">
              <?php if ($valDt && $valDt < $hoje): ?>
                <i class="fas fa-exclamation-circle me-1"></i>
              <?php endif; ?>
              <?php echo $valLabel; ?>
            </td>
            <td>
              <div class="action-btns">
                <a href="/crm/propostas/<?php echo $p->id; ?>" class="btn btn-sm btn-outline-primary" title="Visualizar">
                  <i class="fas fa-eye"></i>
                </a>
                <?php if (in_array($p->status, ['gerada', 'enviada', 'visualizada'])): ?>
                <a href="/crm/propostas/<?php echo $p->id; ?>/edit" class="btn btn-sm btn-outline-secondary" title="Editar">
                  <i class="fas fa-edit"></i>
                </a>
                <?php endif; ?>
                <a href="/crm/propostas/<?php echo $p->id; ?>/pdf" class="btn btn-sm btn-outline-success" title="Baixar PDF">
                  <i class="fas fa-file-pdf"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-danger" title="Excluir"
                  onclick="confirmarExclusao(<?php echo $p->id; ?>, '<?php echo htmlspecialchars(addslashes($p->numero)); ?>')">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h6 class="modal-title"><i class="fas fa-trash text-danger me-2"></i>Excluir Proposta</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p class="mb-1">Tem certeza que deseja excluir a proposta</p>
        <strong id="modalExcluirNumero"></strong>?
        <p class="text-muted small mt-2">Esta ação não pode ser desfeita.</p>
      </div>
      <div class="modal-footer border-0 justify-content-center gap-2">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <form id="formExcluir" method="POST">
          <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function confirmarExclusao(id, numero) {
  document.querySelector('#modalExcluirNumero').textContent = numero;
  document.querySelector('#formExcluir').action = '/crm/propostas/' + id + '/delete';
  new bootstrap.Modal(document.querySelector('#modalExcluir')).show();
}
</script>
