<?php
use App\Core\Auth;
use App\Core\Permission;
use App\Core\View;

require_once dirname(__DIR__) . '/layout/erp_header.php';

$usuario   = $usuario ?? Auth::user();
$activeTab = $_GET['tab'] ?? ($active_tab ?? 'geral');

$permProvider = new Permission();
$userPerms    = $permProvider->getPermissionsForRole($usuario->role ?? 'user');

$permLabels = [
    'view_clients'          => ['Visualizar Clientes',        'fa-users',              'primary'],
    'create_clients'        => ['Cadastrar Clientes',         'fa-user-plus',          'primary'],
    'edit_clients'          => ['Editar Clientes',            'fa-user-edit',          'primary'],
    'delete_clients'        => ['Excluir Clientes',           'fa-user-minus',         'danger'],
    'view_finance'          => ['Visualizar Financeiro',      'fa-chart-line',         'success'],
    'manage_finance'        => ['Gerenciar Financeiro',       'fa-coins',              'success'],
    'view_contas_pagar'     => ['Ver Contas a Pagar',         'fa-arrow-circle-up',    'warning'],
    'create_contas_pagar'   => ['Criar Contas a Pagar',       'fa-plus-circle',        'warning'],
    'edit_contas_pagar'     => ['Editar Contas a Pagar',      'fa-edit',               'warning'],
    'delete_contas_pagar'   => ['Excluir Contas a Pagar',     'fa-trash',              'danger'],
    'view_contas_receber'   => ['Ver Contas a Receber',       'fa-arrow-circle-down',  'info'],
    'create_contas_receber' => ['Criar Contas a Receber',     'fa-plus-circle',        'info'],
    'edit_contas_receber'   => ['Editar Contas a Receber',    'fa-edit',               'info'],
    'delete_contas_receber' => ['Excluir Contas a Receber',   'fa-trash',              'danger'],
    'view_faturamento'      => ['Visualizar Faturamento',     'fa-file-invoice-dollar','success'],
    'view_notas_fiscais'    => ['Ver Notas Fiscais',          'fa-file-alt',           'success'],
    'create_notas_fiscais'  => ['Emitir Notas Fiscais',       'fa-file-medical',       'success'],
    'edit_notas_fiscais'    => ['Editar Notas Fiscais',       'fa-file-signature',     'success'],
    'delete_notas_fiscais'  => ['Excluir Notas Fiscais',      'fa-file-excel',         'danger'],
    'import_notas_fiscais'  => ['Importar Notas Fiscais',     'fa-file-import',        'success'],
    'view_crm'              => ['Visualizar CRM',             'fa-funnel-dollar',      'primary'],
    'manage_leads'          => ['Gerenciar Leads',            'fa-user-tag',           'primary'],
    'manage_oportunidades'  => ['Gerenciar Oportunidades',    'fa-handshake',          'primary'],
    'view_fornecedores'     => ['Ver Fornecedores',           'fa-truck',              'secondary'],
    'create_fornecedores'   => ['Cadastrar Fornecedores',     'fa-plus',               'secondary'],
    'edit_fornecedores'     => ['Editar Fornecedores',        'fa-edit',               'secondary'],
    'delete_fornecedores'   => ['Excluir Fornecedores',       'fa-trash',              'danger'],
    'view_plano_contas'     => ['Ver Plano de Contas',        'fa-sitemap',            'secondary'],
    'create_plano_contas'   => ['Criar Plano de Contas',      'fa-plus',               'secondary'],
    'edit_plano_contas'     => ['Editar Plano de Contas',     'fa-edit',               'secondary'],
    'delete_plano_contas'   => ['Excluir Plano de Contas',    'fa-trash',              'danger'],
    'view_integracoes'      => ['Ver Integracoes',            'fa-plug',               'dark'],
    'manage_integracoes'    => ['Gerenciar Integracoes',      'fa-cogs',               'dark'],
    'view_users'            => ['Visualizar Usuarios',        'fa-users-cog',          'dark'],
    'manage_users'          => ['Gerenciar Usuarios',         'fa-user-shield',        'danger'],
    'view_settings'         => ['Ver Configuracoes',          'fa-cog',                'dark'],
    'manage_settings'       => ['Gerenciar Configuracoes',    'fa-sliders-h',          'dark'],
    'view_profile'          => ['Ver Perfil',                 'fa-id-card',            'secondary'],
    'edit_profile'          => ['Editar Perfil',              'fa-user-edit',          'secondary'],
];

