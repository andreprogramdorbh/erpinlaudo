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
        <h1 class="portal-auth-title">Criar sua Senha</h1>
        <p class="portal-auth-subtitle">
            Bem-vindo! Defina uma senha para acessar a Área do Cliente.
        </p>

        <?php if (!empty($email)): ?>
            <div class="portal-auth-email-badge">
                <i class="fa fa-envelope me-2"></i>
                <?php echo htmlspecialchars($email); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['error'])): ?>
            <?php $errorMap = [
                'senha_curta'       => 'A senha deve ter pelo menos 8 caracteres.',
                'senhas_diferentes' => 'As senhas não coincidem. Tente novamente.',
            ]; ?>
            <div class="portal-alert portal-alert-danger">
                <i class="fa fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($errorMap[$_GET['error']] ?? 'Erro ao criar senha. Tente novamente.'); ?>
            </div>
        <?php endif; ?>

        <form action="/portal/primeiro-acesso" method="POST" class="portal-form" id="formPrimeiroAcesso">
            <?php echo \App\Core\View::csrfField(); ?>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token ?? ''); ?>">

            <div class="portal-form-group">
                <label for="senha" class="portal-label">
                    <i class="fa fa-lock me-1"></i> Nova Senha
                </label>
                <div class="portal-input-group">
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        class="portal-input"
                        placeholder="Mínimo 8 caracteres"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        oninput="portalCheckSenha()"
                    >
                    <button type="button" class="portal-input-toggle" onclick="portalToggleSenha('senha')">
                        <i class="fa fa-eye" id="icon-senha"></i>
                    </button>
                </div>
                <!-- Indicador de força da senha -->
                <div class="portal-senha-strength mt-2" id="senhaStrength">
                    <div class="portal-strength-bar">
                        <div class="portal-strength-fill" id="strengthFill"></div>
                    </div>
                    <span class="portal-strength-label" id="strengthLabel"></span>
                </div>
            </div>

            <div class="portal-form-group">
                <label for="senha_confirmacao" class="portal-label">
                    <i class="fa fa-lock me-1"></i> Confirmar Senha
                </label>
                <div class="portal-input-group">
                    <input
                        type="password"
                        id="senha_confirmacao"
                        name="senha_confirmacao"
                        class="portal-input"
                        placeholder="Repita a senha"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        oninput="portalCheckConfirmacao()"
                    >
                    <button type="button" class="portal-input-toggle" onclick="portalToggleSenha('senha_confirmacao')">
                        <i class="fa fa-eye" id="icon-senha_confirmacao"></i>
                    </button>
                </div>
                <div id="matchMsg" class="portal-match-msg d-none"></div>
            </div>

            <button type="submit" class="portal-btn portal-btn-primary w-100" id="btnCriar">
                <span class="btn-text"><i class="fa fa-check me-2"></i>Criar Senha e Entrar</span>
                <span class="btn-loading d-none"><i class="fa fa-spinner fa-spin me-2"></i>Criando...</span>
            </button>
        </form>

        <p class="portal-auth-footer">
            © <?php echo date('Y'); ?> INLAUDO. Todos os direitos reservados.
        </p>
    </div>
</div>
