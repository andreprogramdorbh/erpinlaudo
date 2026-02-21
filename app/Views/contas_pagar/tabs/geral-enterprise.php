<?php

$action = $isEdit ? '/financeiro/contas-a-pagar/update/' . ($conta->id ?? '') : '/financeiro/contas-a-pagar';
$planos = $planos ?? [];
$fornecedores = $fornecedores ?? [];
?>

<form id="contaPagarFormGeral" action="<?php echo $action; ?>" method="POST" class="enterprise-form-main">

    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-file-invoice-dollar section-icon"></i>
            Dados Principais
        </h2>

        <div class="form-grid form-grid-3">
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
                <label for="fornecedor_id" class="form-label">Fornecedor</label>
                <select name="fornecedor_id" id="fornecedor_id" class="form-select">
                    <option value="">(Opcional)</option>
                    <?php foreach ($fornecedores as $f): ?>
                        <option value="<?php echo (int)$f->id; ?>" <?php echo ((int)($conta->fornecedor_id ?? 0) === (int)$f->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($f->nome ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="aberta" <?php echo ($conta->status ?? 'aberta') === 'aberta' ? 'selected' : ''; ?>>Aberta</option>
                    <option value="paga" <?php echo ($conta->status ?? '') === 'paga' ? 'selected' : ''; ?>>Paga</option>
                    <option value="cancelada" <?php echo ($conta->status ?? '') === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>
        </div>

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label for="descricao" class="form-label required">Descrição</label>
                <input type="text" name="descricao" id="descricao" class="form-control" placeholder="Ex.: Aluguel" value="<?php echo htmlspecialchars($conta->descricao ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="valor" class="form-label required">Valor</label>
                <input type="number" step="0.01" name="valor" id="valor" class="form-control" placeholder="0,00" value="<?php echo htmlspecialchars($conta->valor ?? ''); ?>" required>
            </div>
        </div>

        <div class="form-grid form-grid-4">
            <div class="form-group">
                <label for="data_vencimento" class="form-label required">Data de Vencimento</label>
                <input type="date" name="data_vencimento" id="data_vencimento" class="form-control" value="<?php echo htmlspecialchars($conta->data_vencimento ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="data_pagamento" class="form-label">Data de Pagamento</label>
                <input type="date" name="data_pagamento" id="data_pagamento" class="form-control" value="<?php echo htmlspecialchars($conta->data_pagamento ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="codigo_barras" class="form-label">Código de Barras</label>
                <input type="text" name="codigo_barras" id="codigo_barras" class="form-control" value="<?php echo htmlspecialchars($conta->codigo_barras ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Recorrente</label>
                <div class="form-input-group">
                    <input type="checkbox" name="recorrente" id="recorrente" value="1" <?php echo !empty($conta->recorrente) ? 'checked' : ''; ?>>
                    <label for="recorrente" class="ms-2">Sim</label>
                </div>
            </div>
        </div>

        <div class="form-grid form-grid-3">
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
