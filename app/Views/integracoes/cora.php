<?php
/**
 * View: Configuração da Integração Cora — Boletos
 * URL: /integracao/cora
 */
$status       = $row->status ?? 'inativo';
$isAtivo      = ($status === 'ativo');
$environment  = $config['environment'] ?? 'production';
$clientId     = $config['client_id'] ?? '';
$lastTestAt   = $config['last_test_at'] ?? null;
$webhookToken = $config['webhook_token'] ?? '';
$certExists   = $cert_exists ?? false;
$keyExists    = $key_exists ?? false;
$certOk       = $certExists && $keyExists;
?>

<style>
.cora-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 28px; margin-bottom: 24px; }
.cora-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9; }
.cora-logo { width: 48px; height: 48px; background: linear-gradient(135deg, #FF6B35, #FF8C42); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.cora-logo i { color: #fff; font-size: 22px; }
.cora-title { font-size: 1.25rem; font-weight: 700; color: #1e293b; }
.cora-subtitle { font-size: 0.85rem; color: #64748b; margin-top: 2px; }
.badge-status-ativo { background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }
.badge-status-inativo { background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }
.cert-status { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 8px; font-size: 0.85rem; font-weight: 500; margin-bottom: 8px; }
.cert-ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.cert-missing { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.info-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px 16px; font-size: 0.85rem; color: #1e40af; margin-bottom: 20px; }
.info-box ol { margin: 8px 0 0 0; padding-left: 18px; }
.info-box ol li { margin-bottom: 4px; }
.section-title { font-size: 0.9rem; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; }
.webhook-url-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; font-family: monospace; font-size: 0.82rem; color: #334155; word-break: break-all; }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-university me-2 text-warning"></i> Cora — Boletos</h1>
        <p class="page-subtitle">Configure a emissão de boletos registrados via Cora (Integração Direta)</p>
    </div>
    <div class="page-actions">
        <a href="/configuracoes" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
    </div>
</div>

<!-- Status atual -->
<div class="cora-card">
    <div class="cora-header">
        <div class="cora-logo"><i class="fas fa-university"></i></div>
        <div style="flex:1">
            <div class="cora-title">Cora — Boletos Registrados</div>
            <div class="cora-subtitle">API v2 · Integração Direta com mTLS (OAuth2)</div>
        </div>
        <span class="badge-status-<?php echo $isAtivo ? 'ativo' : 'inativo'; ?>">
            <?php echo $isAtivo ? '● Ativo' : '○ Inativo'; ?>
        </span>
    </div>

    <!-- Informações sobre a integração -->
    <div class="info-box">
        <strong><i class="fas fa-info-circle me-1"></i> Como funciona a Integração Direta Cora:</strong>
        <ol>
            <li>Acesse o <a href="https://app.cora.com.br" target="_blank">painel da Cora</a> → Configurações → Integrações → Integração Direta</li>
            <li>Gere o <strong>Client ID</strong> e baixe o <strong>certificado (.pem)</strong> e a <strong>chave privada (.key)</strong></li>
            <li>Faça o upload dos arquivos abaixo e informe o Client ID</li>
            <li>Clique em <strong>Testar Conexão</strong> para validar</li>
        </ol>
        <div style="margin-top:8px">
            <a href="https://developers.cora.com.br/docs/instrucoes-iniciais" target="_blank" class="text-primary">
                <i class="fas fa-external-link-alt me-1"></i> Documentação oficial da Cora
            </a>
        </div>
    </div>

    <!-- Status dos certificados -->
    <div class="section-title">Status dos Certificados</div>
    <div class="cert-status <?php echo $certExists ? 'cert-ok' : 'cert-missing'; ?>">
        <i class="fas fa-<?php echo $certExists ? 'check-circle' : 'times-circle'; ?>"></i>
        Certificado (.pem): <?php echo $certExists ? 'Enviado' : 'Não enviado'; ?>
    </div>
    <div class="cert-status <?php echo $keyExists ? 'cert-ok' : 'cert-missing'; ?>">
        <i class="fas fa-<?php echo $keyExists ? 'check-circle' : 'times-circle'; ?>"></i>
        Chave Privada (.key): <?php echo $keyExists ? 'Enviada' : 'Não enviada'; ?>
    </div>

    <?php if ($lastTestAt): ?>
    <div style="margin-top:12px; font-size:0.82rem; color:#64748b;">
        <i class="fas fa-clock me-1"></i> Último teste: <?php echo date('d/m/Y H:i', strtotime($lastTestAt)); ?>
    </div>
    <?php endif; ?>
</div>

<!-- Formulário de configuração -->
<div class="cora-card">
    <div class="section-title">Configuração da Integração</div>

    <form id="formCora" enctype="multipart/form-data">
        <div class="row g-3">
            <!-- Client ID -->
            <div class="col-12">
                <label class="form-label fw-semibold">Client ID <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="client_id" name="client_id"
                    value="<?php echo htmlspecialchars($clientId); ?>"
                    placeholder="Ex: 3fa85f64-5717-4562-b3fc-2c963f66afa6"
                    required>
                <div class="form-text">Obtido no painel da Cora → Configurações → Integrações → Integração Direta</div>
            </div>

            <!-- Certificado .pem -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Certificado (.pem / .crt)
                    <?php if (!$certExists): ?><span class="text-danger">*</span><?php endif; ?>
                </label>
                <input type="file" class="form-control" name="cert_file" id="cert_file"
                    accept=".pem,.crt"
                    <?php echo !$certExists ? 'required' : ''; ?>>
                <?php if ($certExists): ?>
                <div class="form-text text-success"><i class="fas fa-check-circle me-1"></i> Certificado já enviado. Envie novamente apenas para substituir.</div>
                <?php else: ?>
                <div class="form-text">Arquivo certificate.pem gerado pela Cora</div>
                <?php endif; ?>
            </div>

            <!-- Chave privada .key -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Chave Privada (.key / .pem)
                    <?php if (!$keyExists): ?><span class="text-danger">*</span><?php endif; ?>
                </label>
                <input type="file" class="form-control" name="key_file" id="key_file"
                    accept=".key,.pem"
                    <?php echo !$keyExists ? 'required' : ''; ?>>
                <?php if ($keyExists): ?>
                <div class="form-text text-success"><i class="fas fa-check-circle me-1"></i> Chave privada já enviada. Envie novamente apenas para substituir.</div>
                <?php else: ?>
                <div class="form-text">Arquivo privateKey.key gerado pela Cora</div>
                <?php endif; ?>
            </div>

            <!-- Ambiente -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">Ambiente</label>
                <select class="form-select" name="environment" id="environment">
                    <option value="production" <?php echo $environment === 'production' ? 'selected' : ''; ?>>
                        Produção (api.cora.com.br)
                    </option>
                    <option value="staging" <?php echo $environment === 'staging' ? 'selected' : ''; ?>>
                        Homologação / Stage (api.stage.cora.com.br)
                    </option>
                </select>
            </div>

            <!-- Status -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">Status</label>
                <select class="form-select" name="status" id="status">
                    <option value="active" <?php echo $isAtivo ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inactive" <?php echo !$isAtivo ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary" id="btnSalvar">
                <i class="fas fa-save me-1"></i> Salvar Configuração
            </button>
            <button type="button" class="btn btn-outline-success" id="btnTestar" <?php echo !$certOk || empty($clientId) ? 'disabled' : ''; ?>>
                <i class="fas fa-plug me-1"></i> Testar Conexão
            </button>
        </div>
    </form>
</div>

<!-- Webhook -->
<div class="cora-card">
    <div class="section-title">Webhook — Notificações Automáticas</div>
    <p class="text-muted" style="font-size:0.875rem;">
        Configure o webhook abaixo no painel da Cora para receber notificações automáticas de pagamento,
        vencimento e cancelamento. O sistema atualizará o status das contas a receber automaticamente.
    </p>

    <label class="form-label fw-semibold">URL do Webhook</label>
    <div class="webhook-url-box mb-3" id="webhookUrl">
        <?php echo rtrim($_SERVER['REQUEST_SCHEME'] ?? 'https', '/') . '://' . ($_SERVER['HTTP_HOST'] ?? 'erp.inlaudo.com.br'); ?>/api/webhooks/cora
    </div>

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label fw-semibold">Token de Validação (opcional)</label>
            <input type="text" class="form-control" id="webhook_token" name="webhook_token"
                value="<?php echo htmlspecialchars($webhookToken); ?>"
                placeholder="Token secreto para validar requisições do webhook">
            <div class="form-text">Se configurado, o sistema validará o header <code>X-Cora-Signature</code> em cada notificação.</div>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="button" class="btn btn-outline-secondary w-100" id="btnCopiarWebhook">
                <i class="fas fa-copy me-1"></i> Copiar URL
            </button>
        </div>
    </div>

    <div class="mt-3">
        <p class="fw-semibold mb-2" style="font-size:0.85rem;">Eventos recomendados para configurar na Cora:</p>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-success">invoice.paid</span>
            <span class="badge bg-warning text-dark">invoice.overdue</span>
            <span class="badge bg-danger">invoice.canceled</span>
            <span class="badge bg-info">invoice.in_payment</span>
        </div>
    </div>
</div>

<!-- Alertas de feedback -->
<div id="alertSucesso" class="alert alert-success d-none" role="alert">
    <i class="fas fa-check-circle me-2"></i> <span id="alertSucessoMsg"></span>
</div>
<div id="alertErro" class="alert alert-danger d-none" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i> <span id="alertErroMsg"></span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form       = document.getElementById('formCora');
    const btnSalvar  = document.getElementById('btnSalvar');
    const btnTestar  = document.getElementById('btnTestar');
    const alertOk    = document.getElementById('alertSucesso');
    const alertErr   = document.getElementById('alertErro');

    function showAlert(type, msg) {
        alertOk.classList.add('d-none');
        alertErr.classList.add('d-none');
        if (type === 'success') {
            document.getElementById('alertSucessoMsg').textContent = msg;
            alertOk.classList.remove('d-none');
            alertOk.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            document.getElementById('alertErroMsg').textContent = msg;
            alertErr.classList.remove('d-none');
            alertErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Salvar configuração
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        btnSalvar.disabled = true;
        btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Salvando...';

        const fd = new FormData(form);

        // Adiciona o token de webhook
        fd.append('webhook_token', document.getElementById('webhook_token').value);

        try {
            const res  = await fetch('/integracao/cora/save', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                showAlert('success', data.message || 'Configuração salva com sucesso!');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('error', data.error || 'Erro ao salvar configuração.');
            }
        } catch (err) {
            showAlert('error', 'Erro de comunicação: ' + err.message);
        } finally {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = '<i class="fas fa-save me-1"></i> Salvar Configuração';
        }
    });

    // Testar conexão
    btnTestar.addEventListener('click', async function () {
        btnTestar.disabled = true;
        btnTestar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Testando...';

        try {
            const res  = await fetch('/integracao/cora/test', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                showAlert('success', data.message || 'Conexão com a Cora estabelecida com sucesso!');
            } else {
                showAlert('error', data.error || 'Falha na conexão com a Cora.');
            }
        } catch (err) {
            showAlert('error', 'Erro de comunicação: ' + err.message);
        } finally {
            btnTestar.disabled = false;
            btnTestar.innerHTML = '<i class="fas fa-plug me-1"></i> Testar Conexão';
        }
    });

    // Copiar URL do webhook
    document.getElementById('btnCopiarWebhook').addEventListener('click', function () {
        const url = document.getElementById('webhookUrl').textContent.trim();
        navigator.clipboard.writeText(url).then(() => {
            this.innerHTML = '<i class="fas fa-check me-1"></i> Copiado!';
            setTimeout(() => { this.innerHTML = '<i class="fas fa-copy me-1"></i> Copiar URL'; }, 2000);
        });
    });
});
</script>
