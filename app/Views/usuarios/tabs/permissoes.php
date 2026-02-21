<?php
/**
 * ERP InLaudo - Aba de Permissões do Formulário de Usuário
 * Configuração de nível de acesso e permissões
 */

$currentUser = $current_user ?? null;
?>

<!-- Seção: Nível de Acesso -->
<section class="form-section">
    <h2 class="form-section-title">
        <i class="fas fa-shield-alt section-icon"></i>
        Nível de Acesso
    </h2>

    <div class="alert alert-warning mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Atenção:</strong> O nível de acesso determina o que o usuário pode fazer no sistema.
    </div>

    <div class="form-grid form-grid-1">
        <div class="form-group">
            <label for="role" class="form-label required">Cargo/Nível de Acesso</label>
            <select name="role" id="role" class="form-select" required>
                <?php if ($currentUser && $currentUser->role === 'superadmin'): ?>
                    <option value="superadmin">Superadmin (Acesso Total)</option>
                    <option value="admin">Admin (Operacional)</option>
                    <option value="user" selected>User (Básico)</option>
                <?php elseif ($currentUser && $currentUser->role === 'admin'): ?>
                    <option value="user" selected>User (Básico)</option>
                <?php else: ?>
                    <option value="user" selected>User (Básico)</option>
                <?php endif; ?>
            </select>
        </div>
    </div>

    <!-- Descrição dos Níveis -->
    <div class="card bg-light border-0 mt-3">
        <div class="card-body">
            <h6 class="card-title mb-3">
                <i class="fas fa-info-circle me-2"></i>
                Descrição dos Níveis de Acesso
            </h6>
            
            <div class="accordion" id="rolesAccordion">
                <?php if ($currentUser && $currentUser->role === 'superadmin'): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#superadminRole">
                                <span class="badge bg-danger me-2">Superadmin</span>
                                Acesso Total ao Sistema
                            </button>
                        </h2>
                        <div id="superadminRole" class="accordion-collapse collapse show" data-bs-parent="#rolesAccordion">
                            <div class="accordion-body">
                                <ul class="mb-0">
                                    <li>Gerenciar todos os usuários (incluindo admins e superadmins)</li>
                                    <li>Acesso a todas as configurações do sistema</li>
                                    <li>Visualizar logs de auditoria</li>
                                    <li>Configurar integrações e sistemas</li>
                                    <li>Acesso irrestrito a todos os módulos</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#adminRole">
                                <span class="badge bg-warning me-2">Admin</span>
                                Acesso Operacional
                            </button>
                        </h2>
                        <div id="adminRole" class="accordion-collapse collapse" data-bs-parent="#rolesAccordion">
                            <div class="accordion-body">
                                <ul class="mb-0">
                                    <li>Gerenciar apenas usuários do tipo "User"</li>
                                    <li>Acesso a módulos operacionais (Clientes, Financeiro, etc.)</li>
                                    <li>Não pode acessar configurações avançadas</li>
                                    <li>Não pode visualizar logs de auditoria</li>
                                    <li>Não pode gerenciar outros admins</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#userRole">
                            <span class="badge bg-info me-2">User</span>
                            Acesso Básico
                        </button>
                    </h2>
                    <div id="userRole" class="accordion-collapse collapse" data-bs-parent="#rolesAccordion">
                        <div class="accordion-body">
                            <ul class="mb-0">
                                <li>Acesso apenas aos módulos liberados</li>
                                <li>Pode visualizar e editar seus próprios dados</li>
                                <li>Não acessa área de configurações</li>
                                <li>Não gerencia outros usuários</li>
                                <li>Operações básicas do dia a dia</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Seção: Permissões Específicas -->
<section class="form-section">
    <h2 class="form-section-title">
        <i class="fas fa-list-check section-icon"></i>
        Permissões Específicas
    </h2>

    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        As permissões específicas são herdadas automaticamente com base no nível de acesso selecionado.
    </div>

    <div class="form-grid form-grid-2">
        <div class="form-group">
            <label class="form-label">Módulos Financeiros</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="finance_access" disabled>
                <label class="form-check-label" for="finance_access">
                    Acesso a Contas a Pagar/Receber
                </label>
            </div>
            <small class="text-muted">Disponível para Admin e Superadmin</small>
        </div>

        <div class="form-group">
            <label class="form-label">Módulo de Clientes</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="clients_access" disabled>
                <label class="form-check-label" for="clients_access">
                    Gerenciar Clientes
                </label>
            </div>
            <small class="text-muted">Disponível para todos os níveis</small>
        </div>

        <div class="form-group">
            <label class="form-label">Relatórios</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="reports_access" disabled>
                <label class="form-check-label" for="reports_access">
                    Visualizar Relatórios
                </label>
            </div>
            <small class="text-muted">Disponível para Admin e Superadmin</small>
        </div>

        <div class="form-group">
            <label class="form-label">Configurações</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="settings_access" disabled>
                <label class="form-check-label" for="settings_access">
                    Acessar Configurações
                </label>
            </div>
            <small class="text-muted">Apenas Superadmin</small>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    
    // Atualiza permissões baseadas no role selecionado
    function updatePermissions() {
        const role = roleSelect.value;
        const financeAccess = document.getElementById('finance_access');
        const clientsAccess = document.getElementById('clients_access');
        const reportsAccess = document.getElementById('reports_access');
        const settingsAccess = document.getElementById('settings_access');
        
        // Reset all checkboxes
        [financeAccess, clientsAccess, reportsAccess, settingsAccess].forEach(cb => {
            cb.checked = false;
        });
        
        // Set permissions based on role
        switch(role) {
            case 'superadmin':
                financeAccess.checked = true;
                clientsAccess.checked = true;
                reportsAccess.checked = true;
                settingsAccess.checked = true;
                break;
            case 'admin':
                financeAccess.checked = true;
                clientsAccess.checked = true;
                reportsAccess.checked = true;
                settingsAccess.checked = false;
                break;
            case 'user':
                financeAccess.checked = false;
                clientsAccess.checked = true;
                reportsAccess.checked = false;
                settingsAccess.checked = false;
                break;
        }
    }
    
    roleSelect.addEventListener('change', updatePermissions);
    updatePermissions(); // Initialize on load
});
</script>
