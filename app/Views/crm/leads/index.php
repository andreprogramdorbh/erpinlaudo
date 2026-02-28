<?php
use App\Core\View;
$hoje = date('Y-m-d');

$statusColors = [
    'novo'        => 'secondary',
    'contatado'   => 'info',
    'qualificado' => 'success',
    'descartado'  => 'danger',
];
$statusIcons = [
    'novo'        => 'fa-star',
    'contatado'   => 'fa-phone',
    'qualificado' => 'fa-check-circle',
    'descartado'  => 'fa-times-circle',
];
?>

<style>
.crm-kpi-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.crm-kpi{flex:1;min-width:140px;background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem 1.25rem;display:flex;align-items:center;gap:.875rem;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.crm-kpi-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.crm-kpi-val{font-size:1.5rem;font-weight:700;line-height:1.1}
.crm-kpi-lbl{font-size:.75rem;color:#6b7280}
.crm-kpi.novo    .crm-kpi-icon{background:#f1f5f9;color:#64748b}
.crm-kpi.contatado .crm-kpi-icon{background:#e0f2fe;color:#0284c7}
.crm-kpi.qualificado .crm-kpi-icon{background:#d1fae5;color:#059669}
.crm-kpi.descartado .crm-kpi-icon{background:#fee2e2;color:#dc2626}

.crm-table-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.crm-table-header{display:flex;align-items:center;justify-content:space-between;padding:.875rem 1.25rem;border-bottom:1px solid #e2e8f0;flex-wrap:wrap;gap:.75rem}
.crm-filters{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center}
.crm-filters .form-select,.crm-filters .form-control{font-size:.8125rem;padding:.3rem .6rem;height:32px;border-radius:.4rem}
.badge-status{font-size:.7rem;padding:.3em .7em;border-radius:20px;font-weight:600;text-transform:uppercase;letter-spacing:.03em}
.lead-row:hover{background:#f8fafc}
.lead-name{font-weight:600;color:#1e293b}
.lead-sub{font-size:.75rem;color:#64748b;margin-top:1px}
.action-btns{display:flex;gap:.35rem}
.action-btns .btn{padding:.25rem .55rem;font-size:.75rem;border-radius:.4rem}
.vencido-badge{background:#fee2e2;color:#dc2626;font-size:.65rem;padding:.15em .5em;border-radius:10px;font-weight:600;margin-left:.4rem}
.empty-state{text-align:center;padding:3.5rem 1rem;color:#94a3b8}
.empty-state i{font-size:3rem;margin-bottom:.75rem;display:block}
</style>

<div class="container-fluid">

  <!-- KPIs -->
  <div class="crm-kpi-bar">
    <?php foreach ($statusList as $key => $label): ?>
    <div class="crm-kpi <?php echo $key; ?>">
      <div class="crm-kpi-icon"><i class="fas <?php echo $statusIcons[$key]; ?>"></i></div>
      <div>
        <div class="crm-kpi-val"><?php echo $counts[$key] ?? 0; ?></div>
        <div class="crm-kpi-lbl"><?php echo $label; ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Tabela -->
  <div class="crm-table-card">
    <div class="crm-table-header">
      <div class="d-flex align-items-center gap-2">
        <i class="fas fa-users text-primary"></i>
        <strong>Leads Cadastrados</strong>
        <span class="badge bg-secondary"><?php echo count($leads); ?></span>
      </div>
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <!-- Filtros -->
        <form method="GET" action="/crm/leads" class="crm-filters">
          <input type="text" name="q" class="form-control" placeholder="Buscar..." value="<?php echo htmlspecialchars($filtros['q']); ?>" style="width:160px">
          <select name="status" class="form-select" style="width:130px" onchange="this.form.submit()">
            <option value="">Todos status</option>
            <?php foreach ($statusList as $k => $v): ?>
            <option value="<?php echo $k; ?>" <?php echo $filtros['status'] === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
            <?php endforeach; ?>
          </select>
          <select name="segmento" class="form-select" style="width:160px" onchange="this.form.submit()">
            <option value="">Todos segmentos</option>
            <?php foreach ($segmentos as $k => $v): ?>
            <option value="<?php echo $k; ?>" <?php echo $filtros['segmento'] === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
          <?php if ($filtros['q'] || $filtros['status'] || $filtros['segmento']): ?>
          <a href="/crm/leads" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
          <?php endif; ?>
        </form>
        <a href="/crm/leads/create" class="btn btn-sm btn-primary">
          <i class="fas fa-plus me-1"></i> Novo Lead
        </a>
      </div>
    </div>

    <?php if (empty($leads)): ?>
    <div class="empty-state">
      <i class="fas fa-users-slash"></i>
      <p class="mb-3">Nenhum lead encontrado.</p>
      <a href="/crm/leads/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Cadastrar Primeiro Lead</a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
        <thead class="table-light">
          <tr>
            <th class="ps-3">Lead / Empresa</th>
            <th>Segmento</th>
            <th>Origem</th>
            <th>Status</th>
            <th>Interações</th>
            <th>Próx. Contato</th>
            <th class="text-end pe-3">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($leads as $lead): ?>
          <?php
            $vencido = $lead->data_proximo_contato && $lead->data_proximo_contato < $hoje && $lead->status_lead !== 'descartado';
            $cor     = $statusColors[$lead->status_lead] ?? 'secondary';
          ?>
          <tr class="lead-row">
            <td class="ps-3">
              <div class="lead-name">
                <?php echo htmlspecialchars($lead->nome_lead); ?>
                <?php if ($lead->convertido_em): ?>
                <span class="badge bg-success-subtle text-success ms-1" style="font-size:.65rem">Convertido</span>
                <?php endif; ?>
              </div>
              <div class="lead-sub">
                <?php if ($lead->email): ?><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($lead->email); ?><?php endif; ?>
                <?php if ($lead->telefone): ?> &nbsp;·&nbsp; <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($lead->telefone); ?><?php endif; ?>
              </div>
            </td>
            <td><?php echo htmlspecialchars($segmentos[$lead->segmento_principal] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($origens[$lead->origem] ?? '—'); ?></td>
            <td>
              <span class="badge-status bg-<?php echo $cor; ?>-subtle text-<?php echo $cor; ?>">
                <i class="fas <?php echo $statusIcons[$lead->status_lead] ?? 'fa-circle'; ?> me-1"></i>
                <?php echo $statusList[$lead->status_lead] ?? $lead->status_lead; ?>
              </span>
            </td>
            <td class="text-center">
              <span class="badge bg-light text-dark border"><?php echo (int)$lead->total_interacoes; ?></span>
            </td>
            <td>
              <?php if ($lead->data_proximo_contato): ?>
                <?php echo date('d/m/Y', strtotime($lead->data_proximo_contato)); ?>
                <?php if ($vencido): ?><span class="vencido-badge">Vencido</span><?php endif; ?>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td class="text-end pe-3">
              <div class="action-btns justify-content-end">
                <a href="/crm/leads/edit/<?php echo $lead->id; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                  <i class="fas fa-edit"></i>
                </a>
                <?php if ($lead->status_lead !== 'descartado' && !$lead->convertido_em): ?>
                <a href="/crm/leads/converter/<?php echo $lead->id; ?>" class="btn btn-sm btn-outline-success" title="Converter em Oportunidade"
                   onclick="return confirm('Converter este lead em oportunidade?')">
                  <i class="fas fa-arrow-right"></i>
                </a>
                <?php endif; ?>
                <?php if ($lead->convertido_em === 'oportunidade'): ?>
                <a href="/crm/oportunidades/edit/<?php echo $lead->convertido_id; ?>" class="btn btn-sm btn-success" title="Ver Oportunidade">
                  <i class="fas fa-chart-line"></i>
                </a>
                <?php endif; ?>
                <form method="POST" action="/crm/leads/delete/<?php echo $lead->id; ?>" class="d-inline"
                      onsubmit="return confirm('Excluir este lead permanentemente?')">
                  <?php echo View::csrfField(); ?>
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
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

<?php require_once dirname(__DIR__, 2) . '/layout/erp_footer.php'; ?>
