<?php

$action = $isEdit ? '/financeiro/contas-a-receber/update/' . ($conta->id ?? '') : '/financeiro/contas-a-receber';
$planos = $planos ?? [];
$clientes = $clientes ?? [];
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
                    <option value="aberta" <?php echo ($conta->status ?? 'aberta') === 'aberta' ? 'selected' : ''; ?>>Aberta</option>
                    <option value="recebida" <?php echo ($conta->status ?? '') === 'recebida' ? 'selected' : ''; ?>>Recebida</option>
                    <option value="cancelada" <?php echo ($conta->status ?? '') === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>
        </div>

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label for="descricao" class="form-label required">Descrição</label>
                <input type="text" name="descricao" id="descricao" class="form-control" placeholder="Ex.: Parcela contrato" value="<?php echo htmlspecialchars($conta->descricao ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="valor_display" class="form-label required">Valor (R$)</label>
                <!-- O JS transforma este campo em display formatado + input hidden com float puro -->
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
                <input type="date" name="data_vencimento" id="data_vencimento" class="form-control" value="<?php echo htmlspecialchars($conta->data_vencimento ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="data_recebimento" class="form-label">Data de Recebimento</label>
                <input type="date" name="data_recebimento" id="data_recebimento" class="form-control" value="<?php echo htmlspecialchars($conta->data_recebimento ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="meio_pagamento" class="form-label">Meio de Pagamento</label>
                <select name="meio_pagamento" id="meio_pagamento" class="form-select">
                    <option value="">(Opcional)</option>
                    <option value="pix" <?php echo ($conta->meio_pagamento ?? '') === 'pix' ? 'selected' : ''; ?>>PIX</option>
                    <option value="boleto" <?php echo ($conta->meio_pagamento ?? '') === 'boleto' ? 'selected' : ''; ?>>Boleto</option>
                    <option value="cartao" <?php echo ($conta->meio_pagamento ?? '') === 'cartao' ? 'selected' : ''; ?>>Cartão</option>
                    <option value="dinheiro" <?php echo ($conta->meio_pagamento ?? '') === 'dinheiro' ? 'selected' : ''; ?>>Dinheiro</option>
                    <option value="transferencia" <?php echo ($conta->meio_pagamento ?? '') === 'transferencia' ? 'selected' : ''; ?>>Transferência</option>
                    <option value="outro" <?php echo ($conta->meio_pagamento ?? '') === 'outro' ? 'selected' : ''; ?>>Outro</option>
                </select>
            </div>
        </div>

        <!-- Informações de Integração com Asaas -->
        <div class="asaas-integration-info" style="display: none;">
            <div class="form-section mt-4">
                <h3 class="form-section-title">
                    <i class="fas fa-credit-card section-icon"></i>
                    Integração com Meios Digitais
                </h3>
                
                <div class="asaas-status mb-3">
                    <span class="badge bg-secondary">Verificando configuração...</span>
                </div>
                
                <div class="payment-method-info">
                    <!-- Mensagens específicas serão inseridas aqui via JavaScript -->
                </div>
                
                <div class="alert alert-info d-flex align-items-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <div>
                        <strong>Como funciona:</strong> Ao salvar, o sistema irá gerar automaticamente a cobrança no Asaas e enviar o link de pagamento por e-mail ao cliente.
                    </div>
                </div>
            </div>
        </div>

        <div class="form-grid form-grid-4">
            <div class="form-group">
                <label class="form-label">Recorrente</label>
                <div class="form-input-group">
                    <input type="checkbox" name="recorrente" id="recorrente" value="1" <?php echo !empty($conta->recorrente) ? 'checked' : ''; ?>>
                    <label for="recorrente" class="ms-2">Sim</label>
                </div>
            </div>

            <div class="form-group">
                <label for="recorrencia_tipo" class="form-label">Tipo de Recorrência</label>
                <select name="recorrencia_tipo" id="recorrencia_tipo" class="form-select">
                    <option value="">(Opcional)</option>
                    <option value="mensal" <?php echo ($conta->recorrencia_tipo ?? '') === 'mensal' ? 'selected' : ''; ?>>Mensal</option>
                    <option value="semanal" <?php echo ($conta->recorrencia_tipo ?? '') === 'semanal' ? 'selected' : ''; ?>>Semanal</option>
                    <option value="anual" <?php echo ($conta->recorrencia_tipo ?? '') === 'anual' ? 'selected' : ''; ?>>Anual</option>
                    <option value="customizada" <?php echo ($conta->recorrencia_tipo ?? '') === 'customizada' ? 'selected' : ''; ?>>Customizada</option>
                </select>
            </div>

            <div class="form-group">
                <label for="recorrencia_intervalo" class="form-label">Intervalo</label>
                <input type="number" name="recorrencia_intervalo" id="recorrencia_intervalo" class="form-control" value="<?php echo htmlspecialchars($conta->recorrencia_intervalo ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="observacoes" class="form-label">Observações</label>
                <textarea name="observacoes" id="observacoes" class="form-control" rows="2"><?php echo htmlspecialchars($conta->observacoes ?? ''); ?></textarea>
            </div>
        </div>
    </section>

</form>
