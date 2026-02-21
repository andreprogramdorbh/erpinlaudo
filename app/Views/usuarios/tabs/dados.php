<?php
/**
 * ERP InLaudo - Aba de Dados do Formulário de Usuário
 * Informações pessoais do novo usuário
 */

$action = '/configuracoes/usuarios/store';
?>

<form id="usuarioCreateForm" action="<?php echo $action; ?>" method="POST" class="enterprise-form-main">

    <!-- Seção: Informações Pessoais -->
    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-user section-icon"></i>
            Informações Pessoais
        </h2>

        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i>
            O usuário receberá um e-mail automático com link para definir sua própria senha no primeiro acesso.
        </div>

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label for="name" class="form-label required">Nome Completo</label>
                <input type="text" name="name" id="name" class="form-control"
                    placeholder="Nome completo do usuário" required>
                <small class="text-muted">Como o usuário será identificado no sistema</small>
            </div>

            <div class="form-group">
                <label for="email" class="form-label required">E-mail</label>
                <input type="email" name="email" id="email" class="form-control"
                    placeholder="usuario@empresa.com" required>
                <small class="text-muted">Será usado para login e comunicações</small>
            </div>
        </div>
    </section>

    <!-- Seção: Configurações Iniciais -->
    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-cog section-icon"></i>
            Configurações Iniciais
        </h2>

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label for="status" class="form-label">Status Inicial</label>
                <select name="status" id="status" class="form-select">
                    <option value="ativo" selected>Ativo</option>
                    <option value="inativo">Inativo</option>
                </select>
                <small class="text-muted">Usuários inativos não podem acessar o sistema</small>
            </div>

            <div class="form-group">
                <label for="send_welcome" class="form-label">E-mail de Boas-vindas</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="send_welcome" name="send_welcome" value="1" checked>
                    <label class="form-check-label" for="send_welcome">
                        Enviar e-mail de boas-vindas
                    </label>
                </div>
                <small class="text-muted">Contém link para definição de senha</small>
            </div>
        </div>
    </section>

    <!-- Alertas de Feedback -->
    <div id="usuarioCreateAlerts"></div>

</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('usuarioCreateForm');
    const alertsContainer = document.getElementById('usuarioCreateAlerts');
    const emailInput = document.getElementById('email');
    
    // Exibe mensagens de feedback baseadas nos parâmetros da URL
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        alertsContainer.appendChild(alertDiv);
        
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
            'invalid_role': 'Você não tem permissão para atribuir este nível de acesso.',
            'create_failed': 'Não foi possível criar o usuário. Tente novamente.',
            'exception': 'Ocorreu um erro inesperado. Tente novamente.'
        };
        showAlert(messages[error] || 'Erro desconhecido.', 'error');
    }
    
    // Validação de e-mail em tempo real
    emailInput.addEventListener('blur', function() {
        if (this.value) {
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
    
    // Validação de nome
    const nameInput = document.getElementById('name');
    nameInput.addEventListener('blur', function() {
        if (this.value.trim().length < 3) {
            this.classList.add('is-invalid');
            showAlert('O nome deve ter pelo menos 3 caracteres.', 'error');
        } else {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });
    
    // Confirmação antes de enviar
    form.addEventListener('submit', function(e) {
        const sendWelcome = document.getElementById('send_welcome').checked;
        const email = emailInput.value;
        const name = nameInput.value;
        
        if (sendWelcome && email) {
            const confirmMsg = `Confirma a criação do usuário "${name}" com e-mail "${email}"?\n\n` +
                             `Um e-mail de boas-vindas será enviado automaticamente.`;
            
            if (!confirm(confirmMsg)) {
                e.preventDefault();
            }
        }
    });
});
</script>
