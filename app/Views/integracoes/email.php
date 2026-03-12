<?php
/**
 * ERP InLaudo - Configuração E-mail (Enterprise Layout)
 */

$config          = $config ?? [];
$cryptoOk        = $crypto_configured ?? false;
?>

<?php if (!$cryptoOk): ?>
<!-- ============================================================
     ALERTA: APP_ENCRYPTION_KEY não configurada
     ============================================================ -->
<div class="alert alert-danger border-0 shadow-sm mb-4 d-flex align-items-start gap-3" id="alertaCriptografia" role="alert">
    <div class="flex-shrink-0 mt-1">
        <i class="fas fa-shield-alt fa-lg text-danger"></i>
    </div>
    <div class="flex-grow-1">
        <h6 class="alert-heading fw-bold mb-1">
            Criptografia não configurada — envio de e-mail bloqueado
        </h6>
        <p class="mb-2 small">
            A variável <code>APP_KEY</code> (ou <code>APP_ENCRYPTION_KEY</code>) não está definida no arquivo
            <code>.env</code> do servidor. Sem ela, a senha SMTP <strong>não pode ser salva com segurança</strong>
            e o envio de e-mails ficará indisponível.
        </p>
        <p class="mb-2 small">
            Gere uma chave segura abaixo, copie e adicione ao <code>.env</code> do servidor:
        </p>

        <!-- Gerador de chave -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button type="button" id="btnGerarChave" class="btn btn-sm btn-danger">
                <i class="fas fa-key me-1"></i> Gerar nova APP_KEY
            </button>
            <div id="chaveGeradaWrapper" class="d-none input-group" style="max-width: 520px;">
                <input type="text" id="chaveGerada" class="form-control form-control-sm font-monospace" readonly>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="btnCopiarChave" title="Copiar chave">
                    <i class="fas fa-copy" id="iconCopiar"></i>
                </button>
            </div>
            <span id="msgCopiado" class="text-success small d-none fw-semibold">
                <i class="fas fa-check me-1"></i>Copiado!
            </span>
        </div>

        <p class="mb-0 mt-2 small text-muted">
            Após adicionar a chave ao <code>.env</code>, reinicie o servidor e recarregue esta página.
        </p>
    </div>
</div>
<?php else: ?>
<!-- Badge: criptografia ativa -->
<div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center gap-2 py-2" role="alert">
    <i class="fas fa-shield-alt text-success"></i>
    <span class="small fw-semibold">Criptografia ativa — a senha SMTP será armazenada com segurança (AES-256-GCM).</span>
