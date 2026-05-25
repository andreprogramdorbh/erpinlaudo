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
    <button class="prf-tab <?php echo $activeTab === 'layout_exames' ? 'active' : ''; ?>" onclick="switchTab('layout_exames', this)">
      <i class="fas fa-file-medical-alt"></i> Layout de Exames
    </button>
    <button class="prf-tab <?php echo $activeTab === 'empresa' ? 'active' : ''; ?>" onclick="switchTab('empresa', this)">
      <i class="fas fa-building"></i> Empresa
      <?php if (!($empresa ?? null)): ?>
        <span class="badge bg-warning text-dark rounded-pill ms-1" style="font-size:.6rem" title="Dados da empresa n&#227;o cadastrados">!</span>
      <?php endif; ?>
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

  <!-- ============================================================ -->
  <!-- ABA: LAYOUT DE EXAMES -->
  <!-- ============================================================ -->
  <div id="tab-layout_exames" style="display:<?php echo $activeTab === 'layout_exames' ? 'block' : 'none'; ?>">
    <div class="prf-card">
      <div class="prf-card-section">
        <h2 class="prf-section-title"><i class="fas fa-file-medical-alt text-primary"></i> Padronização de Layout de Importação</h2>
        <p class="text-muted small mb-4">Configure o mapeamento das colunas do arquivo exportado pelo seu PACS/RIS para que o sistema possa importar e calcular as apurações corretamente.</p>

        <?php if (!empty($layouts_exame)): ?>
        <div class="mb-4">
          <h6 class="fw-bold mb-3">Layouts Cadastrados</h6>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Nome do Layout</th>
                  <th>Separador</th>
                  <th>Linha Cabeçalho</th>
                  <th class="text-center">Colunas Mapeadas</th>
                  <th>Status</th>
                  <th class="text-center">Ações</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($layouts_exame as $lay): ?>
                <tr>
                  <td class="fw-semibold"><?php echo htmlspecialchars($lay->nome); ?></td>
                  <td><code><?php echo htmlspecialchars($lay->separador ?? ';'); ?></code></td>
                  <td class="text-center"><?php echo $lay->linha_cabecalho ?? 1; ?></td>
                  <td class="text-center">
                    <span class="badge bg-primary"><?php echo $lay->total_colunas ?? 0; ?></span>
                  </td>
                  <td>
                    <?php if ($lay->ativo): ?>
                      <span class="badge bg-success">Ativo</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Inativo</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-primary" onclick="editarLayout(<?php echo $lay->id; ?>)" title="Editar">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button type="button" class="btn btn-outline-danger"
                              onclick="if(confirm('Excluir este layout?')) window.location.href='/perfil/layout-exame/delete/<?php echo $lay->id; ?>'">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>

        <!-- Formulário de cadastro/edição de layout -->
        <div class="card border-0 bg-light p-4" id="form-layout-container">
          <h6 class="fw-bold mb-3" id="form-layout-title"><i class="fas fa-plus me-2"></i>Novo Layout de Importação</h6>
          <form method="POST" action="/perfil/layout-exame/store" id="form-layout">
            <input type="hidden" name="layout_id" id="layout_id" value="">

            <div class="row g-3 mb-3">
              <div class="col-md-5">
                <label class="form-label fw-semibold">Nome do Layout <span class="text-danger">*</span></label>
                <input type="text" name="nome" id="layout-nome" class="form-control"
                       placeholder="Ex: Layout PACS Carestream" required>
              </div>
              <div class="col-md-2">
                <label class="form-label fw-semibold">Separador</label>
                <select name="separador" id="layout-separador" class="form-select">
                  <option value=";">Ponto e vírgula (;)</option>
                  <option value=",">Vírgula (,)</option>
                  <option value="\t">Tabulação (Tab)</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label fw-semibold">Linha Cabeçalho</label>
                <input type="number" name="linha_cabecalho" id="layout-cabecalho" class="form-control" value="1" min="1" max="10">
                <small class="text-muted">Linha onde estão os títulos</small>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="ativo" id="layout-ativo" class="form-select">
                  <option value="1">Ativo (padrão para importação)</option>
                  <option value="0">Inativo</option>
                </select>
              </div>
            </div>

            <!-- Mapeamento de colunas -->
            <h6 class="fw-bold mb-3 mt-2">Mapeamento de Colunas</h6>
            <p class="text-muted small mb-3">Informe o nome exato do cabeçalho no arquivo para cada campo do sistema. Campos com * são obrigatórios para a apuração.</p>

            <div class="row g-3">
              <?php
              $camposSistema = [
                  ['key' => 'col_medico',          'label' => 'Médico *',              'placeholder' => 'Ex: Medico, Doctor, Nome Médico',      'required' => true],
                  ['key' => 'col_crm',             'label' => 'CRM',                   'placeholder' => 'Ex: CRM, CRM Médico',                   'required' => false],
                  ['key' => 'col_modalidade',      'label' => 'Modalidade *',          'placeholder' => 'Ex: Modalidade, Modality',              'required' => true],
                  ['key' => 'col_study_description','label' => 'Descrição do Exame *', 'placeholder' => 'Ex: Study Description, Exame',         'required' => true],
                  ['key' => 'col_prioridade',      'label' => 'Prioridade *',          'placeholder' => 'Ex: Prioridade, Priority, Urgência',    'required' => true],
                  ['key' => 'col_data_conclusao',  'label' => 'Data Conclusão *',      'placeholder' => 'Ex: Data Conclusão, Dt Conclusão',     'required' => true],
                  ['key' => 'col_paciente',        'label' => 'Paciente',              'placeholder' => 'Ex: Paciente, Patient Name',            'required' => false],
                  ['key' => 'col_paciente_id',     'label' => 'ID Paciente',           'placeholder' => 'Ex: Paciente ID, Patient ID',           'required' => false],
                  ['key' => 'col_unidade',         'label' => 'Unidade/Origem',        'placeholder' => 'Ex: Unidade, Origem, Clinic',           'required' => false],
                  ['key' => 'col_accession',       'label' => 'Accession Number',      'placeholder' => 'Ex: Accession number, Accession',      'required' => false],
                  ['key' => 'col_convenio',        'label' => 'Convênio',              'placeholder' => 'Ex: Convenio, Convênio, Payer',         'required' => false],
                  ['key' => 'col_valor_exame',     'label' => 'Valor do Exame',        'placeholder' => 'Ex: Valor do exame, Valor, Price',      'required' => false],
                  ['key' => 'col_revisor',         'label' => 'Revisor',               'placeholder' => 'Ex: Revisor, Reviewer',                 'required' => false],
                  ['key' => 'col_data_revisao',    'label' => 'Data Revisão',          'placeholder' => 'Ex: Data/Hora Revisão, Review Date',   'required' => false],
              ];
              foreach ($camposSistema as $campo):
                  $valorAtual = '';
                  if (!empty($layout_edicao)) {
                      $valorAtual = $layout_edicao->{$campo['key']} ?? '';
                  } elseif ($campo['key'] === 'col_medico') {
                      $valorAtual = 'Medico';
                  } elseif ($campo['key'] === 'col_modalidade') {
                      $valorAtual = 'Modalidade';
                  } elseif ($campo['key'] === 'col_study_description') {
                      $valorAtual = 'Study Description';
                  } elseif ($campo['key'] === 'col_prioridade') {
                      $valorAtual = 'Prioridade';
                  } elseif ($campo['key'] === 'col_data_conclusao') {
                      $valorAtual = 'Data Conclusão';
                  }
              ?>
              <div class="col-md-6">
                <label class="form-label fw-semibold small"><?php echo $campo['label']; ?></label>
                <input type="text" name="<?php echo $campo['key']; ?>" class="form-control form-control-sm"
                       placeholder="<?php echo htmlspecialchars($campo['placeholder']); ?>"
                       value="<?php echo htmlspecialchars($valorAtual); ?>"
                       <?php echo $campo['required'] ? 'required' : ''; ?>>
              </div>
              <?php endforeach; ?>

              <!-- Campo de valor urgência -->
              <div class="col-12">
                <div class="card border-warning border-opacity-50 p-3 mt-2">
                  <h6 class="fw-bold small mb-2"><i class="fas fa-exclamation-triangle text-warning me-1"></i>Identificação de Urgência</h6>
                  <p class="text-muted small mb-2">Informe os valores que indicam urgência no campo de prioridade. Separe múltiplos valores por vírgula.</p>
                  <div class="row g-2">
                    <div class="col-md-6">
                      <label class="form-label small fw-semibold">Valores que indicam Urgência</label>
                      <input type="text" name="valores_urgencia" class="form-control form-control-sm"
                             placeholder="Ex: URGENTE,U,URGENT,Urgência,Urgente"
                             value="<?php echo htmlspecialchars($layout_edicao->valores_urgencia ?? 'URGENTE,U,URGENT,Urgência,Urgente'); ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label small fw-semibold">Formato da Data de Conclusão</label>
                      <select name="formato_data" class="form-select form-select-sm">
                        <?php
                        $fmts = ['d/m/Y H:i' => 'DD/MM/AAAA HH:MM', 'd/m/Y' => 'DD/MM/AAAA', 'Y-m-d H:i:s' => 'AAAA-MM-DD HH:MM:SS', 'Y-m-d' => 'AAAA-MM-DD', 'd-m-Y' => 'DD-MM-AAAA'];
                        foreach ($fmts as $v => $l):
                            $sel = ($layout_edicao->formato_data ?? 'd/m/Y H:i') === $v ? 'selected' : '';
                        ?>
                        <option value="<?php echo $v; ?>" <?php echo $sel; ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex gap-2 mt-4">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Salvar Layout
              </button>
              <button type="button" class="btn btn-outline-secondary" onclick="resetFormLayout()">
                <i class="fas fa-times me-1"></i> Cancelar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div id="tab-empresa" style="display:<?php echo $activeTab === 'empresa' ? 'block' : 'none'; ?>">
    <?php
      if ($activeTab === 'empresa') {
          $errEmp = $_GET['error']   ?? '';
          $okEmp  = $_GET['success'] ?? '';
          if ($errEmp === 'exception')   echo '<div class="alert alert-danger mx-0 mt-0 mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Erro inesperado ao salvar. Tente novamente.</div>';
          if ($okEmp  === 'empresa_salva') echo '<div class="alert alert-success mx-0 mt-0 mb-3"><i class="fas fa-check-circle me-2"></i>Dados da empresa salvos com sucesso!</div>';
      }
    ?>
    <?php require_once __DIR__ . '/tabs/empresa.php'; ?>
  </div>

