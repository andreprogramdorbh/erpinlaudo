<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1a56db">
    <title><?php echo htmlspecialchars($title ?? 'Área do Cliente'); ?> | INLAUDO</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/portal.css">
</head>
<body class="portal-body">

<?php
$portalNome  = $_SESSION['portal_cliente_nome']  ?? 'Cliente';
$portalEmail = $_SESSION['portal_cliente_email'] ?? '';
$currentUri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>

<!-- Top Navbar -->
<nav class="portal-navbar">
    <div class="portal-navbar-brand">
        <?php
        $logoPath = '/assets/logo-inlaudo.png';
        $uploadLogoDir = BASE_PATH . '/public/uploads/logo';
        if (is_dir($uploadLogoDir)) {
            $files = array_diff(scandir($uploadLogoDir), ['.', '..']);
            if (!empty($files)) {
                $logoFile = reset($files);
                $logoPath = '/uploads/logo/' . $logoFile;
            }
        }
        ?>
        <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="INLAUDO" class="portal-logo">
        <span class="portal-brand-text">Área do Cliente</span>
    </div>
    <div class="portal-navbar-actions">
        <div class="dropdown">
            <button class="portal-user-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="portal-user-avatar">
                    <?php echo strtoupper(substr($portalNome, 0, 1)); ?>
                </span>
                <span class="portal-user-name d-none d-md-inline">
                    <?php echo htmlspecialchars(mb_substr($portalNome, 0, 20)); ?>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end portal-user-menu">
                <li class="dropdown-header px-3 py-2">
                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($portalNome); ?></div>
                    <div class="small text-muted"><?php echo htmlspecialchars($portalEmail); ?></div>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item" href="/portal/perfil">
                        <i class="fa fa-user-circle me-2 text-primary"></i> Meu Perfil
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <form action="/portal/logout" method="POST" class="m-0">
                        <?php echo \App\Core\View::csrfField(); ?>
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fa fa-sign-out-alt me-2"></i> Sair
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Sidebar + Content wrapper -->
<div class="portal-wrapper">

    <!-- Sidebar (desktop) / Bottom Nav (mobile) -->
    <aside class="portal-sidebar" id="portalSidebar">
        <nav class="portal-sidenav">
            <a href="/portal/dashboard" class="portal-nav-item <?php echo $currentUri === '/portal/dashboard' ? 'active' : ''; ?>">
                <i class="fa fa-home"></i>
                <span>Painel</span>
            </a>
            <a href="/portal/contas-a-pagar" class="portal-nav-item <?php echo str_starts_with($currentUri, '/portal/contas-a-pagar') ? 'active' : ''; ?>">
                <i class="fa fa-file-invoice-dollar"></i>
                <span>Minhas Contas</span>
            </a>
            <div class="portal-nav-group">
                <div class="portal-nav-group-label">
                    <i class="fa fa-receipt"></i>
                    <span>Faturamento</span>
                    <i class="fa fa-chevron-down portal-nav-arrow ms-auto"></i>
                </div>
                <div class="portal-nav-submenu <?php echo str_starts_with($currentUri, '/portal/faturamento') ? 'open' : ''; ?>">
                    <a href="/portal/faturamento/notas-fiscais" class="portal-nav-subitem <?php echo str_starts_with($currentUri, '/portal/faturamento/notas-fiscais') ? 'active' : ''; ?>">
                        <i class="fa fa-file-alt"></i>
                        <span>Minhas Notas Fiscais</span>
                    </a>
                </div>
            </div>
        </nav>
    </aside>

    <!-- Main content -->
    <main class="portal-main">
