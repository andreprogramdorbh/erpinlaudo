<?php
/**
 * Aba Empresa — Perfil do Usuário
 * Dados da empresa gerida no ERP (CPF/CNPJ, logo, emails, endereço)
 *
 * Variáveis disponíveis:
 *   $empresa  — objeto EmpresaConfig ou null
 *   $usuario  — objeto User logado
 */
$e = $empresa ?? null;
$v = fn(string $field, string $default = ''): string
    => htmlspecialchars((string) ($e?->$field ?? $default));
?>

<div class="prf-card">
  <form id="formEmpresa" action="/perfil/empresa/update" method="POST" enctype="multipart/form-data">
    <?php echo \App\Core\View::csrfField(); ?>

    <!-- ── IDENTIFICAÇÃO ─────────────────────────────────────────── -->
    <div class="prf-card-section">
      <h2 class="prf-section-title">
        <i class="fas fa-building text-primary"></i> Identificação da Empresa
      </h2>

      <!-- Tipo de Pessoa -->
      <div class="mb-3">
        <label class="form-label fw-semibold">Tipo de Pessoa</label>
        <div class="d-flex gap-3">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="tipo_pessoa" id="tipoPJ" value="pj"
              <?php echo ($e?->tipo_pessoa ?? 'pj') === 'pj' ? 'checked' : ''; ?>
              onchange="toggleTipoPessoa(this.value)">
            <label class="form-check-label" for="tipoPJ">
              <i class="fas fa-building me-1 text-primary"></i> Pessoa Jurídica (CNPJ)
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="tipo_pessoa" id="tipoPF" value="pf"
              <?php echo ($e?->tipo_pessoa ?? 'pj') === 'pf' ? 'checked' : ''; ?>
              onchange="toggleTipoPessoa(this.value)">
            <label class="form-check-label" for="tipoPF">
              <i class="fas fa-user me-1 text-primary"></i> Pessoa Física (CPF)
            </label>
          </div>
        </div>
      </div>

      <div class="form-grid-2">
        <!-- Razão Social / Nome -->
        <div class="form-group">
          <label class="form-label" id="labelRazao">
            Razão Social <span class="text-danger">*</span>
          </label>
          <input type="text" name="razao_social" id="razaoSocial" class="form-control"
            placeholder="Nome completo da empresa" value="<?php echo $v('razao_social'); ?>">
        </div>

        <!-- Nome Fantasia -->
        <div class="form-group" id="wrapNomeFantasia">
          <label class="form-label">Nome Fantasia</label>
          <input type="text" name="nome_fantasia" class="form-control"
            placeholder="Nome comercial (opcional)" value="<?php echo $v('nome_fantasia'); ?>">
        </div>
      </div>

      <div class="form-grid-2">
        <!-- CPF / CNPJ -->
        <div class="form-group">
          <label class="form-label" id="labelDoc">
            CNPJ <span class="text-danger">*</span>
          </label>
          <input type="text" name="cpf_cnpj" id="inputDoc" class="form-control"
            placeholder="00.000.000/0001-00"
            maxlength="18"
            value="<?php
              $raw = $e?->cpf_cnpj ?? '';
              if (strlen($raw) === 11) {
                  echo htmlspecialchars(preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $raw));
              } elseif (strlen($raw) === 14) {
                  echo htmlspecialchars(preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $raw));
              } else {
                  echo htmlspecialchars($raw);
              }
            ?>">
        </div>

        <!-- Inscrição Estadual (só PJ) -->
        <div class="form-group" id="wrapIE">
          <label class="form-label">Inscrição Estadual</label>
          <input type="text" name="inscricao_estadual" class="form-control"
            placeholder="Opcional" value="<?php echo $v('inscricao_estadual'); ?>">
        </div>
      </div>

      <!-- Inscrição Municipal (só PJ) -->
      <div class="form-group" id="wrapIM">
        <label class="form-label">Inscrição Municipal</label>
        <input type="text" name="inscricao_municipal" class="form-control"
          placeholder="Opcional" value="<?php echo $v('inscricao_municipal'); ?>">
      </div>
    </div>

    <!-- ── LOGO ──────────────────────────────────────────────────── -->
    <div class="prf-card-section">
      <h2 class="prf-section-title">
        <i class="fas fa-image text-primary"></i> Logo da Empresa
      </h2>
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <?php if (!empty($e?->logo_path)): ?>
          <div id="logoPreviewWrap">
            <img src="/<?php echo htmlspecialchars($e->logo_path); ?>"
              id="logoPreview"
              alt="Logo"
              style="max-height:80px;max-width:220px;border:1px solid #e2e8f0;border-radius:.5rem;padding:4px;background:#fff">
          </div>
        <?php else: ?>
          <div id="logoPreviewWrap" style="display:none">
            <img src="" id="logoPreview" alt="Logo"
              style="max-height:80px;max-width:220px;border:1px solid #e2e8f0;border-radius:.5rem;padding:4px;background:#fff">
          </div>
        <?php endif; ?>
        <div>
          <label class="form-label mb-1">Enviar novo logo</label>
          <input type="file" name="logo" id="logoInput" class="form-control" accept="image/*"
            onchange="previewLogo(this)" style="max-width:320px">
          <small class="text-muted">JPG, PNG, GIF ou WebP — máx. 2 MB. Recomendado: 400×120 px.</small>
        </div>
      </div>
    </div>

    <!-- ── CONTATO ───────────────────────────────────────────────── -->
    <div class="prf-card-section">
      <h2 class="prf-section-title">
        <i class="fas fa-envelope text-primary"></i> Contato e E-mails
      </h2>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">E-mail do Responsável <span class="text-danger">*</span></label>
          <input type="email" name="email_responsavel" id="emailResponsavel" class="form-control"
            placeholder="responsavel@empresa.com.br"
            value="<?php echo $v('email_responsavel'); ?>"
            oninput="sincronizarEmailFinanceiro()">
        </div>

        <div class="form-group">
          <label class="form-label">E-mail Financeiro</label>
          <div class="mb-1">
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" id="financeiroMesmo"
                name="financeiro_mesmo_responsavel" value="1"
                <?php echo ($e?->financeiro_mesmo_responsavel ?? 0) ? 'checked' : ''; ?>
                onchange="toggleEmailFinanceiro(this.checked)">
              <label class="form-check-label" for="financeiroMesmo">
                Mesmo e-mail do responsável
              </label>
            </div>
          </div>
          <input type="email" name="email_financeiro" id="emailFinanceiro" class="form-control"
            placeholder="financeiro@empresa.com.br"
            value="<?php echo $v('email_financeiro'); ?>"
            <?php echo ($e?->financeiro_mesmo_responsavel ?? 0) ? 'readonly style="background:#f8fafc;color:#94a3b8"' : ''; ?>>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Telefone / WhatsApp</label>
          <input type="text" name="telefone" class="form-control"
            placeholder="(00) 00000-0000" value="<?php echo $v('telefone'); ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Site</label>
          <input type="url" name="site" class="form-control"
            placeholder="https://www.empresa.com.br" value="<?php echo $v('site'); ?>">
        </div>
      </div>
    </div>

    <!-- ── ENDEREÇO ──────────────────────────────────────────────── -->
    <div class="prf-card-section">
      <h2 class="prf-section-title">
        <i class="fas fa-map-marker-alt text-primary"></i> Endereço
      </h2>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">CEP</label>
          <input type="text" name="cep" id="cepInput" class="form-control"
            placeholder="00000-000" maxlength="9"
            value="<?php
              $cep = $e?->cep ?? '';
              echo htmlspecialchars(strlen($cep) === 8 ? substr($cep,0,5).'-'.substr($cep,5) : $cep);
            ?>"
            oninput="buscarCep(this.value)">
        </div>
        <div class="form-group">
          <label class="form-label">Logradouro</label>
          <input type="text" name="logradouro" id="logradouro" class="form-control"
            placeholder="Rua, Av., Travessa..." value="<?php echo $v('logradouro'); ?>">
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Número</label>
          <input type="text" name="numero" class="form-control"
            placeholder="123 / S/N" value="<?php echo $v('numero'); ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Complemento</label>
          <input type="text" name="complemento" class="form-control"
            placeholder="Sala, Bloco, Apto..." value="<?php echo $v('complemento'); ?>">
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Bairro</label>
          <input type="text" name="bairro" id="bairro" class="form-control"
            placeholder="Bairro" value="<?php echo $v('bairro'); ?>">
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Cidade</label>
            <input type="text" name="cidade" id="cidade" class="form-control"
              placeholder="Cidade" value="<?php echo $v('cidade'); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Estado</label>
            <select name="estado" id="estado" class="form-select">
              <?php
              $estados = ['','AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG',
                          'PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
              $uf = strtoupper($e?->estado ?? '');
              foreach ($estados as $s) {
                  $sel = ($s === $uf) ? 'selected' : '';
                  echo "<option value=\"$s\" $sel>" . ($s ?: '-- UF --') . "</option>";
              }
              ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- ── FOOTER ────────────────────────────────────────────────── -->
    <div class="prf-footer">
      <button type="button" class="btn btn-outline-secondary" onclick="window.location='/perfil'">
        <i class="fas fa-times me-1"></i> Cancelar
      </button>
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-1"></i> Salvar Dados da Empresa
      </button>
    </div>

  </form>
</div>

<script>
// ── Tipo de Pessoa ─────────────────────────────────────────────────
function toggleTipoPessoa(tipo) {
  const isPJ = tipo === 'pj';
  document.getElementById('labelRazao').innerHTML = isPJ
    ? 'Razão Social <span class="text-danger">*</span>'
    : 'Nome Completo <span class="text-danger">*</span>';
  document.getElementById('labelDoc').innerHTML = isPJ
    ? 'CNPJ <span class="text-danger">*</span>'
    : 'CPF <span class="text-danger">*</span>';
  document.getElementById('inputDoc').placeholder = isPJ ? '00.000.000/0001-00' : '000.000.000-00';
  document.getElementById('inputDoc').maxLength   = isPJ ? 18 : 14;
  document.getElementById('wrapNomeFantasia').style.display = isPJ ? '' : 'none';
  document.getElementById('wrapIE').style.display           = isPJ ? '' : 'none';
  document.getElementById('wrapIM').style.display           = isPJ ? '' : 'none';
}

// Inicializa estado correto ao carregar
document.addEventListener('DOMContentLoaded', function () {
  const tipo = document.querySelector('input[name="tipo_pessoa"]:checked')?.value ?? 'pj';
  toggleTipoPessoa(tipo);
});

// ── E-mail Financeiro ──────────────────────────────────────────────
function toggleEmailFinanceiro(checked) {
  const ef = document.getElementById('emailFinanceiro');
  if (checked) {
    ef.value    = document.getElementById('emailResponsavel').value;
    ef.readOnly = true;
    ef.style.background = '#f8fafc';
    ef.style.color      = '#94a3b8';
  } else {
    ef.readOnly = false;
    ef.style.background = '';
    ef.style.color      = '';
  }
}

function sincronizarEmailFinanceiro() {
  if (document.getElementById('financeiroMesmo').checked) {
    document.getElementById('emailFinanceiro').value =
      document.getElementById('emailResponsavel').value;
  }
}

// ── Preview do Logo ────────────────────────────────────────────────
function previewLogo(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      const img  = document.getElementById('logoPreview');
      const wrap = document.getElementById('logoPreviewWrap');
      img.src         = e.target.result;
      wrap.style.display = '';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// ── Busca CEP via ViaCEP ───────────────────────────────────────────
let cepTimer = null;
function buscarCep(val) {
  clearTimeout(cepTimer);
  const cep = val.replace(/\D/g, '');
  if (cep.length !== 8) return;
  cepTimer = setTimeout(async () => {
    try {
      const res  = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
      const data = await res.json();
      if (!data.erro) {
        document.getElementById('logradouro').value = data.logradouro || '';
        document.getElementById('bairro').value     = data.bairro     || '';
        document.getElementById('cidade').value     = data.localidade || '';
        document.getElementById('estado').value     = data.uf         || '';
      }
    } catch (_) {}
  }, 600);
}

// ── Máscara CPF/CNPJ ──────────────────────────────────────────────
document.getElementById('inputDoc').addEventListener('input', function () {
  const tipo = document.querySelector('input[name="tipo_pessoa"]:checked')?.value ?? 'pj';
  let v = this.value.replace(/\D/g, '');
  if (tipo === 'pj') {
    v = v.substring(0, 14);
    v = v.replace(/^(\d{2})(\d)/, '$1.$2');
    v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
    v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
    v = v.replace(/(\d{4})(\d)/, '$1-$2');
  } else {
    v = v.substring(0, 11);
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
    v = v.replace(/\.(\d{3})(\d)/, '.$1-$2');
  }
  this.value = v;
});
</script>
