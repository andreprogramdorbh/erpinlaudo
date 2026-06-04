<?php
$statusConfig = [
    'aberta'          => ['label' => 'Aberta',           'color' => 'primary',   'icon' => 'fa-folder-open'],
    'em_andamento'    => ['label' => 'Em Andamento',     'color' => 'info',      'icon' => 'fa-tools'],
    'aguardando_peca' => ['label' => 'Aguard. Peça',     'color' => 'warning',   'icon' => 'fa-clock'],
    'concluida'       => ['label' => 'Concluída',        'color' => 'success',   'icon' => 'fa-check-circle'],
    'faturada'        => ['label' => 'Faturada',         'color' => 'dark',      'icon' => 'fa-receipt'],
    'cancelada'       => ['label' => 'Cancelada',        'color' => 'danger',    'icon' => 'fa-times-circle'],
];
$tipoConfig = [
    'preventiva' => ['label' => 'Preventiva', 'color' => 'success'],
    'corretiva'  => ['label' => 'Corretiva',  'color' => 'danger'],
];
$prioConfig = [
    'baixa'  => ['label' => 'Baixa',  'color' => 'secondary'],
    'normal' => ['label' => 'Normal', 'color' => 'primary'],
    'alta'   => ['label' => 'Alta',   'color' => 'warning'],
    'urgente'=> ['label' => 'Urgente','color' => 'danger'],
];
$ordens  = $ordens  ?? [];
$kpis    = $kpis    ?? [];
$filtros = $filtros ?? [];
?>
<style>
.os-kpi-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.os-kpi{flex:1;min-width:140px;background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.os-kpi-label{font-size:.75rem;color:#6b7280;margin-bottom:.25rem}
.os-kpi-val{font-size:1.4rem;font-weight:700;color:#1e293b}
.os-kpi-sub{font-size:.75rem;color:#94a3b8;margin-top:.1rem}
.os-table-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.os-table-header{display:flex;align-items:center;justify-content:space-between;padding:.875rem 1.25rem;border-bottom:1px solid #e2e8f0;flex-wrap:wrap;gap:.75rem}
.os-row:hover{background:#f8fafc}
.badge-status{font-size:.7rem;padding:.3em .7em;border-radius:20px;font-weight:600}
.empty-state{text-align:center;padding:3.5rem 1rem;color:#94a3b8}
.empty-state i{font-size:3rem;margin-bottom:.75rem;display:block}
</style>
<div class="container-fluid">
  <!-- KPIs -->
  <div class="os-kpi-bar">
    <div class="os-kpi">
      <div class="os-kpi-label">Total de O.S</div>
      <div class="os-kpi-val"><?= (int)($kpis->total ?? 0) ?></div>
    </div>
    <div class="os-kpi">
      <div class="os-kpi-label"><span class="badge bg-primary-subtle text-primary">Abertas</span></div>
      <div class="os-kpi-val"><?= (int)($kpis->abertas ?? 0) ?></div>
    </div>
    <div class="os-kpi">
      <div class="os-kpi-label"><span class="badge bg-info-subtle text-info">Em Andamento</span></div>
      <div class="os-kpi-val"><?= (int)($kpis->em_andamento ?? 0) ?></div>
    </div>
    <div class="os-kpi">
      <div class="os-kpi-label"><span class="badge bg-warning-subtle text-warning">Aguard. Peça</span></div>
      <div class="os-kpi-val"><?= (int)($kpis->aguardando_peca ?? 0) ?></div>
    </div>
    <div class="os-kpi">
      <div class="os-kpi-label"><span class="badge bg-success-subtle text-success">Concluídas</span></div>
      <div class="os-kpi-val"><?= (int)($kpis->concluidas ?? 0) ?></div>
    </div>
    <div class="os-kpi">
      <div class="os-kpi-label">Valor Total (mês)</div>
      <div class="os-kpi-val" style="color:#1a56db">R$ <?= number_format((float)($kpis->valor_mes ?? 0), 2, ',', '.') ?></div>
    </div>
  </div>

  <!-- Tabela -->
  <div class="os-table-card">
    <div class="os-table-header">
      <div class="d-flex align-items-center gap-2">
        <i class="fas fa-wrench text-primary"></i>
        <strong>Ordens de Serviço</strong>
      </div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <form method="GET" action="/manutencao/ordens" class="d-flex gap-2 align-items-center flex-wrap">
          <input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar cliente, número, série..." value="<?= htmlspecialchars($filtros['q'] ?? '') ?>" style="width:220px">
          <select name="status" class="form-select form-select-sm" style="width:150px">
            <option value="">Todos os status</option>
            <?php foreach ($statusConfig as $k => $s): ?>
            <option value="<?= $k ?>" <?= ($filtros['status'] ?? '') === $k ? 'selected' : '' ?>><?= $s['label'] ?></option>
            <?php endforeach; ?>
          </select>
          <select name="tipo" class="form-select form-select-sm" style="width:140px">
            <option value="">Todos os tipos</option>
            <option value="preventiva" <?= ($filtros['tipo'] ?? '') === 'preventiva' ? 'selected' : '' ?>>Preventiva</option>
            <option value="corretiva"  <?= ($filtros['tipo'] ?? '') === 'corretiva'  ? 'selected' : '' ?>>Corretiva</option>
          </select>
          <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-search"></i></button>
          <?php if (!empty($filtros['q']) || !empty($filtros['status']) || !empty($filtros['tipo'])): ?>
          <a href="/manutencao/ordens" class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></a>
          <?php endif; ?>
        </form>
        <a href="/manutencao/ordens/create" class="btn btn-sm btn-primary">
          <i class="fas fa-plus me-1"></i> Nova O.S
        </a>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0" style="font-size:.85rem">
        <thead style="background:#f8fafc">
          <tr>
            <th class="ps-3">Número</th>
            <th>Cliente</th>
            <th>Equipamento / Série</th>
            <th>Tipo</th>
            <th>Prioridade</th>
            <th>Status</th>
            <th>Abertura</th>
            <th>Valor Total</th>
            <th class="text-end pe-3">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($ordens)): ?>
          <tr><td colspan="9">
            <div class="empty-state">
              <i class="fas fa-tools"></i>
              <p>Nenhuma Ordem de Serviço encontrada.</p>
              <a href="/manutencao/ordens/create" class="btn btn-sm btn-primary">Criar primeira O.S</a>
            </div>
          </td></tr>
        <?php else: ?>
          <?php foreach ($ordens as $os): ?>
          <?php
            $st  = $statusConfig[$os->status]  ?? ['label' => $os->status, 'color' => 'secondary', 'icon' => 'fa-circle'];
            $tp  = $tipoConfig[$os->tipo]       ?? ['label' => ucfirst($os->tipo), 'color' => 'secondary'];
            $pr  = $prioConfig[$os->prioridade ?? 'normal'] ?? ['label' => 'Normal', 'color' => 'primary'];
          ?>
          <tr class="os-row">
            <td class="ps-3">
              <a href="/manutencao/ordens/<?= $os->id ?>" class="fw-semibold text-primary text-decoration-none">
                <?= htmlspecialchars($os->numero) ?>
              </a>
            </td>
            <td>
              <div class="fw-semibold"><?= htmlspecialchars($os->cliente_nome) ?></div>
              <?php if (!empty($os->cliente_cpf_cnpj)): ?>
              <div style="font-size:.75rem;color:#64748b"><?= htmlspecialchars($os->cliente_cpf_cnpj) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <div><?= htmlspecialchars($os->produto_nome ?? '-') ?></div>
              <?php if (!empty($os->numero_serie)): ?>
              <div style="font-size:.75rem;color:#64748b">S/N: <?= htmlspecialchars($os->numero_serie) ?></div>
              <?php endif; ?>
            </td>
            <td><span class="badge bg-<?= $tp['color'] ?>-subtle text-<?= $tp['color'] ?> badge-status"><?= $tp['label'] ?></span></td>
            <td><span class="badge bg-<?= $pr['color'] ?>-subtle text-<?= $pr['color'] ?> badge-status"><?= $pr['label'] ?></span></td>
            <td><span class="badge bg-<?= $st['color'] ?>-subtle text-<?= $st['color'] ?> badge-status"><i class="fas <?= $st['icon'] ?> me-1"></i><?= $st['label'] ?></span></td>
            <td><?= date('d/m/Y', strtotime($os->data_abertura)) ?></td>
            <td class="fw-semibold">R$ <?= number_format((float)$os->valor_total, 2, ',', '.') ?></td>
            <td class="text-end pe-3">
              <div class="d-flex gap-1 justify-content-end">
                <a href="/manutencao/ordens/<?= $os->id ?>" class="btn btn-xs btn-outline-primary" title="Visualizar"><i class="fas fa-eye"></i></a>
                <?php if ($os->status !== 'faturada' && $os->status !== 'cancelada'): ?>
                <a href="/manutencao/ordens/<?= $os->id ?>/edit" class="btn btn-xs btn-outline-secondary" title="Editar"><i class="fas fa-edit"></i></a>
                <?php endif; ?>
                <a href="/manutencao/ordens/<?= $os->id ?>/imprimir" target="_blank" class="btn btn-xs btn-outline-dark" title="Imprimir"><i class="fas fa-print"></i></a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
