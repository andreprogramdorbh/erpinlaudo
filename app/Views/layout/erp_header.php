<?php
// ── Dados da Empresa (carregados uma vez por request) ─────────────
if (!isset($GLOBALS['_empresaConfig'])) {
    try {
        $GLOBALS['_empresaConfig'] = null;
        if (class_exists('\\App\\Core\\Auth') && \App\Core\Auth::check()) {
            $__uid = (int)(\App\Core\Auth::user()->id ?? 0);
            if ($__uid > 0) {
                $__em = new \App\Models\EmpresaConfig();
                $GLOBALS['_empresaConfig'] = $__em->findByUsuarioId($__uid);
            }
        }
    } catch (\Throwable $__e) {
        $GLOBALS['_empresaConfig'] = null;
    }
}
$_ec = $GLOBALS['_empresaConfig'] ?? null;
$_ecNome = '';
if ($_ec) {
    $_ecNome = !empty($_ec->nome_fantasia)
        ? $_ec->nome_fantasia
        : (!empty($_ec->razao_social) ? $_ec->razao_social : '');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>INLAUDO ERP</title>

  <!-- FONTS & ICONS -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <!-- BOOTSTRAP 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Flatpickr: datepicker leve para campos de data -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <!-- CSS Globais do ERP -->
  <link rel="stylesheet" href="/assets/css/form-layout.css">

  <!-- Scripts de Diagnóstico (Carregamento Antecipado) -->
  <script src="/assets/js/error-logger.js"></script>

  <style>
    :root {
      --sidebar-width: 250px;
      --sidebar-collapsed-width: 70px;
      --header-height: 70px;
      --bg-body: #f8f9fa;
      --sidebar-bg: #fdfdfd;
      --sidebar-text: #4a5568;
      --primary: #00529B;
      --border-color: #e2e8f0;
      --transition-speed: 0.3s;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-body);
      overflow-x: hidden;
      margin: 0;
    }

    /* LAYOUT CORE STRUCTURE */
    .layout-wrapper {
      display: flex;
      min-height: 100vh;
      width: 100%;
    }

    /* SIDEBAR */
    .sidebar {
      width: var(--sidebar-width);
      background-color: var(--sidebar-bg);
      border-right: 1px solid var(--border-color);
      position: fixed;
      height: 100vh;
      left: 0;
      top: 0;
      z-index: 1000;
      transition: width var(--transition-speed) ease;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .sidebar.collapsed {
      width: var(--sidebar-collapsed-width);
    }

    /* SIDEBAR LOGO SECTION */
    .sidebar-header {
      height: var(--header-height);
      display: flex;
      align-items: center;
      padding: 0 1.5rem;
      border-bottom: 1px solid var(--border-color);
    }

    .sidebar.collapsed .sidebar-header {
      padding: 0;
      justify-content: center;
    }

    .logo-svg {
      height: 32px;
      color: var(--primary);
      transition: all var(--transition-speed);
    }

    .logo-text {
      font-weight: 700;
      font-size: 1.2rem;
      margin-left: 10px;
      white-space: nowrap;
      opacity: 1;
      transition: opacity var(--transition-speed);
    }

    .sidebar.collapsed .logo-text {
      display: none;
      opacity: 0;
    }

    /* SIDEBAR NAVIGATION SECTION */
    .sidebar-nav {
      flex-grow: 1;
      overflow-y: auto;
      overflow-x: hidden;
      padding: 1rem 0;
    }

    /* Custom Scrollbar for Sidebar */
    .sidebar-nav::-webkit-scrollbar {
      width: 4px;
    }

    .sidebar-nav::-webkit-scrollbar-thumb {
      background: #cbd5e0;
      border-radius: 10px;
    }

    .nav-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .nav-item {
      position: relative;
    }

    .nav-link {
      display: flex;
      align-items: center;
      padding: 0.8rem 1.5rem;
      color: var(--sidebar-text);
      text-decoration: none;
      transition: all 0.2s ease;
      white-space: nowrap;
      font-size: 0.875rem;
      font-weight: 500;
    }

    .nav-link i {
      width: 24px;
      font-size: 1.1rem;
      margin-right: 12px;
      text-align: center;
    }

    .sidebar.collapsed .nav-link {
      padding-left: 0;
      padding-right: 0;
      justify-content: center;
    }

    .sidebar.collapsed .nav-link i {
      margin-right: 0;
    }

    .sidebar.collapsed .link-text {
      display: none;
    }

    .nav-link:hover {
      background-color: #edf2f7;
      color: var(--primary);
    }

    .nav-item.active>.nav-link {
      background-color: #ebf4ff;
      color: var(--primary);
      font-weight: 600;
    }

    .nav-item.active::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 4px;
      background-color: var(--primary);
      border-radius: 0 4px 4px 0;
    }

    /* Labels: MAIÚSCULAS */
    .nav-label {
      padding: 1.5rem 1.5rem 0.5rem;
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #a0aec0;
      font-weight: 700;
    }

    .sidebar.collapsed .nav-label {
      display: none;
    }

    /* SUBMENUS */
    .submenu {
      list-style: none;
      padding-left: 1rem;
      display: none;
      background-color: #f7fafc;
    }

    .nav-item.open .submenu {
      display: block;
    }

    .sidebar.collapsed .submenu {
      display: none !important;
    }

    .submenu .nav-link {
      padding: 0.6rem 1.5rem 0.6rem 2.5rem;
      font-size: 0.8rem;
    }

    /* SIDEBAR FOOTER */
    .sidebar-footer {
      padding: 1rem 1.5rem;
      border-top: 1px solid var(--border-color);
      font-size: 0.7rem;
      color: #a0aec0;
      text-align: center;
    }

    .sidebar.collapsed .sidebar-footer {
      display: none;
    }

    /* MAIN WRAPPER (Header + Content) */
    .main-wrapper {
      margin-left: var(--sidebar-width);
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      transition: margin-left var(--transition-speed) ease;
      min-width: 0;
      /* Important for responsive grids */
    }

    .sidebar-collapsed .main-wrapper {
      margin-left: var(--sidebar-collapsed-width);
    }

    /* HEADER WRAPPER */
    .header-area {
      height: var(--header-height);
      background-color: #fff;
      border-bottom: 1px solid var(--border-color);
      position: sticky;
      top: 0;
      z-index: 999;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 1.5rem;
    }

    .header-left {
      display: flex;
      align-items: center;
    }

    .page-title-box {
      margin-left: 1.5rem;
    }

    .page-title {
      font-size: 1.125rem;
      font-weight: 600;
      color: #1a202c;
      margin: 0;
      line-height: 1.2;
    }

    .breadcrumb {
      margin: 0;
      padding: 0;
      background: transparent;
      font-size: 0.75rem;
    }

    .breadcrumb-item+.breadcrumb-item::before {
      content: "\f105";
      font-family: "Font Awesome 6 Free";
      font-weight: 900;
      color: #a0aec0;
    }

    .breadcrumb-item a {
      color: var(--primary);
      text-decoration: none;
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }

    .notification-btn {
      color: #a0aec0;
      position: relative;
      font-size: 1.25rem;
      cursor: pointer;
      transition: color 0.2s;
    }

    .notification-btn:hover {
      color: var(--primary);
    }

    .user-profile {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.5rem;
      border-radius: 0.5rem;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    .user-profile:hover {
      background-color: #f7fafc;
    }

    .user-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--border-color);
    }

    .user-info {
      display: none;
    }

    @media (min-width: 992px) {
      .user-info {
        display: block;
        text-align: left;
      }
    }

    .user-name {
      font-size: 0.875rem;
      font-weight: 600;
      color: #2d3748;
      display: block;
    }

    .user-role {
      font-size: 0.75rem;
      color: #718096;
      display: block;
    }

    .dropdown-menu-user {
      border: 0;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      border-radius: 0.5rem;
      margin-top: 0.5rem;
      padding: 0.5rem;
      min-width: 200px;
    }

    .dropdown-item-user {
      padding: 0.625rem 1rem;
      border-radius: 0.375rem;
      font-size: 0.875rem;
      color: #4a5568;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      transition: all 0.2s;
    }

    .dropdown-item-user:hover {
      background-color: #f7fafc;
      color: var(--primary);
    }

    /* CONTENT AREA */
    .content-area {
      padding: 24px;
      flex-grow: 1;
    }

    /* RESPONSIVE — MOBILE / TABLET */
    @media (max-width: 991.98px) {
      /* Sidebar sai da tela por padrão em mobile */
      .sidebar {
        position: fixed !important;
        top: 0;
        left: 0;
        height: 100vh;
        width: var(--sidebar-width) !important;
        transform: translateX(-100%);
        transition: transform var(--transition-speed) ease !important;
        z-index: 1050;
        box-shadow: 4px 0 24px rgba(0,0,0,0.18);
      }
      /* Sidebar visível quando ativado pelo botão hamburger */
      .sidebar.show {
        transform: translateX(0) !important;
      }
      /* Conteúdo ocupa 100% da tela — sem margem lateral */
      .main-wrapper {
        margin-left: 0 !important;
      }
      /* Overlay escuro atrás do sidebar (fecha ao clicar fora) */
      .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        z-index: 1045;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
      }
      .sidebar-overlay.active {
        display: block;
      }
      /* Em mobile o sidebar nunca fica colapsado — exibe tudo normalmente */
      .sidebar.collapsed .link-text {
        display: inline !important;
      }
      .sidebar.collapsed .nav-label {
        display: block !important;
      }
      .sidebar.collapsed .nav-link {
        padding: 0.8rem 1.5rem !important;
        justify-content: flex-start !important;
      }
      .sidebar.collapsed .nav-link i {
        margin-right: 12px !important;
      }
      .sidebar.collapsed .sidebar-footer {
        display: block !important;
      }
      .sidebar.collapsed .sidebar-header {
        padding: 0 1.5rem !important;
        justify-content: flex-start !important;
      }
      .sidebar.collapsed .logo-text {
        display: inline !important;
        opacity: 1 !important;
      }
    }
    /* Desktop: overlay nunca aparece */
    @media (min-width: 992px) {
      .sidebar-overlay {
        display: none !important;
      }
    }
  </style>
