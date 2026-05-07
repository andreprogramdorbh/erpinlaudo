<?php
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>
<style>
.of-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e8ecf0;
    padding: 28px;
    margin-bottom: 20px;
}
.of-card h6 {
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f4ff;
}
.connector-card {
    border: 2px solid #e8ecf0;
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all .2s;
    text-align: center;
}
.connector-card:hover { border-color: #4361ee; background: #f0f4ff; }
.connector-card.selected { border-color: #4361ee; background: #f0f4ff; }
.connector-card img { width: 48px; height: 48px; object-fit: contain; margin-bottom: 8px; }
.connector-card .nome { font-size: 12px; font-weight: 600; }
.status-badge-of {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 600;
    font-size: 14px;
}
.status-conectado { background: #e8faf0; color: #27ae60; }
.status-desconectado { background: #fdecea; color: #c0392b; }
</style>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Open Finance</h4>
        <p class="text-muted mb-0">Conta: <strong><?= htmlspecialchars($conta->nome) ?></strong></p>
    </div>
    <a href="/financeiro/contas/<?= $conta->id ?>/movimentacoes" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Voltar ao Extrato
    </a>
</div>

<?php if ($success === 'conectado'): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Conta conectada ao Open Finance com sucesso!</div>
<?php elseif ($success === 'desconectado'): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Conta desconectada do Open Finance.</div>
<?php elseif ($error === 'pluggy_nao_configurado'): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>A integração Pluggy não está configurada. <a href="/integracao/pluggy">Configurar agora</a>.</div>
<?php elseif ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Ocorreu um erro. Tente novamente.</div>
<?php endif; ?>

<!-- Status atual -->
<div class="of-card">
    <h6><i class="fas fa-plug me-2 text-primary"></i>Status da Conexão</h6>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <?php if (!empty($conta->openfinance_account_id)): ?>
                <div class="status-badge-of status-conectado mb-2">
                    <i class="fas fa-check-circle"></i>Conectada ao Open Finance
                </div>
                <div class="text-muted small">
                    Instituição: <strong><?= htmlspecialchars($conta->openfinance_connector ?? 'Não identificada') ?></strong><br>
                    ID da Conta: <code><?= htmlspecialchars($conta->openfinance_account_id) ?></code><br>
                    <?php if (!empty($conta->openfinance_last_sync)): ?>
                        Última sincronização: <?= date('d/m/Y H:i', strtotime($conta->openfinance_last_sync)) ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="status-badge-of status-desconectado mb-2">
                    <i class="fas fa-times-circle"></i>Não conectada
                </div>
                <div class="text-muted small">Esta conta ainda não está vinculada ao Open Finance.</div>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2">
            <?php if (!empty($conta->openfinance_account_id)): ?>
                <button class="btn btn-success" onclick="sincronizarAgora()" id="btnSyncNow">
                    <i class="fas fa-sync me-2"></i>Sincronizar Agora
                </button>
                <button class="btn btn-outline-danger" onclick="confirmarDesconectar()">
                    <i class="fas fa-unlink me-2"></i>Desconectar
                </button>
            <?php else: ?>
                <button class="btn btn-primary" onclick="iniciarConexao()">
                    <i class="fas fa-link me-2"></i>Conectar ao Open Finance
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Como funciona -->
<?php if (empty($conta->openfinance_account_id)): ?>
<div class="of-card">
    <h6><i class="fas fa-question-circle me-2 text-primary"></i>Como Funciona</h6>
    <div class="row g-4">
        <div class="col-md-4 text-center">
            <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;">
                <i class="fas fa-university text-white fa-lg"></i>
            </div>
            <h6 class="fw-bold">1. Selecione o Banco</h6>
            <p class="text-muted small">Escolha sua instituição financeira entre mais de 200 bancos disponíveis.</p>
        </div>
        <div class="col-md-4 text-center">
            <div class="rounded-circle bg-success d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;">
                <i class="fas fa-key text-white fa-lg"></i>
            </div>
            <h6 class="fw-bold">2. Autorize o Acesso</h6>
            <p class="text-muted small">Autentique com suas credenciais bancárias de forma segura via Pluggy.</p>
        </div>
        <div class="col-md-4 text-center">
            <div class="rounded-circle bg-info d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;">
                <i class="fas fa-sync text-white fa-lg"></i>
            </div>
            <h6 class="fw-bold">3. Sincronize</h6>
            <p class="text-muted small">Suas transações são importadas automaticamente e ficam disponíveis no extrato.</p>
        </div>
    </div>

    <div class="alert alert-info mt-3 mb-0">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Sobre o Pluggy:</strong> Utilizamos a plataforma <a href="https://pluggy.ai" target="_blank">Pluggy</a> como agregador financeiro regulamentado pelo Banco Central do Brasil (Open Finance Brasil).
        Para usar este recurso, configure sua chave de API Pluggy em <a href="/integracao/pluggy">Configurações → Pluggy</a>.
    </div>
</div>
<?php endif; ?>

<!-- Alternativa: Importação OFX -->
<div class="of-card">
    <h6><i class="fas fa-file-import me-2 text-primary"></i>Alternativa: Importar Arquivo OFX / OFC</h6>
    <p class="text-muted small mb-3">
        Se preferir não usar o Open Finance, você pode importar o extrato manualmente pelo arquivo OFX ou OFC
        disponível no internet banking do seu banco.
    </p>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 bg-light p-3">
                <div class="fw-semibold small mb-1"><i class="fas fa-university me-2 text-primary"></i>Banco do Brasil</div>
                <div class="text-muted" style="font-size:11px;">Internet Banking → Extrato → Exportar OFX</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 bg-light p-3">
                <div class="fw-semibold small mb-1"><i class="fas fa-university me-2 text-primary"></i>Itaú</div>
                <div class="text-muted" style="font-size:11px;">Extrato → Exportar → Formato OFX</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 bg-light p-3">
                <div class="fw-semibold small mb-1"><i class="fas fa-university me-2 text-primary"></i>Bradesco</div>
                <div class="text-muted" style="font-size:11px;">Extrato → Download → OFX/OFC</div>
            </div>
        </div>
    </div>
    <form method="POST" action="/financeiro/contas/<?= $conta->id ?>/importar-ofx" enctype="multipart/form-data" class="d-flex gap-2 align-items-end">
        <div class="flex-fill">
            <label class="form-label fw-semibold small">Arquivo OFX/OFC/QFX</label>
            <input type="file" name="arquivo" class="form-control" accept=".ofx,.ofc,.qfx" required>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-2"></i>Importar</button>
    </form>
</div>

<!-- Modal Desconectar -->
<div class="modal fade" id="modalDesconectar" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-danger"><i class="fas fa-unlink me-2"></i>Desconectar</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"><p class="mb-0">Deseja desconectar esta conta do Open Finance? As movimentações já importadas serão mantidas.</p></div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <a href="/financeiro/contas/<?= $conta->id ?>/openfinance/desconectar" class="btn btn-danger btn-sm">Desconectar</a>
            </div>
        </div>
    </div>
</div>

<script>
function iniciarConexao() {
    // Abre o widget Pluggy para seleção de banco
    fetch('/financeiro/contas/<?= $conta->id ?>/openfinance/connect-token', { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            if (d.error) { alert('❌ ' + d.error); return; }
            if (d.connect_token) {
                // Carrega o widget Pluggy
                const script = document.createElement('script');
                script.src = 'https://cdn.pluggy.ai/pluggy-connect/v2.1.0/pluggy-connect.js';
                script.onload = function() {
                    PluggyConnect.init({
                        connectToken: d.connect_token,
                        onSuccess: function(itemData) {
                            // Salva o item_id retornado
                            fetch('/financeiro/contas/<?= $conta->id ?>/openfinance/salvar', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ item_id: itemData.item.id })
                            }).then(() => {
                                location.href = '/financeiro/contas/<?= $conta->id ?>/openfinance?success=conectado';
                            });
                        },
                        onError: function(err) {
                            alert('❌ Erro ao conectar: ' + err.message);
                        }
                    });
                };
                document.head.appendChild(script);
            }
        })
        .catch(() => alert('❌ Erro de comunicação.'));
}

function confirmarDesconectar() {
    new bootstrap.Modal(document.getElementById('modalDesconectar')).show();
}

function sincronizarAgora() {
    const btn = document.getElementById('btnSyncNow');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sincronizando...';
    fetch('/financeiro/contas/<?= $conta->id ?>/openfinance/sincronizar', { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            if (d.success) { alert('✅ ' + d.msg); location.reload(); }
            else { alert('❌ ' + (d.error || 'Erro ao sincronizar.')); btn.disabled = false; btn.innerHTML = '<i class="fas fa-sync me-2"></i>Sincronizar Agora'; }
        });
}
</script>
