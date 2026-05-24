<?php
$proposta  = $proposta  ?? null;
$itens     = $itens     ?? [];
$historico = $historico ?? [];
$user      = $user      ?? null;
$isAdmin   = $isAdmin   ?? false;

if (!$proposta) { header('Location: /crm/propostas'); exit(); }

$statusConfig = [
    'gerada'     => ['label' => 'Gerada',     'color' => 'secondary', 'icon' => 'fa-file-alt',     'bg' => '#f3f4f6', 'text' => '#374151'],
    'enviada'    => ['label' => 'Enviada',    'color' => 'primary',   'icon' => 'fa-paper-plane',  'bg' => '#eff6ff', 'text' => '#1d4ed8'],
    'visualizada'=> ['label' => 'Visualizada','color' => 'info',      'icon' => 'fa-eye',           'bg' => '#ecfeff', 'text' => '#0e7490'],
    'aceita'     => ['label' => 'Aceita',     'color' => 'success',   'icon' => 'fa-check-circle', 'bg' => '#f0fdf4', 'text' => '#166534'],
    'recusada'   => ['label' => 'Recusada',   'color' => 'danger',    'icon' => 'fa-times-circle', 'bg' => '#fef2f2', 'text' => '#991b1b'],
    'expirada'   => ['label' => 'Expirada',   'color' => 'warning',   'icon' => 'fa-clock',        'bg' => '#fffbeb', 'text' => '#92400e'],
];
$sc = $statusConfig[$proposta->status] ?? $statusConfig['gerada'];

