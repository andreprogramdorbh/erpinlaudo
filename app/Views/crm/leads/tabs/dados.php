<?php
/**
 * ERP InLaudo - Aba Dados do Lead
 * Conteúdo da aba principal do formulário de leads
 */
$action = $isEdit ? '/crm/leads/update/' . ($lead->id ?? '') : '/crm/leads';
$espAtivas  = json_decode($lead->especialidades_interesse ?? '[]', true) ?: [];
$prodAtivos = json_decode($lead->produtos_interesse ?? '[]', true) ?: [];
?>
<style>
.esp-grid{display:flex;flex-wrap:wrap;gap:.5rem}
.esp-chip{display:flex;align-items:center;gap:.4rem;padding:.35rem .75rem;border:1px solid #e2e8f0;border-radius:20px;font-size:.8125rem;cursor:pointer;transition:all .2s;user-select:none}
.esp-chip:hover{border-color:#00529B;background:#ebf4ff}
.esp-chip input{display:none}
.esp-chip.checked{background:#00529B;color:#fff;border-color:#00529B}
</style>

<?php if (!empty($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
  <i class="fas fa-check-circle me-2"></i>
  <?php echo $_GET['success'] === 'criado' ? 'Lead cadastrado com sucesso!' : 'Lead atualizado com sucesso!'; ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
  <i class="fas fa-exclamation-circle me-2"></i> Ocorreu um erro ao salvar. Tente novamente.
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form id="leadForm" action="<?php echo $action; ?>" method="POST">
  <?php
  if (class_exists('App\Core\View')) {
      echo App\Core\View::csrfField();
  } elseif (!empty($_SESSION['csrf_token'])) {
      echo '<input type="hidden" name="_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
  }
  ?>

  <!-- Seção: Identificação -->
  <section class="form-section">
    <h2 class="form-section-title"><i class="fas fa-id-card section-icon text-primary"></i> Identificação</h2>
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
    <h2 class="form-section-title"><i class="fas fa-building section-icon text-primary"></i> Dados Principais</h2>
    <div class="form-grid form-grid-2">
      <div class="form-group">
        <label class="form-label required">Nome do Lead / Empresa</label>
        <input type="text" name="nome_lead" class="form-control" required
               placeholder="Nome da empresa ou pessoa"
               value="<?php echo htmlspecialchars($lead->nome_lead ?? ''); ?>">
      </div>
      <div class="form-group">
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
        <div class="input-group">
          <input type="text" name="telefone" id="telefone_lead" class="form-control"
                 value="<?php echo htmlspecialchars($lead->telefone ?? ''); ?>">
          <?php $rawTF = preg_replace('/\D/', '', $lead->telefone ?? ''); if (strlen($rawTF) >= 10): $waTF = (substr($rawTF,0,2)==='55')?$rawTF:'55'.$rawTF; ?>
          <a href="https://wa.me/<?php echo $waTF; ?>" target="_blank" class="btn btn-success" title="WhatsApp">
            <i class="fab fa-whatsapp"></i>
          </a>
          <?php else: ?>
          <a href="#" class="btn btn-success" title="WhatsApp" onclick="return abrirWaInput('telefone_lead', event)">
            <i class="fab fa-whatsapp"></i>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Celular / WhatsApp</label>
        <div class="input-group">
          <input type="text" name="celular" id="celular_lead" class="form-control"
                 value="<?php echo htmlspecialchars($lead->celular ?? ''); ?>">
          <?php $rawCF = preg_replace('/\D/', '', $lead->celular ?? ''); if (strlen($rawCF) >= 10): $waCF = (substr($rawCF,0,2)==='55')?$rawCF:'55'.$rawCF; ?>
          <a href="https://wa.me/<?php echo $waCF; ?>" target="_blank" class="btn btn-success" title="WhatsApp">
            <i class="fab fa-whatsapp"></i>
          </a>
          <?php else: ?>
          <a href="#" class="btn btn-success" title="WhatsApp" onclick="return abrirWaInput('celular_lead', event)">
            <i class="fab fa-whatsapp"></i>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Data Próximo Contato</label>
        <input type="date" name="data_proximo_contato" class="form-control"
               value="<?php echo htmlspecialchars($lead->data_proximo_contato ?? ''); ?>">
      </div>
    </div>
    <!-- Mídias Sociais -->
    <div class="form-grid form-grid-3 mt-3">
      <div class="form-group">
        <label class="form-label"><i class="fas fa-globe text-secondary me-1"></i> Website</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-link"></i></span>
          <input type="url" name="website" class="form-control" placeholder="https://www.empresa.com.br"
                 value="<?php echo htmlspecialchars($lead->website ?? ''); ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fab fa-instagram text-danger me-1"></i> Instagram</label>
        <div class="input-group">
          <span class="input-group-text">@</span>
          <input type="text" name="instagram" class="form-control" placeholder="perfil_empresa"
                 value="<?php echo htmlspecialchars($lead->instagram ?? ''); ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fab fa-linkedin text-primary me-1"></i> LinkedIn</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
          <input type="url" name="linkedin" class="form-control" placeholder="https://linkedin.com/company/..."
                 value="<?php echo htmlspecialchars($lead->linkedin ?? ''); ?>">
        </div>
      </div>
    </div>
  </section>

  <!-- Seção: Endereço -->
  <section class="form-section">
    <h2 class="form-section-title"><i class="fas fa-map-marker-alt section-icon text-primary"></i> Endereço</h2>
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
    <h2 class="form-section-title"><i class="fas fa-briefcase-medical section-icon text-primary"></i> Qualificação Comercial — Radiologia</h2>
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
        <input type="number" name="volume_exames_mes" class="form-control" min="0" placeholder="Ex: 500"
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
        <input type="text" name="acreditacao" class="form-control" placeholder="Ex: ONA Nível 2"
               value="<?php echo htmlspecialchars($lead->acreditacao ?? ''); ?>">
      </div>
    </div>
    <div class="form-grid form-grid-2 mt-3">
      <div class="form-group">
        <label class="form-label">Sistema Atual Utilizado</label>
        <input type="text" name="sistema_atual" class="form-control" placeholder="Ex: RIS/PACS atual, planilha..."
               value="<?php echo htmlspecialchars($lead->sistema_atual ?? ''); ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Equipamentos que Possui</label>
        <input type="text" name="equipamentos_possui" class="form-control" placeholder="Ex: TC 64 canais, RM 1.5T..."
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
    <h2 class="form-section-title"><i class="fas fa-user-tie section-icon text-primary"></i> Contato / Decisor</h2>
    <div class="form-grid form-grid-2">
      <div class="form-group">
        <label class="form-label">Nome do Responsável</label>
        <input type="text" name="responsavel_nome" class="form-control"
               value="<?php echo htmlspecialchars($lead->responsavel_nome ?? ''); ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Cargo</label>
        <input type="text" name="responsavel_cargo" class="form-control" placeholder="Ex: Diretor Clínico, Gestor de TI"
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
        <div class="input-group">
          <input type="text" name="responsavel_telefone" id="responsavel_telefone_lead" class="form-control"
                 value="<?php echo htmlspecialchars($lead->responsavel_telefone ?? ''); ?>">
          <?php $rawRF = preg_replace('/\D/', '', $lead->responsavel_telefone ?? ''); if (strlen($rawRF) >= 10): $waRF = (substr($rawRF,0,2)==='55')?$rawRF:'55'.$rawRF; ?>
          <a href="https://wa.me/<?php echo $waRF; ?>" target="_blank" class="btn btn-success" title="WhatsApp">
            <i class="fab fa-whatsapp"></i>
          </a>
          <?php else: ?>
          <a href="#" class="btn btn-success" title="WhatsApp" onclick="return abrirWaInput('responsavel_telefone_lead', event)">
            <i class="fab fa-whatsapp"></i>
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Seção: Observações -->
  <section class="form-section">
    <h2 class="form-section-title"><i class="fas fa-sticky-note section-icon text-primary"></i> Observações</h2>
    <textarea name="observacoes" class="form-control" rows="4"
              placeholder="Anotações gerais sobre este lead..."><?php echo htmlspecialchars($lead->observacoes ?? ''); ?></textarea>
  </section>

</form>

<script>
// Toggle tipo pessoa
function toggleTipoPessoa() {
  const tipo = document.getElementById('tipo_pessoa').value;
  document.getElementById('campo-cnpj').style.display = tipo === 'PJ' ? '' : 'none';
  document.getElementById('campo-cpf').style.display  = tipo === 'PF' ? '' : 'none';
}
toggleTipoPessoa();

// Toggle chips de especialidade
function toggleChip(input) {
  input.closest('.esp-chip').classList.toggle('checked', input.checked);
}

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
      const nomeLead = document.querySelector('[name="nome_lead"]');
      if (nomeLead && !nomeLead.value) nomeLead.value = d.razao_social || d.nome_fantasia || '';
    })
    .catch(() => alert('Erro ao consultar CNPJ.'))
    .finally(() => {
      this.innerHTML = '<i class="fas fa-search"></i>';
      this.disabled  = false;
    });
});

// WhatsApp a partir do input
function abrirWaInput(inputId, e) {
  e.preventDefault();
  const raw = document.getElementById(inputId).value.replace(/\D/g, '');
  if (!raw || raw.length < 10) { alert('Informe um número válido antes de abrir o WhatsApp.'); return false; }
  const numero = raw.startsWith('55') ? raw : '55' + raw;
  window.open('https://wa.me/' + numero, '_blank');
  return false;
}
</script>
