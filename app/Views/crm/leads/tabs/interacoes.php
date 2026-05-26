<?php
/**
 * ERP InLaudo - Aba Interações do Lead
 */
$tiposIcones = [
    'email'              => 'fa-envelope text-primary',
    'telefone'           => 'fa-phone text-success',
    'whatsapp'           => 'fa-whatsapp text-success',
    'reuniao_presencial' => 'fa-handshake text-warning',
    'reuniao_online'     => 'fa-video text-info',
    'visita_tecnica'     => 'fa-map-marker-alt text-danger',
    'proposta_enviada'   => 'fa-file-alt text-secondary',
    'contrato_enviado'   => 'fa-file-signature text-dark',
    'outro'              => 'fa-comment text-muted',
];
?>
<style>
.timeline{position:relative;padding-left:2rem}
.timeline::before{content:'';position:absolute;left:.75rem;top:0;bottom:0;width:2px;background:#e2e8f0}
.timeline-item{position:relative;margin-bottom:1.25rem}
.timeline-dot{position:absolute;left:-1.5rem;top:.25rem;width:1.25rem;height:1.25rem;border-radius:50%;background:#fff;border:2px solid #e2e8f0;display:flex;align-items:center;justify-content:center;font-size:.6rem}
.timeline-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.5rem;padding:.875rem 1rem}
.timeline-meta{display:flex;align-items:center;gap:.75rem;margin-bottom:.4rem;flex-wrap:wrap}
.timeline-tipo{font-size:.75rem;font-weight:600;color:#00529B;background:#ebf4ff;padding:.2em .6em;border-radius:10px}
.timeline-data{font-size:.75rem;color:#94a3b8}
.timeline-user{font-size:.75rem;color:#64748b}
.timeline-resumo{font-size:.875rem;color:#374151;line-height:1.5}
.timeline-del{float:right;font-size:.7rem;color:#dc2626;cursor:pointer;background:none;border:none;padding:0}
.int-form-card{background:#f0f9ff;border:1px solid #bae6fd;border-radius:.5rem;padding:1.25rem;margin-bottom:1.5rem}
.int-form-card h3{font-size:.9rem;font-weight:600;color:#0284c7;margin-bottom:1rem}
</style>

<!-- Formulário nova interação -->
<div class="int-form-card">
  <h3><i class="fas fa-plus-circle me-1"></i> Registrar Nova Interação</h3>
  <div class="form-grid form-grid-3">
    <div class="form-group">
      <label class="form-label required">Data e Hora</label>
      <input type="datetime-local" id="int_data" class="form-control"
             value="<?php echo date('Y-m-d\TH:i'); ?>">
    </div>
    <div class="form-group">
      <label class="form-label required">Tipo de Interação</label>
      <select id="int_tipo" class="form-select">
        <?php foreach ($tiposInteracao as $k => $v): ?>
        <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group d-flex align-items-end">
      <button type="button" class="btn btn-primary w-100" onclick="salvarInteracao()">
        <i class="fas fa-save me-1"></i> Salvar Interação
      </button>
    </div>
  </div>
  <div class="form-group mt-2">
    <label class="form-label required">Resumo da Interação</label>
    <textarea id="int_resumo" class="form-control" rows="3"
              placeholder="Descreva o que foi discutido, próximos passos, resultado..."></textarea>
  </div>
</div>

<!-- Timeline -->
<?php if (empty($interacoes)): ?>
<div class="text-center py-4 text-muted">
  <i class="fas fa-comments fa-2x mb-2 d-block"></i>
  Nenhuma interação registrada ainda. Registre o primeiro contato acima.
</div>
<?php else: ?>
<div class="timeline" id="timeline-container">
  <?php foreach ($interacoes as $int): ?>
  <?php $icone = $tiposIcones[$int->tipo_interacao] ?? 'fa-comment text-muted'; ?>
  <div class="timeline-item" id="int-<?php echo $int->id; ?>">
    <div class="timeline-dot"><i class="fas <?php echo explode(' ', $icone)[0]; ?>" style="font-size:.55rem"></i></div>
    <div class="timeline-card">
      <div class="timeline-meta">
        <span class="timeline-tipo"><?php echo htmlspecialchars($tiposInteracao[$int->tipo_interacao] ?? $int->tipo_interacao); ?></span>
        <span class="timeline-data"><i class="fas fa-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($int->data_interacao)); ?></span>
        <span class="timeline-user"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($int->usuario_nome ?? 'Sistema'); ?></span>
        <button class="timeline-del ms-auto" onclick="deletarInteracao(<?php echo $int->id; ?>)" title="Excluir">
          <i class="fas fa-trash"></i> Excluir
        </button>
      </div>
      <div class="timeline-resumo"><?php echo nl2br(htmlspecialchars($int->resumo)); ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
// Token CSRF disponível globalmente para esta aba
var _csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
    || '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>';

function salvarInteracao() {
  const data    = document.getElementById('int_data').value;
  const tipo    = document.getElementById('int_tipo').value;
  const resumo  = document.getElementById('int_resumo').value.trim();
  if (!resumo) { alert('O resumo da interação é obrigatório.'); return; }
  const form = new FormData();
  form.append('related_id',     '<?php echo (int)($lead->id ?? 0); ?>');
  form.append('data_interacao', data.replace('T', ' '));
  form.append('tipo_interacao', tipo);
  form.append('resumo',         resumo);
  form.append('_token',         _csrfToken);
  fetch('/crm/leads/interacao/add', { method: 'POST', body: form })
    .then(r => r.json())
    .then(res => {
      if (!res.success) { alert(res.error || 'Erro ao salvar.'); return; }
      const url = new URL(window.location.href);
      url.searchParams.set('tab', 'interacoes');
      url.searchParams.delete('success');
      url.searchParams.delete('error');
      window.location.href = url.toString();
    })
    .catch(() => alert('Erro de conexão.'));
}

function deletarInteracao(id) {
  if (!confirm('Excluir esta interação?')) return;
  const form = new FormData();
  form.append('_token', _csrfToken);
  fetch('/crm/leads/interacao/delete/' + id, { method: 'POST', body: form })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        const el = document.getElementById('int-' + id);
        if (el) el.remove();
      }
    });
}
</script>