</div>
<?php endif; ?>

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
                        <button type="button" id="btnTestarEmail" class="btn btn-outline-primary btn-sm"
                            <?php echo !$cryptoOk ? 'disabled title="Configure a criptografia antes de testar"' : ''; ?>>
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
                                    <input type="text" name="host" id="host" class="form-control" required
                                           value="<?php echo htmlspecialchars((string)($config['host'] ?? '')); ?>"
                                           placeholder="smtp.seudominio.com">
                                </div>
                                <div class="col-md-3">
                                    <label for="port" class="form-label">Porta <span class="text-danger">*</span></label>
                                    <input type="number" name="port" id="port" class="form-control" required
                                           value="<?php echo htmlspecialchars((string)($config['port'] ?? 587)); ?>"
                                           min="1" max="65535">
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
                                    <input type="text" name="username" id="username" class="form-control" required
                                           value="<?php echo htmlspecialchars((string)($config['username'] ?? '')); ?>"
                                           placeholder="usuario@seudominio.com">
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label">
                                        Senha
                                        <?php if (!empty($config['password_enc'])): ?>
                                            <span class="badge bg-success ms-1" style="font-size:0.7rem;">
                                                <i class="fas fa-lock me-1"></i>Configurada
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="password" class="form-control"
                                               value="<?php echo !empty($config['password_enc']) ? '********' : ''; ?>"
                                               placeholder="<?php echo !empty($config['password_enc']) ? '(mantém a atual se não alterar)' : 'Senha de App Google (16 caracteres)'; ?>"
                                               <?php echo !$cryptoOk ? 'disabled' : ''; ?>>
                                        <button class="btn btn-outline-secondary" type="button" id="btnToggleSenha"
                                                title="Mostrar/ocultar senha" <?php echo !$cryptoOk ? 'disabled' : ''; ?>>
                                            <i class="fas fa-eye" id="iconSenha"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        <?php if (!$cryptoOk): ?>
                                            <span class="text-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                Configure a <code>APP_KEY</code> no <code>.env</code> para habilitar o campo de senha.
                                            </span>
                                        <?php else: ?>
                                            A senha é armazenada criptografada no banco (AES-256-GCM).
                                        <?php endif; ?>
                                    </div>
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
                                    <input type="email" name="from_email" id="from_email" class="form-control"
                                           value="<?php echo htmlspecialchars((string)($config['from_email'] ?? '')); ?>"
                                           placeholder="noreply@seudominio.com">
                                </div>
                                <div class="col-md-6">
                                    <label for="from_name" class="form-label">From (Nome)</label>
                                    <input type="text" name="from_name" id="from_name" class="form-control"
                                           value="<?php echo htmlspecialchars((string)($config['from_name'] ?? 'ERP InLaudo')); ?>"
                                           placeholder="ERP InLaudo">
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
                                <button type="submit" class="btn btn-primary px-5"
                                        <?php echo !$cryptoOk ? 'disabled title="Configure a criptografia antes de salvar"' : ''; ?>>
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
document.addEventListener('DOMContentLoaded', function () {

    /* ── Toggle visibilidade da senha ─────────────────────────── */
    const btnToggleSenha = document.getElementById('btnToggleSenha');
    if (btnToggleSenha) {
        btnToggleSenha.addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon  = document.getElementById('iconSenha');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    }

    /* ── Gerador de APP_KEY ───────────────────────────────────── */
    const btnGerar  = document.getElementById('btnGerarChave');
    const wrapper   = document.getElementById('chaveGeradaWrapper');
    const inputKey  = document.getElementById('chaveGerada');
    const btnCopiar = document.getElementById('btnCopiarChave');
    const msgCop    = document.getElementById('msgCopiado');

    if (btnGerar) {
        btnGerar.addEventListener('click', function () {
            btnGerar.disabled = true;
            btnGerar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Gerando...';

            fetch('/integracao/email/gerar-chave', { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        inputKey.value = data.chave;
                        wrapper.classList.remove('d-none');
                        btnGerar.innerHTML = '<i class="fas fa-sync me-1"></i> Gerar outra';
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erro', text: data.error || 'Falha ao gerar chave.' });
                        btnGerar.innerHTML = '<i class="fas fa-key me-1"></i> Gerar nova APP_KEY';
                    }
                })
                .catch(() => {
                    Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha na requisição.' });
                    btnGerar.innerHTML = '<i class="fas fa-key me-1"></i> Gerar nova APP_KEY';
                })
                .finally(() => { btnGerar.disabled = false; });
        });
    }

    if (btnCopiar) {
        btnCopiar.addEventListener('click', function () {
            navigator.clipboard.writeText(inputKey.value).then(() => {
                document.getElementById('iconCopiar').classList.replace('fa-copy', 'fa-check');
                msgCop.classList.remove('d-none');
                setTimeout(() => {
                    document.getElementById('iconCopiar').classList.replace('fa-check', 'fa-copy');
                    msgCop.classList.add('d-none');
                }, 2500);
            }).catch(() => {
                inputKey.select();
                document.execCommand('copy');
            });
        });
    }

    /* ── Alerta de senha incompatível (chave trocada) ─────────── */
    function alertaSenhaIncompativel(contexto) {
        const campoSenha = document.getElementById('password');
        Swal.fire({
            icon: 'warning',
            title: 'Senha desatualizada',
            html: '<p>A senha foi salva com uma <strong>chave de criptografia diferente</strong> da atual.</p>' +
                  '<p>Por segurança, <strong>digite a Senha de App do Google novamente</strong> no campo Senha e clique em <em>Salvar Alterações</em>.</p>',
            confirmButtonText: 'Entendido, vou redigitar',
            confirmButtonColor: '#0d6efd'
        }).then(() => {
            // Limpa o campo e foca nele
            campoSenha.value = '';
            campoSenha.placeholder = 'Digite a Senha de App do Google (16 caracteres)';
            campoSenha.focus();
            // Destaca o campo visualmente
            campoSenha.classList.add('border-warning');
            campoSenha.addEventListener('input', function () {
                campoSenha.classList.remove('border-warning');
            }, { once: true });
        });
    }

    /* ── Salvar configuração ──────────────────────────────────── */
    const form = document.getElementById('formEmailConfig');
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData  = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';

        fetch('/integracao/email/save', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message, timer: 2000, showConfirmButton: false });
                } else if (data.error_type === 'password_key_mismatch') {
                    alertaSenhaIncompativel('save');
                } else {
                    throw new Error(data.error || 'Erro ao salvar configuração');
                }
            })
            .catch(error => {
                Swal.fire({ icon: 'error', title: 'Erro', text: error.message });
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Salvar Alterações';
            });
    });

    /* ── Enviar e-mail de teste ───────────────────────────────── */
    const btnTestar = document.getElementById('btnTestarEmail');
    btnTestar.addEventListener('click', function () {
        this.disabled = true;
        const originalHtml = this.innerHTML;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

        fetch('/integracao/email/test', { method: 'POST' })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Enviado!', text: data.message });
                } else if (data.error_type === 'password_key_mismatch') {
                    alertaSenhaIncompativel('test');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Falha no envio',
                        html: '<p>' + (data.error || 'Falha ao enviar e-mail') + '</p>' +
                              (data.error_type ? '<p class="text-muted small mb-0">Código: ' + data.error_type + '</p>' : '')
                    });
                }
            })
            .catch(error => {
                Swal.fire({ icon: 'error', title: 'Falha', text: error.message });
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = originalHtml;
            });
    });
});
</script>
