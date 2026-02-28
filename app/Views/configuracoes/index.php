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
.cfg-wrap{padding:1.5rem;width:100%}
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
    <?php if (Auth::can('manage_settings')): ?>
    <button class="cfg-tab <?php echo $activeTab === 'notas-fiscais' ? 'active' : ''; ?>" onclick="switchTab('notas-fiscais', this)">
      <i class="fas fa-file-invoice"></i> Notas Fiscais
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

  <!-- ===== ABA: NOTAS FISCAIS ===== -->
  <?php if (Auth::can('manage_settings')): ?>
  <div id="tab-notas-fiscais" style="display:<?php echo $activeTab === 'notas-fiscais' ? 'block' : 'none'; ?>">
    <div class="cfg-card">
      <div class="cfg-card-header">
        <h2 class="cfg-card-title"><i class="fas fa-file-invoice text-primary"></i> Configurações de Emissão de NFS-e</h2>
        <span class="badge" style="background:#6f42c1;color:#fff;font-size:.75rem;padding:.35rem .75rem;border-radius:20px">
          <i class="fas fa-globe me-1"></i> Portal Nacional NFS-e
        </span>
      </div>

      <?php
      $nfsConfig  = $configNfs ?? null;
      $layoutTipo = $nfsConfig->layout_tipo ?? 'padrao';
      ?>

      <!-- Alerta informativo -->
      <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:1rem;margin-bottom:1.5rem;display:flex;gap:.75rem;align-items:flex-start">
        <i class="fas fa-info-circle" style="color:#3b82f6;margin-top:2px"></i>
        <div style="font-size:.875rem;color:#1e40af">
          <strong>NFS-e Nacional (Portal Nacional)</strong> &mdash; A emissão é realizada via Asaas integrado ao Portal Nacional da SEFAZ.
          As informações fiscais (CNPJ <strong>31.865.440/0001-35</strong>, inscrição municipal e código de serviço) já estão configuradas no painel Asaas.
          Aqui você define apenas o <strong>padrão de emissão</strong> que o sistema usará automaticamente.
        </div>
      </div>

      <form method="POST" action="/configuracoes/nfs/salvar" id="formNfs">
        <?php echo View::csrfField(); ?>

        <!-- Seleção do tipo de layout -->
        <div style="margin-bottom:1.5rem">
          <label style="font-weight:600;font-size:.9rem;color:#374151;display:block;margin-bottom:.75rem">
            <i class="fas fa-layer-group me-1 text-primary"></i> Tipo de Layout de Emissão
          </label>
          <div style="display:flex;gap:1rem;flex-wrap:wrap">

            <!-- Layout Padrão -->
            <label style="flex:1;min-width:240px;cursor:pointer">
              <input type="radio" name="layout_tipo" value="padrao" id="layout_padrao"
                     <?php echo $layoutTipo === 'padrao' ? 'checked' : ''; ?>
                     onchange="toggleLayoutPanel()" style="display:none">
              <div class="layout-card" id="card_padrao" style="border:2px solid <?php echo $layoutTipo === 'padrao' ? '#00529B' : '#e2e8f0'; ?>;border-radius:10px;padding:1.25rem;background:<?php echo $layoutTipo === 'padrao' ? '#eff6ff' : '#fff'; ?>;transition:all .2s">
                <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem">
                  <div style="width:36px;height:36px;border-radius:8px;background:#00529B;display:flex;align-items:center;justify-content:center">
                    <i class="fas fa-bolt" style="color:#fff;font-size:.9rem"></i>
                  </div>
                  <div>
                    <div style="font-weight:700;color:#1e293b">Layout Padrão</div>
                    <div style="font-size:.75rem;color:#64748b">Simples e rápido</div>
                  </div>
                  <?php if ($layoutTipo === 'padrao'): ?>
                  <span style="margin-left:auto;background:#00529B;color:#fff;font-size:.65rem;padding:.2rem .6rem;border-radius:20px">ATIVO</span>
                  <?php endif; ?>
                </div>
                <p style="font-size:.8rem;color:#64748b;margin:0">
                  O sistema envia automaticamente <strong>valor</strong>, <strong>data</strong> e a <strong>descrição padrão</strong> configurada abaixo.
                  Ideal para serviços com padrão fixo (ex: Serviços de Laudo).
                </p>
              </div>
            </label>

            <!-- Layout Personalizado -->
            <label style="flex:1;min-width:240px;cursor:pointer">
              <input type="radio" name="layout_tipo" value="personalizado" id="layout_personalizado"
                     <?php echo $layoutTipo === 'personalizado' ? 'checked' : ''; ?>
                     onchange="toggleLayoutPanel()" style="display:none">
              <div class="layout-card" id="card_personalizado" style="border:2px solid <?php echo $layoutTipo === 'personalizado' ? '#6f42c1' : '#e2e8f0'; ?>;border-radius:10px;padding:1.25rem;background:<?php echo $layoutTipo === 'personalizado' ? '#f5f3ff' : '#fff'; ?>;transition:all .2s">
                <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem">
                  <div style="width:36px;height:36px;border-radius:8px;background:#6f42c1;display:flex;align-items:center;justify-content:center">
                    <i class="fas fa-code" style="color:#fff;font-size:.9rem"></i>
                  </div>
                  <div>
                    <div style="font-weight:700;color:#1e293b">Layout Personalizado</div>
                    <div style="font-size:.75rem;color:#64748b">Controle total do JSON</div>
                  </div>
                  <?php if ($layoutTipo === 'personalizado'): ?>
                  <span style="margin-left:auto;background:#6f42c1;color:#fff;font-size:.65rem;padding:.2rem .6rem;border-radius:20px">ATIVO</span>
                  <?php endif; ?>
                </div>
                <p style="font-size:.8rem;color:#64748b;margin:0">
                  Você define um <strong>template JSON</strong> completo. O sistema substitui as variáveis dinâmicas
                  (<code>{{value}}</code>, <code>{{date}}</code>, <code>{{payment_id}}</code>) automaticamente.
                </p>
              </div>
            </label>

          </div>
        </div>

        <!-- Painel: Layout Padrão -->
        <div id="panel_padrao" style="display:<?php echo $layoutTipo === 'padrao' ? 'block' : 'none'; ?>">
          <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1.25rem;margin-bottom:1rem">
            <h3 style="font-size:.9rem;font-weight:700;color:#374151;margin-bottom:1rem">
              <i class="fas fa-cog me-1 text-primary"></i> Campos Padrão da NFS-e
            </h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
              <div style="grid-column:1/-1">
                <label style="font-size:.8rem;font-weight:600;color:#374151">Descrição do Serviço *</label>
                <input type="text" name="service_description" class="form-control form-control-sm mt-1"
                       value="<?php echo htmlspecialchars($nfsConfig->service_description ?? 'SERVIÇOS DE LAUDO'); ?>"
                       placeholder="Ex: SERVIÇOS DE LAUDO" required>
              </div>
              <div>
                <label style="font-size:.8rem;font-weight:600;color:#374151">Nome do Serviço Municipal</label>
                <input type="text" name="municipal_service_name" class="form-control form-control-sm mt-1"
                       value="<?php echo htmlspecialchars($nfsConfig->municipal_service_name ?? 'Serviços de Saúde / Radiologia'); ?>"
                       placeholder="Ex: Serviços de Saúde">
              </div>
              <div>
                <label style="font-size:.8rem;font-weight:600;color:#374151">Código do Serviço Municipal (LC 116)</label>
                <input type="text" name="municipal_service_code" class="form-control form-control-sm mt-1"
                       value="<?php echo htmlspecialchars($nfsConfig->municipal_service_code ?? '4.03'); ?>"
                       placeholder="Ex: 4.03">
                <small style="font-size:.7rem;color:#94a3b8">Formato: X.XX (conforme lista da prefeitura)</small>
              </div>
              <div>
                <label style="font-size:.8rem;font-weight:600;color:#374151">CNAE</label>
                <input type="text" name="cnae" class="form-control form-control-sm mt-1"
                       value="<?php echo htmlspecialchars($nfsConfig->cnae ?? '8640205'); ?>"
                       placeholder="Ex: 8640205">
                <small style="font-size:.7rem;color:#94a3b8">Atividade Econômica (sem pontos/traços)</small>
              </div>
              <div>
                <label style="font-size:.8rem;font-weight:600;color:#374151">ID do Serviço Municipal (Asaas)</label>
                <input type="text" name="municipal_service_id" class="form-control form-control-sm mt-1"
                       value="<?php echo htmlspecialchars($nfsConfig->municipal_service_id ?? ''); ?>"
                       placeholder="Deixe em branco para usar o código acima">
                <small style="font-size:.7rem;color:#94a3b8">Obtido via API Asaas /invoices/municipalServices</small>
              </div>
              <div>
                <label style="font-size:.8rem;font-weight:600;color:#374151">Série da NF</label>
                <input type="text" name="serie_nf" class="form-control form-control-sm mt-1"
                       value="<?php echo htmlspecialchars($nfsConfig->serie_nf ?? ''); ?>"
                       placeholder="Ex: 80001 (Portal Nacional: 80000-89999)">
              </div>
              <div style="grid-column:1/-1">
                <label style="font-size:.8rem;font-weight:600;color:#374151">Observações (aparecerão na NF)</label>
                <textarea name="observations" class="form-control form-control-sm mt-1" rows="2"
                          placeholder="Ex: Serviço de laudo radiologico remoto"><?php echo htmlspecialchars($nfsConfig->observations ?? ''); ?></textarea>
              </div>
            </div>

            <!-- Tributos -->
            <h3 style="font-size:.9rem;font-weight:700;color:#374151;margin:1rem 0 .75rem">
              <i class="fas fa-percentage me-1 text-warning"></i> Tributos
            </h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.75rem">
              <div>
                <label style="font-size:.75rem;font-weight:600;color:#374151">ISS (%)</label>
                <input type="number" name="iss_aliquota" class="form-control form-control-sm mt-1" step="0.01" min="0" max="100"
                       value="<?php echo $nfsConfig->iss_aliquota ?? 0; ?>">
              </div>
              <div>
                <label style="font-size:.75rem;font-weight:600;color:#374151">PIS (%)</label>
                <input type="number" name="pis_aliquota" class="form-control form-control-sm mt-1" step="0.01" min="0" max="100"
                       value="<?php echo $nfsConfig->pis_aliquota ?? 0; ?>">
              </div>
              <div>
                <label style="font-size:.75rem;font-weight:600;color:#374151">COFINS (%)</label>
                <input type="number" name="cofins_aliquota" class="form-control form-control-sm mt-1" step="0.01" min="0" max="100"
                       value="<?php echo $nfsConfig->cofins_aliquota ?? 0; ?>">
              </div>
              <div>
                <label style="font-size:.75rem;font-weight:600;color:#374151">CSLL (%)</label>
                <input type="number" name="csll_aliquota" class="form-control form-control-sm mt-1" step="0.01" min="0" max="100"
                       value="<?php echo $nfsConfig->csll_aliquota ?? 0; ?>">
              </div>
              <div>
                <label style="font-size:.75rem;font-weight:600;color:#374151">INSS (%)</label>
                <input type="number" name="inss_aliquota" class="form-control form-control-sm mt-1" step="0.01" min="0" max="100"
                       value="<?php echo $nfsConfig->inss_aliquota ?? 0; ?>">
              </div>
              <div>
                <label style="font-size:.75rem;font-weight:600;color:#374151">IR (%)</label>
                <input type="number" name="ir_aliquota" class="form-control form-control-sm mt-1" step="0.01" min="0" max="100"
                       value="<?php echo $nfsConfig->ir_aliquota ?? 0; ?>">
              </div>
              <div>
                <label style="font-size:.75rem;font-weight:600;color:#374151">Deduções (R$)</label>
                <input type="number" name="deductions" class="form-control form-control-sm mt-1" step="0.01" min="0"
                       value="<?php echo $nfsConfig->deductions ?? 0; ?>">
              </div>
            </div>

            <div style="margin-top:.75rem">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="retain_iss" id="retain_iss" value="1"
                       <?php echo ($nfsConfig->retain_iss ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="retain_iss" style="font-size:.85rem">
                  Reter ISS na fonte
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Painel: Layout Personalizado -->
        <div id="panel_personalizado" style="display:<?php echo $layoutTipo === 'personalizado' ? 'block' : 'none'; ?>">
          <div style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:8px;padding:1.25rem;margin-bottom:1rem">
            <h3 style="font-size:.9rem;font-weight:700;color:#374151;margin-bottom:.5rem">
              <i class="fas fa-code me-1" style="color:#6f42c1"></i> Template JSON Personalizado
            </h3>
            <p style="font-size:.8rem;color:#64748b;margin-bottom:.75rem">
              Defina o JSON completo que será enviado ao Asaas. Use as variáveis abaixo para inserção dinâmica:
            </p>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.75rem">
              <code style="background:#ede9fe;color:#6f42c1;padding:.15rem .5rem;border-radius:4px;font-size:.75rem">{{value}}</code>
              <code style="background:#ede9fe;color:#6f42c1;padding:.15rem .5rem;border-radius:4px;font-size:.75rem">{{date}}</code>
              <code style="background:#ede9fe;color:#6f42c1;padding:.15rem .5rem;border-radius:4px;font-size:.75rem">{{payment_id}}</code>
              <code style="background:#ede9fe;color:#6f42c1;padding:.15rem .5rem;border-radius:4px;font-size:.75rem">{{customer_id}}</code>
              <code style="background:#ede9fe;color:#6f42c1;padding:.15rem .5rem;border-radius:4px;font-size:.75rem">{{description}}</code>
            </div>
            <textarea name="json_template" id="json_template" class="form-control form-control-sm" rows="12"
                      style="font-family:monospace;font-size:.8rem;background:#1e1e2e;color:#cdd6f4;border:1px solid #6f42c1"
                      placeholder='{
  "serviceDescription": "SERVIÇOS DE LAUDO",
  "value": {{value}},
  "effectiveDate": "{{date}}",
  "serviceCode": "4.03",
  "cnae": "8640205",
  "taxes": {
    "retainIss": false
  },
  "payment": "{{payment_id}}"
}'><?php echo htmlspecialchars($nfsConfig->json_template ?? ''); ?></textarea>
            <div style="display:flex;gap:.5rem;margin-top:.5rem">
              <button type="button" onclick="preencherExemplo()" class="btn btn-sm" style="background:#6f42c1;color:#fff;font-size:.75rem">
                <i class="fas fa-magic me-1"></i> Usar Exemplo
              </button>
              <button type="button" onclick="validarJson()" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem">
                <i class="fas fa-check me-1"></i> Validar JSON
              </button>
            </div>
            <div id="json_validation_msg" style="margin-top:.5rem;font-size:.8rem"></div>
          </div>
        </div>

        <!-- Botão Salvar -->
        <div style="display:flex;justify-content:flex-end;gap:.75rem;padding-top:1rem;border-top:1px solid #f1f5f9">
          <a href="/configuracoes?tab=notas-fiscais" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-undo me-1"></i> Cancelar
          </a>
          <button type="submit" class="btn btn-sm btn-primary">
            <i class="fas fa-save me-1"></i> Salvar Configurações de NFS-e
          </button>
        </div>

      </form>
    </div>
  </div>
  <?php endif; ?>

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

