<?php
/**
 * ERP InLaudo - Aba de Segurança do Formulário de Perfil
 * Troca de senha e configurações de segurança
 */

$action = '/perfil/changePassword';
?>

<form id="perfilFormSeguranca" action="<?php echo $action; ?>" method="POST" class="enterprise-form-main">

    <!-- Seção: Alteração de Senha -->
    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-key section-icon"></i>
            Alteração de Senha
        </h2>

        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i>
            Para sua segurança, informe sua senha atual antes de definir uma nova.
        </div>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="current_password" class="form-label required">Senha Atual</label>
                <div class="input-group">
                    <input type="password" name="current_password" id="current_password" class="form-control"
                        placeholder="Digite sua senha atual" required>
                    <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="new_password" class="form-label required">Nova Senha</label>
                <div class="input-group">
                    <input type="password" name="new_password" id="new_password" class="form-control"
                        placeholder="Mínimo 6 caracteres" required minlength="6">
                    <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength mt-2">
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                    </div>
                    <small class="text-muted" id="passwordStrengthText">Força da senha</small>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label required">Confirmar Nova Senha</label>
                <div class="input-group">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                        placeholder="Repita a nova senha" required>
                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Seção: Configurações de Segurança -->
    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-shield-alt section-icon"></i>
            Configurações de Segurança
        </h2>

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label class="form-label">Autenticação de Dois Fatores</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="twoFactorEnabled" disabled>
                    <label class="form-check-label" for="twoFactorEnabled">
                        Habilitar 2FA (Em breve)
                    </label>
                </div>
                <small class="text-muted">Proteção adicional com código via SMS ou app</small>
            </div>

            <div class="form-group">
                <label class="form-label">Notificações de Segurança</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="securityNotifications" checked disabled>
                    <label class="form-check-label" for="securityNotifications">
                        Receber alertas por e-mail
                    </label>
                </div>
                <small class="text-muted">Seja notificado sobre atividades suspeitas</small>
            </div>
        </div>
    </section>

    <!-- Seção: Histórico de Acessos -->
    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-history section-icon"></i>
            Histórico de Acessos Recentes
        </h2>

        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>IP</th>
                        <th>Dispositivo/Navegador</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $usuario->last_login ? date('d/m/Y H:i', strtotime($usuario->last_login)) : 'N/A'; ?></td>
                        <td><?php echo $_SERVER['REMOTE_ADDR'] ?? 'N/A'; ?></td>
                        <td><?php echo $_SERVER['HTTP_USER_AGENT'] ? substr($_SERVER['HTTP_USER_AGENT'], 0, 50) . '...' : 'N/A'; ?></td>
                        <td><span class="badge bg-success">Sucesso</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Alertas de Feedback -->
    <div id="segurancaAlerts"></div>

</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('perfilFormSeguranca');
    const alertsContainer = document.getElementById('segurancaAlerts');
    
    // Toggle de visibilidade das senhas
    function setupPasswordToggle(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        
        toggle.addEventListener('click', function() {
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }
    
    setupPasswordToggle('toggleCurrentPassword', 'current_password');
    setupPasswordToggle('toggleNewPassword', 'new_password');
    setupPasswordToggle('toggleConfirmPassword', 'confirm_password');
    
    // Verificação de força da senha
    const newPasswordInput = document.getElementById('new_password');
    const strengthBar = document.getElementById('passwordStrength');
    const strengthText = document.getElementById('passwordStrengthText');
    
    newPasswordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        let strengthLabel = 'Fraca';
        
        if (password.length >= 6) strength += 25;
        if (password.length >= 10) strength += 25;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
        if (/[0-9]/.test(password)) strength += 12.5;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 12.5;
        
        if (strength <= 25) {
            strengthBar.className = 'progress-bar bg-danger';
            strengthLabel = 'Fraca';
        } else if (strength <= 50) {
            strengthBar.className = 'progress-bar bg-warning';
            strengthLabel = 'Média';
        } else if (strength <= 75) {
            strengthBar.className = 'progress-bar bg-info';
            strengthLabel = 'Boa';
        } else {
            strengthBar.className = 'progress-bar bg-success';
            strengthLabel = 'Forte';
        }
        
        strengthBar.style.width = strength + '%';
        strengthText.textContent = `Força da senha: ${strengthLabel}`;
    });
    
    // Validação de confirmação de senha
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    confirmPasswordInput.addEventListener('input', function() {
        if (this.value !== newPasswordInput.value) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });
    
    // Exibe mensagens de feedback baseadas nos parâmetros da URL
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const success = urlParams.get('success');
    
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        alertsContainer.appendChild(alertDiv);
        
        // Remove o alerta após 5 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
    
    if (error) {
        const messages = {
            'missing_fields': 'Por favor, preencha todos os campos obrigatórios.',
            'password_mismatch': 'A nova senha e a confirmação não coincidem.',
            'password_too_short': 'A nova senha deve ter pelo menos 6 caracteres.',
            'wrong_current_password': 'A senha atual informada está incorreta.',
            'update_failed': 'Não foi possível atualizar sua senha. Tente novamente.',
            'unauthorized': 'Ação não autorizada.',
            'exception': 'Ocorreu um erro inesperado. Tente novamente.'
        };
        showAlert(messages[error] || 'Erro desconhecido.', 'error');
    }
    
    if (success) {
        const messages = {
            'password_changed': 'Sua senha foi alterada com sucesso!'
        };
        showAlert(messages[success] || 'Operação concluída.', 'success');
    }
});
</script>
