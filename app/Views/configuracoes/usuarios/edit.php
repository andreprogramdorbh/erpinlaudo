<?php
use App\Core\View;
require_once dirname(__DIR__, 2) . '/layout/erp_header.php';
$usuario     = $usuario ?? null;
$currentUser = $currentUser ?? null;
?>
<style>
.usr-wrap{padding:1.5rem;width:100%}
.usr-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.usr-card-header{background:linear-gradient(135deg,#0f766e,#059669);color:#fff;padding:1.5rem 2rem}
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
.role-card:hover{border-color:#059669;background:#f0fdf4}
.role-card.selected{border-color:#059669;background:#d1fae5}
.role-card input[type=radio]{display:none}
.role-card-title{font-weight:600;font-size:.875rem;color:#1e293b;margin-bottom:.25rem}
.role-card-desc{font-size:.75rem;color:#64748b}
.user-info-bar{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:.5rem;padding:.875rem 1rem;display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem}
.user-info-avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#059669,#10b981);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:700;flex-shrink:0}
</style>

<div class="usr-wrap">
  <div class="usr-card">
    <div class="usr-card-header">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <h1><i class="fas fa-user-edit me-2"></i>Editar Usuário</h1>
          <p>Atualize os dados e permissões do usuário</p>
        </div>
        <a href="/configuracoes?tab=usuarios" class="btn btn-sm btn-light"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
      </div>
    </div>

    <div class="usr-body">
      <div id="alertContainer"></div>

      <!-- Info do usuário sendo editado -->
      <?php
        $initials = strtoupper(substr($usuario->name, 0, 1) . (strpos($usuario->name, ' ') !== false ? substr($usuario->name, strpos($usuario->name, ' ') + 1, 1) : ''));
      ?>
      <div class="user-info-bar">
        <div class="user-info-avatar"><?php echo $initials; ?></div>
        <div>
          <div style="font-weight:600;color:#1e293b"><?php echo htmlspecialchars($usuario->name); ?></div>
          <div style="font-size:.75rem;color:#64748b"><?php echo htmlspecialchars($usuario->email); ?> &mdash; Cadastrado em <?php echo date('d/m/Y', strtotime($usuario->created_at ?? 'now')); ?></div>
        </div>
      </div>

      <form id="editUserForm" action="/configuracoes/usuarios/update/<?php echo $usuario->id; ?>" method="POST">
        <?php echo View::csrfField(); ?>

        <!-- Dados Pessoais -->
        <div class="form-section">
          <h2 class="form-section-title"><i class="fas fa-user text-success"></i> Dados do Usuário</h2>
          <div class="form-grid-2">
            <div class="form-group">
              <label for="name" class="form-label required">Nome Completo</label>
              <input type="text" name="name" id="name" class="form-control" required
                     value="<?php echo htmlspecialchars($usuario->name); ?>" minlength="3">
            </div>
            <div class="form-group">
              <label for="email" class="form-label required">E-mail</label>
              <input type="email" name="email" id="email" class="form-control" required
                     value="<?php echo htmlspecialchars($usuario->email); ?>">
            </div>
          </div>
        </div>

        <!-- Perfil de Acesso -->
        <div class="form-section">
          <h2 class="form-section-title"><i class="fas fa-shield-alt text-success"></i> Perfil de Acesso</h2>
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
              $isSelected = $usuario->role === $key;
            ?>
            <div class="col-md-6">
              <label class="role-card <?php echo $isSelected ? 'selected' : ''; ?>" onclick="selectRole(this, '<?php echo $key; ?>')">
                <input type="radio" name="role" value="<?php echo $key; ?>" <?php echo $isSelected ? 'checked' : ''; ?>>
                <div class="d-flex align-items-center gap-2">
                  <i class="fas <?php echo $info['icon']; ?> text-success"></i>
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

        <!-- Status -->
        <div class="form-section">
          <h2 class="form-section-title"><i class="fas fa-toggle-on text-success"></i> Status da Conta</h2>
          <div style="max-width:250px">
            <select name="status" class="form-select">
              <option value="ativo" <?php echo ($usuario->status ?? 'ativo') === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
              <option value="inativo" <?php echo ($usuario->status ?? '') === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
            </select>
          </div>
        </div>

      </form>
    </div>

    <div class="usr-footer">
      <a href="/configuracoes?tab=usuarios" class="btn btn-light">Cancelar</a>
      <button type="submit" form="editUserForm" class="btn btn-success">
        <i class="fas fa-save me-1"></i> Salvar Alterações
      </button>
    </div>
  </div>
</div>

<script>
function selectRole(label, role) {
  document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
  label.classList.add('selected');
}

const urlParams = new URLSearchParams(window.location.search);
const error = urlParams.get('error');
if (error) {
  const msgs = {
    missing_fields: 'Preencha todos os campos obrigatórios.',
    email_exists:   'Este e-mail já está em uso.',
    invalid_role:   'Você não tem permissão para atribuir este perfil.',
    update_failed:  'Falha ao atualizar. Tente novamente.',
    exception:      'Erro inesperado. Tente novamente.',
  };
  const div = document.createElement('div');
  div.className = 'alert alert-danger alert-dismissible fade show';
  div.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${msgs[error] || 'Erro desconhecido.'}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
  document.getElementById('alertContainer').appendChild(div);
}
</script>

<?php require_once dirname(__DIR__, 2) . '/layout/erp_footer.php'; ?>