function toggleLayoutPanel() {
  const isPadrao = document.getElementById('layout_padrao').checked;
  document.getElementById('panel_padrao').style.display       = isPadrao ? 'block' : 'none';
  document.getElementById('panel_personalizado').style.display = isPadrao ? 'none' : 'block';
  // Atualiza visual dos cards
  document.getElementById('card_padrao').style.border          = isPadrao ? '2px solid #00529B' : '2px solid #e2e8f0';
  document.getElementById('card_padrao').style.background      = isPadrao ? '#eff6ff' : '#fff';
  document.getElementById('card_personalizado').style.border   = !isPadrao ? '2px solid #6f42c1' : '2px solid #e2e8f0';
  document.getElementById('card_personalizado').style.background = !isPadrao ? '#f5f3ff' : '#fff';
}

function preencherExemplo() {
  const exemplo = `{
  "serviceDescription": "SERVIÇOS DE LAUDO",
  "value": {{value}},
  "effectiveDate": "{{date}}",
  "municipalServiceCode": "4.03",
  "cnae": "8640205",
  "taxes": {
    "retainIss": false
  },
  "payment": "{{payment_id}}"
}`;
  document.getElementById('json_template').value = exemplo;
  document.getElementById('json_validation_msg').innerHTML = '';
}

function validarJson() {
  const raw = document.getElementById('json_template').value;
  // Substitui variáveis por valores de teste para validar
  const testJson = raw
    .replace(/{{value}}/g, '100.00')
    .replace(/{{date}}/g, '2026-01-01')
    .replace(/{{payment_id}}/g, 'pay_test123')
    .replace(/{{customer_id}}/g, 'cus_test123')
    .replace(/{{description}}/g, 'TESTE');
  const msg = document.getElementById('json_validation_msg');
  try {
    JSON.parse(testJson);
    msg.innerHTML = '<span style="color:#16a34a"><i class="fas fa-check-circle me-1"></i>JSON válido!</span>';
  } catch (e) {
    msg.innerHTML = '<span style="color:#dc2626"><i class="fas fa-times-circle me-1"></i>JSON inválido: ' + e.message + '</span>';
  }
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
