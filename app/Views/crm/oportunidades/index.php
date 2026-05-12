<?php
use App\Core\View;
$statusColors = [
    'aberta'  => 'primary',
    'ganha'   => 'success',
    'perdida' => 'danger',
];
$etapaColors = [
    'qualificacao' => 'secondary',
    'proposta'     => 'info',
    'negociacao'   => 'warning',
    'fechamento'   => 'success',
];

// Função auxiliar para formatar data de próximo contato com urgência
function proximoContatoLabel(string|null $data): string {
    if (!$data) return '<span class="text-muted">—</span>';
    $hoje   = new DateTime('today');
    $dt     = new DateTime($data);
    $diff   = (int) $hoje->diff($dt)->days;
    $sinal  = $dt >= $hoje ? 1 : -1;
    $dias   = $diff * $sinal;
    $fmt    = date('d/m/Y', strtotime($data));

    if ($dias < 0) {
        return "<span class=\"badge bg-danger-subtle text-danger\" title=\"Atrasado {$diff}d\"><i class=\"fas fa-exclamation-circle me-1\"></i>{$fmt}</span>";
    } elseif ($dias === 0) {
        return "<span class=\"badge bg-warning-subtle text-warning\"><i class=\"fas fa-clock me-1\"></i>Hoje</span>";
    } elseif ($dias <= 3) {
        return "<span class=\"badge bg-warning-subtle text-warning\"><i class=\"fas fa-calendar-day me-1\"></i>{$fmt}</span>";
    } else {
        return "<span class=\"text-muted\" style=\"font-size:.8rem\">{$fmt}</span>";
    }
}

