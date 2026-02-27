<?php
use App\Core\View;
require_once dirname(__DIR__, 2) . '/layout/erp_header.php';
$currentUser = $currentUser ?? null;
?>
<style>
.usr-wrap{max-width:700px;margin:0 auto;padding:1.5rem}
.usr-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.usr-card-header{background:linear-gradient(135deg,#00529B,#0284c7);color:#fff;padding:1.5rem 2rem}
.usr-card-header h1{font-size:1.25rem;font-weight:700;margin:0 0 .25rem}
.usr-card-header p{margin:0;opacity:.85;font-size:.875rem}
.usr-body{padding:2rem}
.usr-footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:1.25rem 2rem;display:flex;justify-content:space-between;align-items:center}
.form-section{margin-bottom:1.75rem}
.form-section-title{font-size:.9375rem;font-weight:600;color:#1e293b;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.5rem}
.form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
@media(max-width:600px){.form-grid-2{grid-template-columns:1fr}}
.form-label.required::after{content:" *";color:#ef4444}
.role-card{border:2px solid #e2e8f0;border-radius:.5rem;padding:.875rem;cursor:pointer;transition:all .2s}
.role-card:hover{border-color:#00529B;background:#f0f7ff}
.role-card.selected{border-color:#00529B;background:#e0f2fe}
.role-card input[type=radio]{display:none}
.role-card-title{font-weight:600;font-size:.875rem;color:#1e293b;margin-bottom:.25rem}
.role-card-desc{font-size:.75rem;color:#64748b}
.alert-feedback{padding:.875rem 1rem;border-radius:.5rem;margin-bottom:1rem;font-size:.875rem;display:flex;align-items:center;gap:.5rem}
</style>

<div class="main-content">
<div class="usr-wrap">
  <div class="usr-card">
    <div class="usr-card-header">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <h1><i class="fas fa-user-plus me-2"></i>Novo Usuário</h1>
          <p>Cadastre um novo usuário com acesso ao ERP InLaudo</p>
        </div>
        <a href="/configuracoes?tab=usuarios" class="btn btn-sm btn-light"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
      </div>
    </div>

    <div class="usr-body">
      <div id="alertContainer"></div>

      <form id="createUserForm" action="/configuracoes/usuarios" method="POST">
        <?php echo View::csrfField(); ?>

        <!-- Dados Pessoais -->
        <div class="form-section">
          <h2 class="form-section-title"><i class="fas fa-user text-primary"></i> Dados do Usuário</h2>
          <div class="form-grid-2">
            <div class="form-group">
              <label for="name" class="form-label required">Nome Completo</label>
              <input type="text" name="name" id="name" class="form-control" required
                     placeholder="Ex: João da Silva" minlength="3">
            </div>
            <div class="form-group">
              <label for="email" class="form-label required">E-mail</label>
              <input type="email" name="email" id="email" class="form-control" required
                     placeholder="usuario@empresa.com">
              <small class="text-muted">Usado para login e comunicações</small>
            </div>
          </div>
        </div>

        <!-- Perfil de Acesso -->
        <div class="form-section">
          <h2 class="form-section-title"><i class="fas fa-shield-alt text-primary"></i> Perfil de Acesso</h2>
          <div class="row g-2">
            <?php
            $roles = [
                'operador'   => ['label' => 'Operador',      'desc' => 'Acesso ao CRM e cadastros de clientes', 'icon' => 'fa-user'],
                'financeiro' => ['label' => 'Financeiro',    'desc' => 'Acesso ao módulo financeiro e faturamento', 'icon' => 'fa-wallet'],
                'leitura'    => ['label' => 'Leitura',       'desc' => 'Visualização apenas, sem edição', 'icon' => 'fa-eye'],
                'admin'      => ['label' => 'Administrador', 'desc' => 'Acesso completo exceto superadmin', 'icon' => 'fa-user-shield'],
            ];
            if ($currentUser && $currentUser->role === 'superadmin') {
                $roles['superadmin'] = ['label' => 'Super Admin', 'desc' => 'Acesso total ao sistema', 'icon' => 'fa-crown'];
            }
            foreach ($roles as $key => $info):
            ?>
            <div class="col-md-6">
              <label class="role-card" onclick="selectRole(this, '<?php echo $key; ?>')">
                <input type="radio" name="role" value="<?php echo $key; ?>" <?php echo $key === 'operador' ? 'checked' : ''; ?>>
                <div class="d-flex align-items-center gap-2">
                  <i class="fas <?php echo $info['icon']; ?> text-primary"></i>
                  <div>
                    <div class="role-card-title"><?php echo $info['label']; ?></div>
                    <div class="role-card-desc"><?php echo $info['desc']; ?></div>
                  </div>
                </div>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Configurações -->
        <div class="form-section">
          <h2 class="form-section-title"><i class="fas fa-cog text-primary"></i> Configurações</h2>
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Status Inicial</label>
              <select name="status" class="form-select">
                <option value="ativo">Ativo</option>
                <option value="inativo">Inativo</option>
              </select>
            </div>
            <div class="form-group d-flex align-items-end">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="send_welcome" name="send_welcome" value="1" checked>
                <label class="form-check-label" for="send_welcome">
                  <strong>Enviar e-mail de boas-vindas</strong><br>
                  <small class="text-muted">Com link para definição de senha</small>
                </label>
              </div>
            </div>
          </div>
        </div>

      </form>
    </div>

    <div class="usr-footer">
      <a href="/configuracoes?tab=usuarios" class="btn btn-light">Cancelar</a>
      <button type="submit" form="createUserForm" class="btn btn-primary">
        <i class="fas fa-user-plus me-1"></i> Criar Usuário
      </button>
    </div>
  </div>
</div>
</div>

<script>
function selectRole(label, role) {
  document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
  label.classList.add('selected');
}
// Marca o primeiro como selecionado
document.querySelector('.role-card')?.classList.add('selected');

// Alertas por URL
const urlParams = new URLSearchParams(window.location.search);
const error = urlParams.get('error');
if (error) {
  const msgs = {
    missing_fields: 'Preencha todos os campos obrigatórios.',
    email_exists:   'Este e-mail já está em uso.',
    invalid_role:   'Você não tem permissão para atribuir este perfil.',
    create_failed:  'Falha ao criar usuário. Tente novamente.',
    exception:      'Erro inesperado. Tente novamente.',
  };
  const div = document.createElement('div');
  div.className = 'alert alert-danger alert-dismissible fade show';
  div.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${msgs[error] || 'Erro desconhecido.'}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
  document.getElementById('alertContainer').appendChild(div);
}
</script>

<?php require_once dirname(__DIR__, 2) . '/layout/erp_footer.php'; ?>
