<?php
use App\Core\View;
$action            = $isEdit ? '/crm/oportunidades/update/' . $op->id : '/crm/oportunidades';
$activeTab         = $_GET['tab'] ?? 'dados';
$modalidadesAtivas = $modalidadesAtivas ?? json_decode($op->modalidades_interesse ?? '[]', true) ?: [];

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
/* Anexos */
.anx-form-card{background:#fffbeb;border:1px solid #fde68a;border-radius:.5rem;padding:1.25rem;margin-bottom:1.5rem}
.anx-form-card h3{font-size:.9rem;font-weight:600;color:#b45309;margin-bottom:1rem}
.anx-table{width:100%;border-collapse:collapse;font-size:.875rem}
.anx-table th{background:#f8fafc;font-size:.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.03em;padding:.6rem .75rem;border-bottom:2px solid #e2e8f0;text-align:left}
.anx-table td{padding:.7rem .75rem;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.anx-table tr:last-child td{border-bottom:none}
.anx-table tr:hover td{background:#fffbeb}
.anx-tipo-badge{font-size:.7rem;font-weight:600;padding:.2em .6em;border-radius:10px;white-space:nowrap}
.anx-tipo-contrato{background:#dbeafe;color:#1d4ed8}
.anx-tipo-termo_aceite{background:#dcfce7;color:#15803d}
.anx-tipo-proposta_comercial{background:#fef9c3;color:#854d0e}
.anx-tipo-edital{background:#fee2e2;color:#b91c1c}
.anx-tipo-outro{background:#f1f5f9;color:#475569}
.anx-size{font-size:.75rem;color:#94a3b8}
.lead-origin-badge{background:#e0f2fe;color:#0284c7;font-size:.65rem;padding:.2em .6em;border-radius:10px;font-weight:600}
.mod-grid{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.5rem}
.mod-chip{display:flex;align-items:center;gap:.4rem;padding:.35rem .75rem;border:1px solid #e2e8f0;border-radius:20px;font-size:.8125rem;cursor:pointer;transition:all .2s;user-select:none}
.mod-chip:hover{border-color:#059669;background:#f0fdf4}
.mod-chip input{display:none}
.mod-chip.checked{background:#059669;color:#fff;border-color:#059669}
.mod-table-header{display:grid;grid-template-columns:1fr 1fr 120px 1fr 40px;gap:.5rem;padding:.5rem .75rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:.5rem .5rem 0 0;font-size:.75rem;font-weight:600;color:#64748b;margin-top:.75rem}
.mod-linha{border:1px solid #e2e8f0;border-top:none;background:#fff;transition:background .15s}
.mod-linha:hover{background:#f0fdf4}
.mod-linha:last-child{border-radius:0 0 .5rem .5rem}
.mod-linha-inner{display:grid;grid-template-columns:1fr 1fr 120px 1fr 40px;gap:.5rem;padding:.5rem .75rem;align-items:center}
.mod-linha-inner .form-select,.mod-linha-inner .form-control{font-size:.8125rem}
/* Seção Transferência */
.transf-section{background:#fffbeb;border:1px solid #fde68a;border-radius:.625rem;padding:1.5rem;margin-top:1.5rem}
.transf-section-title{font-size:.9375rem;font-weight:600;color:#92400e;margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem}
.transf-responsavel-atual{background:#fff;border:1px solid #fde68a;border-radius:.5rem;padding:.75rem 1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.75rem}
.transf-responsavel-atual .label{font-size:.75rem;color:#92400e;font-weight:600;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:.15rem}
.transf-historico{margin-top:1.25rem;border-top:1px solid #fde68a;padding-top:1rem}
.transf-historico-title{font-size:.8rem;font-weight:600;color:#92400e;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.75rem}
.transf-item{background:#fff;border:1px solid #fde68a;border-radius:.5rem;padding:.75rem 1rem;margin-bottom:.5rem;font-size:.8125rem}
.transf-item-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:.3rem}
.transf-arrow{color:#d97706;font-weight:700}
.transf-meta{font-size:.75rem;color:#92400e;opacity:.8}
.transf-obs{font-size:.75rem;color:#78350f;font-style:italic;margin-top:.25rem}
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
    <div class="crm-tab <?php echo (!$isEdit ? 'crm-tab-locked' : ($activeTab === 'anexos' ? 'active' : '')); ?>"
         onclick="<?php echo $isEdit ? "switchTab('anexos')" : 'void(0)'; ?>">
      <i class="fas fa-paperclip"></i> Anexos
      <?php if ($isEdit && !empty($anexos)): ?>
      <span class="badge bg-warning rounded-pill ms-1" style="font-size:.65rem"><?php echo count($anexos); ?></span>
      <?php endif; ?>
    </div>
    <?php if ($isEdit): ?>
    <div class="crm-tab <?php echo $activeTab === 'transferencia' ? 'active' : ''; ?>" onclick="switchTab('transferencia')">
      <i class="fas fa-exchange-alt"></i> Transferência
      <?php if (!empty($transferencias)): ?>
      <span class="badge bg-warning rounded-pill ms-1" style="font-size:.65rem"><?php echo count($transferencias); ?></span>
      <?php endif; ?>
    </div>
    <?php endif; ?>
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
        <h2 class="form-section-title d-flex align-items-center justify-content-between">
          <span><i class="fas fa-x-ray text-success"></i> Detalhes — Radiologia / Tecnologia</span>
          <button type="button" class="btn btn-sm btn-success" onclick="addLinhaModalidade()" title="Adicionar modalidade">
            <i class="fas fa-plus me-1"></i> Adicionar Modalidade
          </button>
        </h2>

        <!-- Cabeçalho da tabela dinâmica -->
        <div class="mod-table-header">
          <span>Modalidade</span>
          <span>Tipo de Contrato / Comercialização</span>
          <span>Volume Est./Mês</span>
          <span>Observação</span>
          <span></span>
        </div>

        <!-- Container das linhas dinâmicas -->
        <div id="mod-linhas-container">
          <?php
          $linhasExistentes = $linhasModalidades ?? [];
          if (empty($linhasExistentes)):
            // Linha inicial vazia
          ?>
          <div class="mod-linha" data-index="0">
            <div class="mod-linha-inner">
              <select name="mod_modalidade[]" class="form-select form-select-sm">
                <option value="">Selecione...</option>
                <?php foreach ($modalidades as $k => $v): ?>
                <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                <?php endforeach; ?>
              </select>
              <select name="mod_tipo_contrato[]" class="form-select form-select-sm">
                <option value="">Selecione...</option>
                <?php foreach ($tiposContrato as $k => $v): ?>
                <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                <?php endforeach; ?>
                <option value="comercializacao_software">Comercialização de Software</option>
              </select>
              <input type="number" name="mod_volume[]" class="form-control form-control-sm" min="0" placeholder="Ex: 500">
              <input type="text" name="mod_observacao[]" class="form-control form-control-sm" placeholder="Obs. opcional">
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerLinha(this)" title="Remover">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
          <?php else: ?>
          <?php foreach ($linhasExistentes as $i => $linha): ?>
          <div class="mod-linha" data-index="<?php echo $i; ?>">
            <div class="mod-linha-inner">
              <select name="mod_modalidade[]" class="form-select form-select-sm">
                <option value="">Selecione...</option>
                <?php foreach ($modalidades as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo ($linha->modalidade ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                <?php endforeach; ?>
              </select>
              <select name="mod_tipo_contrato[]" class="form-select form-select-sm">
                <option value="">Selecione...</option>
                <?php foreach ($tiposContrato as $k => $v): ?>
                <option value="<?php echo $k; ?>" <?php echo ($linha->tipo_contrato ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                <?php endforeach; ?>
                <option value="comercializacao_software" <?php echo ($linha->tipo_contrato ?? '') === 'comercializacao_software' ? 'selected' : ''; ?>>Comercialização de Software</option>
              </select>
              <input type="number" name="mod_volume[]" class="form-control form-control-sm" min="0"
                     value="<?php echo htmlspecialchars($linha->volume_estimado_mes ?? ''); ?>" placeholder="Ex: 500">
              <input type="text" name="mod_observacao[]" class="form-control form-control-sm"
                     value="<?php echo htmlspecialchars($linha->observacao ?? ''); ?>" placeholder="Obs. opcional">
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerLinha(this)" title="Remover">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Chips de interesse (visão rápida) -->
        <div class="form-group mt-3">
          <label class="form-label text-muted" style="font-size:.8rem">Interesse geral (visão rápida)</label>
          <div class="mod-grid">
            <?php foreach ($modalidades as $k => $v): ?>
            <?php $checked = in_array($k, $modalidadesAtivas); ?>
            <label class="mod-chip <?php echo $checked ? 'checked' : ''; ?>">
              <input type="checkbox" name="modalidades_interesse[]" value="<?php echo htmlspecialchars($k); ?>"
                     <?php echo $checked ? 'checked' : ''; ?> onchange="toggleModChip(this)">
              <i class="fas fa-x-ray" style="font-size:.7rem"></i>
              <?php echo htmlspecialchars($v); ?>
            </label>
            <?php endforeach; ?>
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
            <?php if ($isEdit && $op->cliente_id): ?>
            <small class="text-success mt-1 d-block"><i class="fas fa-check-circle me-1"></i> Cliente vinculado automaticamente (ID <?php echo (int)$op->cliente_id; ?>)</small>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label">Data do Próximo Contato</label>
            <input type="date" name="data_proximo_contato" id="data_proximo_contato" class="form-control"
                   value="<?php echo htmlspecialchars($op->data_proximo_contato ?? ''); ?>">
            <small class="text-muted" style="font-size:.7rem">
              <i class="fas fa-sync-alt me-1"></i> Atualizado automaticamente ao registrar interação com Retorno
            </small>
          </div>
        </div>
        <div class="form-group mt-3">
          <label class="form-label">Observações</label>
          <textarea name="observacoes" class="form-control" rows="3"
                    placeholder="Contexto adicional da oportunidade..."><?php echo htmlspecialchars($op->observacoes ?? ''); ?></textarea>
        </div>
      </section>

    </form>

  </div>

  <!-- Aba: Interações -->
  <?php if ($isEdit): ?>
  <div class="crm-body" id="tab-interacoes" style="display:<?php echo $activeTab === 'interacoes' ? 'block' : 'none'; ?>">

    <!-- Formulário nova interação -->
    <?php $statusAtual = $op->status_oportunidade ?? 'aberta'; ?>
    <?php $retornoObrigatorio = !in_array($statusAtual, ['perdida', 'ganha']); ?>
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
        <div class="form-group">
          <label class="form-label<?php echo $retornoObrigatorio ? ' required' : ''; ?>" id="label_retorno">
            <i class="fas fa-calendar-check me-1 text-warning"></i> Retorno
            <small class="text-muted fw-normal">(<?php echo $retornoObrigatorio ? 'obrigatório' : 'opcional'; ?>)</small>
          </label>
          <input type="date" id="int_retorno" class="form-control"
                 min="<?php echo date('Y-m-d'); ?>"
                 title="Data do próximo retorno/contato">
          <small class="text-muted" style="font-size:.7rem">
            <i class="fas fa-sync-alt me-1"></i>
            Atualiza automaticamente &quot;Data do Próximo Contato&quot;
          </small>
        </div>
      </div>
      <div class="form-group mt-2">
        <label class="form-label required">Resumo</label>
        <textarea id="int_resumo" class="form-control" rows="3"
                  placeholder="Descreva o que foi discutido, resultado, próximos passos..."></textarea>
      </div>
      <div class="mt-2 d-flex justify-content-end">
        <button type="button" class="btn btn-success" onclick="salvarInteracao()">
          <i class="fas fa-save me-1"></i> Salvar Interação
        </button>
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
          <?php if (!empty($int->data_retorno)): ?>
          <?php
            $hoje = date('Y-m-d');
            $semana = date('Y-m-d', strtotime('+7 days'));
            $dr = $int->data_retorno;
            if ($dr < $hoje) {
                $retCor = '#dc2626'; $retBg = '#fee2e2'; $retIcon = 'fa-exclamation-circle'; $retLabel = 'Atrasado';
            } elseif ($dr === $hoje) {
                $retCor = '#dc2626'; $retBg = '#fee2e2'; $retIcon = 'fa-bell'; $retLabel = 'Hoje';
            } elseif ($dr <= $semana) {
                $retCor = '#d97706'; $retBg = '#fef3c7'; $retIcon = 'fa-clock'; $retLabel = 'Esta semana';
            } else {
                $retCor = '#059669'; $retBg = '#d1fae5'; $retIcon = 'fa-calendar-check'; $retLabel = 'Programado';
            }
          ?>
          <div class="mt-2" style="display:inline-flex;align-items:center;gap:.4rem;background:<?php echo $retBg; ?>;color:<?php echo $retCor; ?>;font-size:.75rem;font-weight:600;padding:.25rem .6rem;border-radius:.375rem">
            <i class="fas <?php echo $retIcon; ?>"></i>
            Retorno: <?php echo date('d/m/Y', strtotime($dr)); ?>
            <span style="font-weight:400;opacity:.8">(<?php echo $retLabel; ?>)</span>
          </div>
          <?php endif; ?>
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

  <!-- Aba: Anexos -->
  <?php if ($isEdit): ?>
  <div class="crm-body" id="tab-anexos" style="display:<?php echo $activeTab === 'anexos' ? 'block' : 'none'; ?>">

    <!-- Alertas de feedback -->
    <?php if (!empty($_GET['success']) && $_GET['success'] === 'anexo_salvo'): ?>
    <div class="alert alert-success alert-dismissible fade show mb-3">
      <i class="fas fa-check-circle me-2"></i> Documento anexado com sucesso!
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
    <?php $erros = [
      'nome_obrigatorio'   => 'O nome do documento é obrigatório.',
      'upload_failed'      => 'Falha ao enviar o arquivo. Tente novamente.',
      'file_too_large'     => 'Arquivo muito grande. Limite: 10 MB.',
      'invalid_file_type'  => 'Tipo de arquivo não permitido. Use PDF, Word, Excel ou imagem.',
      'db_failure'         => 'Erro ao salvar no banco de dados.',
    ]; ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3">
      <i class="fas fa-exclamation-circle me-2"></i>
      <?php echo htmlspecialchars($erros[$_GET['error']] ?? 'Erro ao processar o anexo.'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Formulário de novo anexo -->
    <div class="anx-form-card">
      <h3><i class="fas fa-upload me-1"></i> Anexar Novo Documento</h3>
      <form method="POST" action="/crm/oportunidades/anexo/upload" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        <input type="hidden" name="related_id" value="<?php echo $op->id; ?>">
        <div class="form-grid form-grid-3">
          <div class="form-group">
            <label class="form-label required">Nome do Documento</label>
            <input type="text" name="nome_documento" class="form-control"
                   placeholder="Ex: Contrato Assinado v1" required maxlength="255">
          </div>
          <div class="form-group">
            <label class="form-label required">Tipo</label>
            <select name="tipo_documento" class="form-select" required>
              <?php foreach ($tiposAnexo as $k => $v): ?>
              <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label required">Arquivo</label>
            <input type="file" name="arquivo" class="form-control" required
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
            <div class="form-text">PDF, Word, Excel ou imagem. Máx: 10 MB.</div>
          </div>
        </div>
        <div class="d-flex justify-content-end mt-2">
          <button type="submit" class="btn btn-warning">
            <i class="fas fa-upload me-1"></i> Salvar Documento
          </button>
        </div>
      </form>
    </div>

    <!-- Lista de anexos -->
    <?php if (empty($anexos)): ?>
    <div class="text-center py-4 text-muted">
      <i class="fas fa-paperclip fa-2x mb-2 d-block"></i>
      Nenhum documento anexado ainda. Use o formulário acima para adicionar.
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="anx-table">
        <thead>
          <tr>
            <th>Documento</th>
            <th>Tipo</th>
            <th>Arquivo</th>
            <th>Tamanho</th>
            <th>Salvo em</th>
            <th>Por</th>
            <th style="width:80px">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($anexos as $anx): ?>
          <?php
            $tipoKey   = $anx->tipo_documento ?? 'outro';
            $tipoLabel = $tiposAnexo[$tipoKey] ?? 'Outro';
            $iconeAnx  = $iconesAnexo[$tipoKey] ?? 'fa-paperclip text-muted';
            $sizeHuman = '';
            if ($anx->file_size) {
              $sizeHuman = $anx->file_size >= 1048576
                ? round($anx->file_size / 1048576, 1) . ' MB'
                : round($anx->file_size / 1024, 0) . ' KB';
            }
          ?>
          <tr id="anx-<?php echo $anx->id; ?>">
            <td>
              <i class="fas <?php echo explode(' ', $iconeAnx)[0]; ?> me-2 <?php echo explode(' ', $iconeAnx)[1] ?? ''; ?>"></i>
              <strong><?php echo htmlspecialchars($anx->nome_documento); ?></strong>
            </td>
            <td>
              <span class="anx-tipo-badge anx-tipo-<?php echo htmlspecialchars($tipoKey); ?>">
                <?php echo htmlspecialchars($tipoLabel); ?>
              </span>
            </td>
            <td>
              <span class="text-muted" style="font-size:.8rem"><?php echo htmlspecialchars($anx->original_name); ?></span>
            </td>
            <td><span class="anx-size"><?php echo $sizeHuman ?: '—'; ?></span></td>
            <td style="white-space:nowrap;font-size:.8rem;color:#64748b">
              <i class="fas fa-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($anx->created_at)); ?>
            </td>
            <td style="font-size:.8rem;color:#64748b"><?php echo htmlspecialchars($anx->usuario_nome ?? 'Sistema'); ?></td>
            <td>
              <div class="d-flex gap-1">
                <a href="/crm/oportunidades/anexo/download/<?php echo $anx->id; ?>" class="btn btn-sm btn-outline-primary" title="Baixar">
                  <i class="fas fa-download"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-danger" title="Excluir"
                        onclick="deletarAnexo(<?php echo $anx->id; ?>)">
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
  <?php endif; ?>

  <!-- Aba: Transferência -->
  <?php if ($isEdit): ?>
  <div class="crm-body" id="tab-transferencia" style="display:<?php echo $activeTab === 'transferencia' ? 'block' : 'none'; ?>">

    <div id="transf-op-alert" class="alert d-none mb-3"></div>

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
      <h2 class="form-section-title"><i class="fas fa-exchange-alt text-warning"></i> Transferir Oportunidade</h2>
      <div class="form-grid form-grid-3">
        <div class="form-group">
          <label class="form-label required">Transferir para</label>
          <select class="form-select" id="transf-op-para-usuario">
            <option value="">Selecione o usuário...</option>
            <?php foreach ($todosUsuarios ?? [] as $u): ?>
            <?php if ((int)($u['id'] ?? 0) === (int)($op->usuario_id ?? 0)) continue; ?>
            <option value="<?php echo (int)($u['id'] ?? 0); ?>">
              <?php echo htmlspecialchars($u['name'] ?? ''); ?>
              <?php if (!empty($u['role'])): ?>(<?php echo htmlspecialchars($u['role']); ?>)<?php endif; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label required">Motivo da Transferência</label>
          <select class="form-select" id="transf-op-motivo">
            <option value="">Selecione o motivo...</option>
            <?php foreach ($motivosTransferencia ?? [] as $key => $label): ?>
            <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group d-flex align-items-end">
          <button type="button" class="btn btn-warning w-100" onclick="confirmarTransferenciaOp()">
            <i class="fas fa-exchange-alt me-1"></i> Confirmar Transferência
          </button>
        </div>
      </div>
      <div class="form-group mt-3">
        <label class="form-label">Observação <small class="text-muted">(opcional)</small></label>
        <textarea class="form-control" id="transf-op-observacao" rows="3"
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
      Nenhuma transferência registrada para esta oportunidade.
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
  // Esconde todas as abas
  document.querySelectorAll('[id^="tab-"]').forEach(el => el.style.display = 'none');
  const tabEl = document.getElementById('tab-' + tab);
  if (tabEl) tabEl.style.display = 'block';
  // Marca a aba ativa no nav
  document.querySelectorAll('.crm-tab').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.crm-tab').forEach(el => {
    const oc = el.getAttribute('onclick') || '';
    if (oc.includes("'" + tab + "'") || oc.includes('"' + tab + '"')) {
      el.classList.add('active');
    }
  });
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
  const data    = document.getElementById('int_data').value;
  const tipo    = document.getElementById('int_tipo').value;
  const resumo  = document.getElementById('int_resumo').value.trim();
  const retorno = document.getElementById('int_retorno').value;

  // Validação: resumo sempre obrigatório
  if (!resumo) { alert('O resumo é obrigatório.'); return; }

  // Validação: retorno obrigatório exceto para status perdida/ganha
  const retornoObrigatorio = <?php echo json_encode($retornoObrigatorio); ?>;
  if (retornoObrigatorio && !retorno) {
    alert('O campo Retorno é obrigatório. Informe a data do próximo contato.');
    document.getElementById('int_retorno').focus();
    return;
  }

  const form = new FormData();
  form.append('related_id',     '<?php echo $op->id ?? 0; ?>');
  form.append('data_interacao', data.replace('T', ' '));
  form.append('tipo_interacao', tipo);
  form.append('resumo',         resumo);
  form.append('data_retorno',   retorno);
  form.append('_token',         document.querySelector('input[name="_token"]')?.value || '');

  fetch('/crm/oportunidades/interacao/add', { method: 'POST', body: form })
    .then(r => r.json())
    .then(res => {
      if (!res.success) { alert(res.error || 'Erro ao salvar.'); return; }

      // Se preencheu retorno, sincroniza o campo Data do Próximo Contato
      // e salva via PATCH para não perder os outros dados do formulário
      if (retorno) {
        const campoProximo = document.getElementById('data_proximo_contato');
        if (campoProximo) { campoProximo.value = retorno; }
        // Persiste no banco via endpoint de atualização parcial
        const patchForm = new FormData();
        patchForm.append('data_proximo_contato', retorno);
        patchForm.append('_token', document.querySelector('input[name="_token"]')?.value || '');
        fetch('/crm/oportunidades/update-retorno/<?php echo $op->id ?? 0; ?>', {
          method: 'POST', body: patchForm
        }).catch(() => {/* não bloqueia o redirect */});
      }

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

// Deletar anexo
function deletarAnexo(id) {
  if (!confirm('Excluir este documento? Esta ação não pode ser desfeita.')) return;
  const form = new FormData();
  form.append('_token', document.querySelector('input[name="_token"]')?.value || '');
  fetch('/crm/oportunidades/anexo/delete/' + id, { method: 'POST', body: form })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        const el = document.getElementById('anx-' + id);
        if (el) el.remove();
      } else {
        alert(res.error || 'Erro ao excluir o documento.');
      }
    })
    .catch(() => alert('Erro de conexão.'));
}

function toggleModChip(checkbox) {
  const chip = checkbox.closest('.mod-chip');
  if (checkbox.checked) {
    chip.classList.add('checked');
  } else {
    chip.classList.remove('checked');
  }
}

// ---- Linhas dinâmicas de modalidades ----
const MOD_OPTIONS = <?php echo json_encode($modalidades); ?>;
const CONTRATO_OPTIONS = <?php echo json_encode(array_merge($tiposContrato, ['comercializacao_software' => 'Comercialização de Software'])); ?>;

let modIndex = document.querySelectorAll('.mod-linha').length;

function buildSelect(name, options, selected) {
  let html = `<select name="${name}" class="form-select form-select-sm"><option value="">Selecione...</option>`;
  for (const [k, v] of Object.entries(options)) {
    const sel = k === selected ? ' selected' : '';
    html += `<option value="${k}"${sel}>${v}</option>`;
  }
  html += '</select>';
  return html;
}

function addLinhaModalidade(data) {
  const container = document.getElementById('mod-linhas-container');
  const idx = modIndex++;
  const div = document.createElement('div');
  div.className = 'mod-linha';
  div.dataset.index = idx;
  div.innerHTML = `<div class="mod-linha-inner">
    ${buildSelect('mod_modalidade[]', MOD_OPTIONS, data?.modalidade || '')}
    ${buildSelect('mod_tipo_contrato[]', CONTRATO_OPTIONS, data?.tipo_contrato || '')}
    <input type="number" name="mod_volume[]" class="form-control form-control-sm" min="0" placeholder="Ex: 500" value="${data?.volume || ''}">
    <input type="text" name="mod_observacao[]" class="form-control form-control-sm" placeholder="Obs. opcional" value="${data?.obs || ''}">
    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerLinha(this)" title="Remover"><i class="fas fa-trash"></i></button>
  </div>`;
  container.appendChild(div);
  // Anima a entrada
  div.style.opacity = '0';
  requestAnimationFrame(() => { div.style.transition = 'opacity .2s'; div.style.opacity = '1'; });
}

function removerLinha(btn) {
  const linha = btn.closest('.mod-linha');
  const container = document.getElementById('mod-linhas-container');
  // Mantém pelo menos 1 linha
  if (container.querySelectorAll('.mod-linha').length <= 1) {
    // Limpa os valores em vez de remover
    linha.querySelectorAll('select').forEach(s => s.value = '');
    linha.querySelectorAll('input').forEach(i => i.value = '');
    return;
  }
  linha.style.transition = 'opacity .15s';
  linha.style.opacity = '0';
  setTimeout(() => linha.remove(), 150);
}

// ── Transferência de Oportunidade ──
function confirmarTransferenciaOp() {
  const paraUsuarioId = document.getElementById('transf-op-para-usuario').value;
  const motivo        = document.getElementById('transf-op-motivo').value;
  const observacao    = document.getElementById('transf-op-observacao').value;
  const alertEl       = document.getElementById('transf-op-alert');

  alertEl.className = 'alert d-none mb-3';

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

  // Usa o botão inline da seção (event.target ou querySelector)
  const btn = document.querySelector('.transf-section .btn-warning');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Transferindo...'; }

  const formData = new FormData();
  formData.append('para_usuario_id', paraUsuarioId);
  formData.append('motivo',          motivo);
  formData.append('observacao',      observacao);
  formData.append('_token',          document.querySelector('input[name="_token"]')?.value || '');

  const opId = <?php echo (int)($op->id ?? 0); ?>;

  fetch('/crm/oportunidades/transferir/' + opId, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        alertEl.className = 'alert alert-danger mb-3';
        alertEl.textContent = res.error || 'Erro ao transferir.';
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-exchange-alt me-1"></i> Confirmar Transferência'; }
        return;
      }
      alertEl.className = 'alert alert-success mb-3';
      alertEl.textContent = 'Oportunidade transferida com sucesso para ' + res.para_nome + ' (Motivo: ' + res.motivo + ').';
      if (btn) btn.disabled = true;
      setTimeout(() => { window.location.href = '/crm/oportunidades'; }, 2000);
    })
    .catch(() => {
      alertEl.className = 'alert alert-danger mb-3';
      alertEl.textContent = 'Erro de conexão. Tente novamente.';
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-exchange-alt me-1"></i> Confirmar Transferência'; }
    });
}
</script>

