<div class="portal-page-header">
    <div>
        <h1 class="portal-page-title"><i class="fa fa-user-circle me-2"></i>Meu Perfil</h1>
        <p class="portal-page-subtitle">Gerencie suas informações e segurança</p>
    </div>
</div>

<!-- Alertas de feedback -->
<?php if (!empty($_GET['success'])): ?>
    <?php $msgs = ['senha_alterada' => 'Senha alterada com sucesso!']; ?>
    <div class="portal-alert portal-alert-success mb-3">
        <i class="fa fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($msgs[$_GET['success']] ?? 'Operação realizada com sucesso!'); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
    <?php $erros = [
        'senha_atual_incorreta' => 'A senha atual informada está incorreta.',
        'senha_curta'           => 'A nova senha deve ter pelo menos 8 caracteres.',
        'senhas_diferentes'     => 'As novas senhas não coincidem.',
    ]; ?>
    <div class="portal-alert portal-alert-danger mb-3">
        <i class="fa fa-exclamation-circle me-2"></i>
        <?php echo htmlspecialchars($erros[$_GET['error']] ?? 'Ocorreu um erro.'); ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Dados da empresa -->
    <div class="col-12 col-lg-6">
        <div class="portal-section-card">
            <h2 class="portal-section-card-title">
                <i class="fa fa-building me-2 text-primary"></i>Dados da Empresa
            </h2>
            <div class="portal-info-card">
                <div class="portal-info-row">
                    <span class="portal-info-label">Razão Social</span>
                    <span class="portal-info-value"><?php echo htmlspecialchars($portal->razao_social ?? '—'); ?></span>
                </div>
                <?php if (!empty($portal->nome_fantasia)): ?>
                <div class="portal-info-row">
                    <span class="portal-info-label">Nome Fantasia</span>
                    <span class="portal-info-value"><?php echo htmlspecialchars($portal->nome_fantasia); ?></span>
                </div>
                <?php endif; ?>
                <div class="portal-info-row">
                    <span class="portal-info-label">CNPJ/CPF</span>
                    <span class="portal-info-value"><?php echo htmlspecialchars($portal->cpf_cnpj ?? '—'); ?></span>
                </div>
                <div class="portal-info-row">
                    <span class="portal-info-label">E-mail de Acesso</span>
                    <span class="portal-info-value"><?php echo htmlspecialchars($portal->email ?? '—'); ?></span>
                </div>
                <?php if (!empty($portal->telefone)): ?>
                <div class="portal-info-row">
                    <span class="portal-info-label">Telefone</span>
                    <span class="portal-info-value"><?php echo htmlspecialchars($portal->telefone); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($portal->cidade)): ?>
                <div class="portal-info-row">
                    <span class="portal-info-label">Cidade</span>
                    <span class="portal-info-value"><?php echo htmlspecialchars($portal->cidade . '/' . $portal->estado); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($portal->ultimo_acesso)): ?>
                <div class="portal-info-row">
                    <span class="portal-info-label">Último Acesso</span>
                    <span class="portal-info-value text-muted">
                        <?php echo date('d/m/Y H:i', strtotime($portal->ultimo_acesso)); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Alterar senha -->
    <div class="col-12 col-lg-6">
        <div class="portal-section-card">
            <h2 class="portal-section-card-title">
                <i class="fa fa-lock me-2 text-primary"></i>Alterar Senha
            </h2>
            <form action="/portal/perfil/alterar-senha" method="POST" class="portal-form" id="formAlterarSenha">
                <?php echo \App\Core\View::csrfField(); ?>

                <div class="portal-form-group">
                    <label for="senha_atual" class="portal-label">Senha Atual</label>
                    <div class="portal-input-group">
                        <input
                            type="password"
                            id="senha_atual"
                            name="senha_atual"
                            class="portal-input"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="portal-input-toggle" onclick="portalToggleSenha('senha_atual')">
                            <i class="fa fa-eye" id="icon-senha_atual"></i>
                        </button>
                    </div>
                </div>

                <div class="portal-form-group">
                    <label for="nova_senha" class="portal-label">Nova Senha</label>
                    <div class="portal-input-group">
                        <input
                            type="password"
                            id="nova_senha"
                            name="nova_senha"
                            class="portal-input"
                            placeholder="Mínimo 8 caracteres"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            oninput="portalCheckSenha()"
                        >
                        <button type="button" class="portal-input-toggle" onclick="portalToggleSenha('nova_senha')">
                            <i class="fa fa-eye" id="icon-nova_senha"></i>
                        </button>
                    </div>
                    <div class="portal-senha-strength mt-2" id="senhaStrength">
                        <div class="portal-strength-bar">
                            <div class="portal-strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="portal-strength-label" id="strengthLabel"></span>
                    </div>
                </div>

                <div class="portal-form-group">
                    <label for="nova_senha_confirm" class="portal-label">Confirmar Nova Senha</label>
                    <div class="portal-input-group">
                        <input
                            type="password"
                            id="nova_senha_confirm"
                            name="nova_senha_confirm"
                            class="portal-input"
                            placeholder="Repita a nova senha"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            oninput="portalCheckConfirmacao()"
                        >
                        <button type="button" class="portal-input-toggle" onclick="portalToggleSenha('nova_senha_confirm')">
                            <i class="fa fa-eye" id="icon-nova_senha_confirm"></i>
                        </button>
                    </div>
                    <div id="matchMsg" class="portal-match-msg d-none"></div>
                </div>

                <button type="submit" class="portal-btn portal-btn-primary w-100">
                    <i class="fa fa-save me-2"></i>Salvar Nova Senha
                </button>
            </form>
        </div>
    </div>
</div>