</div>

<script>
// ============================================================
// Layout de Exames
// ============================================================
const layoutsData = <?php echo json_encode(array_values($layouts_exame ?? [])); ?>;

function editarLayout(id) {
  const lay = layoutsData.find(l => l.id == id);
  if (!lay) return;
  document.getElementById('layout_id').value       = lay.id;
  document.getElementById('layout-nome').value     = lay.nome || '';
  document.getElementById('layout-separador').value = lay.separador || ';';
  document.getElementById('layout-cabecalho').value = lay.linha_cabecalho || 1;
  document.getElementById('layout-ativo').value    = lay.ativo ? '1' : '0';
  const campos = ['col_medico','col_crm','col_modalidade','col_study_description','col_prioridade',
    'col_data_conclusao','col_paciente','col_paciente_id','col_unidade','col_accession',
    'col_convenio','col_valor_exame','col_revisor','col_data_revisao','valores_urgencia'];
  campos.forEach(c => {
    const el = document.querySelector('[name="' + c + '"]');
    if (el) el.value = lay[c] || '';
  });
  const fmtEl = document.querySelector('[name="formato_data"]');
  if (fmtEl) fmtEl.value = lay.formato_data || 'd/m/Y H:i';
  document.getElementById('form-layout-title').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Layout: ' + lay.nome;
  document.getElementById('form-layout-container').scrollIntoView({behavior:'smooth'});
}

function resetFormLayout() {
  document.getElementById('form-layout').reset();
  document.getElementById('layout_id').value = '';
  document.getElementById('form-layout-title').innerHTML = '<i class="fas fa-plus me-2"></i>Novo Layout de Importação';
}

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