$roleInfo = [
    'superadmin' => ['Super Admin',    'danger',    'fa-crown'],
    'admin'      => ['Administrador',  'warning',   'fa-user-shield'],
    'financeiro' => ['Financeiro',     'success',   'fa-wallet'],
    'operador'   => ['Operador',       'primary',   'fa-user'],
    'leitura'    => ['Leitura',        'secondary', 'fa-eye'],
    'user'       => ['Usuario',        'secondary', 'fa-user'],
];
$rInfo    = $roleInfo[$usuario->role ?? 'user'] ?? ['Usuario', 'secondary', 'fa-user'];
$initials = strtoupper(substr($usuario->name, 0, 1) . (strpos($usuario->name, ' ') !== false ? substr($usuario->name, strpos($usuario->name, ' ') + 1, 1) : ''));
?>
<style>
.prf-wrap{padding:1.5rem;width:100%}
.prf-hero{background:linear-gradient(135deg,#00529B 0%,#0284c7 60%,#0ea5e9 100%);border-radius:.875rem;padding:2rem;color:#fff;display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem;box-shadow:0 4px 20px rgba(0,82,155,.3)}
.prf-avatar{width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.2);border:3px solid rgba(255,255,255,.5);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;flex-shrink:0}
.prf-hero-name{font-size:1.5rem;font-weight:700;margin:0 0 .25rem}
.prf-hero-email{opacity:.85;font-size:.9rem;margin:0 0 .5rem}
.prf-hero-role{display:inline-flex;align-items:center;gap:.4rem;background:rgba(255,255,255,.2);padding:.3em .8em;border-radius:20px;font-size:.8rem;font-weight:600}
.prf-hero-stats{margin-left:auto;text-align:right;flex-shrink:0}
.prf-stat-item{font-size:.75rem;opacity:.8;margin-bottom:.25rem}
.prf-stat-value{font-size:1.1rem;font-weight:700}
.prf-tabs{display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:1.5rem}
.prf-tab{padding:.75rem 1.5rem;cursor:pointer;font-size:.875rem;font-weight:500;color:#64748b;border-bottom:2px solid transparent;margin-bottom:-2px;display:flex;align-items:center;gap:.5rem;transition:all .2s;background:none;border-top:none;border-left:none;border-right:none}
.prf-tab:hover{color:#00529B}
.prf-tab.active{color:#00529B;border-bottom-color:#00529B;font-weight:600}
.prf-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.prf-card-section{padding:1.5rem;border-bottom:1px solid #f1f5f9}
.prf-card-section:last-child{border-bottom:none}
.prf-section-title{font-size:.9375rem;font-weight:600;color:#1e293b;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
.prf-footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:1.25rem 1.5rem;display:flex;justify-content:flex-end;gap:.75rem}
.form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
@media(max-width:600px){.form-grid-2{grid-template-columns:1fr}.prf-hero{flex-direction:column;text-align:center}.prf-hero-stats{margin-left:0;text-align:center}}
.pwd-input-wrap{position:relative}
.pwd-toggle{position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:0}
.pwd-strength{height:6px;border-radius:3px;background:#e2e8f0;margin-top:.5rem;overflow:hidden}
.pwd-strength-bar{height:100%;border-radius:3px;transition:width .3s,background .3s;width:0}
.pwd-strength-label{font-size:.75rem;color:#64748b;margin-top:.25rem}
.perm-group{margin-bottom:1.25rem}
.perm-group-title{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.6rem;padding-bottom:.35rem;border-bottom:1px solid #f1f5f9}
.perm-grid{display:flex;flex-wrap:wrap;gap:.4rem}
.perm-badge{display:inline-flex;align-items:center;gap:.35rem;font-size:.75rem;padding:.3em .7em;border-radius:20px;font-weight:500}
.perm-badge.has{background:#d1fae5;color:#065f46}
.perm-badge.no{background:#f1f5f9;color:#94a3b8;text-decoration:line-through;opacity:.6}
.perm-summary{display:flex;gap:1rem;margin-bottom:1.25rem;flex-wrap:wrap}
.perm-summary-item{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.5rem;padding:.75rem 1.25rem;text-align:center;flex:1;min-width:100px}
.perm-summary-num{font-size:1.5rem;font-weight:700;color:#00529B}
.perm-summary-label{font-size:.75rem;color:#64748b}
</style>

<div class="prf-wrap">

  <div class="prf-hero">
    <div class="prf-avatar"><?php echo $initials; ?></div>
    <div>
      <h1 class="prf-hero-name"><?php echo htmlspecialchars($usuario->name); ?></h1>
      <p class="prf-hero-email"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars((string)($usuario->email ?? '')); ?></p>
      <span class="prf-hero-role"><i class="fas <?php echo $rInfo[2]; ?>"></i> <?php echo $rInfo[0]; ?></span>
    </div>
    <div class="prf-hero-stats d-none d-md-block">
      <div class="prf-stat-item">ID do Usuario</div>
      <div class="prf-stat-value">#<?php echo str_pad($usuario->id ?? '0', 5, '0', STR_PAD_LEFT); ?></div>
      <div class="prf-stat-item mt-2">Membro desde</div>
      <div class="prf-stat-value"><?php echo date('d/m/Y', strtotime($usuario->created_at ?? 'now')); ?></div>
    </div>
  </div>

  <div class="prf-tabs">
    <button class="prf-tab <?php echo $activeTab === 'geral' ? 'active' : ''; ?>" onclick="switchTab('geral', this)">
      <i class="fas fa-user"></i> Dados Gerais
    </button>
    <button class="prf-tab <?php echo $activeTab === 'seguranca' ? 'active' : ''; ?>" onclick="switchTab('seguranca', this)">
      <i class="fas fa-lock"></i> Seguranca
    </button>
    <button class="prf-tab <?php echo $activeTab === 'permissoes' ? 'active' : ''; ?>" onclick="switchTab('permissoes', this)">
      <i class="fas fa-shield-alt"></i> Permissoes
      <span class="badge bg-primary rounded-pill ms-1" style="font-size:.65rem"><?php echo count($userPerms); ?></span>
    </button>
  </div>

  <div id="tab-geral" style="display:<?php echo $activeTab === 'geral' ? 'block' : 'none'; ?>">
    <div class="prf-card">
      <form id="formGeral" action="/perfil/update" method="POST">
        <?php echo View::csrfField(); ?>
        <div class="prf-card-section">
          <h2 class="prf-section-title"><i class="fas fa-id-card text-primary"></i> Informacoes Pessoais</h2>
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Nome Completo <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($usuario->name); ?>">
            </div>
            <div class="form-group">
              <label class="form-label">E-mail <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($usuario->email); ?>">
            </div>
          </div>
        </div>
        <div class="prf-card-section">
          <h2 class="prf-section-title"><i class="fas fa-info-circle text-primary"></i> Informacoes do Sistema</h2>
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Perfil de Acesso</label>
              <input type="text" class="form-control" readonly value="<?php echo $rInfo[0]; ?>">
              <small class="text-muted">Definido pelo administrador</small>
            </div>
            <div class="form-group">
              <label class="form-label">Status da Conta</label>
              <input type="text" class="form-control" readonly value="<?php echo ucfirst($usuario->status ?? 'ativo'); ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Ultimo Acesso</label>
              <input type="text" class="form-control" readonly value="<?php echo !empty($usuario->last_login) ? date('d/m/Y H:i', strtotime($usuario->last_login)) : 'Primeiro acesso'; ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Membro Desde</label>
              <input type="text" class="form-control" readonly value="<?php echo date('d/m/Y', strtotime($usuario->created_at ?? 'now')); ?>">
            </div>
          </div>
        </div>
        <div id="alertGeral"></div>
      </form>
      <div class="prf-footer">
        <a href="/dashboard" class="btn btn-light">Cancelar</a>
        <button type="submit" form="formGeral" class="btn btn-primary"><i class="fas fa-save me-1"></i> Salvar Alteracoes</button>
      </div>
    </div>
  </div>

  <div id="tab-seguranca" style="display:<?php echo $activeTab === 'seguranca' ? 'block' : 'none'; ?>">
    <div class="prf-card">
      <form id="formSenha" action="/perfil/change-password" method="POST">
        <?php echo View::csrfField(); ?>
        <div class="prf-card-section">
          <h2 class="prf-section-title"><i class="fas fa-key text-warning"></i> Alterar Senha</h2>
          <p class="text-muted small mb-3">Por seguranca, voce precisa informar sua senha atual para definir uma nova.</p>
          <div style="max-width:420px">
            <div class="form-group mb-3">
              <label class="form-label">Senha Atual <span class="text-danger">*</span></label>
              <div class="pwd-input-wrap">
                <input type="password" name="current_password" id="currentPassword" class="form-control" required placeholder="Informe sua senha atual">
                <button type="button" class="pwd-toggle" onclick="togglePwd('currentPassword', this)"><i class="fas fa-eye"></i></button>
              </div>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Nova Senha <span class="text-danger">*</span></label>
              <div class="pwd-input-wrap">
                <input type="password" name="new_password" id="newPassword" class="form-control" required placeholder="Minimo 6 caracteres" oninput="checkStrength(this.value)">
                <button type="button" class="pwd-toggle" onclick="togglePwd('newPassword', this)"><i class="fas fa-eye"></i></button>
              </div>
              <div class="pwd-strength mt-2"><div class="pwd-strength-bar" id="strengthBar"></div></div>
              <div class="pwd-strength-label" id="strengthLabel">Digite a nova senha</div>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Confirmar Nova Senha <span class="text-danger">*</span></label>
              <div class="pwd-input-wrap">
                <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required placeholder="Repita a nova senha" oninput="checkMatch()">
                <button type="button" class="pwd-toggle" onclick="togglePwd('confirmPassword', this)"><i class="fas fa-eye"></i></button>
              </div>
              <div id="matchMsg" style="font-size:.75rem;margin-top:.25rem"></div>
            </div>
          </div>
        </div>
        <div class="prf-card-section">
          <h2 class="prf-section-title"><i class="fas fa-shield-alt text-success"></i> Dicas de Seguranca</h2>
          <ul class="small text-muted mb-0" style="padding-left:1.25rem">
            <li>Use no minimo 8 caracteres</li>
            <li>Combine letras maiusculas, minusculas, numeros e simbolos</li>
            <li>Nao reutilize senhas de outros servicos</li>
            <li>Nunca compartilhe sua senha com terceiros</li>
          </ul>
        </div>
        <div id="alertSenha"></div>
      </form>
      <div class="prf-footer">
        <button type="button" class="btn btn-light" onclick="document.getElementById('formSenha').reset()">Limpar</button>
        <button type="submit" form="formSenha" class="btn btn-warning text-white"><i class="fas fa-lock me-1"></i> Alterar Senha</button>
      </div>
    </div>
  </div>

  <div id="tab-permissoes" style="display:<?php echo $activeTab === 'permissoes' ? 'block' : 'none'; ?>">
    <div class="prf-card">
      <div class="prf-card-section">
        <h2 class="prf-section-title"><i class="fas fa-shield-alt text-primary"></i> Suas Permissoes de Acesso</h2>
        <p class="text-muted small">Permissoes atribuidas ao perfil <strong><?php echo $rInfo[0]; ?></strong>. Para alteracoes, contate o administrador.</p>
        <div class="perm-summary">
          <div class="perm-summary-item">
            <div class="perm-summary-num"><?php echo count($userPerms); ?></div>
            <div class="perm-summary-label">Permissoes Ativas</div>
          </div>
          <div class="perm-summary-item">
            <div class="perm-summary-num"><?php echo count($permLabels) - count($userPerms); ?></div>
            <div class="perm-summary-label">Sem Acesso</div>
          </div>
          <div class="perm-summary-item">
            <div class="perm-summary-num"><?php echo count($permLabels); ?></div>
            <div class="perm-summary-label">Total no Sistema</div>
          </div>
        </div>
        <?php
        $groups = [
            'Clientes'          => ['view_clients','create_clients','edit_clients','delete_clients'],
            'Financeiro'        => ['view_finance','manage_finance','view_contas_pagar','create_contas_pagar','edit_contas_pagar','delete_contas_pagar','view_contas_receber','create_contas_receber','edit_contas_receber','delete_contas_receber'],
            'Faturamento'       => ['view_faturamento','view_notas_fiscais','create_notas_fiscais','edit_notas_fiscais','delete_notas_fiscais','import_notas_fiscais'],
            'CRM'               => ['view_crm','manage_leads','manage_oportunidades'],
            'Fornecedores'      => ['view_fornecedores','create_fornecedores','edit_fornecedores','delete_fornecedores'],
            'Plano de Contas'   => ['view_plano_contas','create_plano_contas','edit_plano_contas','delete_plano_contas'],
            'Integracoes'       => ['view_integracoes','manage_integracoes'],
            'Usuarios e Config' => ['view_users','manage_users','view_settings','manage_settings'],
            'Perfil'            => ['view_profile','edit_profile'],
        ];
        foreach ($groups as $groupName => $perms):
        ?>
        <div class="perm-group">
          <div class="perm-group-title"><?php echo $groupName; ?></div>
          <div class="perm-grid">
            <?php foreach ($perms as $perm):
              $has  = in_array($perm, $userPerms);
              $info = $permLabels[$perm] ?? [$perm, 'fa-circle', 'secondary'];
            ?>
            <span class="perm-badge <?php echo $has ? 'has' : 'no'; ?>">
              <i class="fas <?php echo $info[1]; ?>" style="font-size:.65rem"></i>
              <?php echo $info[0]; ?>
            </span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<script>
function switchTab(tab, btn) {
  document.querySelectorAll('[id^="tab-"]').forEach(el => el.style.display = 'none');
  document.getElementById('tab-' + tab).style.display = 'block';
  document.querySelectorAll('.prf-tab').forEach(el => el.classList.remove('active'));
  btn.classList.add('active');
  const url = new URL(window.location);
  url.searchParams.set('tab', tab);
  window.history.replaceState({}, '', url);
}
function togglePwd(id, btn) {
  const input = document.getElementById(id);
  const isText = input.type === 'text';
  input.type = isText ? 'password' : 'text';
  btn.innerHTML = isText ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
}
function checkStrength(pwd) {
  let score = 0;
  if (pwd.length >= 6)  score += 20;
  if (pwd.length >= 10) score += 20;
  if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) score += 25;
  if (/[0-9]/.test(pwd)) score += 20;
  if (/[^a-zA-Z0-9]/.test(pwd)) score += 15;
  const bar = document.getElementById('strengthBar');
  const label = document.getElementById('strengthLabel');
  bar.style.width = score + '%';
  if (score <= 25)      { bar.style.background = '#ef4444'; label.textContent = 'Forca: Fraca';  label.style.color = '#ef4444'; }
  else if (score <= 50) { bar.style.background = '#f59e0b'; label.textContent = 'Forca: Media';  label.style.color = '#f59e0b'; }
  else if (score <= 75) { bar.style.background = '#3b82f6'; label.textContent = 'Forca: Boa';    label.style.color = '#3b82f6'; }
  else                  { bar.style.background = '#10b981'; label.textContent = 'Forca: Forte';  label.style.color = '#10b981'; }
}
function checkMatch() {
  const np = document.getElementById('newPassword').value;
  const cp = document.getElementById('confirmPassword').value;
  const msg = document.getElementById('matchMsg');
  if (!cp) { msg.textContent = ''; return; }
  if (np === cp) {
    msg.innerHTML = '<span style="color:#10b981"><i class="fas fa-check-circle me-1"></i>Senhas coincidem</span>';
    document.getElementById('confirmPassword').classList.remove('is-invalid');
    document.getElementById('confirmPassword').classList.add('is-valid');
  } else {
    msg.innerHTML = '<span style="color:#ef4444"><i class="fas fa-times-circle me-1"></i>Senhas nao coincidem</span>';
    document.getElementById('confirmPassword').classList.add('is-invalid');
    document.getElementById('confirmPassword').classList.remove('is-valid');
  }
}
document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const error   = urlParams.get('error');
  const success = urlParams.get('success');
  const tab     = urlParams.get('tab') || 'geral';
  const errorMsgs = {
    missing_fields:        'Preencha todos os campos obrigatorios.',
    email_exists:          'Este e-mail ja esta em uso.',
    update_failed:         'Falha ao atualizar. Tente novamente.',
    unauthorized:          'Acao nao autorizada.',
    exception:             'Erro inesperado. Tente novamente.',
    password_mismatch:     'As senhas nao coincidem.',
    password_too_short:    'A nova senha deve ter pelo menos 6 caracteres.',
    wrong_current_password:'Senha atual incorreta.',
  };
  const successMsgs = {
    profile_updated: 'Perfil atualizado com sucesso!',
    password_changed:'Senha alterada com sucesso!',
  };
  const container = tab === 'seguranca' ? document.getElementById('alertSenha') : document.getElementById('alertGeral');
  function showAlert(msg, type) {
    const div = document.createElement('div');
    div.className = 'alert alert-' + (type === 'error' ? 'danger' : 'success') + ' alert-dismissible fade show m-3';
    div.innerHTML = '<i class="fas fa-' + (type === 'error' ? 'exclamation-triangle' : 'check-circle') + ' me-2"></i>' + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    container.appendChild(div);
    setTimeout(() => div.remove(), 6000);
  }
  if (error && errorMsgs[error])        showAlert(errorMsgs[error], 'error');
  if (success && successMsgs[success])  showAlert(successMsgs[success], 'success');
});
</script>

<?php require_once dirname(__DIR__) . '/layout/erp_footer.php'; ?>