$isAdmin   = $isAdmin ?? false;
$filtroUid = $filtroUid ?? 0;
$usuariosComOportunidades = $usuariosComOportunidades ?? [];
?>
<style>
.crm-kpi-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.crm-kpi{flex:1;min-width:160px;background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.crm-kpi-label{font-size:.75rem;color:#6b7280;margin-bottom:.25rem}
.crm-kpi-val{font-size:1.4rem;font-weight:700;color:#1e293b}
.crm-kpi-sub{font-size:.75rem;color:#94a3b8;margin-top:.1rem}
.crm-table-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.crm-table-header{display:flex;align-items:center;justify-content:space-between;padding:.875rem 1.25rem;border-bottom:1px solid #e2e8f0;flex-wrap:wrap;gap:.75rem}
.badge-etapa{font-size:.7rem;padding:.3em .7em;border-radius:20px;font-weight:600}
.badge-status{font-size:.7rem;padding:.3em .7em;border-radius:20px;font-weight:600}
.op-row:hover{background:#f8fafc}
.op-name{font-weight:600;color:#1e293b}
.op-sub{font-size:.75rem;color:#64748b}
.action-btns{display:flex;gap:.35rem;justify-content:flex-end}
.action-btns .btn{padding:.25rem .55rem;font-size:.75rem;border-radius:.4rem}
.empty-state{text-align:center;padding:3.5rem 1rem;color:#94a3b8}
.empty-state i{font-size:3rem;margin-bottom:.75rem;display:block}
.prob-bar{height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden;margin-top:3px}
.prob-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,#f59e0b,#10b981)}
.proximo-contato-col{min-width:120px}
.admin-user-selector{background:#f0f7ff;border:1px solid #bfdbfe;border-radius:.5rem;padding:.4rem .75rem;display:flex;align-items:center;gap:.5rem;font-size:.8125rem;color:#1e40af}
</style>

<div class="container-fluid">

  <!-- KPIs por etapa -->
  <div class="crm-kpi-bar">
    <?php foreach ($etapas as $key => $label): ?>
    <?php $r = $resumo[$key] ?? ['total' => 0, 'valor_total' => 0]; ?>
    <div class="crm-kpi">
      <div class="crm-kpi-label"><span class="badge bg-<?php echo $etapaColors[$key] ?? 'secondary'; ?>-subtle text-<?php echo $etapaColors[$key] ?? 'secondary'; ?>"><?php echo $label; ?></span></div>
      <div class="crm-kpi-val"><?php echo $r['total']; ?> <small style="font-size:.875rem;font-weight:400">oport.</small></div>
      <div class="crm-kpi-sub">R$ <?php echo number_format($r['valor_total'], 2, ',', '.'); ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Tabela -->
  <div class="crm-table-card">
    <div class="crm-table-header">
      <div class="d-flex align-items-center gap-2">
        <i class="fas fa-chart-line text-success"></i>
        <strong>Oportunidades</strong>
        <span class="badge bg-secondary"><?php echo count($oportunidades); ?></span>
        <?php if ($isAdmin && $filtroUid > 0): ?>
        <?php
          $nomeUsuarioFiltrado = '';
          foreach ($usuariosComOportunidades as $u) {
              if ((int)$u->id === (int)$filtroUid) { $nomeUsuarioFiltrado = $u->name; break; }
          }
        ?>
        <span class="badge bg-info-subtle text-info" style="font-size:.7rem">
          <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($nomeUsuarioFiltrado); ?>
        </span>
        <?php elseif ($isAdmin): ?>
        <span class="badge bg-primary-subtle text-primary" style="font-size:.7rem">
          <i class="fas fa-users me-1"></i>Todos os usuários
        </span>
        <?php endif; ?>
      </div>
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <form method="GET" action="/crm/oportunidades" class="d-flex gap-2 flex-wrap align-items-center">
          <?php if ($isAdmin && !empty($usuariosComOportunidades)): ?>
          <div class="admin-user-selector">
            <i class="fas fa-user-cog"></i>
            <select name="uid" class="form-select form-select-sm border-0 bg-transparent p-0" style="width:160px;box-shadow:none" onchange="this.form.submit()" title="Filtrar por usuário">
              <option value="0" <?php echo $filtroUid == 0 ? 'selected' : ''; ?>>Todos os usuários</option>
              <?php foreach ($usuariosComOportunidades as $u): ?>
              <option value="<?php echo $u->id; ?>" <?php echo (int)$filtroUid === (int)$u->id ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($u->name); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar..." value="<?php echo htmlspecialchars($filtros['q']); ?>" style="width:160px">
          <select name="etapa" class="form-select form-select-sm" style="width:140px" onchange="this.form.submit()">
            <option value="">Todas etapas</option>
            <?php foreach ($etapas as $k => $v): ?>
            <option value="<?php echo $k; ?>" <?php echo $filtros['etapa'] === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
            <?php endforeach; ?>
          </select>
          <select name="status" class="form-select form-select-sm" style="width:120px" onchange="this.form.submit()">
            <option value="">Todos status</option>
            <?php foreach ($statusList as $k => $v): ?>
            <option value="<?php echo $k; ?>" <?php echo $filtros['status'] === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
          <?php if ($filtros['q'] || $filtros['etapa'] || ($filtros['status'] && $filtros['status'] !== 'aberta') || ($isAdmin && $filtroUid > 0)): ?>
          <a href="/crm/oportunidades" class="btn btn-sm btn-outline-secondary" title="Limpar filtros"><i class="fas fa-times"></i></a>
          <?php endif; ?>
        </form>
        <a href="/crm/oportunidades/create" class="btn btn-sm btn-success">
          <i class="fas fa-plus me-1"></i> Nova Oportunidade
        </a>
        <a href="/crm/funil<?php echo $isAdmin && $filtroUid > 0 ? '?uid=' . $filtroUid : ''; ?>" class="btn btn-sm btn-outline-primary">
          <i class="fas fa-columns me-1"></i> Ver Funil
        </a>
      </div>
    </div>

    <?php if (empty($oportunidades)): ?>
    <div class="empty-state">
      <i class="fas fa-chart-bar"></i>
      <p class="mb-3">Nenhuma oportunidade encontrada.</p>
      <a href="/crm/oportunidades/create" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i> Criar Primeira Oportunidade</a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size:.875rem">
        <thead class="table-light">
          <tr>
            <th class="ps-3">Oportunidade</th>
            <th>Contato</th>
            <?php if ($isAdmin && $filtroUid == 0): ?>
            <th>Responsável</th>
            <?php endif; ?>
            <th>Etapa</th>
            <th>Valor Est.</th>
            <th>Probabilidade</th>
            <th>Próx. Contato</th>
            <th>Fechamento</th>
            <th>Status</th>
            <th class="text-end pe-3">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($oportunidades as $op): ?>
          <?php
            $corEtapa  = $etapaColors[$op->etapa_funil] ?? 'secondary';
            $corStatus = $statusColors[$op->status_oportunidade] ?? 'secondary';
            $prob      = (int)($op->probabilidade_sucesso ?? 0);
          ?>
          <tr class="op-row">
            <td class="ps-3">
              <div class="op-name"><?php echo htmlspecialchars($op->titulo_oportunidade); ?></div>
              <div class="op-sub">
                <?php if ($op->total_interacoes): ?>
                <i class="fas fa-comments me-1"></i><?php echo $op->total_interacoes; ?> interações
                <?php endif; ?>
                <?php if (!empty($op->modalidade_principal) && isset($modalidades[$op->modalidade_principal])): ?>
                &nbsp;<span class="badge bg-success-subtle text-success" style="font-size:.65rem"><?php echo $modalidades[$op->modalidade_principal]; ?></span>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <div><?php echo htmlspecialchars($op->nome_contato ?? '—'); ?></div>
              <?php if ($op->lead_email): ?>
              <div class="op-sub"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($op->lead_email); ?></div>
              <?php endif; ?>
              <?php if (!empty($op->lead_telefone)): ?>
              <div class="op-sub">
                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($op->lead_telefone); ?>
                <?php $rawOT = preg_replace('/\D/', '', $op->lead_telefone); if (strlen($rawOT) >= 10): $waOT = (substr($rawOT,0,2)==='55')?$rawOT:'55'.$rawOT; ?>
                <a href="https://wa.me/<?php echo $waOT; ?>" target="_blank" title="WhatsApp" class="ms-1 text-success" onclick="event.stopPropagation()"><i class="fab fa-whatsapp"></i></a>
                <?php endif; ?>
              </div>
              <?php endif; ?>
            </td>
            <?php if ($isAdmin && $filtroUid == 0): ?>
            <td>
              <span class="badge bg-secondary-subtle text-secondary" style="font-size:.7rem">
                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($op->usuario_nome ?? '—'); ?>
              </span>
            </td>
            <?php endif; ?>
            <td>
              <span class="badge-etapa bg-<?php echo $corEtapa; ?>-subtle text-<?php echo $corEtapa; ?>">
                <?php echo $etapas[$op->etapa_funil] ?? $op->etapa_funil; ?>
              </span>
            </td>
            <td>
              <?php if ($op->valor_estimado): ?>
              <strong>R$ <?php echo number_format($op->valor_estimado, 2, ',', '.'); ?></strong>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td style="min-width:90px">
              <span><?php echo $prob; ?>%</span>
              <div class="prob-bar"><div class="prob-fill" style="width:<?php echo $prob; ?>%"></div></div>
            </td>
            <td class="proximo-contato-col">
              <?php echo proximoContatoLabel($op->data_proximo_contato ?? null); ?>
            </td>
            <td>
              <?php if ($op->data_fechamento_prevista): ?>
              <?php echo date('d/m/Y', strtotime($op->data_fechamento_prevista)); ?>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td>
              <span class="badge-status bg-<?php echo $corStatus; ?>-subtle text-<?php echo $corStatus; ?>">
                <?php echo $statusList[$op->status_oportunidade] ?? $op->status_oportunidade; ?>
              </span>
            </td>
            <td class="text-end pe-3">
              <div class="action-btns">
                <a href="/crm/oportunidades/edit/<?php echo $op->id; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                  <i class="fas fa-edit"></i>
                </a>
                <form method="POST" action="/crm/oportunidades/delete/<?php echo $op->id; ?>" class="d-inline"
                      onsubmit="return confirm('Excluir esta oportunidade?')">
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
