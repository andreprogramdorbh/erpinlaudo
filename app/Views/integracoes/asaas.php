<?php
/**
 * ERP InLaudo - Configuração Asaas (Enterprise Layout)
 */

use App\Core\UI;

$config = $config ?? null;
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0 fw-bold">Configuração da Integração Asaas</h5>
                        <p class="text-muted small mb-0">Gerencie suas credenciais e ambiente de pagamentos</p>
                    </div>
                    <div class="col-auto">
                        <button type="button" id="btnTestarConexao" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plug me-1"></i> Testar Conexão
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-4">
                <form id="formAsaasConfig" novalidate>
                    <div class="row g-4">
                        <!-- Seção: Credenciais -->
                        <div class="col-12">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-key me-2"></i>Credenciais de API
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="api_key" class="form-label">API Access Token (Produção ou Sandbox)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted border-end-0">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" 
                                               name="api_key" 
                                               id="api_key" 
                                               class="form-control border-start-0" 
                                               placeholder="Digite seu token de acesso do Asaas"
                                               value="<?php echo !empty($config->api_key) ? '********' : ''; ?>"
                                               required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text mt-2">
                                        Você encontra este token no menu <b>Minha Conta > Integração</b> no painel do Asaas.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 text-light">

                        <!-- Seção: Ambiente -->
                        <div class="col-12">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-server me-2"></i>Ambiente e Status
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="environment" class="form-label">Ambiente</label>
                                    <select name="environment" id="environment" class="form-select" required>
                                        <option value="sandbox" <?php echo ($config->environment ?? 'sandbox') === 'sandbox' ? 'selected' : ''; ?>>Sandbox (Testes)</option>
                                        <option value="production" <?php echo ($config->environment ?? '') === 'production' ? 'selected' : ''; ?>>Produção</option>
                                    </select>
                                    <div class="form-text">Configure conforme a conta que você deseja integrar.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Status da Integração</label>
                                    <select name="status" id="status" class="form-select" required>
                                        <option value="active" <?php echo ($config->status ?? 'active') === 'active' ? 'selected' : ''; ?>>Ativo</option>
                                        <option value="inactive" <?php echo ($config->status ?? '') === 'inactive' ? 'selected' : ''; ?>>Inativo</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 text-light">

                        <!-- Seção: Webhook -->
                        <div class="col-12">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-bell me-2"></i>Configuração de Webhook
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">URL de Notificação (Endpoint Público)</label>
                                    <div class="input-group">
                                        <input type="text" 
                                               class="form-control bg-light" 
                                               value="<?php echo "https://" . $_SERVER['HTTP_HOST'] . "/api/webhooks/asaas"; ?>" 
                                               readonly>
                                        <button class="btn btn-outline-secondary" type="button" id="copyUrlBtn">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <div class="form-text mt-2">
                                        Copie esta URL e configure-a na seção de Webhooks do seu painel Asaas para receber notificações automáticas de pagamento.
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label for="webhook_token" class="form-label">Token de Autenticação Webhook (Opcional)</label>
                                    <input type="text" 
                                           name="webhook_token" 
                                           id="webhook_token" 
                                           class="form-control" 
                                           placeholder="Token definido no painel do Asaas"
                                           value="<?php echo $config->webhook_token ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-5">
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="/configuracoes" class="btn btn-light px-4">Voltar</a>
                                <button type="submit" class="btn btn-primary px-5">
                                    <i class="fas fa-save me-2"></i>Salvar Alterações
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formAsaasConfig');
    const btnTestar = document.getElementById('btnTestarConexao');
    const togglePassword = document.getElementById('togglePassword');
    const apiKeyInput = document.getElementById('api_key');
    const copyUrlBtn = document.getElementById('copyUrlBtn');

    // Toggle Visibility API Key
    togglePassword.addEventListener('click', function() {
        const type = apiKeyInput.getAttribute('type') === 'password' ? 'text' : 'password';
        apiKeyInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });

    // Copy URL shortcut
    copyUrlBtn.addEventListener('click', function() {
        const urlInput = this.parentElement.querySelector('input');
        urlInput.select();
        document.execCommand('copy');
        Swal.fire({
            icon: 'success',
            title: 'Copiado!',
            text: 'URL do Webhook copiada para a área de transferência.',
            timer: 1500,
            showConfirmButton: false
        });
    });

    // Form Submit (Save Config)
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';

        fetch('/integracao/asaas/save', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso',
                    text: data.message,
                    timer: 2000
                });
            } else {
                throw new Error(data.error || 'Erro ao salvar configuração');
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: error.message
            });
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Salvar Alterações';
        });
    });

    // Test Connection
    btnTestar.addEventListener('click', function() {
        this.disabled = true;
        const originalHtml = this.innerHTML;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Testando...';

        fetch('/integracao/asaas/test', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Conectado!',
                    text: data.message
                });
            } else {
                throw new Error(data.error || 'Conexão falhou');
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Falha na Conexão',
                text: error.message
            });
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = originalHtml;
        });
    });
});
</script>
