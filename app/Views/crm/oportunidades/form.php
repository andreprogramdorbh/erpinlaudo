<?php
use App\Core\View;
$action    = $isEdit ? '/crm/oportunidades/update/' . $op->id : '/crm/oportunidades';
$activeTab = $_GET['tab'] ?? 'dados';

$tiposIcones = [
    'email'              => 'fa-envelope',
    'telefone'           => 'fa-phone',
    'whatsapp'           => 'fa-whatsapp',
    'reuniao_presencial' => 'fa-handshake',
    'reuniao_online'     => 'fa-video',
    'visita_tecnica'     => 'fa-map-marker-alt',
    'proposta_enviada'   => 'fa-file-alt',
    'contrato_enviado'   => 'fa-file-signature',
    'outro'              => 'fa-comment',
];
?>
<link rel="stylesheet" href="/assets/css/form-layout.css">
<style>
.crm-form-wrap{width:100%}
.crm-header{background:linear-gradient(135deg,#059669 0%,#047857 100%);color:#fff;padding:1.75rem 2rem;border-radius:.75rem .75rem 0 0}
.crm-header h1{font-size:1.5rem;font-weight:700;margin:0 0 .25rem}
.crm-header p{margin:0;opacity:.85;font-size:.9rem}
.crm-tabs{background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;padding:0 2rem}
.crm-tab{padding:.875rem 1.25rem;cursor:pointer;font-size:.875rem;font-weight:500;color:#64748b;border-bottom:2px solid transparent;display:flex;align-items:center;gap:.5rem;transition:all .2s}
.crm-tab:hover{color:#059669}
.crm-tab.active{color:#059669;border-bottom-color:#059669;font-weight:600}
.crm-tab-locked{opacity:.45;cursor:not-allowed}
.crm-body{background:#fff;padding:2rem;border:1px solid #e2e8f0;border-top:none}
.crm-footer{background:#f8fafc;border:1px solid #e2e8f0;border-top:none;padding:1.25rem 2rem;display:flex;justify-content:space-between;align-items:center;border-radius:0 0 .75rem .75rem}
.form-section{margin-bottom:2rem}
.form-section-title{font-size:.9375rem;font-weight:600;color:#1e293b;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.5rem}
.form-grid{display:grid;gap:1rem}
.form-grid-2{grid-template-columns:repeat(2,1fr)}
.form-grid-3{grid-template-columns:repeat(3,1fr)}
@media(max-width:768px){.form-grid-2,.form-grid-3{grid-template-columns:1fr}}
.form-label.required::after{content:" *";color:#ef4444}
.prob-slider{-webkit-appearance:none;width:100%;height:6px;border-radius:3px;background:#e2e8f0;outline:none}
.prob-slider::-webkit-slider-thumb{-webkit-appearance:none;width:18px;height:18px;border-radius:50%;background:#059669;cursor:pointer}
.timeline{position:relative;padding-left:2rem}
.timeline::before{content:'';position:absolute;left:.75rem;top:0;bottom:0;width:2px;background:#e2e8f0}
.timeline-item{position:relative;margin-bottom:1.25rem}
.timeline-dot{position:absolute;left:-1.5rem;top:.25rem;width:1.25rem;height:1.25rem;border-radius:50%;background:#fff;border:2px solid #e2e8f0;display:flex;align-items:center;justify-content:center;font-size:.6rem}
.timeline-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.5rem;padding:.875rem 1rem}
.timeline-meta{display:flex;align-items:center;gap:.75rem;margin-bottom:.4rem;flex-wrap:wrap}
.timeline-tipo{font-size:.75rem;font-weight:600;color:#059669;background:#d1fae5;padding:.2em .6em;border-radius:10px}
.timeline-tipo.lead-origin{color:#0284c7;background:#e0f2fe}
.timeline-data{font-size:.75rem;color:#94a3b8}
.timeline-user{font-size:.75rem;color:#64748b}
.timeline-resumo{font-size:.875rem;color:#374151;line-height:1.5}
.timeline-del{float:right;font-size:.7rem;color:#dc2626;cursor:pointer;background:none;border:none;padding:0}
.int-form-card{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:.5rem;padding:1.25rem;margin-bottom:1.5rem}
.int-form-card h3{font-size:.9rem;font-weight:600;color:#059669;margin-bottom:1rem}
.lead-origin-badge{background:#e0f2fe;color:#0284c7;font-size:.65rem;padding:.2em .6em;border-radius:10px;font-weight:600}
</style>

<div class="crm-form-wrap">

  <!-- Header -->
  <div class="crm-header">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h1><i class="fas fa-chart-line me-2"></i><?php echo $isEdit ? 'Editar Oportunidade' : 'Nova Oportunidade'; ?></h1>
        <p><?php echo $isEdit ? htmlspecialchars($op->titulo_oportunidade) : 'Registre uma nova oportunidade de negócio'; ?></p>
      </div>
      <a href="/crm/oportunidades" class="btn btn-sm btn-light"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
    </div>
  </div>

  <!-- Abas -->
  <div class="crm-tabs">
    <div class="crm-tab <?php echo $activeTab === 'dados' ? 'active' : ''; ?>" onclick="switchTab('dados')">
      <i class="fas fa-info-circle"></i> Detalhes
    </div>
    <div class="crm-tab <?php echo (!$isEdit ? 'crm-tab-locked' : ($activeTab === 'interacoes' ? 'active' : '')); ?>"
         onclick="<?php echo $isEdit ? "switchTab('interacoes')" : 'void(0)'; ?>">
      <i class="fas fa-comments"></i> Interações
      <?php if ($isEdit && !empty($interacoes)): ?>
      <span class="badge bg-success rounded-pill ms-1" style="font-size:.65rem"><?php echo count($interacoes); ?></span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Aba: Dados -->
  <div class="crm-body" id="tab-dados" style="display:<?php echo $activeTab === 'dados' ? 'block' : 'none'; ?>">

    <?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-3">
      <i class="fas fa-check-circle me-2"></i>
      <?php echo $_GET['success'] === 'convertido' ? 'Lead convertido em oportunidade com sucesso!' : ($_GET['success'] === 'criado' ? 'Oportunidade criada!' : 'Oportunidade atualizada!'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form id="opForm" action="<?php echo $action; ?>" method="POST">
      <?php echo View::csrfField(); ?>

      <!-- Seção: Dados Principais -->
      <section class="form-section">
        <h2 class="form-section-title"><i class="fas fa-chart-line text-success"></i> Dados da Oportunidade</h2>
        <div class="form-group mb-3">
          <label class="form-label required">Título da Oportunidade</label>
          <input type="text" name="titulo_oportunidade" class="form-control" required
                 placeholder="Ex: Contrato Laudos TC — Hospital São Lucas"
                 value="<?php echo htmlspecialchars($op->titulo_oportunidade ?? ''); ?>">
        </div>
        <div class="form-grid form-grid-3">
          <div class="form-group">
            <label class="form-label required">Etapa do Funil</label>
            <select name="etapa_funil" class="form-select">
              <?php foreach ($etapas as $k => $v): ?>
              <option value="<?php echo $k; ?>" <?php echo ($op->etapa_funil ?? 'qualificacao') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label required">Status</label>
            <select name="status_oportunidade" id="status_op" class="form-select" onchange="toggleMotivoPerdas()">
              <?php foreach ($statusList as $k => $v): ?>
              <option value="<?php echo $k; ?>" <?php echo ($op->status_oportunidade ?? 'aberta') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" id="campo-motivo-perda" style="display:<?php echo ($op->status_oportunidade ?? '') === 'perdida' ? '' : 'none'; ?>">
            <label class="form-label">Motivo da Perda</label>
            <input type="text" name="motivo_perda" class="form-control"
                   placeholder="Ex: Preço, Concorrente, Sem orçamento"
                   value="<?php echo htmlspecialchars($op->motivo_perda ?? ''); ?>">
          </div>
        </div>
      </section>

      <!-- Seção: Valores -->
      <section class="form-section">
        <h2 class="form-section-title"><i class="fas fa-dollar-sign text-success"></i> Valores e Datas</h2>
        <div class="form-grid form-grid-3">
          <div class="form-group">
            <label class="form-label">Valor Estimado (R$)</label>
            <input type="number" name="valor_estimado" class="form-control" step="0.01" min="0"
                   placeholder="0,00"
                   value="<?php echo htmlspecialchars($op->valor_estimado ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Data de Fechamento Prevista</label>
            <input type="date" name="data_fechamento_prevista" class="form-control"
                   value="<?php echo htmlspecialchars($op->data_fechamento_prevista ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Probabilidade de Sucesso: <strong id="prob-val"><?php echo $op->probabilidade_sucesso ?? 20; ?>%</strong></label>
            <input type="range" name="probabilidade_sucesso" class="prob-slider" min="0" max="100" step="5"
                   value="<?php echo $op->probabilidade_sucesso ?? 20; ?>"
                   oninput="document.getElementById('prob-val').textContent = this.value + '%'">
          </div>
        </div>
      </section>

      <!-- Seção: Radiologia -->
      <section class="form-section">
        <h2 class="form-section-title"><i class="fas fa-x-ray text-success"></i> Detalhes — Radiologia</h2>
        <div class="form-grid form-grid-3">
          <div class="form-group">
            <label class="form-label">Modalidade Principal</label>
            <select name="modalidade_principal" class="form-select">
              <option value="">Selecione...</option>
              <?php foreach ($modalidades as $k => $v): ?>
              <option value="<?php echo $k; ?>" <?php echo ($op->modalidade_principal ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Tipo de Contrato</label>
            <select name="tipo_contrato" class="form-select">
              <option value="">Selecione...</option>
              <?php foreach ($tiposContrato as $k => $v): ?>
              <option value="<?php echo $k; ?>" <?php echo ($op->tipo_contrato ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Volume Estimado/Mês (exames)</label>
            <input type="number" name="volume_estimado_mes" class="form-control" min="0"
                   value="<?php echo htmlspecialchars($op->volume_estimado_mes ?? ''); ?>">
          </div>
        </div>
      </section>

      <!-- Seção: Lead Vinculado -->
      <section class="form-section">
        <h2 class="form-section-title"><i class="fas fa-link text-success"></i> Lead / Cliente Vinculado</h2>
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label class="form-label">Lead de Origem</label>
            <select name="lead_id" class="form-select">
              <option value="">Nenhum</option>
              <?php foreach ($leads as $l): ?>
              <option value="<?php echo $l->id; ?>" <?php echo ((int)($op->lead_id ?? 0) === (int)$l->id) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($l->nome_lead); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Observações</label>
            <textarea name="observacoes" class="form-control" rows="3"
                      placeholder="Contexto adicional da oportunidade..."><?php echo htmlspecialchars($op->observacoes ?? ''); ?></textarea>
          </div>
        </div>
      </section>

    </form>
  </div>

  <!-- Aba: Interações -->
  <?php if ($isEdit): ?>
  <div class="crm-body" id="tab-interacoes" style="display:<?php echo $activeTab === 'interacoes' ? 'block' : 'none'; ?>">

    <!-- Formulário nova interação -->
    <div class="int-form-card">
      <h3><i class="fas fa-plus-circle me-1"></i> Registrar Nova Interação</h3>
      <div class="form-grid form-grid-3">
        <div class="form-group">
          <label class="form-label required">Data e Hora</label>
          <input type="datetime-local" id="int_data" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
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
          <button type="button" class="btn btn-success w-100" onclick="salvarInteracao()">
            <i class="fas fa-save me-1"></i> Salvar Interação
          </button>
        </div>
      </div>
      <div class="form-group mt-2">
        <label class="form-label required">Resumo</label>
        <textarea id="int_resumo" class="form-control" rows="3"
                  placeholder="Descreva o que foi discutido, resultado, próximos passos..."></textarea>
      </div>
    </div>

    <!-- Interações desta oportunidade -->
    <?php if (!empty($interacoes)): ?>
    <h6 class="text-muted mb-2" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.05em">
      <i class="fas fa-chart-line me-1"></i> Interações desta Oportunidade
    </h6>
    <div class="timeline mb-4" id="timeline-container">
      <?php foreach ($interacoes as $int): ?>
      <div class="timeline-item" id="int-<?php echo $int->id; ?>">
        <div class="timeline-dot"><i class="fas <?php echo $tiposIcones[$int->tipo_interacao] ?? 'fa-comment'; ?>" style="font-size:.55rem"></i></div>
        <div class="timeline-card">
          <div class="timeline-meta">
            <span class="timeline-tipo"><?php echo htmlspecialchars($tiposInteracao[$int->tipo_interacao] ?? $int->tipo_interacao); ?></span>
            <span class="timeline-data"><i class="fas fa-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($int->data_interacao)); ?></span>
            <span class="timeline-user"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($int->usuario_nome ?? 'Sistema'); ?></span>
            <button class="timeline-del ms-auto" onclick="deletarInteracao(<?php echo $int->id; ?>)"><i class="fas fa-trash"></i> Excluir</button>
          </div>
          <div class="timeline-resumo"><?php echo nl2br(htmlspecialchars($int->resumo)); ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Interações herdadas do Lead -->
    <?php if (!empty($interacoesLead)): ?>
    <h6 class="text-muted mb-2" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.05em">
      <i class="fas fa-user me-1"></i> Histórico do Lead de Origem
    </h6>
    <div class="timeline">
      <?php foreach ($interacoesLead as $int): ?>
      <div class="timeline-item">
        <div class="timeline-dot"><i class="fas <?php echo $tiposIcones[$int->tipo_interacao] ?? 'fa-comment'; ?>" style="font-size:.55rem;color:#0284c7"></i></div>
        <div class="timeline-card" style="border-color:#bae6fd;background:#f0f9ff">
          <div class="timeline-meta">
            <span class="timeline-tipo lead-origin"><?php echo htmlspecialchars($tiposInteracao[$int->tipo_interacao] ?? $int->tipo_interacao); ?></span>
            <span class="lead-origin-badge">Lead</span>
            <span class="timeline-data"><i class="fas fa-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($int->data_interacao)); ?></span>
            <span class="timeline-user"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($int->usuario_nome ?? 'Sistema'); ?></span>
          </div>
          <div class="timeline-resumo"><?php echo nl2br(htmlspecialchars($int->resumo)); ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($interacoes) && empty($interacoesLead)): ?>
    <div class="text-center py-4 text-muted">
      <i class="fas fa-comments fa-2x mb-2 d-block"></i>
      Nenhuma interação registrada ainda.
    </div>
    <?php endif; ?>

  </div>
  <?php endif; ?>

  <!-- Footer -->
  <div class="crm-footer">
    <div></div>
    <div class="d-flex gap-2">
      <a href="/crm/oportunidades" class="btn btn-light">Cancelar</a>
      <button type="submit" form="opForm" class="btn btn-success">
        <i class="fas fa-save me-1"></i> <?php echo $isEdit ? 'Salvar Alterações' : 'Criar Oportunidade'; ?>
      </button>
    </div>
  </div>

</div>

<script>
function switchTab(tab) {
  document.querySelectorAll('[id^="tab-"]').forEach(el => el.style.display = 'none');
  document.getElementById('tab-' + tab).style.display = 'block';
  document.querySelectorAll('.crm-tab').forEach(el => el.classList.remove('active'));
  event.currentTarget.classList.add('active');
  // Atualiza a URL para preservar a aba ativa em reloads
  const url = new URL(window.location.href);
  url.searchParams.set('tab', tab);
  history.replaceState(null, '', url.toString());
}

function toggleMotivoPerdas() {
  const status = document.getElementById('status_op').value;
  document.getElementById('campo-motivo-perda').style.display = status === 'perdida' ? '' : 'none';
}

function salvarInteracao() {
  const data   = document.getElementById('int_data').value;
  const tipo   = document.getElementById('int_tipo').value;
  const resumo = document.getElementById('int_resumo').value.trim();
  if (!resumo) { alert('O resumo é obrigatório.'); return; }

  const form = new FormData();
  form.append('related_id',     '<?php echo $op->id ?? 0; ?>');
  form.append('data_interacao', data.replace('T', ' '));
  form.append('tipo_interacao', tipo);
  form.append('resumo',         resumo);
  form.append('_token',         document.querySelector('input[name="_token"]')?.value || '');

  fetch('/crm/oportunidades/interacao/add', { method: 'POST', body: form })
    .then(r => r.json())
    .then(res => {
      if (!res.success) { alert(res.error || 'Erro ao salvar.'); return; }
      // Redireciona preservando a aba de interações
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
  form.append('_token', document.querySelector('input[name="_token"]')?.value || '');
  fetch('/crm/oportunidades/interacao/delete/' + id, { method: 'POST', body: form })
    .then(r => r.json())
    .then(res => { if (res.success) { const el = document.getElementById('int-' + id); if (el) el.remove(); } });
}
</script>

<?php require_once dirname(__DIR__, 2) . '/layout/erp_footer.php'; ?>
