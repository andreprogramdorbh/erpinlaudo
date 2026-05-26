<?php
/**
 * ERP InLaudo - Aba Transferência do Lead
 */
?>
<style>
.transf-responsavel-atual{background:#fff;border:1px solid #fde68a;border-radius:.5rem;padding:.75rem 1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.75rem}
.transf-responsavel-atual .label{font-size:.75rem;color:#92400e;font-weight:600;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.15rem}
</style>

<div id="transf-alert" class="alert d-none mb-3"></div>

<!-- Responsável atual -->
<div class="transf-responsavel-atual mb-4">
  <i class="fas fa-user-circle fa-2x text-warning"></i>
  <div>
    <span class="label">Responsável atual</span>
    <strong><?php echo htmlspecialchars($donomeAtual ?? ($_SESSION['user_name'] ?? 'Usuário')); ?></strong>
  </div>
</div>

<!-- Formulário de transferência -->
<section class="form-section">
  <h2 class="form-section-title"><i class="fas fa-exchange-alt text-warning"></i> Transferir Lead</h2>
  <div class="form-grid form-grid-3">
    <div class="form-group">
      <label class="form-label required">Transferir para</label>
      <select class="form-select" id="transf-para-usuario">
        <option value="">Selecione o usuário...</option>
        <?php foreach ($todosUsuarios ?? [] as $u): ?>
        <?php if ((int)($u['id'] ?? 0) === (int)($lead->usuario_id ?? 0)) continue; ?>
        <option value="<?php echo (int)($u['id'] ?? 0); ?>">
          <?php echo htmlspecialchars($u['name'] ?? ''); ?>
          <?php if (!empty($u['role'])): ?>(<?php echo htmlspecialchars($u['role']); ?>)<?php endif; ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label required">Motivo da Transferência</label>
      <select class="form-select" id="transf-motivo">
        <option value="">Selecione o motivo...</option>
        <?php foreach ($motivosTransferencia ?? [] as $key => $label): ?>
        <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($label); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group d-flex align-items-end">
      <button type="button" class="btn btn-warning w-100" onclick="confirmarTransferenciaLead()">
        <i class="fas fa-exchange-alt me-1"></i> Confirmar Transferência
      </button>
    </div>
  </div>
  <div class="form-group mt-3">
    <label class="form-label">Observação <small class="text-muted">(opcional)</small></label>
    <textarea class="form-control" id="transf-observacao" rows="3"
              placeholder="Descreva detalhes adicionais sobre a transferência..."></textarea>
  </div>
</section>

<!-- Histórico de transferências -->
<?php if (!empty($transferencias)): ?>
<section class="form-section">
  <h2 class="form-section-title"><i class="fas fa-history text-warning"></i> Histórico de Transferências</h2>
  <div class="timeline">
    <?php foreach ($transferencias as $t): ?>
    <div class="timeline-item">
      <div class="timeline-dot"><i class="fas fa-exchange-alt" style="font-size:.55rem;color:#d97706"></i></div>
      <div class="timeline-card" style="border-color:#fde68a;background:#fffbeb">
        <div class="timeline-meta">
          <span class="timeline-tipo" style="background:#fef3c7;color:#92400e">
            <?php echo htmlspecialchars(($motivosTransferencia ?? [])[$t->motivo] ?? $t->motivo); ?>
          </span>
          <span class="timeline-data"><i class="fas fa-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($t->created_at)); ?></span>
          <span class="timeline-user"><i class="fas fa-user me-1"></i>Por: <?php echo htmlspecialchars($t->executor_nome ?? ''); ?></span>
        </div>
        <div class="timeline-resumo">
          <i class="fas fa-user me-1 text-muted"></i><?php echo htmlspecialchars($t->de_nome ?? 'Desconhecido'); ?>
          <span style="color:#d97706;font-weight:700;margin:0 .5rem">&rarr;</span>
          <i class="fas fa-user me-1 text-warning"></i><strong><?php echo htmlspecialchars($t->para_nome ?? 'Desconhecido'); ?></strong>
        </div>
        <?php if (!empty($t->observacao)): ?>
        <div class="mt-1" style="font-size:.8rem;color:#78350f;font-style:italic">
          <i class="fas fa-comment me-1"></i><?php echo htmlspecialchars($t->observacao); ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php else: ?>
<div class="text-center py-4 text-muted">
  <i class="fas fa-exchange-alt fa-2x mb-2 d-block"></i>
  Nenhuma transferência registrada para este lead.
</div>
<?php endif; ?>

<script>
function confirmarTransferenciaLead() {
  const paraUsuarioId = document.getElementById('transf-para-usuario').value;
  const motivo        = document.getElementById('transf-motivo').value;
  const observacao    = document.getElementById('transf-observacao').value;
  const alertEl       = document.getElementById('transf-alert');
  alertEl.className   = 'alert d-none mb-3';
  if (!paraUsuarioId) {
    alertEl.className = 'alert alert-danger mb-3';
    alertEl.textContent = 'Selecione o usuário de destino.';
    return;
  }
  if (!motivo) {
    alertEl.className = 'alert alert-danger mb-3';
    alertEl.textContent = 'Selecione o motivo da transferência.';
    return;
  }
  const btn = document.querySelector('#tab-transferencia .btn-warning');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Transferindo...'; }
  const formData = new FormData();
  formData.append('para_usuario_id', paraUsuarioId);
  formData.append('motivo',          motivo);
  formData.append('observacao',      observacao);
  formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content
    || '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>');
  const leadId = <?php echo (int)($lead->id ?? 0); ?>;
  fetch('/crm/leads/transferir/' + leadId, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        alertEl.className = 'alert alert-danger mb-3';
        alertEl.textContent = res.error || 'Erro ao transferir.';
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-exchange-alt me-1"></i> Confirmar Transferência'; }
        return;
      }
      alertEl.className = 'alert alert-success mb-3';
      alertEl.textContent = 'Lead transferido com sucesso para ' + res.para_nome + '.';
      if (btn) btn.disabled = true;
      setTimeout(() => { window.location.href = '/crm/leads'; }, 2000);
    })
    .catch(() => {
      alertEl.className = 'alert alert-danger mb-3';
      alertEl.textContent = 'Erro de conexão. Tente novamente.';
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-exchange-alt me-1"></i> Confirmar Transferência'; }
    });
}
</script>