$hoje  = new DateTime('today');
$valDt = !empty($proposta->validade_proposta) ? new DateTime($proposta->validade_proposta) : null;
$expirada = $valDt && $valDt < $hoje;
$editavel = in_array($proposta->status, ['gerada', 'enviada', 'visualizada']);
?>
<style>
.prop-header{background:linear-gradient(135deg,#1a56db 0%,#0e3a8c 100%);color:#fff;border-radius:.75rem;padding:1.5rem 2rem;margin-bottom:1.5rem;box-shadow:0 4px 12px rgba(26,86,219,.2)}
.prop-header .numero{font-size:1.5rem;font-weight:700}
.prop-header .titulo{font-size:1rem;opacity:.9;margin-top:.25rem}
.status-badge{display:inline-flex;align-items:center;gap:.4rem;padding:.4rem .9rem;border-radius:20px;font-size:.8rem;font-weight:600}
.info-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06);height:100%}
.info-card-title{font-size:.7rem;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;font-weight:700;margin-bottom:.75rem;padding-bottom:.5rem;border-bottom:1px solid #e2e8f0}
.info-row{display:flex;justify-content:space-between;align-items:flex-start;padding:.3rem 0;border-bottom:1px solid #f1f5f9;font-size:.875rem}
.info-row:last-child{border-bottom:none}
.info-label{color:#6b7280;flex-shrink:0;margin-right:.5rem}
.info-value{font-weight:500;color:#1e293b;text-align:right}
.itens-table th{background:#f8fafc;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;font-weight:600;padding:.6rem .75rem;border-bottom:2px solid #e2e8f0}
.itens-table td{padding:.65rem .75rem;border-bottom:1px solid #f1f5f9;font-size:.875rem;vertical-align:middle}
.itens-table tr:last-child td{border-bottom:none}
.itens-table tr:hover td{background:#f8fafc}
.total-section{background:#f0f7ff;border-radius:.5rem;padding:1rem 1.25rem}
.total-row{display:flex;justify-content:space-between;padding:.3rem 0;font-size:.875rem}
.total-final{font-size:1.1rem;font-weight:700;color:#1a56db;border-top:2px solid #bfdbfe;padding-top:.5rem;margin-top:.25rem}
.hist-item{display:flex;gap:.75rem;padding:.75rem 0;border-bottom:1px solid #f1f5f9}
.hist-item:last-child{border-bottom:none}
.hist-dot{width:10px;height:10px;border-radius:50%;background:#1a56db;flex-shrink:0;margin-top:.3rem}
.hist-content{flex:1}
.hist-status{font-weight:600;font-size:.875rem}
.hist-meta{font-size:.75rem;color:#6b7280}
.action-bar{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem 1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.06);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.5rem}
.margem-badge{display:inline-block;padding:.15em .5em;border-radius:10px;font-size:.7rem;font-weight:600}
.margem-pos{background:#dcfce7;color:#166534}
.margem-neg{background:#fee2e2;color:#991b1b}
</style>

<div class="container-fluid">

  <!-- Cabeçalho da proposta -->
  <div class="prop-header">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <a href="/crm/propostas" class="btn btn-sm btn-light btn-sm opacity-75">
            <i class="fas fa-arrow-left"></i>
          </a>
          <span class="numero"><?php echo htmlspecialchars($proposta->numero); ?></span>
        </div>
        <div class="titulo"><?php echo htmlspecialchars($proposta->titulo); ?></div>
        <div style="font-size:.8rem;opacity:.75;margin-top:.4rem">
          Criada em <?php echo date('d/m/Y \à\s H:i', strtotime($proposta->created_at)); ?>
        </div>
      </div>
      <div class="text-end">
        <div class="status-badge" style="background:<?php echo $sc['bg']; ?>;color:<?php echo $sc['text']; ?>">
          <i class="fas <?php echo $sc['icon']; ?>"></i>
          <?php echo $sc['label']; ?>
        </div>
        <?php if ($expirada && $proposta->status !== 'expirada'): ?>
        <div class="mt-1" style="font-size:.75rem;color:#fbbf24">
          <i class="fas fa-exclamation-triangle me-1"></i>Proposta expirada
        </div>
        <?php endif; ?>
        <div style="font-size:1.5rem;font-weight:700;margin-top:.5rem">
          R$ <?php echo number_format((float)$proposta->total, 2, ',', '.'); ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Barra de ações -->
  <div class="action-bar">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="/crm/propostas/<?php echo $proposta->id; ?>/pdf" class="btn btn-outline-success btn-sm">
        <i class="fas fa-file-pdf me-1"></i>Baixar PDF
      </a>
      <?php if (!empty($proposta->cliente_email)): ?>
      <button type="button" class="btn btn-primary btn-sm" onclick="enviarProposta()">
        <i class="fas fa-paper-plane me-1"></i>Enviar por E-mail
      </button>
      <?php else: ?>
      <button type="button" class="btn btn-primary btn-sm" disabled title="Cadastre o e-mail do cliente para enviar">
        <i class="fas fa-paper-plane me-1"></i>Enviar por E-mail
      </button>
      <?php endif; ?>
      <?php if ($editavel): ?>
      <a href="/crm/propostas/<?php echo $proposta->id; ?>/edit" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-edit me-1"></i>Editar
      </a>
      <?php endif; ?>
    </div>
    <div class="d-flex align-items-center gap-2">
      <!-- Alterar status manualmente -->
      <div class="dropdown">
        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
          <i class="fas fa-exchange-alt me-1"></i>Alterar Status
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <?php
          $statusOpcoes = ['gerada','enviada','visualizada','aceita','recusada','expirada'];
          foreach ($statusOpcoes as $s):
            $sc2 = $statusConfig[$s];
            if ($s === $proposta->status) continue;
          ?>
          <li>
            <a class="dropdown-item" href="#" onclick="alterarStatus('<?php echo $s; ?>')">
              <i class="fas <?php echo $sc2['icon']; ?> me-2 text-<?php echo $sc2['color']; ?>"></i>
              <?php echo $sc2['label']; ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmarExclusao()">
        <i class="fas fa-trash me-1"></i>Excluir
      </button>
    </div>
  </div>

  <div class="row g-3">

    <!-- Coluna esquerda: dados + itens -->
    <div class="col-lg-8">

      <!-- Dados do cliente e proposta -->
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <div class="info-card">
            <div class="info-card-title"><i class="fas fa-user-tie me-1"></i>Dados do Cliente</div>
            <div class="info-row"><span class="info-label">Nome:</span><span class="info-value"><?php echo htmlspecialchars($proposta->cliente_nome); ?></span></div>
            <?php if (!empty($proposta->cliente_cnpj_cpf)): ?>
            <div class="info-row"><span class="info-label">CPF/CNPJ:</span><span class="info-value"><?php echo htmlspecialchars($proposta->cliente_cnpj_cpf); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($proposta->cliente_email)): ?>
            <div class="info-row"><span class="info-label">E-mail:</span><span class="info-value"><?php echo htmlspecialchars($proposta->cliente_email); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($proposta->cliente_telefone)): ?>
            <div class="info-row"><span class="info-label">Telefone:</span><span class="info-value"><?php echo htmlspecialchars($proposta->cliente_telefone); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($proposta->cliente_endereco)): ?>
            <div class="info-row"><span class="info-label">Endereço:</span><span class="info-value"><?php echo htmlspecialchars($proposta->cliente_endereco); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($proposta->cliente_cidade)): ?>
            <div class="info-row"><span class="info-label">Cidade/UF:</span><span class="info-value"><?php echo htmlspecialchars($proposta->cliente_cidade . ' - ' . $proposta->cliente_estado); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($proposta->cliente_responsavel)): ?>
            <div class="info-row"><span class="info-label">Responsável:</span><span class="info-value"><?php echo htmlspecialchars($proposta->cliente_responsavel); ?></span></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="info-card">
            <div class="info-card-title"><i class="fas fa-file-contract me-1"></i>Dados da Proposta</div>
            <div class="info-row"><span class="info-label">Número:</span><span class="info-value fw-bold text-primary"><?php echo htmlspecialchars($proposta->numero); ?></span></div>
            <div class="info-row">
              <span class="info-label">Validade:</span>
              <span class="info-value <?php echo $expirada ? 'text-danger' : ''; ?>">
                <?php echo !empty($proposta->validade_proposta) ? date('d/m/Y', strtotime($proposta->validade_proposta)) : '—'; ?>
                <?php if ($expirada): ?><i class="fas fa-exclamation-circle ms-1"></i><?php endif; ?>
              </span>
            </div>
            <?php if (!empty($proposta->prazo_entrega)): ?>
            <div class="info-row"><span class="info-label">Prazo Entrega:</span><span class="info-value"><?php echo htmlspecialchars($proposta->prazo_entrega); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($proposta->condicao_pagamento)): ?>
            <div class="info-row"><span class="info-label">Pagamento:</span><span class="info-value"><?php echo htmlspecialchars($proposta->condicao_pagamento); ?></span></div>
            <?php endif; ?>
            <?php
            $freteLabels = ['a_calcular'=>'A calcular','cif'=>'CIF','fob'=>'FOB','gratis'=>'Grátis','valor'=>'Valor fixo'];
            $freteLabel  = $freteLabels[$proposta->frete_tipo ?? 'a_calcular'] ?? $proposta->frete_tipo;
            ?>
            <div class="info-row"><span class="info-label">Frete:</span><span class="info-value"><?php echo $freteLabel; ?> <?php echo (float)($proposta->frete_valor ?? 0) > 0 ? '(R$ ' . number_format((float)$proposta->frete_valor, 2, ',', '.') . ')' : ''; ?></span></div>
            <?php if (!empty($proposta->local_entrega)): ?>
            <div class="info-row"><span class="info-label">Local Entrega:</span><span class="info-value"><?php echo htmlspecialchars($proposta->local_entrega); ?></span></div>
            <?php endif; ?>
            <?php if (!empty($proposta->oportunidade_id)): ?>
            <div class="info-row">
              <span class="info-label">Oportunidade:</span>
              <span class="info-value">
                <a href="/crm/oportunidades/<?php echo $proposta->oportunidade_id; ?>" class="text-decoration-none">
                  #<?php echo $proposta->oportunidade_id; ?>
                </a>
              </span>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Itens -->
      <div class="info-card mb-3">
        <div class="info-card-title"><i class="fas fa-boxes me-1"></i>Itens da Proposta</div>
        <?php if (empty($itens)): ?>
        <p class="text-muted text-center py-3">Nenhum item cadastrado.</p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table itens-table mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Código</th>
                <th>Descrição</th>
                <th class="text-center">Un.</th>
                <th class="text-end">Qtd.</th>
                <th class="text-end">Preço Unit.</th>
                <th class="text-end">Desc. %</th>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($itens as $i => $item): ?>
              <tr>
                <td class="text-muted"><?php echo $i + 1; ?></td>
                <td><?php echo htmlspecialchars($item->codigo ?? '—'); ?></td>
                <td>
                  <?php echo htmlspecialchars($item->descricao); ?>
                  <?php if ((float)($item->margem_lucro ?? 0) > 0): ?>
                  <span class="margem-badge margem-pos ms-1"><?php echo number_format((float)$item->margem_lucro, 1); ?>% mg</span>
                  <?php elseif ((float)($item->margem_lucro ?? 0) < 0): ?>
                  <span class="margem-badge margem-neg ms-1"><?php echo number_format((float)$item->margem_lucro, 1); ?>% mg</span>
                  <?php endif; ?>
                </td>
                <td class="text-center"><?php echo htmlspecialchars($item->unidade ?? 'un'); ?></td>
                <td class="text-end"><?php echo number_format((float)$item->quantidade, 2, ',', '.'); ?></td>
                <td class="text-end">R$ <?php echo number_format((float)$item->preco_unitario, 2, ',', '.'); ?></td>
                <td class="text-end"><?php echo (float)$item->desconto_item > 0 ? number_format((float)$item->desconto_item, 1) . '%' : '—'; ?></td>
                <td class="text-end fw-bold">R$ <?php echo number_format((float)$item->total_item, 2, ',', '.'); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <!-- Totais -->
        <div class="d-flex justify-content-end mt-3">
          <div class="total-section" style="min-width:280px">
            <div class="total-row"><span class="text-muted">Subtotal:</span><span>R$ <?php echo number_format((float)$proposta->subtotal, 2, ',', '.'); ?></span></div>
            <?php if ((float)($proposta->desconto_total ?? 0) > 0): ?>
            <div class="total-row"><span class="text-muted">Desconto:</span><span class="text-danger">- R$ <?php echo number_format((float)$proposta->desconto_total, 2, ',', '.'); ?></span></div>
            <?php endif; ?>
            <?php if ((float)($proposta->frete_valor ?? 0) > 0): ?>
            <div class="total-row"><span class="text-muted">Frete:</span><span>R$ <?php echo number_format((float)$proposta->frete_valor, 2, ',', '.'); ?></span></div>
            <?php endif; ?>
            <div class="total-row total-final"><span>TOTAL GERAL:</span><span>R$ <?php echo number_format((float)$proposta->total, 2, ',', '.'); ?></span></div>
          </div>
        </div>
      </div>

      <!-- Observações -->
      <?php if (!empty($proposta->observacoes)): ?>
      <div class="info-card mb-3">
        <div class="info-card-title"><i class="fas fa-comment-alt me-1"></i>Observações</div>
        <p class="mb-0" style="font-size:.875rem;white-space:pre-wrap"><?php echo htmlspecialchars($proposta->observacoes); ?></p>
      </div>
      <?php endif; ?>

    </div>

    <!-- Coluna direita: histórico + ações rápidas -->
    <div class="col-lg-4">

      <!-- Status atual -->
      <div class="info-card mb-3">
        <div class="info-card-title"><i class="fas fa-info-circle me-1"></i>Status Atual</div>
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="status-badge" style="background:<?php echo $sc['bg']; ?>;color:<?php echo $sc['text']; ?>">
            <i class="fas <?php echo $sc['icon']; ?>"></i><?php echo $sc['label']; ?>
          </span>
        </div>
        <?php if (!empty($proposta->cliente_email)): ?>
        <button type="button" class="btn btn-primary btn-sm w-100 mt-2" onclick="enviarProposta()">
          <i class="fas fa-paper-plane me-1"></i>Enviar por E-mail
        </button>
        <?php endif; ?>
        <a href="/crm/propostas/<?php echo $proposta->id; ?>/pdf" class="btn btn-outline-success btn-sm w-100 mt-2">
          <i class="fas fa-file-pdf me-1"></i>Baixar PDF
        </a>
        <?php if ($editavel): ?>
        <a href="/crm/propostas/<?php echo $proposta->id; ?>/edit" class="btn btn-outline-secondary btn-sm w-100 mt-2">
          <i class="fas fa-edit me-1"></i>Editar Proposta
        </a>
        <?php endif; ?>
      </div>

      <!-- Histórico -->
      <div class="info-card">
        <div class="info-card-title"><i class="fas fa-history me-1"></i>Histórico</div>
        <?php if (empty($historico)): ?>
        <p class="text-muted small text-center py-2">Nenhum registro no histórico.</p>
        <?php else: ?>
        <div style="max-height:350px;overflow-y:auto">
          <?php foreach ($historico as $h):
            $hsc = $statusConfig[$h->status_novo] ?? $statusConfig['gerada'];
          ?>
          <div class="hist-item">
            <div class="hist-dot" style="background:<?php echo $hsc['text']; ?>"></div>
            <div class="hist-content">
              <div class="hist-status">
                <span class="badge bg-<?php echo $hsc['color']; ?>-subtle text-<?php echo $hsc['color']; ?>" style="font-size:.7rem">
                  <?php echo $hsc['label']; ?>
                </span>
              </div>
              <?php if (!empty($h->observacao)): ?>
              <div style="font-size:.8rem;color:#374151;margin-top:.2rem"><?php echo htmlspecialchars($h->observacao); ?></div>
              <?php endif; ?>
              <div class="hist-meta">
                <?php echo htmlspecialchars($h->usuario_nome ?? 'Sistema'); ?> ·
                <?php echo date('d/m/Y H:i', strtotime($h->created_at)); ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<!-- Modal: Alterar Status -->
<div class="modal fade" id="modalStatus" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h6 class="modal-title"><i class="fas fa-exchange-alt text-primary me-2"></i>Alterar Status</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formStatus" method="POST" action="/crm/propostas/<?php echo $proposta->id; ?>/status">
        <div class="modal-body">
          <input type="hidden" name="status" id="novoStatus">
          <div class="mb-3">
            <label class="form-label">Novo Status</label>
            <div id="novoStatusLabel" class="fw-bold"></div>
          </div>
          <div>
            <label class="form-label">Observação <span class="text-muted small">(opcional)</span></label>
            <textarea name="observacao" class="form-control form-control-sm" rows="2" placeholder="Motivo da alteração..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-primary">Confirmar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Excluir -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h6 class="modal-title"><i class="fas fa-trash text-danger me-2"></i>Excluir Proposta</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p>Tem certeza que deseja excluir a proposta <strong><?php echo htmlspecialchars($proposta->numero); ?></strong>?</p>
        <p class="text-muted small">Esta ação não pode ser desfeita.</p>
      </div>
      <div class="modal-footer border-0 justify-content-center gap-2">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <form method="POST" action="/crm/propostas/<?php echo $proposta->id; ?>/delete">
          <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
  <div id="toastMsg" class="toast align-items-center text-white border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toastBody"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script>
const statusLabels = {
  gerada:'Gerada', enviada:'Enviada', visualizada:'Visualizada',
  aceita:'Aceita', recusada:'Recusada', expirada:'Expirada'
};

function alterarStatus(status) {
  document.querySelector('#novoStatus').value      = status;
  document.querySelector('#novoStatusLabel').textContent = statusLabels[status] || status;
  new bootstrap.Modal(document.querySelector('#modalStatus')).show();
}

function confirmarExclusao() {
  new bootstrap.Modal(document.querySelector('#modalExcluir')).show();
}

async function enviarProposta() {
  const btn = event.currentTarget;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enviando...';

  try {
    const r = await fetch('/crm/propostas/<?php echo $proposta->id; ?>/enviar', { method: 'POST' });
    const j = await r.json();
    if (j.success) {
      showToast(j.message || 'Proposta enviada com sucesso!', 'success');
      setTimeout(() => location.reload(), 2000);
    } else {
      showToast(j.error || 'Erro ao enviar proposta.', 'danger');
    }
  } catch(e) {
    showToast('Erro de comunicação.', 'danger');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Enviar por E-mail';
  }
}

// Submit status via AJAX
document.querySelector('#formStatus').addEventListener('submit', async function(e) {
  e.preventDefault();
  const fd = new FormData(this);
  try {
    const r = await fetch(this.action, { method: 'POST', body: fd });
    const j = await r.json();
    if (j.success) {
      bootstrap.Modal.getInstance(document.querySelector('#modalStatus'))?.hide();
      showToast('Status atualizado!', 'success');
      setTimeout(() => location.reload(), 1200);
    } else {
      showToast(j.error || 'Erro ao atualizar status.', 'danger');
    }
  } catch(e) {
    showToast('Erro de comunicação.', 'danger');
  }
});

function showToast(msg, type = 'success') {
  const el = document.querySelector('#toastMsg');
  el.className = 'toast align-items-center text-white border-0 bg-' + type;
  document.querySelector('#toastBody').textContent = msg;
  new bootstrap.Toast(el, { delay: 4000 }).show();
}
</script>
