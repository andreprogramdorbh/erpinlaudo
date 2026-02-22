<?php
$valor      = number_format((float)($conta->valor ?? 0), 2, ',', '.');
$vencimento = !empty($conta->data_vencimento)
    ? date('d/m/Y', strtotime($conta->data_vencimento))
    : '—';
$expiracao  = !empty($pixExpiracao)
    ? date('d/m/Y H:i', strtotime($pixExpiracao))
    : '—';
?>

<div class="portal-page-header">
    <a href="/portal/contas-a-pagar" class="portal-back-btn">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
    <h1 class="portal-page-title">
        <i class="fas fa-qrcode"></i> Pagar com PIX
    </h1>
</div>

<div class="portal-pix-container">

    <!-- Resumo da cobrança -->
    <div class="portal-card mb-4">
        <div class="portal-card-header">
            <i class="fas fa-file-invoice-dollar"></i> Resumo da Cobrança
        </div>
        <div class="portal-card-body">
            <div class="portal-info-grid">
                <div class="portal-info-item">
                    <span class="portal-info-label">Descrição</span>
                    <span class="portal-info-value"><?php echo htmlspecialchars($conta->descricao ?? ''); ?></span>
                </div>
                <div class="portal-info-item">
                    <span class="portal-info-label">Valor</span>
                    <span class="portal-info-value portal-valor-destaque">R$ <?php echo $valor; ?></span>
                </div>
                <div class="portal-info-item">
                    <span class="portal-info-label">Vencimento</span>
                    <span class="portal-info-value"><?php echo $vencimento; ?></span>
                </div>
                <?php if ($expiracao !== '—'): ?>
                <div class="portal-info-item">
                    <span class="portal-info-label">QR Code válido até</span>
                    <span class="portal-info-value text-warning"><?php echo $expiracao; ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- QR Code PIX -->
    <div class="portal-card portal-pix-card">
        <div class="portal-card-header">
            <i class="fas fa-qrcode"></i> QR Code PIX
        </div>
        <div class="portal-card-body text-center">

            <div class="portal-pix-steps mb-4">
                <div class="portal-pix-step">
                    <span class="portal-pix-step-num">1</span>
                    <span>Abra o app do seu banco</span>
                </div>
                <div class="portal-pix-step">
                    <span class="portal-pix-step-num">2</span>
                    <span>Escolha pagar via PIX</span>
                </div>
                <div class="portal-pix-step">
                    <span class="portal-pix-step-num">3</span>
                    <span>Escaneie o QR Code ou cole o código</span>
                </div>
            </div>

            <div class="portal-qrcode-wrapper">
                <img src="data:image/png;base64,<?php echo htmlspecialchars($pixEncodedImage); ?>"
                     alt="QR Code PIX"
                     class="portal-qrcode-img"
                     id="pixQrCodeImg">
            </div>

            <?php if (!empty($pixPayload)): ?>
            <div class="portal-pix-copy mt-4">
                <p class="portal-pix-copy-label">
                    <i class="fas fa-copy"></i> PIX Copia e Cola
                </p>
                <div class="portal-pix-copy-group">
                    <input type="text"
                           id="pixPayload"
                           class="portal-pix-copy-input"
                           value="<?php echo htmlspecialchars($pixPayload); ?>"
                           readonly>
                    <button type="button"
                            class="portal-pix-copy-btn"
                            onclick="copiarPix()"
                            id="btnCopiarPix">
                        <i class="fas fa-copy"></i> Copiar
                    </button>
                </div>
                <p class="portal-pix-copy-hint" id="pixCopiadoMsg" style="display:none;">
                    <i class="fas fa-check-circle text-success"></i> Código copiado!
                </p>
            </div>
            <?php endif; ?>

            <div class="alert alert-info mt-4 text-start">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Pagamento instantâneo:</strong> Após o pagamento, o status será atualizado
                automaticamente em alguns segundos. Você pode fechar esta tela com segurança.
            </div>

            <div class="portal-pix-actions mt-3">
                <button type="button" class="portal-btn portal-btn-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimir QR Code
                </button>
                <a href="/portal/contas-a-pagar" class="portal-btn portal-btn-outline">
                    <i class="fas fa-arrow-left"></i> Voltar às Contas
                </a>
            </div>
        </div>
    </div>

</div>

<script>
function copiarPix() {
    var input = document.getElementById('pixPayload');
    var btn   = document.getElementById('btnCopiarPix');
    var msg   = document.getElementById('pixCopiadoMsg');

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value).then(function () {
            btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
            btn.classList.add('portal-btn-success');
            msg.style.display = 'block';
            setTimeout(function () {
                btn.innerHTML = '<i class="fas fa-copy"></i> Copiar';
                btn.classList.remove('portal-btn-success');
                msg.style.display = 'none';
            }, 3000);
        });
    } else {
        input.select();
        document.execCommand('copy');
        msg.style.display = 'block';
        setTimeout(function () { msg.style.display = 'none'; }, 3000);
    }
}

// Verifica o status a cada 10 segundos para atualizar automaticamente
(function verificarStatus() {
    var contaId = <?php echo (int)($conta->id ?? 0); ?>;
    if (!contaId) return;

    setTimeout(function poll() {
        fetch('/portal/contas-a-pagar/status/' + contaId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'recebida') {
                    window.location.href = '/portal/contas-a-pagar?info=pix_confirmado';
                } else {
                    setTimeout(poll, 10000);
                }
            })
            .catch(function() { setTimeout(poll, 15000); });
    }, 10000);
})();
</script>

<style>
@media print {
    .portal-header, .portal-nav, .portal-back-btn,
    .portal-pix-steps, .portal-pix-copy, .portal-pix-actions,
    .alert, .portal-info-grid { display: none !important; }
    .portal-qrcode-img { width: 300px !important; height: 300px !important; }
}
</style>
