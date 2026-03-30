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
    <button class="cfg-tab <?php echo $activeTab === 'cnes' ? 'active' : ''; ?>" onclick="switchTab('cnes', this)">
      <i class="fas fa-hospital"></i> CNES
      <?php if (($cnesTotalEstab ?? 0) > 0): ?>
      <span class="badge rounded-pill ms-1" style="background:#2d9b5e;color:#fff;font-size:.62rem"><?php echo number_format($cnesTotalEstab ?? 0); ?></span>
      <?php endif; ?>
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


  <!-- ===== ABA: CNES ===== -->
  <?php if (Auth::can('manage_settings')): ?>
  <?php
    $cnesHistorico  = $cnesHistorico  ?? [];
    $cnesTotalEstab = $cnesTotalEstab ?? 0;
    $cnesTotalEquip = $cnesTotalEquip ?? 0;
    $cnesTotalProf  = $cnesTotalProf  ?? 0;
    $ufs = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];
  ?>
  <div id="tab-cnes" style="display:<?php echo $activeTab === 'cnes' ? 'block' : 'none'; ?>">

    <!-- Hero CNES -->
    <div style="background:linear-gradient(135deg,#1a6b3c 0%,#2d9b5e 100%);border-radius:12px;padding:1.75rem 2rem;color:#fff;margin-bottom:1.5rem;position:relative;overflow:hidden">
      <div style="position:absolute;right:1.5rem;top:50%;transform:translateY(-50%);font-size:5rem;opacity:.1">&#127973;</div>
      <h2 style="font-size:1.35rem;font-weight:700;margin:0 0 .3rem"><i class="fas fa-hospital me-2"></i>Importação da Base CNES</h2>
      <p style="margin:0;opacity:.9;font-size:.9rem">Cadastro Nacional de Estabelecimentos de Saúde &mdash; DATASUS/CNES<br>Gerencie a importação e atualização mensal da base completa.</p>
    </div>

    <!-- Stats atuais -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="cfg-card" style="border-left:4px solid #2d9b5e">
          <div class="cfg-section" style="padding:1rem 1.25rem">
            <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b">Estabelecimentos</div>
            <div style="font-size:1.75rem;font-weight:700;color:#2d9b5e"><?php echo number_format($cnesTotalEstab); ?></div>
            <?php if ($cnesTotalEstab > 0): ?>
            <a href="/cnes" class="btn btn-sm btn-outline-success mt-1" style="font-size:.75rem">
              <i class="fas fa-list me-1"></i>Ver listagem
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="cfg-card" style="border-left:4px solid #1976d2">
          <div class="cfg-section" style="padding:1rem 1.25rem">
            <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b">Equipamentos</div>
            <div style="font-size:1.75rem;font-weight:700;color:#1976d2"><?php echo number_format($cnesTotalEquip); ?></div>
            <?php if ($cnesTotalEstab > 0 && $cnesTotalEquip === 0): ?>
            <span style="font-size:.72rem;color:#dc2626"><i class="fas fa-exclamation-triangle me-1"></i>Tabela vazia</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="cfg-card" style="border-left:4px solid #f59e0b">
          <div class="cfg-section" style="padding:1rem 1.25rem">
            <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b">Profissionais</div>
            <div style="font-size:1.75rem;font-weight:700;color:#f59e0b"><?php echo number_format($cnesTotalProf); ?></div>
            <?php if ($cnesTotalEstab > 0 && $cnesTotalProf === 0): ?>
            <span style="font-size:.72rem;color:#dc2626"><i class="fas fa-exclamation-triangle me-1"></i>Tabela vazia</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Alerta dados incompletos -->
    <?php if ($cnesTotalEstab > 0 && ($cnesTotalEquip === 0 || $cnesTotalProf === 0)): ?>
    <div class="cfg-alert" style="background:#fff3cd;color:#856404;border:1px solid #ffc107;margin-bottom:1.25rem">
      <i class="fas fa-exclamation-triangle"></i>
      <div>
        <strong>Dados incompletos detectados!</strong>
        <?php if ($cnesTotalEquip === 0): ?> &nbsp;<span style="color:#dc3545">Equipamentos: 0 registros.</span><?php endif; ?>
        <?php if ($cnesTotalProf === 0): ?> &nbsp;<span style="color:#dc3545">Profissionais: 0 registros.</span><?php endif; ?>
        &nbsp;Use a <strong>Reimportação Parcial</strong> abaixo para corrigir sem reimportar os estabelecimentos.
      </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">

      <!-- Coluna principal -->
      <div class="col-lg-8">

        <!-- Info box: como obter o ZIP -->
        <div style="background:#e3f2fd;border-left:4px solid #1976d2;border-radius:0 8px 8px 0;padding:1rem 1.2rem;margin-bottom:1.25rem">
          <h5 style="color:#1565c0;margin:0 0 .4rem;font-size:.9rem"><i class="fas fa-download me-1"></i> Como obter o arquivo ZIP</h5>
          <p style="margin:0;font-size:.85rem;color:#333">Acesse <a href="https://cnes.datasus.gov.br/pages/downloads/arquivosBaseDados.jsp" target="_blank">cnes.datasus.gov.br &rarr; Downloads &rarr; Base de Dados</a> e baixe o arquivo <code style="background:#bbdefb;padding:.1rem .4rem;border-radius:4px">BASE_DE_DADOS_CNES_AAAAMM.ZIP</code>. A base é atualizada mensalmente pelo DATASUS.</p>
        </div>

        <!-- Card: Upload e Importação Completa -->
        <div class="cfg-card mb-4">
          <div class="cfg-card-header">
            <h2 class="cfg-card-title"><i class="fas fa-cloud-upload-alt" style="color:#2d9b5e"></i> Upload da Base CNES</h2>
            <span class="badge" style="background:#2d9b5e;color:#fff;font-size:.72rem;padding:.3rem .7rem;border-radius:20px">Importação Completa</span>
          </div>
          <div class="cfg-section">

            <form id="cnesFormImportar" enctype="multipart/form-data">

              <!-- Drop zone -->
              <div id="cnesDropZone" onclick="document.getElementById('cnesArquivoZip').click()"
                   style="border:2.5px dashed #c8e6c9;border-radius:10px;background:#f9fffe;padding:2.5rem 1.5rem;text-align:center;cursor:pointer;transition:all .2s">
                <div style="font-size:2.5rem;margin-bottom:.75rem">&#128230;</div>
                <h3 style="color:#1a6b3c;margin:0 0 .4rem;font-size:1.05rem">Arraste o arquivo ZIP aqui</h3>
                <p style="color:#666;font-size:.85rem;margin:0">ou clique para selecionar &mdash; BASE_DE_DADOS_CNES_AAAAMM.ZIP</p>
                <p style="margin:.5rem 0 0;color:#94a3b8;font-size:.78rem">Tamanho máximo: 2 GB</p>
              </div>
              <input type="file" id="cnesArquivoZip" name="arquivo_zip" accept=".zip" style="display:none">

              <!-- Arquivo selecionado -->
              <div id="cnesArqSel" class="cfg-alert cfg-alert-success mt-3" style="display:none">
                <i class="fas fa-file-archive"></i>
                <span id="cnesNomeArq"></span>
                <span class="text-muted ms-2" id="cnesTamanhoArq"></span>
              </div>

              <!-- Opções -->
              <div class="row g-3 mt-2">
                <div class="col-md-4">
                  <label class="form-label fw-semibold" style="font-size:.85rem">Filtrar por UF <span class="text-muted">(opcional)</span></label>
                  <select name="uf" class="form-select form-select-sm">
                    <option value="">Todos os estados</option>
                    <?php foreach ($ufs as $uf): ?>
                    <option value="<?= $uf ?>"><?= $uf ?></option>
                    <?php endforeach; ?>
                  </select>
                  <div class="form-text" style="font-size:.75rem">Importar apenas um estado acelera o processo.</div>
                </div>
                <div class="col-md-8 d-flex align-items-end">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="apenas_imagem" id="cnesApenasImagem" value="1">
                    <label class="form-check-label" for="cnesApenasImagem" style="font-size:.85rem">
                      <strong>Apenas Diagnóstico por Imagem</strong>
                      <div class="form-text" style="font-size:.75rem">Importa somente estabelecimentos com equipamentos de imagem (Raio-X, TC, RM, US, etc.)</div>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Botão -->
              <div class="mt-3">
                <button type="submit" class="btn btn-success px-4" id="cnesBtnImportar" disabled>
                  <i class="fas fa-cloud-upload-alt me-2"></i>Iniciar Importação Completa
                </button>
                <span class="text-muted ms-3" style="font-size:.82rem">Executa em segundo plano (5&ndash;30 min).</span>
              </div>
            </form>

            <!-- Card de progresso -->
            <div id="cnesProgressCard" style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:1.25rem;margin-top:1.25rem">
              <div class="d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0" id="cnesProgressTitle">Importando base CNES...</h6>
                <span id="cnesProgressBadge" style="display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .8rem;border-radius:50px;font-size:.78rem;font-weight:600;background:#fff3e0;color:#e65100">
                  <span class="spinner-border spinner-border-sm"></span> Processando
                </span>
              </div>
              <div style="background:#d1fae5;border-radius:50px;height:10px;overflow:hidden;margin:.75rem 0">
                <div id="cnesProgressBar" style="height:100%;background:linear-gradient(90deg,#2d9b5e,#4caf50);border-radius:50px;transition:width .5s;width:0%"></div>
              </div>
              <div class="row text-center g-2">
                <div class="col-4">
                  <div class="fw-bold text-success" id="cnesCntEstab">0</div>
                  <div class="text-muted" style="font-size:.75rem">Estabelecimentos</div>
                </div>
                <div class="col-4">
                  <div class="fw-bold" style="color:#1976d2" id="cnesCntEquip">0</div>
                  <div class="text-muted" style="font-size:.75rem">Equipamentos</div>
                </div>
                <div class="col-4">
                  <div class="fw-bold" style="color:#f59e0b" id="cnesCntProf">0</div>
                  <div class="text-muted" style="font-size:.75rem">Profissionais</div>
                </div>
              </div>
              <div class="mt-2 text-muted" style="font-size:.82rem" id="cnesProgressMsg"></div>
            </div>

          </div>
        </div>

        <!-- Card: Diagnóstico & Reimportação Parcial -->
        <div class="cfg-card mb-4" style="border-top:3px solid #1976d2">
          <div class="cfg-card-header" style="background:linear-gradient(135deg,#1565c0,#1976d2);color:#fff">
            <h2 class="cfg-card-title" style="color:#fff"><i class="fas fa-search-plus me-2"></i>Diagnóstico de ZIP &amp; Reimportação Parcial</h2>
            <span style="font-size:.78rem;opacity:.85">Verifique o conteúdo do ZIP e reimporte apenas equip./profissionais</span>
          </div>
          <div class="cfg-section">

            <!-- Passo 1: Diagnóstico -->
            <h3 class="cfg-section-title" style="font-size:.9rem"><i class="fas fa-search me-1 text-primary"></i> Passo 1 &mdash; Diagnosticar o ZIP</h3>
            <div id="cnesDiagDropZone" onclick="document.getElementById('cnesDiagArquivoZip').click()"
                 style="border:2px dashed #90caf9;border-radius:10px;background:#f3f8ff;padding:1.5rem;text-align:center;cursor:pointer;transition:all .2s">
              <i class="fas fa-file-archive fa-2x" style="color:#1976d2;margin-bottom:.5rem"></i>
              <div class="fw-semibold" style="font-size:.9rem">Clique ou arraste o ZIP para diagnóstico</div>
              <div class="text-muted" style="font-size:.8rem">Não importa nada &mdash; apenas analisa o conteúdo</div>
            </div>
            <input type="file" id="cnesDiagArquivoZip" accept=".zip" style="display:none">
            <div id="cnesDiagArqSel" class="mt-2" style="display:none">
              <span class="badge bg-primary"><i class="fas fa-file-archive me-1"></i><span id="cnesDiagNomeArq"></span></span>
              <button class="btn btn-sm btn-primary ms-2" id="cnesBtnDiagnosticar">
                <i class="fas fa-search me-1"></i>Analisar ZIP
              </button>
            </div>

            <!-- Resultado do diagnóstico -->
            <div id="cnesDiagResultado" style="display:none;margin-top:1rem">
              <hr style="margin:.75rem 0">
              <h3 class="cfg-section-title" style="font-size:.9rem"><i class="fas fa-list-check me-1"></i> Arquivos encontrados no ZIP</h3>
              <div id="cnesDiagListaArquivos"></div>

              <!-- Passo 2: Reimportar -->
              <div id="cnesDiagPasso2" style="display:none;margin-top:1rem">
                <hr style="margin:.75rem 0">
                <h3 class="cfg-section-title" style="font-size:.9rem;color:#2d9b5e"><i class="fas fa-sync-alt me-1"></i> Passo 2 &mdash; Reimportar</h3>
                <div class="row g-2 mb-3">
                  <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:.82rem">Filtrar por UF</label>
                    <select id="cnesDiagUf" class="form-select form-select-sm">
                      <option value="">Todos os estados</option>
                      <?php foreach ($ufs as $uf): ?>
                      <option value="<?= $uf ?>"><?= $uf ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-8 d-flex align-items-end gap-3">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="cnesDiagImportarEquip" checked>
                      <label class="form-check-label" for="cnesDiagImportarEquip" style="font-size:.85rem">Importar Equipamentos</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="cnesDiagImportarProf" checked>
                      <label class="form-check-label" for="cnesDiagImportarProf" style="font-size:.85rem">Importar Profissionais</label>
                    </div>
                  </div>
                </div>
                <button class="btn btn-success" id="cnesBtnReimportar">
                  <i class="fas fa-sync-alt me-2"></i>Reimportar Equipamentos/Profissionais
                </button>
                <span class="text-muted ms-2" style="font-size:.8rem">Os estabelecimentos já importados não serão alterados.</span>
              </div>
            </div>

          </div>
        </div>

        <!-- Card: Histórico de Importações -->
        <?php if (!empty($cnesHistorico)): ?>
        <div class="cfg-card">
          <div class="cfg-card-header">
            <h2 class="cfg-card-title"><i class="fas fa-history text-secondary"></i> Histórico de Importações</h2>
          </div>
          <div class="table-responsive">
            <table class="user-table">
              <thead>
                <tr>
                  <th class="ps-3">Competência</th>
                  <th>Status</th>
                  <th>Estabelecimentos</th>
                  <th>Equipamentos</th>
                  <th>Profissionais</th>
                  <th>Iniciado em</th>
                  <th>Concluído em</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($cnesHistorico as $h): ?>
                <?php
                  $comp = $h->competencia ?? '';
                  if (strlen($comp) === 6) {
                    $meses = ['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun',
                              '07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];
                    $m = substr($comp, 4, 2); $a = substr($comp, 0, 4);
                    $compFmt = ($meses[$m] ?? $m) . '/' . $a;
                  } else { $compFmt = htmlspecialchars($comp); }
                ?>
                <tr>
                  <td class="ps-3 fw-semibold"><?= $compFmt ?></td>
                  <td>
                    <?php if ($h->status === 'concluido'): ?>
                      <span style="display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .65rem;border-radius:50px;font-size:.75rem;font-weight:600;background:#d1fae5;color:#065f46">
                        <i class="fas fa-check-circle"></i> Concluído
                      </span>
                    <?php elseif ($h->status === 'processando'): ?>
                      <span style="display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .65rem;border-radius:50px;font-size:.75rem;font-weight:600;background:#fff3e0;color:#e65100">
                        <span class="spinner-border spinner-border-sm" style="width:.7rem;height:.7rem"></span> Processando
                      </span>
                    <?php else: ?>
                      <span style="display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .65rem;border-radius:50px;font-size:.75rem;font-weight:600;background:#fee2e2;color:#991b1b">
                        <i class="fas fa-times-circle"></i> Erro
                      </span>
                    <?php endif; ?>
                  </td>
                  <td><?= number_format((int)($h->total_estab ?? 0)) ?></td>
                  <td><?= number_format((int)($h->total_equip ?? 0)) ?></td>
                  <td><?= number_format((int)($h->total_prof ?? 0)) ?></td>
                  <td><?= $h->iniciado_em ? date('d/m/Y H:i', strtotime($h->iniciado_em)) : '—' ?></td>
                  <td><?= $h->concluido_em ? date('d/m/Y H:i', strtotime($h->concluido_em)) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>

      </div>

      <!-- Coluna lateral -->
      <div class="col-lg-4">

        <!-- Importação via SSH -->
        <div class="cfg-card mb-4">
          <div class="cfg-card-header">
            <h2 class="cfg-card-title"><i class="fas fa-terminal text-secondary"></i> Importação via SSH</h2>
          </div>
          <div class="cfg-section">
            <p class="text-muted" style="font-size:.85rem">Para servidores com limite de upload, use o SSH:</p>
            <ol style="font-size:.82rem;padding-left:1.2rem">
              <li class="mb-2">Execute a migration SQL:<br>
                <code style="font-size:.75rem;background:#f5f5f5;padding:.2rem .4rem;border-radius:4px;display:block;margin-top:.3rem">mysql -u user -p banco &lt; database/migrations/2026-03-25_cnes_global.sql</code>
              </li>
              <li class="mb-2">Extraia o ZIP:<br>
                <code style="font-size:.75rem;background:#f5f5f5;padding:.2rem .4rem;border-radius:4px;display:block;margin-top:.3rem">unzip BASE_CNES.ZIP -d /tmp/cnes_base/</code>
              </li>
              <li class="mb-2">Execute o script:<br>
                <code style="font-size:.75rem;background:#f5f5f5;padding:.2rem .4rem;border-radius:4px;display:block;margin-top:.3rem">php database/importar_cnes.php --dir=/tmp/cnes_base --uf=MG</code>
              </li>
            </ol>
            <p class="text-muted" style="font-size:.78rem">
              <i class="fas fa-info-circle me-1"></i>
              Omita <code>--uf</code> para importar todos os estados (~605 mil estabelecimentos).
            </p>
          </div>
        </div>

        <!-- Atualização Mensal -->
        <div class="cfg-card mb-4">
          <div class="cfg-card-header">
            <h2 class="cfg-card-title"><i class="fas fa-calendar-check text-secondary"></i> Atualização Mensal</h2>
          </div>
          <div class="cfg-section">
            <p style="font-size:.85rem;color:#555">O DATASUS publica a base CNES toda <strong>primeira semana de cada mês</strong>. Para automatizar via cron:</p>
            <code style="font-size:.73rem;background:#f5f5f5;padding:.5rem;border-radius:6px;display:block;line-height:1.6">
              # Cron: todo dia 10 às 3h<br>
              0 3 10 * * php /caminho/database/importar_cnes.php --dir=/tmp/cnes_base --uf=MG
            </code>
            <p class="mt-2 text-muted" style="font-size:.78rem">
              O script usa <code>INSERT ... ON DUPLICATE KEY UPDATE</code>, então re-executar é seguro &mdash; apenas atualiza registros existentes.
            </p>
          </div>
        </div>

        <!-- Link para listagem CNES -->
        <div class="cfg-card">
          <div class="cfg-section" style="text-align:center;padding:1.5rem">
            <div style="font-size:2.5rem;margin-bottom:.5rem">&#127973;</div>
            <div class="fw-bold" style="font-size:1.1rem;color:#1e293b"><?php echo number_format($cnesTotalEstab); ?></div>
            <div class="text-muted" style="font-size:.82rem">estabelecimentos na base atual</div>
            <?php if ($cnesTotalEstab > 0): ?>
            <a href="/cnes" class="btn btn-outline-success btn-sm mt-3">
              <i class="fas fa-list me-1"></i>Ver listagem CNES
            </a>
            <?php endif; ?>
          </div>
        </div>

      </div>
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

// ═══════════════════════════════════════════════════════════════════════
// ABA CNES — Upload, Diagnóstico e Reimportação Parcial
// ═══════════════════════════════════════════════════════════════════════
(function () {
  // Elementos de upload
  const dropZone    = document.getElementById('cnesDropZone');
  const fileInput   = document.getElementById('cnesArquivoZip');
  const btnImportar = document.getElementById('cnesBtnImportar');
  const arqSel      = document.getElementById('cnesArqSel');
  const nomeArq     = document.getElementById('cnesNomeArq');
  const tamanhoArq  = document.getElementById('cnesTamanhoArq');
  const form        = document.getElementById('cnesFormImportar');
  const progressCard = document.getElementById('cnesProgressCard');
  let pollingInterval = null;

  if (!dropZone || !fileInput) return; // aba não está no DOM

  function formatBytes(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    if (bytes < 1073741824) return (bytes / 1048576).toFixed(1) + ' MB';
    return (bytes / 1073741824).toFixed(2) + ' GB';
  }

  function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Drag & Drop upload principal ──────────────────────────────────────
  ['dragenter','dragover'].forEach(e => dropZone.addEventListener(e, ev => {
    ev.preventDefault();
    dropZone.style.borderColor = '#2d9b5e';
    dropZone.style.background  = '#e8f5e9';
  }));
  ['dragleave','drop'].forEach(e => dropZone.addEventListener(e, ev => {
    ev.preventDefault();
    dropZone.style.borderColor = '#c8e6c9';
    dropZone.style.background  = '#f9fffe';
  }));
  dropZone.addEventListener('drop', ev => {
    const files = ev.dataTransfer.files;
    if (files.length) { fileInput.files = files; mostrarArquivo(files[0]); }
  });
  fileInput.addEventListener('change', function () {
    if (this.files.length) mostrarArquivo(this.files[0]);
  });

  function mostrarArquivo(file) {
    nomeArq.textContent    = file.name;
    tamanhoArq.textContent = '(' + formatBytes(file.size) + ')';
    arqSel.style.display   = 'block';
    btnImportar.disabled   = false;
  }

  // ── Submit importação completa ─────────────────────────────────────────
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!fileInput.files.length) {
      if (typeof Swal !== 'undefined') Swal.fire('Atenção', 'Selecione o arquivo ZIP da base CNES.', 'warning');
      else alert('Selecione o arquivo ZIP da base CNES.');
      return;
    }
    btnImportar.disabled = true;
    btnImportar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
    progressCard.style.display = 'block';
    progressCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    const fd = new FormData(form);
    fetch('/cnes/importar/upload', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        if (typeof Swal !== 'undefined') Swal.fire('Erro', data.error || 'Erro ao iniciar importação.', 'error');
        else alert(data.error || 'Erro ao iniciar importação.');
        btnImportar.disabled = false;
        btnImportar.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i>Iniciar Importação Completa';
        return;
      }
      document.getElementById('cnesProgressMsg').textContent = data.message || 'Importação iniciada...';
      btnImportar.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i>Importando...';
      pollingInterval = setInterval(verificarStatus, 5000);
      verificarStatus();
    })
    .catch(err => {
      if (typeof Swal !== 'undefined') Swal.fire('Erro', 'Falha na comunicação: ' + err.message, 'error');
      else alert('Falha na comunicação: ' + err.message);
      btnImportar.disabled = false;
      btnImportar.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i>Iniciar Importação Completa';
    });
  });

  // ── Polling de status ──────────────────────────────────────────────────
  function verificarStatus() {
    fetch('/cnes/importar/status', { cache: 'no-store' })
    .then(r => r.json())
    .then(data => {
      const estab = data.estab || data.db_estab || 0;
      const equip = data.equip || data.db_equip || 0;
      const prof  = data.prof  || data.db_prof  || 0;
      document.getElementById('cnesCntEstab').textContent = Number(estab).toLocaleString('pt-BR');
      document.getElementById('cnesCntEquip').textContent = Number(equip).toLocaleString('pt-BR');
      document.getElementById('cnesCntProf').textContent  = Number(prof).toLocaleString('pt-BR');
      document.getElementById('cnesProgressMsg').textContent = data.etapa || data.message || '';

      const pct = data.pct || 0;
      const bar = document.getElementById('cnesProgressBar');
      if (pct > 0) {
        bar.style.width = Math.min(pct, 99) + '%';
      } else {
        const w = parseFloat(bar.style.width || '0');
        bar.style.width = Math.min(w + 1.5, 90) + '%';
      }

      const badge = document.getElementById('cnesProgressBadge');
      if (data.status === 'concluido') {
        clearInterval(pollingInterval);
        badge.style.background = '#d1fae5'; badge.style.color = '#065f46';
        badge.innerHTML = '<i class="fas fa-check-circle"></i> Concluído';
        bar.style.width = '100%';
        document.getElementById('cnesProgressTitle').textContent = 'Importação concluída!';
        btnImportar.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i>Iniciar Nova Importação';
        btnImportar.disabled = false;
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: 'Importação concluída!',
            html: `<strong>${Number(estab).toLocaleString('pt-BR')}</strong> estabelecimentos, <strong>${Number(equip).toLocaleString('pt-BR')}</strong> equipamentos e <strong>${Number(prof).toLocaleString('pt-BR')}</strong> profissionais importados.`,
            confirmButtonText: 'Ver listagem CNES',
          }).then(() => window.location.href = '/cnes');
        }
      } else if (data.status === 'erro') {
        clearInterval(pollingInterval);
        badge.style.background = '#fee2e2'; badge.style.color = '#991b1b';
        badge.innerHTML = '<i class="fas fa-times-circle"></i> Erro';
        document.getElementById('cnesProgressTitle').textContent = 'Erro na importação';
        btnImportar.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i>Tentar Novamente';
        btnImportar.disabled = false;
        const erros = data.erros ? data.erros.join('<br>') : (data.etapa || 'Verifique o log do servidor.');
        if (typeof Swal !== 'undefined') Swal.fire('Erro na importação', erros, 'error');
      }
    })
    .catch(() => {});
  }

  // Verificar se há importação em andamento ao carregar
  <?php foreach (($cnesHistorico ?? []) as $h): if ($h->status === 'processando'): ?>
  progressCard.style.display = 'block';
  pollingInterval = setInterval(verificarStatus, 5000);
  verificarStatus();
  <?php break; endif; endforeach; ?>

  // ── Diagnóstico de ZIP ─────────────────────────────────────────────────
  const diagInput    = document.getElementById('cnesDiagArquivoZip');
  const diagDropZone = document.getElementById('cnesDiagDropZone');
  const diagArqSel   = document.getElementById('cnesDiagArqSel');
  const diagNomeArq  = document.getElementById('cnesDiagNomeArq');
  const btnDiag      = document.getElementById('cnesBtnDiagnosticar');
  const diagResult   = document.getElementById('cnesDiagResultado');
  const diagLista    = document.getElementById('cnesDiagListaArquivos');
  const diagPasso2   = document.getElementById('cnesDiagPasso2');
  const btnReimport  = document.getElementById('cnesBtnReimportar');
  let diagZipPath    = null;
  let diagPolling    = null;

  ['dragenter','dragover'].forEach(e => diagDropZone.addEventListener(e, ev => {
    ev.preventDefault();
    diagDropZone.style.borderColor = '#1976d2';
    diagDropZone.style.background  = '#e3f2fd';
  }));
  ['dragleave','drop'].forEach(e => diagDropZone.addEventListener(e, ev => {
    ev.preventDefault();
    diagDropZone.style.borderColor = '#90caf9';
    diagDropZone.style.background  = '#f3f8ff';
  }));
  diagDropZone.addEventListener('drop', ev => {
    const files = ev.dataTransfer.files;
    if (files.length) { diagInput.files = files; mostrarDiagArq(files[0]); }
  });
  diagInput.addEventListener('change', function () {
    if (this.files.length) mostrarDiagArq(this.files[0]);
  });

  function mostrarDiagArq(file) {
    diagNomeArq.textContent   = file.name + ' (' + formatBytes(file.size) + ')';
    diagArqSel.style.display  = 'block';
    diagResult.style.display  = 'none';
    diagZipPath = null;
  }

  btnDiag.addEventListener('click', function () {
    if (!diagInput.files.length) return;
    btnDiag.disabled = true;
    btnDiag.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Analisando...';
    diagResult.style.display = 'none';

    const fd = new FormData();
    fd.append('arquivo_zip', diagInput.files[0]);

    fetch('/cnes/importar/diagnostico-zip', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      btnDiag.disabled = false;
      btnDiag.innerHTML = '<i class="fas fa-search me-1"></i>Analisar ZIP';
      if (!data.success) {
        if (typeof Swal !== 'undefined') Swal.fire('Erro', data.error || 'Falha no diagnóstico.', 'error');
        return;
      }
      diagZipPath = data.zip_path || null;
      renderDiagnostico(data);
    })
    .catch(err => {
      btnDiag.disabled = false;
      btnDiag.innerHTML = '<i class="fas fa-search me-1"></i>Analisar ZIP';
      if (typeof Swal !== 'undefined') Swal.fire('Erro', 'Falha na comunicação: ' + err.message, 'error');
    });
  });

  function renderDiagnostico(data) {
    diagResult.style.display = 'block';
    diagLista.innerHTML = '';

    const reconhecidos = data.reconhecidos || [];
    const faltando     = data.faltando     || [];
    const outros       = data.nao_reconhecidos || [];

    reconhecidos.forEach(f => {
      const row = document.createElement('div');
      row.style.cssText = 'display:flex;align-items:center;gap:.5rem;padding:.4rem .6rem;border-radius:6px;margin-bottom:.3rem;font-size:.84rem;background:#e8f5e9';
      row.innerHTML = `<i class="fas fa-check-circle" style="color:#2d9b5e"></i>
        <span class="fw-semibold">${escHtml(f.nome)}</span>
        <span style="font-size:.72rem;padding:.2rem .5rem;border-radius:50px;background:#c8e6c9;color:#1b5e20">${escHtml(f.descricao || f.tipo)}</span>
        <span class="text-muted ms-auto" style="font-size:.75rem">${escHtml(f.tamanho || '')}</span>`;
      diagLista.appendChild(row);
    });

    faltando.forEach(f => {
      const row = document.createElement('div');
      row.style.cssText = 'display:flex;align-items:center;gap:.5rem;padding:.4rem .6rem;border-radius:6px;margin-bottom:.3rem;font-size:.84rem;background:#fff3e0';
      row.innerHTML = `<i class="fas fa-exclamation-triangle" style="color:#f59e0b"></i>
        <span class="text-muted">${escHtml(f.prefixo)}*.csv</span>
        <span style="font-size:.72rem;padding:.2rem .5rem;border-radius:50px;background:#ffe0b2;color:#e65100">${escHtml(f.descricao)}</span>
        <span class="text-danger ms-auto" style="font-size:.75rem">Não encontrado${f.obrigatorio ? ' (obrigatório)' : ''}</span>`;
      diagLista.appendChild(row);
    });

    if (outros.length) {
      const row = document.createElement('div');
      row.style.cssText = 'display:flex;align-items:center;gap:.5rem;padding:.4rem .6rem;border-radius:6px;margin-bottom:.3rem;font-size:.84rem;background:#f5f5f5';
      row.innerHTML = `<i class="fas fa-file"></i> <span class="text-muted">${outros.length} arquivo(s) não reconhecido(s) pelo sistema</span>`;
      diagLista.appendChild(row);
    }

    const temEquip = data.tem_equipamento;
    const temProf  = data.tem_profissional;
    if (temEquip || temProf) {
      diagPasso2.style.display = 'block';
      document.getElementById('cnesDiagImportarEquip').disabled = !temEquip;
      document.getElementById('cnesDiagImportarProf').disabled  = !temProf;
      if (!temEquip) document.getElementById('cnesDiagImportarEquip').checked = false;
      if (!temProf)  document.getElementById('cnesDiagImportarProf').checked  = false;
    } else {
      diagPasso2.style.display = 'none';
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'warning',
          title: 'Arquivos não encontrados',
          html: 'O ZIP não contém os arquivos de equipamentos (<code>rlEstabEquipamento*.csv</code>) nem de profissionais (<code>tbCargaHorariaSus*.csv</code>).<br><br>Verifique se baixou a base completa do DATASUS.',
        });
      }
    }
  }

  btnReimport.addEventListener('click', function () {
    if (!diagZipPath) {
      if (typeof Swal !== 'undefined') Swal.fire('Atenção', 'Faça o diagnóstico do ZIP primeiro.', 'warning');
      return;
    }
    const importarEquip = document.getElementById('cnesDiagImportarEquip').checked;
    const importarProf  = document.getElementById('cnesDiagImportarProf').checked;
    const uf            = document.getElementById('cnesDiagUf').value;

    if (!importarEquip && !importarProf) {
      if (typeof Swal !== 'undefined') Swal.fire('Atenção', 'Selecione ao menos uma opção: Equipamentos ou Profissionais.', 'warning');
      return;
    }

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Confirmar reimportação parcial?',
        html: `Serão importados:<br>
          ${importarEquip ? '<b>Equipamentos</b><br>' : ''}
          ${importarProf  ? '<b>Profissionais</b><br>' : ''}
          ${uf ? '<br>Filtro UF: <b>' + uf + '</b>' : '<br>Todos os estados'}<br><br>
          Os estabelecimentos já importados <b>não serão alterados</b>.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, reimportar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2d9b5e',
      }).then(result => {
        if (!result.isConfirmed) return;
        executarReimportacao(importarEquip, importarProf, uf);
      });
    } else {
      if (confirm('Confirmar reimportação parcial?')) executarReimportacao(importarEquip, importarProf, uf);
    }
  });

  function executarReimportacao(importarEquip, importarProf, uf) {
    btnReimport.disabled = true;
    btnReimport.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Iniciando...';

    fetch('/cnes/importar/parcial', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        zip_path:       diagZipPath,
        uf:             uf,
        importar_equip: importarEquip,
        importar_prof:  importarProf,
        competencia:    new Date().toISOString().slice(0,7).replace('-',''),
      }),
    })
    .then(r => r.json())
    .then(data => {
      if (!data.success) {
        if (typeof Swal !== 'undefined') Swal.fire('Erro', data.error || 'Falha ao iniciar reimportação.', 'error');
        btnReimport.disabled = false;
        btnReimport.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Reimportar Equipamentos/Profissionais';
        return;
      }
      progressCard.style.display = 'block';
      document.getElementById('cnesProgressTitle').textContent = 'Reimportando equipamentos/profissionais...';
      document.getElementById('cnesProgressMsg').textContent   = data.message || 'Processando...';
      progressCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      if (diagPolling) clearInterval(diagPolling);
      diagPolling = setInterval(verificarStatus, 4000);
      verificarStatus();
      btnReimport.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Reimportando...';
    })
    .catch(err => {
      if (typeof Swal !== 'undefined') Swal.fire('Erro', 'Falha na comunicação: ' + err.message, 'error');
      btnReimport.disabled = false;
      btnReimport.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Reimportar Equipamentos/Profissionais';
    });
  }

})();
</script>

<?php require_once dirname(__DIR__) . '/layout/erp_footer.php'; ?>
