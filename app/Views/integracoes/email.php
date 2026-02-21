<?php
/**
 * ERP InLaudo - Configuração E-mail (Enterprise Layout)
 */

$config = $config ?? [];
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0 fw-bold">Configuração de E-mail (SMTP)</h5>
                        <p class="text-muted small mb-0">Configure o envio transacional do sistema (alertas, tokens e testes)</p>
                    </div>
                    <div class="col-auto">
                        <button type="button" id="btnTestarEmail" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-paper-plane me-1"></i> Enviar E-mail de Teste
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <form id="formEmailConfig" novalidate>
                    <div class="row g-4">
                        <div class="col-12">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-server me-2"></i>Servidor SMTP
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="host" class="form-label">Host <span class="text-danger">*</span></label>
                                    <input type="text" name="host" id="host" class="form-control" required value="<?php echo htmlspecialchars((string)($config['host'] ?? '')); ?>" placeholder="smtp.seudominio.com">
                                </div>
                                <div class="col-md-3">
                                    <label for="port" class="form-label">Porta <span class="text-danger">*</span></label>
                                    <input type="number" name="port" id="port" class="form-control" required value="<?php echo htmlspecialchars((string)($config['port'] ?? 587)); ?>" min="1" max="65535">
                                </div>
                                <div class="col-md-3">
                                    <label for="protocol" class="form-label">Protocolo <span class="text-danger">*</span></label>
                                    <select name="protocol" id="protocol" class="form-select" required>
                                        <option value="tls" <?php echo (($config['protocol'] ?? 'tls') === 'tls') ? 'selected' : ''; ?>>TLS (STARTTLS)</option>
                                        <option value="ssl" <?php echo (($config['protocol'] ?? '') === 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 text-light">

                        <div class="col-12">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-key me-2"></i>Autenticação
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Usuário <span class="text-danger">*</span></label>
                                    <input type="text" name="username" id="username" class="form-control" required value="<?php echo htmlspecialchars((string)($config['username'] ?? '')); ?>" placeholder="usuario@seudominio.com">
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Senha</label>
                                    <input type="password" name="password" id="password" class="form-control" value="<?php echo !empty($config['password_enc']) ? '********' : ''; ?>" placeholder="(mantém a atual se não alterar)">
                                    <div class="form-text">A senha é armazenada criptografada no banco.</div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 text-light">

                        <div class="col-12">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-at me-2"></i>Remetente
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="from_email" class="form-label">From (E-mail)</label>
                                    <input type="email" name="from_email" id="from_email" class="form-control" value="<?php echo htmlspecialchars((string)($config['from_email'] ?? '')); ?>" placeholder="noreply@seudominio.com">
                                </div>
                                <div class="col-md-6">
                                    <label for="from_name" class="form-label">From (Nome)</label>
                                    <input type="text" name="from_name" id="from_name" class="form-control" value="<?php echo htmlspecialchars((string)($config['from_name'] ?? 'ERP InLaudo')); ?>" placeholder="ERP InLaudo">
                                </div>
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="active" <?php echo (($config['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Ativo</option>
                                        <option value="inactive" <?php echo (($config['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inativo</option>
                                    </select>
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
    const form = document.getElementById('formEmailConfig');
    const btnTestar = document.getElementById('btnTestarEmail');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';

        fetch('/integracao/email/save', {
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

    btnTestar.addEventListener('click', function() {
        this.disabled = true;
        const originalHtml = this.innerHTML;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

        fetch('/integracao/email/test', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Enviado!',
                    text: data.message
                });
            } else {
                throw new Error(data.error || 'Falha ao enviar e-mail');
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Falha',
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
