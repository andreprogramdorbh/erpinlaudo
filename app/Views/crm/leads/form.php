<?php
use App\Core\View;
$action     = $isEdit ? '/crm/leads/update/' . $lead->id : '/crm/leads';
$activeTab  = $_GET['tab'] ?? 'dados';
$espAtivas  = json_decode($lead->especialidades_interesse ?? '[]', true) ?: [];
$prodAtivos = json_decode($lead->produtos_interesse ?? '[]', true) ?: [];

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
<link rel="stylesheet" href="/assets/css/form-layout.css">
<style>
.crm-form-wrap{width:100%}
.crm-header{background:linear-gradient(135deg,#00529B 0%,#003d75 100%);color:#fff;padding:1.75rem 2rem;border-radius:.75rem .75rem 0 0}
.crm-header h1{font-size:1.5rem;font-weight:700;margin:0 0 .25rem}
.crm-header p{margin:0;opacity:.85;font-size:.9rem}
.crm-tabs{background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;padding:0 2rem}
.crm-tab{padding:.875rem 1.25rem;cursor:pointer;font-size:.875rem;font-weight:500;color:#64748b;border-bottom:2px solid transparent;display:flex;align-items:center;gap:.5rem;transition:all .2s}
.crm-tab:hover{color:#00529B}
.crm-tab.active{color:#00529B;border-bottom-color:#00529B;font-weight:600}
.crm-tab-locked{opacity:.45;cursor:not-allowed}
.crm-body{background:#fff;padding:2rem;border:1px solid #e2e8f0;border-top:none}
.crm-footer{background:#f8fafc;border:1px solid #e2e8f0;border-top:none;padding:1.25rem 2rem;display:flex;justify-content:space-between;align-items:center;border-radius:0 0 .75rem .75rem}
.form-section{margin-bottom:2rem}
.form-section-title{font-size:.9375rem;font-weight:600;color:#1e293b;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.5rem}
.form-grid{display:grid;gap:1rem}
.form-grid-2{grid-template-columns:repeat(2,1fr)}
.form-grid-3{grid-template-columns:repeat(3,1fr)}
.form-grid-4{grid-template-columns:repeat(4,1fr)}
@media(max-width:768px){.form-grid-2,.form-grid-3,.form-grid-4{grid-template-columns:1fr}}
.form-label.required::after{content:" *";color:#ef4444}
.esp-grid{display:flex;flex-wrap:wrap;gap:.5rem}
.esp-chip{display:flex;align-items:center;gap:.4rem;padding:.35rem .75rem;border:1px solid #e2e8f0;border-radius:20px;font-size:.8125rem;cursor:pointer;transition:all .2s;user-select:none}
.esp-chip:hover{border-color:#00529B;background:#ebf4ff}
.esp-chip input{display:none}
.esp-chip.checked{background:#00529B;color:#fff;border-color:#00529B}

/* Timeline de interações */
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

/* Formulário de nova interação */
.int-form-card{background:#f0f9ff;border:1px solid #bae6fd;border-radius:.5rem;padding:1.25rem;margin-bottom:1.5rem}
.int-form-card h3{font-size:.9rem;font-weight:600;color:#0284c7;margin-bottom:1rem}
</style>

<div class="crm-form-wrap">

  <!-- Header -->
  <div class="crm-header">
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h1><i class="fas fa-user-plus me-2"></i><?php echo $isEdit ? 'Editar Lead' : 'Novo Lead'; ?></h1>
        <p><?php echo $isEdit ? htmlspecialchars($lead->nome_lead) : 'Cadastre um novo contato comercial'; ?></p>
      </div>
      <a href="/crm/leads" class="btn btn-sm btn-light"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
    </div>
  </div>

  <!-- Abas -->
  <div class="crm-tabs">
    <div class="crm-tab <?php echo $activeTab === 'dados' ? 'active' : ''; ?>" onclick="switchTab('dados')">
      <i class="fas fa-id-card"></i> Dados do Lead
    </div>
    <div class="crm-tab <?php echo (!$isEdit ? 'crm-tab-locked' : ($activeTab === 'interacoes' ? 'active' : '')); ?>"
         onclick="<?php echo $isEdit ? "switchTab('interacoes')" : 'void(0)'; ?>">
      <i class="fas fa-comments"></i> Interações
      <?php if ($isEdit && !empty($interacoes)): ?>
      <span class="badge bg-primary rounded-pill ms-1" style="font-size:.65rem"><?php echo count($interacoes); ?></span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Aba: Dados -->
  <div class="crm-body" id="tab-dados" style="display:<?php echo $activeTab === 'dados' ? 'block' : 'none'; ?>">

    <?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
      <i class="fas fa-check-circle me-2"></i>
      <?php echo $_GET['success'] === 'criado' ? 'Lead cadastrado com sucesso!' : 'Lead atualizado com sucesso!'; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
      <i class="fas fa-exclamation-circle me-2"></i> Ocorreu um erro. Tente novamente.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form id="leadForm" action="<?php echo $action; ?>" method="POST">
      <?php echo View::csrfField(); ?>

      <!-- Seção: Identificação -->
      <section class="form-section">
        <h2 class="form-section-title"><i class="fas fa-id-card text-primary"></i> Identificação</h2>
        <div class="form-grid form-grid-3">
          <div class="form-group">
            <label class="form-label required">Tipo de Pessoa</label>
            <select name="tipo_pessoa" id="tipo_pessoa" class="form-select" onchange="toggleTipoPessoa()">
              <option value="PJ" <?php echo ($lead->tipo_pessoa ?? 'PJ') === 'PJ' ? 'selected' : ''; ?>>Pessoa Jurídica (CNPJ)</option>
              <option value="PF" <?php echo ($lead->tipo_pessoa ?? '') === 'PF' ? 'selected' : ''; ?>>Pessoa Física (CPF)</option>
            </select>
          </div>
          <div class="form-group" id="campo-cnpj">
            <label class="form-label">CNPJ</label>
            <div class="input-group">
              <input type="text" name="cnpj" id="cnpj" class="form-control" placeholder="00.000.000/0000-00"
                     value="<?php echo htmlspecialchars($lead->cnpj ?? ''); ?>">
              <button type="button" class="btn btn-primary" id="btn-buscar-cnpj" title="Buscar dados do CNPJ">
                <i class="fas fa-search"></i>
              </button>
            </div>
            <small class="text-muted">Clique em buscar para preencher automaticamente</small>
          </div>
          <div class="form-group" id="campo-cpf" style="display:none">
            <label class="form-label">CPF</label>
            <input type="text" name="cpf" id="cpf" class="form-control" placeholder="000.000.000-00"
                   value="<?php echo htmlspecialchars($lead->cpf ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label required">Status do Lead</label>
            <select name="status_lead" class="form-select">
              <?php foreach ($statusList as $k => $v): ?>
              <option value="<?php echo $k; ?>" <?php echo ($lead->status_lead ?? 'novo') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </section>

      <!-- Seção: Dados Principais -->
      <section class="form-section">
        <h2 class="form-section-title"><i class="fas fa-building text-primary"></i> Dados Principais</h2>
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label class="form-label required">Nome do Lead / Empresa</label>
            <input type="text" name="nome_lead" class="form-control" required
                   placeholder="Nome da empresa ou pessoa"
                   value="<?php echo htmlspecialchars($lead->nome_lead ?? ''); ?>">
          </div>
          <div class="form-group" id="campo-razao">
            <label class="form-label">Razão Social</label>
            <input type="text" name="razao_social" id="razao_social" class="form-control"
                   placeholder="Razão Social (preenchido via CNPJ)"
                   value="<?php echo htmlspecialchars($lead->razao_social ?? ''); ?>">
          </div>
        </div>
        <div class="form-grid form-grid-2 mt-3">
          <div class="form-group">
            <label class="form-label">Nome Fantasia</label>
            <input type="text" name="nome_fantasia" id="nome_fantasia" class="form-control"
                   value="<?php echo htmlspecialchars($lead->nome_fantasia ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" id="email_lead" class="form-control"
                   value="<?php echo htmlspecialchars($lead->email ?? ''); ?>">
          </div>
        </div>
        <div class="form-grid form-grid-3 mt-3">
          <div class="form-group">
            <label class="form-label">Telefone</label>
            <input type="text" name="telefone" id="telefone_lead" class="form-control"
                   value="<?php echo htmlspecialchars($lead->telefone ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Celular / WhatsApp</label>
            <input type="text" name="celular" class="form-control"
                   value="<?php echo htmlspecialchars($lead->celular ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Data Próximo Contato</label>
            <input type="date" name="data_proximo_contato" class="form-control"
                   value="<?php echo htmlspecialchars($lead->data_proximo_contato ?? ''); ?>">
          </div>
        </div>
      </section>

      <!-- Seção: Endereço -->
      <section class="form-section">
        <h2 class="form-section-title"><i class="fas fa-map-marker-alt text-primary"></i> Endereço</h2>
        <div class="form-grid form-grid-4">
          <div class="form-group">
            <label class="form-label">CEP</label>
            <input type="text" name="cep" id="cep_lead" class="form-control" placeholder="00000-000"
                   value="<?php echo htmlspecialchars($lead->cep ?? ''); ?>">
          </div>
          <div class="form-group" style="grid-column:span 2">
            <label class="form-label">Endereço</label>
            <input type="text" name="endereco" id="endereco_lead" class="form-control"
                   value="<?php echo htmlspecialchars($lead->endereco ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Número</label>
            <input type="text" name="numero" id="numero_lead" class="form-control"
                   value="<?php echo htmlspecialchars($lead->numero ?? ''); ?>">
          </div>
        </div>
        <div class="form-grid form-grid-4 mt-3">
          <div class="form-group">
            <label class="form-label">Complemento</label>
            <input type="text" name="complemento" class="form-control"
                   value="<?php echo htmlspecialchars($lead->complemento ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Bairro</label>
            <input type="text" name="bairro" id="bairro_lead" class="form-control"
                   value="<?php echo htmlspecialchars($lead->bairro ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Cidade</label>
            <input type="text" name="cidade" id="cidade_lead" class="form-control"
                   value="<?php echo htmlspecialchars($lead->cidade ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Estado</label>
            <input type="text" name="estado" id="estado_lead" class="form-control" maxlength="2"
                   value="<?php echo htmlspecialchars($lead->estado ?? ''); ?>">
          </div>
        </div>
      </section>

      <!-- Seção: Qualificação Comercial -->
      <section class="form-section">
        <h2 class="form-section-title"><i class="fas fa-briefcase-medical text-primary"></i> Qualificação Comercial — Radiologia</h2>
        <div class="form-grid form-grid-3">
          <div class="form-group">
            <label class="form-label">Segmento Principal</label>
            <select name="segmento_principal" class="form-select">
              <option value="">Selecione...</option>
              <?php foreach ($segmentos as $k => $v): ?>
              <option value="<?php echo $k; ?>" <?php echo ($lead->segmento_principal ?? '') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Origem do Lead</label>
            <select name="origem" class="form-select">
              <?php foreach ($origens as $k => $v): ?>
              <option value="<?php echo $k; ?>" <?php echo ($lead->origem ?? 'outro') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Volume de Exames/Mês (est.)</label>
            <input type="number" name="volume_exames_mes" class="form-control" min="0"
                   placeholder="Ex: 500"
                   value="<?php echo htmlspecialchars($lead->volume_exames_mes ?? ''); ?>">
          </div>
        </div>
        <div class="form-grid form-grid-3 mt-3">
          <div class="form-group">
            <label class="form-label">Nº de Médicos / Radiologistas</label>
            <input type="number" name="num_medicos" class="form-control" min="0"
                   value="<?php echo htmlspecialchars($lead->num_medicos ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Nº de Unidades / Filiais</label>
            <input type="number" name="num_unidades" class="form-control" min="0"
                   value="<?php echo htmlspecialchars($lead->num_unidades ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Acreditação (ONA, JCI...)</label>
            <input type="text" name="acreditacao" class="form-control"
                   placeholder="Ex: ONA Nível 2"
                   value="<?php echo htmlspecialchars($lead->acreditacao ?? ''); ?>">
          </div>
        </div>
        <div class="form-grid form-grid-2 mt-3">
          <div class="form-group">
            <label class="form-label">Sistema Atual Utilizado</label>
            <input type="text" name="sistema_atual" class="form-control"
                   placeholder="Ex: RIS/PACS atual, planilha..."
                   value="<?php echo htmlspecialchars($lead->sistema_atual ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Equipamentos que Possui</label>
            <input type="text" name="equipamentos_possui" class="form-control"
                   placeholder="Ex: TC 64 canais, RM 1.5T..."
                   value="<?php echo htmlspecialchars($lead->equipamentos_possui ?? ''); ?>">
          </div>
        </div>

        <!-- Especialidades de Interesse -->
        <div class="form-group mt-3">
          <label class="form-label">Especialidades / Modalidades de Interesse</label>
          <div class="esp-grid">
            <?php foreach ($especialidades as $esp): ?>
            <?php $checked = in_array($esp, $espAtivas); ?>
            <label class="esp-chip <?php echo $checked ? 'checked' : ''; ?>">
              <input type="checkbox" name="especialidades_interesse[]" value="<?php echo htmlspecialchars($esp); ?>"
                     <?php echo $checked ? 'checked' : ''; ?> onchange="toggleChip(this)">
              <i class="fas fa-x-ray me-1" style="font-size:.75rem"></i>
              <?php echo htmlspecialchars($esp); ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <!-- Seção: Contato Decisor -->
      <section class="form-section">
        <h2 class="form-section-title"><i class="fas fa-user-tie text-primary"></i> Contato / Decisor</h2>
        <div class="form-grid form-grid-2">
          <div class="form-group">
            <label class="form-label">Nome do Responsável</label>
            <input type="text" name="responsavel_nome" class="form-control"
                   value="<?php echo htmlspecialchars($lead->responsavel_nome ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Cargo</label>
            <input type="text" name="responsavel_cargo" class="form-control"
                   placeholder="Ex: Diretor Clínico, Gestor de TI"
                   value="<?php echo htmlspecialchars($lead->responsavel_cargo ?? ''); ?>">
          </div>
        </div>
        <div class="form-grid form-grid-2 mt-3">
          <div class="form-group">
            <label class="form-label">E-mail do Responsável</label>
            <input type="email" name="responsavel_email" class="form-control"
                   value="<?php echo htmlspecialchars($lead->responsavel_email ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Telefone do Responsável</label>
            <input type="text" name="responsavel_telefone" class="form-control"
                   value="<?php echo htmlspecialchars($lead->responsavel_telefone ?? ''); ?>">
          </div>
        </div>
      </section>

      <!-- Seção: Observações -->
      <section class="form-section">
        <h2 class="form-section-title"><i class="fas fa-sticky-note text-primary"></i> Observações</h2>
        <textarea name="observacoes" class="form-control" rows="4"
                  placeholder="Anotações gerais sobre este lead..."><?php echo htmlspecialchars($lead->observacoes ?? ''); ?></textarea>
      </section>

    </form>
  </div><!-- /tab-dados -->

  <!-- Aba: Interações -->
  <?php if ($isEdit): ?>
  <div class="crm-body" id="tab-interacoes" style="display:<?php echo $activeTab === 'interacoes' ? 'block' : 'none'; ?>">

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

  </div>
  <?php endif; ?>

  <!-- Footer -->
  <div class="crm-footer">
    <div>
      <?php if ($isEdit && !$lead->convertido_em): ?>
      <a href="/crm/leads/converter/<?php echo $lead->id; ?>" class="btn btn-success btn-sm"
         onclick="return confirm('Converter este lead em oportunidade?')">
        <i class="fas fa-arrow-right me-1"></i> Converter em Oportunidade
      </a>
      <?php elseif ($isEdit && $lead->convertido_em === 'oportunidade'): ?>
      <a href="/crm/oportunidades/edit/<?php echo $lead->convertido_id; ?>" class="btn btn-outline-success btn-sm">
        <i class="fas fa-chart-line me-1"></i> Ver Oportunidade
      </a>
      <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
      <a href="/crm/leads" class="btn btn-light">Cancelar</a>
      <button type="submit" form="leadForm" class="btn btn-primary">
        <i class="fas fa-save me-1"></i> <?php echo $isEdit ? 'Salvar Alterações' : 'Cadastrar Lead'; ?>
      </button>
    </div>
  </div>

</div>

<script>
// Troca de abas
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

// Toggle chips de especialidade
function toggleChip(input) {
  input.closest('.esp-chip').classList.toggle('checked', input.checked);
}

// Toggle tipo pessoa
function toggleTipoPessoa() {
  const tipo = document.getElementById('tipo_pessoa').value;
  document.getElementById('campo-cnpj').style.display = tipo === 'PJ' ? '' : 'none';
  document.getElementById('campo-cpf').style.display  = tipo === 'PF' ? '' : 'none';
}
toggleTipoPessoa();

// Busca CNPJ
document.getElementById('btn-buscar-cnpj')?.addEventListener('click', function() {
  const cnpj = document.getElementById('cnpj').value.replace(/\D/g, '');
  if (cnpj.length !== 14) { alert('CNPJ inválido.'); return; }

  this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
  this.disabled  = true;

  fetch('/crm/leads/buscar-cnpj?cnpj=' + cnpj)
    .then(r => r.json())
    .then(res => {
      if (!res.success) { alert(res.error || 'CNPJ não encontrado.'); return; }
      const d = res.data;
      const set = (id, val) => { const el = document.getElementById(id); if (el && val) el.value = val; };
      set('razao_social',  d.razao_social);
      set('nome_fantasia', d.nome_fantasia);
      set('email_lead',    d.email);
      set('telefone_lead', d.telefone);
      set('cep_lead',      d.cep);
      set('endereco_lead', d.endereco);
      set('numero_lead',   d.numero);
      set('bairro_lead',   d.bairro);
      set('cidade_lead',   d.cidade);
      set('estado_lead',   d.estado);
      // Preenche nome_lead com razão social se estiver vazio
      const nomeLead = document.querySelector('[name="nome_lead"]');
      if (nomeLead && !nomeLead.value) nomeLead.value = d.razao_social || d.nome_fantasia || '';
    })
    .catch(() => alert('Erro ao consultar CNPJ.'))
    .finally(() => {
      this.innerHTML = '<i class="fas fa-search"></i>';
      this.disabled  = false;
    });
});

// Salvar interação via AJAX
function salvarInteracao() {
  const data    = document.getElementById('int_data').value;
  const tipo    = document.getElementById('int_tipo').value;
  const resumo  = document.getElementById('int_resumo').value.trim();

  if (!resumo) { alert('O resumo da interação é obrigatório.'); return; }

  const form = new FormData();
  form.append('related_id',     '<?php echo $lead->id ?? 0; ?>');
  form.append('data_interacao', data.replace('T', ' '));
  form.append('tipo_interacao', tipo);
  form.append('resumo',         resumo);
  form.append('_token',         document.querySelector('input[name="_token"]')?.value || '');

  fetch('/crm/leads/interacao/add', { method: 'POST', body: form })
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

// Deletar interação
function deletarInteracao(id) {
  if (!confirm('Excluir esta interação?')) return;
  const form = new FormData();
  form.append('_token', document.querySelector('input[name="_token"]')?.value || '');
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

<?php require_once dirname(__DIR__, 2) . '/layout/erp_footer.php'; ?>