</head>

<body>

  <!-- Overlay para fechar o sidebar em mobile ao clicar fora -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div
    class="layout-wrapper <?php echo isset($_COOKIE['sidebarCollapsed']) && $_COOKIE['sidebarCollapsed'] == 'true' ? 'sidebar-collapsed' : ''; ?>">

    <!-- SIDEBAR -->
    <aside
      class="sidebar <?php echo isset($_COOKIE['sidebarCollapsed']) && $_COOKIE['sidebarCollapsed'] == 'true' ? 'collapsed' : ''; ?>"
      id="mainSidebar">

      <!-- TOP: LOGO -->
      <div class="sidebar-header">
        <?php if (!empty($_ec->logo_path) && file_exists(BASE_PATH . '/' . ltrim($_ec->logo_path, '/'))): ?>
          <img src="/<?php echo htmlspecialchars(ltrim($_ec->logo_path, '/')); ?>"
               alt="Logo"
               style="max-height:34px;max-width:34px;object-fit:contain;border-radius:4px;">
        <?php else: ?>
          <svg class="logo-svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2L2 7L12 12L22 7L12 2Z" />
            <path d="M2 17L12 22L22 17M2 12L12 17L22 12" />
          </svg>
        <?php endif; ?>
        <span class="logo-text"><?php echo htmlspecialchars(!empty($_ecNome) ? $_ecNome : 'INLAUDO ERP'); ?></span>
      </div>

      <!-- CENTER: NAV -->
      <nav class="sidebar-nav">
        <div class="nav-label">Geral</div>
        <ul class="nav-list">
          <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : ''; ?>">
            <a href="/dashboard" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
              <i class="fas fa-chart-line"></i>
              <span class="link-text">DASHBOARD</span>
            </a>
          </li>

          <div class="nav-label">Cadastros</div>
          <li class="nav-item has-submenu <?php echo (strpos($_SERVER['REQUEST_URI'], '/clientes') !== false || strpos($_SERVER['REQUEST_URI'], '/cnes') !== false) ? 'open active' : ''; ?>">
            <a href="/clientes" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Clientes">
              <i class="fas fa-users"></i>
              <span class="link-text">CLIENTES</span>
            </a>
            <ul class="submenu">
              <li>
                <a href="/clientes" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/clientes') !== false) ? 'active' : ''; ?>">
                  <i class="fas fa-address-book me-2"></i> Todos os Clientes
                </a>
              </li>
              <li>
                <a href="/cnes" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/cnes') !== false) ? 'active' : ''; ?>">
                  <i class="fas fa-hospital me-2"></i> CNES Global
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item <?php echo (strpos($_SERVER['REQUEST_URI'], '/fornecedores') !== false || strpos($_SERVER['REQUEST_URI'], '/financeiro/fornecedores') !== false) ? 'active' : ''; ?>">
            <a href="/fornecedores" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Fornecedores">
              <i class="fas fa-truck"></i>
              <span class="link-text">FORNECEDORES</span>
            </a>
          </li>
          <li class="nav-item has-submenu <?php echo (strpos($_SERVER['REQUEST_URI'], '/medicos') !== false || strpos($_SERVER['REQUEST_URI'], '/especialidades') !== false || strpos($_SERVER['REQUEST_URI'], '/escalas') !== false || strpos($_SERVER['REQUEST_URI'], '/exames-tabela') !== false) ? 'open active' : ''; ?>">
            <a href="/medicos" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Corpo Clinico">
              <i class="fas fa-user-doctor"></i>
              <span class="link-text">CORPO CLINICO</span>
            </a>
            <ul class="submenu">
              <li><a href="/medicos"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/medicos') !== false ? 'active' : ''; ?>">MEDICOS</a></li>
              <li><a href="/especialidades"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/especialidades') !== false ? 'active' : ''; ?>">ESPECIALIDADES</a></li>
              <li><a href="/escalas"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/escalas') !== false ? 'active' : ''; ?>">ESCALAS</a></li>
              <li><a href="/exames-tabela"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/exames-tabela') !== false ? 'active' : ''; ?>">SERVIÇOS / EXAMES</a></li>
            </ul>
          </li>

          <?php if (\App\Core\Auth::can('view_colaboradores')): ?>
          <li class="nav-item has-submenu <?php echo strpos($_SERVER['REQUEST_URI'], '/colaboradores') !== false ? 'open active' : ''; ?>">
            <a href="/colaboradores" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Colaboradores">
              <i class="fas fa-users-gear"></i>
              <span class="link-text">COLABORADORES</span>
            </a>
            <ul class="submenu">
              <li><a href="/colaboradores"
                  class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/colaboradores') !== false && strpos($_SERVER['REQUEST_URI'], '/colaboradores/create') === false) ? 'active' : ''; ?>">LISTAR</a></li>
              <?php if (\App\Core\Auth::can('create_colaboradores')): ?>
              <li><a href="/colaboradores/create"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/colaboradores/create') !== false ? 'active' : ''; ?>">NOVO COLABORADOR</a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <div class="nav-label">Operacional</div>
          <li class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], '/contratos') !== false ? 'active' : ''; ?>">
            <a href="/contratos" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Contratos">
              <i class="fas fa-file-contract"></i>
              <span class="link-text">CONTRATOS</span>
            </a>
          </li>
          <li class="nav-item has-submenu">
            <a href="#" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Financeiro">
              <i class="fas fa-wallet"></i>
              <span class="link-text">FINANCEIRO</span>
            </a>
            <ul class="submenu">
              <li><a href="/financeiro/pagar"
                  class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/financeiro/pagar') !== false || strpos($_SERVER['REQUEST_URI'], '/financeiro/contas-a-pagar') !== false) ? 'active' : ''; ?>">CONTAS
                  A PAGAR</a></li>
              <li><a href="/financeiro/receber"
                  class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/financeiro/receber') !== false || strpos($_SERVER['REQUEST_URI'], '/financeiro/contas-a-receber') !== false) ? 'active' : ''; ?>">CONTAS
                  A RECEBER</a></li>
              <li><a href="/financeiro/plano-contas"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/financeiro/plano-contas') !== false ? 'active' : ''; ?>">PLANO
                  DE CONTAS</a></li>
              <li><a href="/financeiro/contas"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/financeiro/contas') !== false ? 'active' : ''; ?>"><i class="fas fa-university me-1"></i> CONTAS</a></li>
            </ul>
          </li>

          <li class="nav-item has-submenu <?php echo (strpos($_SERVER['REQUEST_URI'], '/faturamento') !== false) ? 'open active' : ''; ?>">
            <a href="#" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Faturamento">
              <i class="fas fa-file-invoice"></i>
              <span class="link-text">FATURAMENTO</span>
            </a>
            <ul class="submenu">
              <li><a href="/faturamento/notas-fiscais"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/faturamento/notas-fiscais') !== false ? 'active' : ''; ?>">NOTAS FISCAIS</a></li>
              <li><a href="/faturamento/apuracao-prestador"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/faturamento/apuracao-prestador') !== false ? 'active' : ''; ?>">APURAÇÃO PRESTADOR</a></li>
              <li><a href="/faturamento/apuracao-cliente"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/faturamento/apuracao-cliente') !== false ? 'active' : ''; ?>">APURAÇÃO CLIENTE</a></li>
            </ul>
          </li>

          <li class="nav-item has-submenu <?php echo (strpos($_SERVER['REQUEST_URI'], '/estoque') !== false) ? 'open active' : ''; ?>">
            <a href="#" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Estoque">
              <i class="fas fa-boxes"></i>
              <span class="link-text">ESTOQUE</span>
            </a>
            <ul class="submenu">
              <li><a href="/estoque/produtos"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/estoque/produtos') !== false ? 'active' : ''; ?>"><i class="fas fa-box me-1"></i> PRODUTOS</a></li>
              <li><a href="/estoque/movimentacoes"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/estoque/movimentacoes') !== false ? 'active' : ''; ?>"><i class="fas fa-exchange-alt me-1"></i> MOVIMENTAÇÕES</a></li>
              <li><a href="/estoque/compras"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/estoque/compras') !== false ? 'active' : ''; ?>"><i class="fas fa-shopping-cart me-1"></i> PEDIDOS DE COMPRA</a></li>
              <li><a href="/estoque/vendas"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/estoque/vendas') !== false ? 'active' : ''; ?>"><i class="fas fa-store me-1"></i> PEDIDOS DE VENDA</a></li>
            </ul>
          </li>

          <div class="nav-label">CRM</div>
          <li class="nav-item has-submenu <?php echo strpos($_SERVER['REQUEST_URI'], '/crm') !== false ? 'open active' : ''; ?>">
            <a href="/crm/funil" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="CRM">
              <i class="fas fa-headset"></i>
              <span class="link-text">CRM</span>
            </a>
            <ul class="submenu">
              <li><a href="/crm/funil"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/crm/funil') !== false ? 'active' : ''; ?>"><i class="fas fa-filter me-1"></i> FUNIL</a></li>
              <li><a href="/crm/leads"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/crm/leads') !== false ? 'active' : ''; ?>"><i class="fas fa-user-plus me-1"></i> LEADS</a></li>
              <li><a href="/crm/oportunidades"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/crm/oportunidades') !== false ? 'active' : ''; ?>"><i class="fas fa-chart-line me-1"></i> OPORTUNIDADES</a></li>
              <li><a href="/crm/propostas"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/crm/propostas') !== false ? 'active' : ''; ?>"><i class="fas fa-file-contract me-1"></i> PROPOSTAS</a></li>
            </ul>
          </li>

          <div class="nav-label">Manutenção</div>
          <li class="nav-item has-submenu <?php echo strpos($_SERVER['REQUEST_URI'], '/manutencao') !== false ? 'open active' : ''; ?>">
            <a href="/manutencao/ordens" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Manutenção">
              <i class="fas fa-tools"></i>
              <span class="link-text">MANUTENÇÃO</span>
            </a>
            <ul class="submenu">
              <li><a href="/manutencao/ordens"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/manutencao/ordens') !== false ? 'active' : ''; ?>"><i class="fas fa-clipboard-list me-1"></i> ORDENS DE SERVIÇO</a></li>
            </ul>
          </li>

          <div class="nav-label">Configurações</div>
          <li
            class="nav-item has-submenu <?php echo (strpos($_SERVER['REQUEST_URI'], '/configuracoes') !== false) ? 'open active' : ''; ?>">
            <a href="/configuracoes" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Configurações">
              <i class="fas fa-cog"></i>
              <span class="link-text">CONFIGURAÇÕES</span>
            </a>
            <ul class="submenu">
              <li><a href="/configuracoes?tab=geral"
                  class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/configuracoes') !== false && ($_GET['tab'] ?? 'geral') === 'geral') ? 'active' : ''; ?>">GERAL</a>
              </li>
              <li><a href="/configuracoes?tab=financeiro"
                  class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/configuracoes') !== false && ($_GET['tab'] ?? '') === 'financeiro') ? 'active' : ''; ?>">FINANCEIRO</a>
              </li>
              <li><a href="/configuracoes?tab=notas-fiscais"
                  class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/configuracoes') !== false && ($_GET['tab'] ?? '') === 'notas-fiscais') ? 'active' : ''; ?>">NOTA FISCAL</a>
              </li>
            </ul>
          </li>
          <li
            class="nav-item has-submenu <?php echo (strpos($_SERVER['REQUEST_URI'], '/integracao') !== false) ? 'open active' : ''; ?>">
            <a href="#" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Integração">
              <i class="fas fa-plug"></i>
              <span class="link-text">INTEGRAÇÃO</span>
            </a>
            <ul class="submenu">
              <li><a href="/integracao/asaas"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/integracao/asaas') !== false ? 'active' : ''; ?>">ASAAS</a>
              </li>
              <li><a href="/integracao/cora"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/integracao/cora') !== false ? 'active' : ''; ?>">CORA</a>
              </li>
              <li><a href="/integracao/email"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/integracao/email') !== false ? 'active' : ''; ?>">E-MAIL</a>
              </li>
              <li><a href="/integracao/whatsapp"
                  class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/integracao/whatsapp') !== false ? 'active' : ''; ?>">WHATSAPP</a>
              </li>
            </ul>
          </li>
        </ul>
      </nav>

      <!-- BOTTOM: SYSTEM INFO -->
      <div class="sidebar-footer">
        v1.0.2 · InLaudo ERP
      </div>

    </aside>

    <!-- MAIN BODY -->
    <div class="main-wrapper">

      <!-- HEADER (PHASE 3) -->
      <header class="header-area">
        <div class="header-left">
          <button class="btn btn-link link-dark p-0" onclick="toggleSidebar()">
            <i class="fas fa-bars fa-lg"></i>
          </button>

          <div class="page-title-box">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
                <?php if (isset($breadcrumb)): ?>
                  <?php foreach ($breadcrumb as $label => $link): ?>
                    <?php if (is_numeric($label)): ?>
                      <li class="breadcrumb-item active" aria-current="page"><?php echo $link; ?></li>
                    <?php else: ?>
                      <li class="breadcrumb-item"><a href="<?php echo $link; ?>"><?php echo $label; ?></a></li>
                    <?php endif; ?>
                  <?php endforeach; ?>
                <?php endif; ?>
              </ol>
            </nav>
            <h1 class="page-title"><?php echo $title ?? 'Dashboard'; ?></h1>
          </div>
        </div>

        <div class="header-right">
          <!-- Notificações Placeholder -->
          <div class="notification-btn" title="Notificações">
            <i class="far fa-bell"></i>
          </div>

          <!-- Perfil do Usuário -->
          <div class="dropdown">
            <div class="user-profile" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <img
                src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name'] ?? 'User'); ?>&background=00529B&color=fff"
                alt="Avatar" class="user-avatar">
              <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?></span>
                <span class="user-role">Administrador</span>
              </div>
              <i class="fas fa-chevron-down ms-2 fs-xs text-muted"></i>
            </div>

            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-user" aria-labelledby="userDropdown">
              <li>
                <a class="dropdown-item dropdown-item-user" href="/perfil">
                  <i class="far fa-user"></i> Meu Perfil
                </a>
              </li>
              <li>
                <a class="dropdown-item dropdown-item-user" href="/configuracoes">
                  <i class="fas fa-cog"></i> Configurações
                </a>
              </li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li>
                <a class="dropdown-item dropdown-item-user text-danger" href="javascript:void(0);"
                  onclick="confirmLogout()">
                  <i class="fas fa-sign-out-alt"></i> Sair do Sistema
                </a>
              </li>
            </ul>
          </div>
        </div>
      </header>

      <!-- CONTENT -->
      <main class="content-area">
