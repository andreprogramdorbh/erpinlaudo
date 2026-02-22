<?php

$action   = $isEdit ? '/financeiro/contas-a-receber/update/' . ($conta->id ?? '') : '/financeiro/contas-a-receber';
$planos   = $planos   ?? [];
$clientes = $clientes ?? [];

// Meios de pagamento Asaas (geram cobrança automática)
$meiosAsaas = ['pix', 'boleto', 'checkout'];
$meioPagamentoAtual = $conta->meio_pagamento ?? '';
?>

<form id="contaReceberFormGeral" action="<?php echo $action; ?>" method="POST" class="enterprise-form-main">

    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-hand-holding-usd section-icon"></i>
            Dados Principais
        </h2>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="cliente_id" class="form-label required">Cliente</label>
                <select name="cliente_id" id="cliente_id" class="form-select" required>
                    <option value="" disabled <?php echo empty($conta->cliente_id) ? 'selected' : ''; ?>>Selecione...</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?php echo (int)$c->id; ?>" <?php echo ((int)($conta->cliente_id ?? 0) === (int)$c->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c->razao_social ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="plano_conta_id" class="form-label required">Plano de Conta</label>
                <select name="plano_conta_id" id="plano_conta_id" class="form-select" required>
                    <option value="" disabled <?php echo empty($conta->plano_conta_id) ? 'selected' : ''; ?>>Selecione...</option>
                    <?php foreach ($planos as $p): ?>
                        <option value="<?php echo (int)$p->id; ?>" <?php echo ((int)($conta->plano_conta_id ?? 0) === (int)$p->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(($p->codigo ?? '') . ' - ' . ($p->nome ?? '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="aberta"    <?php echo ($conta->status ?? 'aberta') === 'aberta'    ? 'selected' : ''; ?>>Aberta</option>
                    <option value="recebida"  <?php echo ($conta->status ?? '') === 'recebida'  ? 'selected' : ''; ?>>Recebida</option>
                    <option value="cancelada" <?php echo ($conta->status ?? '') === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>
        </div>

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label for="descricao" class="form-label required">Descrição</label>
                <input type="text" name="descricao" id="descricao" class="form-control"
                       placeholder="Ex.: Parcela contrato"
                       value="<?php echo htmlspecialchars($conta->descricao ?? ''); ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="valor" class="form-label required">Valor (R$)</label>
                <!-- O JS converte para float puro antes do envio -->
                <input type="text" name="valor" id="valor" class="form-control"
                       value="<?php echo htmlspecialchars($conta->valor ?? ''); ?>"
                       placeholder="Ex.: 1.500,00"
                       inputmode="numeric"
                       autocomplete="off"
                       required>
            </div>
        </div>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="data_vencimento" class="form-label required">Data de Vencimento</label>
                <input type="date" name="data_vencimento" id="data_vencimento" class="form-control"
                       value="<?php echo htmlspecialchars($conta->data_vencimento ?? ''); ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="data_recebimento" class="form-label">Data de Recebimento</label>
                <input type="date" name="data_recebimento" id="data_recebimento" class="form-control"
                       value="<?php echo htmlspecialchars($conta->data_recebimento ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="meio_pagamento" class="form-label">Meio de Pagamento</label>
                <select name="meio_pagamento" id="meio_pagamento" class="form-select">
                    <optgroup label="— Pagamento Manual —">
                        <option value=""          <?php echo $meioPagamentoAtual === ''             ? 'selected' : ''; ?>>(Não definido)</option>
                        <option value="dinheiro"  <?php echo $meioPagamentoAtual === 'dinheiro'     ? 'selected' : ''; ?>>Dinheiro</option>
                        <option value="transferencia" <?php echo $meioPagamentoAtual === 'transferencia' ? 'selected' : ''; ?>>Transferência</option>
                        <option value="cartao"    <?php echo $meioPagamentoAtual === 'cartao'       ? 'selected' : ''; ?>>Cartão (manual)</option>
                        <option value="outro"     <?php echo $meioPagamentoAtual === 'outro'        ? 'selected' : ''; ?>>Outro</option>
                    </optgroup>
                    <optgroup label="— Via Asaas (cobrança automática) —">
                        <option value="pix"      <?php echo $meioPagamentoAtual === 'pix'      ? 'selected' : ''; ?>>PIX (gerado pelo sistema)</option>
                        <option value="boleto"   <?php echo $meioPagamentoAtual === 'boleto'   ? 'selected' : ''; ?>>Boleto (gerado pelo sistema)</option>
                        <option value="checkout" <?php echo $meioPagamentoAtual === 'checkout' ? 'selected' : ''; ?>>Checkout Asaas (cliente escolhe o meio)</option>
                    </optgroup>
                </select>
            </div>
        </div>

        <!-- Painel de informação Asaas — exibido dinamicamente pelo JS -->
        <div id="asaas-info-panel" class="mt-3" style="display:none;">
            <!-- PIX -->
            <div id="asaas-info-pix" class="alert alert-info d-flex align-items-start gap-2" style="display:none!important;">
                <i class="fas fa-qrcode fa-lg mt-1"></i>
                <div>
                    <strong>PIX via Asaas</strong><br>
                    Ao salvar, o sistema gerará automaticamente uma cobrança PIX no Asaas.
                    O QR Code ficará disponível no portal do cliente para pagamento imediato.
                    O status é atualizado automaticamente via webhook após a confirmação.
                </div>
            </div>
            <!-- Boleto -->
            <div id="asaas-info-boleto" class="alert alert-info d-flex align-items-start gap-2" style="display:none!important;">
                <i class="fas fa-barcode fa-lg mt-1"></i>
                <div>
                    <strong>Boleto Bancário via Asaas</strong><br>
                    Ao salvar, o sistema gerará um boleto bancário no Asaas.
                    O link do boleto ficará disponível no portal do cliente para download e pagamento.
                    O status é atualizado automaticamente via webhook após a compensação.
                </div>
            </div>
            <!-- Checkout -->
            <div id="asaas-info-checkout" class="alert alert-info d-flex align-items-start gap-2" style="display:none!important;">
                <i class="fas fa-external-link-alt fa-lg mt-1"></i>
                <div>
                    <strong>Checkout Asaas (múltiplos meios)</strong><br>
                    Ao salvar, o sistema gerará um link de checkout no Asaas.
                    No portal do cliente, ao clicar em "Pagar", o cliente será redirecionado para a
                    página de pagamento do Asaas onde poderá escolher entre PIX, Boleto ou Cartão de Crédito.
                    O status é atualizado automaticamente via webhook após a confirmação.
                </div>
            </div>
            <!-- Asaas não configurado -->
            <div id="asaas-info-nao-configurado" class="alert alert-warning d-flex align-items-start gap-2" style="display:none!important;">
                <i class="fas fa-exclamation-triangle fa-lg mt-1"></i>
                <div>
                    <strong>Asaas não configurado</strong><br>
                    A integração com o Asaas não está configurada neste ambiente.
                    A conta será salva normalmente, mas a cobrança <strong>não será gerada automaticamente</strong>.
                    Configure a chave API em <a href="/configuracoes/integracoes" class="alert-link">Integrações → Asaas</a>.
                </div>
            </div>
        </div>

        <?php if ($isEdit && !empty($conta->asaas_payment_id)): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mt-2">
            <i class="fas fa-check-circle"></i>
            <div>
                <strong>Cobrança Asaas vinculada:</strong>
                <code><?php echo htmlspecialchars($conta->asaas_payment_id); ?></code>
                <?php if (!empty($conta->asaas_subscription_id)): ?>
                    &nbsp;|&nbsp; <strong>Assinatura:</strong>
                    <code><?php echo htmlspecialchars($conta->asaas_subscription_id); ?></code>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-grid form-grid-4">
            <div class="form-group">
                <label class="form-label">Recorrente</label>
                <div class="form-input-group">
                    <input type="checkbox" name="recorrente" id="recorrente" value="1"
                           <?php echo !empty($conta->recorrente) ? 'checked' : ''; ?>>
                    <label for="recorrente" class="ms-2">Sim</label>
                </div>
            </div>

            <div class="form-group">
                <label for="recorrencia_tipo" class="form-label">Tipo de Recorrência</label>
                <select name="recorrencia_tipo" id="recorrencia_tipo" class="form-select">
                    <option value="">(Opcional)</option>
                    <option value="mensal"     <?php echo ($conta->recorrencia_tipo ?? '') === 'mensal'     ? 'selected' : ''; ?>>Mensal</option>
                    <option value="semanal"    <?php echo ($conta->recorrencia_tipo ?? '') === 'semanal'    ? 'selected' : ''; ?>>Semanal</option>
                    <option value="anual"      <?php echo ($conta->recorrencia_tipo ?? '') === 'anual'      ? 'selected' : ''; ?>>Anual</option>
                    <option value="customizada"<?php echo ($conta->recorrencia_tipo ?? '') === 'customizada'? 'selected' : ''; ?>>Customizada</option>
                </select>
            </div>

            <div class="form-group">
                <label for="recorrencia_intervalo" class="form-label">Intervalo</label>
                <input type="number" name="recorrencia_intervalo" id="recorrencia_intervalo"
                       class="form-control"
                       value="<?php echo htmlspecialchars($conta->recorrencia_intervalo ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="observacoes" class="form-label">Observações</label>
                <textarea name="observacoes" id="observacoes" class="form-control" rows="2"><?php echo htmlspecialchars($conta->observacoes ?? ''); ?></textarea>
            </div>
        </div>
    </section>

</form>

<script>
// Painel de informação dinâmico para meios de pagamento Asaas
(function () {
    var asaasConfigured = <?php echo \App\Services\AsaasService::isConfigured() ? 'true' : 'false'; ?>;
    var meiosAsaas      = ['pix', 'boleto', 'checkout'];

    function updateAsaasPanel() {
        var meio  = document.getElementById('meio_pagamento');
        var panel = document.getElementById('asaas-info-panel');
        if (!meio || !panel) return;

        var val = meio.value;

        // Esconde todos os sub-painéis
        ['pix', 'boleto', 'checkout', 'nao-configurado'].forEach(function (k) {
            var el = document.getElementById('asaas-info-' + k);
            if (el) el.style.display = 'none';
        });

        if (meiosAsaas.indexOf(val) === -1) {
            panel.style.display = 'none';
            return;
        }

        panel.style.display = 'block';

        if (!asaasConfigured) {
            document.getElementById('asaas-info-nao-configurado').style.display = 'flex';
        } else {
            var target = document.getElementById('asaas-info-' + val);
            if (target) target.style.display = 'flex';
        }
    }

    var select = document.getElementById('meio_pagamento');
    if (select) {
        select.addEventListener('change', updateAsaasPanel);
        updateAsaasPanel(); // estado inicial
    }
})();
</script>
