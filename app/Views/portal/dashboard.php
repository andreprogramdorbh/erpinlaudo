<?php
$nomeExibido = $portal->nome_fantasia ?: ($portal->razao_social ?? 'Cliente');
?>

<?php if (!empty($welcome)): ?>
<div class="portal-welcome-banner">
    <i class="fa fa-party-horn me-2"></i>
    Bem-vindo(a) à Área do Cliente! Sua senha foi criada com sucesso.
    <button type="button" class="portal-welcome-close" onclick="this.parentElement.remove()">
        <i class="fa fa-times"></i>
    </button>
</div>
<?php endif; ?>

<div class="portal-page-header">
    <div>
        <h1 class="portal-page-title">Olá, <?php echo htmlspecialchars(mb_substr($nomeExibido, 0, 30)); ?>!</h1>
        <p class="portal-page-subtitle">Aqui está o resumo da sua conta</p>
    </div>
</div>

<!-- Cards de resumo -->
<div class="portal-cards-grid">
    <div class="portal-card portal-card-info">
        <div class="portal-card-icon">
            <i class="fa fa-file-invoice-dollar"></i>
        </div>
        <div class="portal-card-content">
            <div class="portal-card-value"><?php echo (int) $contasAbertas; ?></div>
            <div class="portal-card-label">Conta(s) em Aberto</div>
        </div>
        <a href="/portal/contas-a-pagar" class="portal-card-link">Ver todas <i class="fa fa-arrow-right"></i></a>
    </div>

    <div class="portal-card <?php echo $contasVencidas > 0 ? 'portal-card-danger' : 'portal-card-success'; ?>">
        <div class="portal-card-icon">
            <i class="fa fa-<?php echo $contasVencidas > 0 ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
        </div>
        <div class="portal-card-content">
            <div class="portal-card-value"><?php echo (int) $contasVencidas; ?></div>
            <div class="portal-card-label">Conta(s) Vencida(s)</div>
        </div>
        <a href="/portal/contas-a-pagar?status=aberta" class="portal-card-link">Ver detalhes <i class="fa fa-arrow-right"></i></a>
    </div>

    <div class="portal-card portal-card-warning">
        <div class="portal-card-icon">
            <i class="fa fa-dollar-sign"></i>
        </div>
        <div class="portal-card-content">
            <div class="portal-card-value">R$ <?php echo number_format((float) $totalAberto, 2, ',', '.'); ?></div>
            <div class="portal-card-label">Total em Aberto</div>
        </div>
        <a href="/portal/contas-a-pagar" class="portal-card-link">Pagar agora <i class="fa fa-arrow-right"></i></a>
    </div>
</div>

<!-- Atalhos rápidos -->
<div class="portal-quick-actions mt-4">
    <h2 class="portal-section-title">Acesso Rápido</h2>
    <div class="portal-quick-grid">
        <a href="/portal/contas-a-pagar" class="portal-quick-item">
            <i class="fa fa-file-invoice-dollar"></i>
            <span>Minhas Contas</span>
        </a>
        <a href="/portal/faturamento/notas-fiscais" class="portal-quick-item">
            <i class="fa fa-file-alt"></i>
            <span>Notas Fiscais</span>
        </a>
        <a href="/portal/perfil" class="portal-quick-item">
            <i class="fa fa-user-cog"></i>
            <span>Meu Perfil</span>
        </a>
    </div>
</div>

<!-- Dados da empresa -->
<div class="portal-company-info mt-4">
    <h2 class="portal-section-title">Seus Dados</h2>
    <div class="portal-info-card">
        <div class="portal-info-row">
            <span class="portal-info-label"><i class="fa fa-building me-2"></i>Empresa</span>
            <span class="portal-info-value"><?php echo htmlspecialchars($portal->razao_social ?? '—'); ?></span>
        </div>
        <?php if (!empty($portal->nome_fantasia)): ?>
        <div class="portal-info-row">
            <span class="portal-info-label"><i class="fa fa-tag me-2"></i>Nome Fantasia</span>
            <span class="portal-info-value"><?php echo htmlspecialchars($portal->nome_fantasia); ?></span>
        </div>
        <?php endif; ?>
        <div class="portal-info-row">
            <span class="portal-info-label"><i class="fa fa-id-card me-2"></i>CNPJ/CPF</span>
            <span class="portal-info-value"><?php echo htmlspecialchars($portal->cpf_cnpj ?? '—'); ?></span>
        </div>
        <div class="portal-info-row">
            <span class="portal-info-label"><i class="fa fa-envelope me-2"></i>E-mail</span>
            <span class="portal-info-value"><?php echo htmlspecialchars($portal->email ?? '—'); ?></span>
        </div>
        <?php if (!empty($portal->cidade)): ?>
        <div class="portal-info-row">
            <span class="portal-info-label"><i class="fa fa-map-marker-alt me-2"></i>Cidade</span>
            <span class="portal-info-value"><?php echo htmlspecialchars($portal->cidade . '/' . $portal->estado); ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>
