<?php
use App\Core\Auth;
use App\Core\View;
require_once dirname(__DIR__) . '/layout/erp_header.php';

$activeTab   = $activeTab ?? 'geral';
$currentUser = $currentUser ?? Auth::user();
$usuarios    = $usuarios ?? [];

$roleLabels = [
    'superadmin' => ['label' => 'Super Admin',   'color' => 'danger'],
    'admin'      => ['label' => 'Administrador', 'color' => 'warning'],
    'financeiro' => ['label' => 'Financeiro',    'color' => 'info'],
    'operador'   => ['label' => 'Operador',      'color' => 'primary'],
    'leitura'    => ['label' => 'Leitura',       'color' => 'secondary'],
    'user'       => ['label' => 'Usuário',       'color' => 'secondary'],
];
?>
<style>
/* ===== Configurações — Layout ===== */
.cfg-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.cfg-header{margin-bottom:1.5rem}
.cfg-header h1{font-size:1.5rem;font-weight:700;color:#1e293b;margin:0 0 .25rem}
.cfg-header p{color:#64748b;margin:0;font-size:.9rem}

/* Abas */
.cfg-tabs{display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:1.5rem}
.cfg-tab{padding:.75rem 1.5rem;cursor:pointer;font-size:.875rem;font-weight:500;color:#64748b;border-bottom:2px solid transparent;margin-bottom:-2px;display:flex;align-items:center;gap:.5rem;transition:all .2s;background:none;border-top:none;border-left:none;border-right:none}
.cfg-tab:hover{color:#00529B}
.cfg-tab.active{color:#00529B;border-bottom-color:#00529B;font-weight:600}

/* Cards */
.cfg-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.cfg-card-header{padding:1rem 1.5rem;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem}
.cfg-card-title{font-size:1rem;font-weight:600;color:#1e293b;display:flex;align-items:center;gap:.5rem;margin:0}

/* Alertas */
.cfg-alert{padding:.875rem 1.25rem;border-radius:.5rem;margin-bottom:1rem;display:flex;align-items:center;gap:.75rem;font-size:.875rem}
.cfg-alert-success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
.cfg-alert-danger{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}

/* Tabela de usuários */
.user-table{width:100%;border-collapse:collapse;font-size:.875rem}
.user-table th{background:#f8fafc;padding:.75rem 1rem;text-align:left;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b;border-bottom:1px solid #e2e8f0}
.user-table td{padding:.875rem 1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.user-table tr:last-child td{border-bottom:none}
.user-table tr:hover td{background:#f8fafc}
.user-avatar-sm{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#00529B,#0284c7);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.875rem;font-weight:700;flex-shrink:0}
.user-name-cell{display:flex;align-items:center;gap:.75rem}
.user-name-text{font-weight:600;color:#1e293b}
.user-email-text{font-size:.75rem;color:#64748b}
.badge-role{font-size:.7rem;padding:.3em .7em;border-radius:20px;font-weight:600}
.badge-status{font-size:.7rem;padding:.3em .7em;border-radius:20px;font-weight:600}
.action-cell{display:flex;gap:.35rem;justify-content:flex-end}
.action-cell .btn{padding:.25rem .55rem;font-size:.75rem;border-radius:.4rem}
.you-badge{font-size:.65rem;background:#e0f2fe;color:#0284c7;padding:.15em .5em;border-radius:10px;font-weight:600;margin-left:.35rem}

/* Seção Geral */
.cfg-section{padding:1.5rem}
.cfg-section-title{font-size:.9375rem;font-weight:600;color:#1e293b;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.5rem}
.cfg-info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem}
.cfg-info-item{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.5rem;padding:1rem}
.cfg-info-label{font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:.35rem}
.cfg-info-value{font-size:.9375rem;font-weight:600;color:#1e293b}
</style>

<div class="main-content">
<div class="cfg-wrap">

  <!-- Header -->
  <div class="cfg-header">
    <h1><i class="fas fa-cog me-2" style="color:#00529B"></i>Configurações</h1>
    <p>Gerencie as configurações do sistema e os usuários com acesso ao ERP</p>
  </div>

  <!-- Alertas de feedback -->
  <?php
  $success = $_GET['success'] ?? '';
  $error   = $_GET['error']   ?? '';
  $successMessages = [
      'user_created'   => 'Usuário criado com sucesso!',
      'user_updated'   => 'Usuário atualizado com sucesso!',
      'password_reset' => 'E-mail de redefinição de senha enviado com sucesso!',
  ];
  $errorMessages = [
      'unauthorized'  => 'Você não tem permissão para esta ação.',
      'cannot_edit'   => 'Você não pode editar este usuário.',
      'cannot_reset'  => 'Você não pode resetar a senha deste usuário.',
      'reset_failed'  => 'Falha ao enviar e-mail de redefinição.',
  ];
  if ($success && isset($successMessages[$success])): ?>
  <div class="cfg-alert cfg-alert-success"><i class="fas fa-check-circle"></i> <?php echo $successMessages[$success]; ?></div>
  <?php endif; ?>
  <?php if ($error && isset($errorMessages[$error])): ?>
  <div class="cfg-alert cfg-alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $errorMessages[$error]; ?></div>
  <?php endif; ?>

  <!-- Abas -->
  <div class="cfg-tabs">
    <button class="cfg-tab <?php echo $activeTab === 'geral' ? 'active' : ''; ?>" onclick="switchTab('geral', this)">
      <i class="fas fa-sliders-h"></i> Geral
    </button>
    <?php if (Auth::can('manage_users')): ?>
    <button class="cfg-tab <?php echo $activeTab === 'usuarios' ? 'active' : ''; ?>" onclick="switchTab('usuarios', this)">
      <i class="fas fa-users-cog"></i> Usuários
      <span class="badge bg-secondary rounded-pill ms-1" style="font-size:.65rem"><?php echo count($usuarios); ?></span>
    </button>
    <?php endif; ?>
  </div>

  <!-- ===== ABA: GERAL ===== -->
  <div id="tab-geral" style="display:<?php echo $activeTab === 'geral' ? 'block' : 'none'; ?>">
    <div class="cfg-card">
      <div class="cfg-section">
        <h2 class="cfg-section-title"><i class="fas fa-info-circle text-primary"></i> Informações do Sistema</h2>
        <div class="cfg-info-grid">
          <div class="cfg-info-item">
            <div class="cfg-info-label">Versão</div>
            <div class="cfg-info-value">InLaudo ERP v1.0.2</div>
          </div>
          <div class="cfg-info-item">
            <div class="cfg-info-label">Ambiente</div>
            <div class="cfg-info-value"><?php echo php_uname('n'); ?></div>
          </div>
          <div class="cfg-info-item">
            <div class="cfg-info-label">PHP</div>
            <div class="cfg-info-value"><?php echo PHP_VERSION; ?></div>
          </div>
          <div class="cfg-info-item">
            <div class="cfg-info-label">Usuário Logado</div>
            <div class="cfg-info-value"><?php echo htmlspecialchars($currentUser->name ?? '—'); ?></div>
          </div>
          <div class="cfg-info-item">
            <div class="cfg-info-label">Perfil de Acesso</div>
            <div class="cfg-info-value"><?php echo $roleLabels[$currentUser->role ?? 'user']['label'] ?? ucfirst($currentUser->role ?? ''); ?></div>
          </div>
          <div class="cfg-info-item">
            <div class="cfg-info-label">Data/Hora do Servidor</div>
            <div class="cfg-info-value"><?php echo date('d/m/Y H:i:s'); ?></div>
          </div>
        </div>
      </div>

      <div class="cfg-section" style="border-top:1px solid #f1f5f9">
        <h2 class="cfg-section-title"><i class="fas fa-plug text-primary"></i> Integrações Disponíveis</h2>
        <div class="d-flex gap-2 flex-wrap">
          <a href="/integracao/asaas" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-credit-card me-1"></i> Asaas — Pagamentos
          </a>
          <a href="/integracao/email" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-envelope me-1"></i> E-mail (SMTP)
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== ABA: USUÁRIOS ===== -->
  <?php if (Auth::can('manage_users')): ?>
  <div id="tab-usuarios" style="display:<?php echo $activeTab === 'usuarios' ? 'block' : 'none'; ?>">
    <div class="cfg-card">
      <div class="cfg-card-header">
        <h2 class="cfg-card-title"><i class="fas fa-users-cog text-primary"></i> Gerenciamento de Usuários</h2>
        <a href="/configuracoes/usuarios/create" class="btn btn-sm btn-primary">
          <i class="fas fa-user-plus me-1"></i> Novo Usuário
        </a>
      </div>

      <?php if (empty($usuarios)): ?>
      <div class="text-center py-5 text-muted">
        <i class="fas fa-users-slash fa-2x mb-2 d-block"></i>
        Nenhum usuário cadastrado.
      </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="user-table">
          <thead>
            <tr>
              <th class="ps-3">Usuário</th>
              <th>Perfil</th>
              <th>Status</th>
              <th>Criado em</th>
              <th class="text-end pe-3">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios as $u): ?>
            <?php
              $isMe    = $u->id == $currentUser->id;
              $rInfo   = $roleLabels[$u->role] ?? ['label' => ucfirst($u->role), 'color' => 'secondary'];
              $status  = $u->status ?? 'ativo';
              $initials = strtoupper(substr($u->name, 0, 1) . (strpos($u->name, ' ') !== false ? substr($u->name, strpos($u->name, ' ') + 1, 1) : ''));
            ?>
            <tr id="user-row-<?php echo $u->id; ?>">
              <td class="ps-3">
                <div class="user-name-cell">
                  <div class="user-avatar-sm"><?php echo $initials; ?></div>
                  <div>
                    <div class="user-name-text">
                      <?php echo htmlspecialchars($u->name); ?>
                      <?php if ($isMe): ?><span class="you-badge">Você</span><?php endif; ?>
                    </div>
                    <div class="user-email-text"><?php echo htmlspecialchars($u->email); ?></div>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge-role bg-<?php echo $rInfo['color']; ?>-subtle text-<?php echo $rInfo['color']; ?>">
                  <?php echo $rInfo['label']; ?>
                </span>
              </td>
              <td>
                <span class="badge-status <?php echo $status === 'ativo' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'; ?>"
                      id="status-badge-<?php echo $u->id; ?>">
                  <?php echo $status === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                </span>
              </td>
              <td><?php echo date('d/m/Y', strtotime($u->created_at ?? 'now')); ?></td>
              <td class="text-end pe-3">
                <div class="action-cell">
                  <?php
              $canManage = ($currentUser->role === 'superadmin') || ($currentUser->role === 'admin' && !in_array($u->role, ['admin','superadmin']));
              if (!$isMe && $canManage):
              ?>
                  <a href="/configuracoes/usuarios/edit/<?php echo $u->id; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                    <i class="fas fa-edit"></i>
                  </a>
                  <button type="button" class="btn btn-sm btn-outline-warning" title="Resetar Senha"
                          onclick="confirmResetPassword(<?php echo $u->id; ?>, '<?php echo htmlspecialchars($u->name, ENT_QUOTES); ?>')">
                    <i class="fas fa-key"></i>
                  </button>
                  <button type="button" class="btn btn-sm <?php echo $status === 'ativo' ? 'btn-outline-secondary' : 'btn-outline-success'; ?>"
                          title="<?php echo $status === 'ativo' ? 'Desativar' : 'Ativar'; ?>"
                          onclick="toggleStatus(<?php echo $u->id; ?>, this)">
                    <i class="fas <?php echo $status === 'ativo' ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                  </button>
                  <?php else: ?>
                  <span class="text-muted" style="font-size:.75rem">—</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Legenda de perfis -->
      <div style="padding:1rem 1.5rem;border-top:1px solid #f1f5f9;background:#f8fafc">
        <div style="font-size:.75rem;color:#64748b;font-weight:600;margin-bottom:.5rem">PERFIS DE ACESSO</div>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($roleLabels as $key => $info): ?>
          <span class="badge-role bg-<?php echo $info['color']; ?>-subtle text-<?php echo $info['color']; ?>">
            <?php echo $info['label']; ?>
          </span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
  <?php endif; ?>

</div>
</div>

<!-- Form oculto para reset de senha -->
<form id="resetPasswordForm" method="POST" style="display:none">
  <?php echo View::csrfField(); ?>
</form>

<script>
function switchTab(tab, btn) {
  document.querySelectorAll('[id^="tab-"]').forEach(el => el.style.display = 'none');
  document.getElementById('tab-' + tab).style.display = 'block';
  document.querySelectorAll('.cfg-tab').forEach(el => el.classList.remove('active'));
  btn.classList.add('active');
  // Atualiza URL sem recarregar
  const url = new URL(window.location);
  url.searchParams.set('tab', tab);
  window.history.replaceState({}, '', url);
}

function confirmResetPassword(userId, userName) {
  if (!confirm('Deseja realmente resetar a senha de "' + userName + '"?\n\nUm e-mail com link de redefinição será enviado.')) return;
  const form = document.getElementById('resetPasswordForm');
  form.action = '/configuracoes/usuarios/reset-password/' + userId;
  form.submit();
}

function toggleStatus(userId, btn) {
  const token = document.querySelector('input[name="_token"]')?.value || '';
  const form  = new FormData();
  form.append('_token', token);

  fetch('/configuracoes/usuarios/toggle-status/' + userId, { method: 'POST', body: form })
    .then(r => r.json())
    .then(res => {
      if (!res.success) { alert('Erro ao alterar status.'); return; }
      const badge = document.getElementById('status-badge-' + userId);
      if (res.status === 'ativo') {
        badge.textContent = 'Ativo';
        badge.className = 'badge-status bg-success-subtle text-success';
        btn.className = 'btn btn-sm btn-outline-secondary';
        btn.title = 'Desativar';
        btn.innerHTML = '<i class="fas fa-user-slash"></i>';
      } else {
        badge.textContent = 'Inativo';
        badge.className = 'badge-status bg-secondary-subtle text-secondary';
        btn.className = 'btn btn-sm btn-outline-success';
        btn.title = 'Ativar';
        btn.innerHTML = '<i class="fas fa-user-check"></i>';
      }
    })
    .catch(() => alert('Erro de conexão.'));
}
</script>

<?php require_once dirname(__DIR__) . '/layout/erp_footer.php'; ?>
