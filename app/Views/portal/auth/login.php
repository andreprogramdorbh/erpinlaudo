<div class="portal-auth-container">
    <div class="portal-auth-card">
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
        <div class="portal-auth-logo">
            <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="INLAUDO">
        </div>
        <h1 class="portal-auth-title">Área do Cliente</h1>
        <p class="portal-auth-subtitle">Acesse sua conta para visualizar faturas e documentos</p>

        <?php if (!empty($_GET['error'])): ?>
            <?php $errorMap = [
                'credenciais'         => 'E-mail ou senha incorretos.',
                'campos_obrigatorios' => 'Preencha todos os campos.',
                'token_invalido'      => 'Link inválido. Solicite um novo acesso.',
                'token_expirado'      => 'Link expirado. Solicite um novo acesso.',
                'sessao_expirada'     => 'Sua sessão expirou. Faça login novamente.',
            ]; ?>
            <div class="portal-alert portal-alert-danger">
                <i class="fa fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($errorMap[$_GET['error']] ?? 'Ocorreu um erro. Tente novamente.'); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['logout'])): ?>
            <div class="portal-alert portal-alert-success">
                <i class="fa fa-check-circle me-2"></i>
                Você saiu com segurança.
            </div>
        <?php endif; ?>

        <form action="/login" method="POST" class="portal-form" id="portalLoginForm">
            <?php echo \App\Core\View::csrfField(); ?>

            <div class="portal-form-group">
                <label for="email" class="portal-label">
                    <i class="fa fa-envelope me-1"></i> E-mail
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="portal-input"
                    placeholder="seu@email.com.br"
                    required
                    autofocus
                    autocomplete="email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                >
            </div>

            <div class="portal-form-group">
                <label for="senha" class="portal-label">
                    <i class="fa fa-lock me-1"></i> Senha
                </label>
                <div class="portal-input-group">
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        class="portal-input"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="portal-input-toggle" onclick="portalToggleSenha('senha')">
                        <i class="fa fa-eye" id="icon-senha"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="portal-btn portal-btn-primary w-100" id="btnLogin">
                <span class="btn-text"><i class="fa fa-sign-in-alt me-2"></i>Entrar</span>
                <span class="btn-loading d-none"><i class="fa fa-spinner fa-spin me-2"></i>Entrando...</span>
            </button>
        </form>

        <p class="portal-auth-footer">
            © <?php echo date('Y'); ?> INLAUDO. Todos os direitos reservados.
        </p>
    </div>
</div>
