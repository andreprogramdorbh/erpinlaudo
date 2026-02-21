<?php
/**
 * ERP InLaudo - Aba Geral do Formulário de Perfil
 * Dados pessoais do usuário
 */

$action = '/perfil/update';
?>

<form id="perfilFormGeral" action="<?php echo $action; ?>" method="POST" class="enterprise-form-main">

    <!-- Seção: Informações Pessoais -->
    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-user section-icon"></i>
            Informações Pessoais
        </h2>

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label for="name" class="form-label required">Nome Completo</label>
                <input type="text" name="name" id="name" class="form-control"
                    placeholder="Seu nome completo"
                    value="<?php echo htmlspecialchars($usuario->name ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="email" class="form-label required">E-mail</label>
                <input type="email" name="email" id="email" class="form-control"
                    placeholder="seu.email@empresa.com"
                    value="<?php echo htmlspecialchars($usuario->email ?? ''); ?>" required>
            </div>
        </div>

        <div class="form-grid form-grid-2 mt-3">
            <div class="form-group">
                <label for="role" class="form-label">Cargo/Função</label>
                <input type="text" name="role_display" id="role_display" class="form-control"
                    value="<?php echo ucfirst($usuario->role ?? 'user'); ?>" readonly>
                <small class="text-muted">Seu nível de acesso no sistema (apenas leitura)</small>
            </div>

            <div class="form-group">
                <label for="created_at" class="form-label">Membro Desde</label>
                <input type="text" name="created_at_display" id="created_at_display" class="form-control"
                    value="<?php echo date('d/m/Y', strtotime($usuario->created_at ?? 'now')); ?>" readonly>
                <small class="text-muted">Data de criação da sua conta</small>
            </div>
        </div>
    </section>

    <!-- Seção: Informações Adicionais -->
    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-info-circle section-icon"></i>
            Informações do Sistema
        </h2>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="user_id" class="form-label">ID do Usuário</label>
                <input type="text" name="user_id_display" id="user_id_display" class="form-control"
                    value="#<?php echo str_pad($usuario->id ?? '0', 5, '0', STR_PAD_LEFT); ?>" readonly>
            </div>

            <div class="form-group">
                <label for="last_login" class="form-label">Último Acesso</label>
                <input type="text" name="last_login_display" id="last_login_display" class="form-control"
                    value="<?php echo $usuario->last_login ? date('d/m/Y H:i', strtotime($usuario->last_login)) : 'Primeiro acesso'; ?>" readonly>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status da Conta</label>
                <input type="text" name="status_display" id="status_display" class="form-control"
                    value="Ativo" readonly>
            </div>
        </div>
    </section>

    <!-- Alertas de Feedback -->
    <div id="perfilAlerts"></div>

</form>

<script>
// Script para gerenciar feedback visual e validações
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('perfilFormGeral');
    const alertsContainer = document.getElementById('perfilAlerts');
    
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
            'email_exists': 'Este e-mail já está em uso por outro usuário.',
            'update_failed': 'Não foi possível atualizar seus dados. Tente novamente.',
            'unauthorized': 'Ação não autorizada.',
            'exception': 'Ocorreu um erro inesperado. Tente novamente.'
        };
        showAlert(messages[error] || 'Erro desconhecido.', 'error');
    }
    
    if (success) {
        const messages = {
            'profile_updated': 'Seu perfil foi atualizado com sucesso!'
        };
        showAlert(messages[success] || 'Operação concluída.', 'success');
    }
    
    // Validação de e-mail em tempo real
    const emailInput = document.getElementById('email');
    const originalEmail = emailInput.value;
    
    emailInput.addEventListener('blur', function() {
        if (this.value !== originalEmail && this.value) {
            // Simulação de verificação (em produção, faria uma chamada AJAX)
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(this.value)) {
                this.classList.add('is-invalid');
                showAlert('Por favor, insira um e-mail válido.', 'error');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        }
    });
});
</script>
