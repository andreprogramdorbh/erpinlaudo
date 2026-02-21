<?php

$action = $isEdit ? '/faturamento/notas-fiscais/update/' . ($nota->id ?? '') : '/faturamento/notas-fiscais';
$clientes = $clientes ?? [];
?>

<form id="notaFiscalFormGeral" action="<?php echo $action; ?>" method="POST" class="enterprise-form-main">

    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-file-invoice section-icon"></i>
            Dados da Nota
        </h2>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="cliente_id" class="form-label required">Cliente</label>
                <select name="cliente_id" id="cliente_id" class="form-select" required>
                    <option value="" disabled <?php echo empty($nota->cliente_id) ? 'selected' : ''; ?>>Selecione...</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?php echo (int)$c->id; ?>" <?php echo ((int)($nota->cliente_id ?? 0) === (int)$c->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(($c->razao_social ?? '') . ' (' . ($c->cpf_cnpj ?? '') . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="numero_nf" class="form-label required">Número</label>
                <input type="text" name="numero_nf" id="numero_nf" class="form-control" value="<?php echo htmlspecialchars($nota->numero_nf ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="serie" class="form-label required">Série</label>
                <input type="text" name="serie" id="serie" class="form-control" value="<?php echo htmlspecialchars($nota->serie ?? ''); ?>" required>
            </div>
        </div>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="data_emissao" class="form-label required">Data de Emissão</label>
                <input type="date" name="data_emissao" id="data_emissao" class="form-control" value="<?php echo htmlspecialchars($nota->data_emissao ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="valor_total" class="form-label required">Valor Total</label>
                <input type="number" step="0.01" name="valor_total" id="valor_total" class="form-control" value="<?php echo htmlspecialchars($nota->valor_total ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="rascunho" <?php echo ($nota->status ?? 'rascunho') === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                    <option value="emitida" <?php echo ($nota->status ?? '') === 'emitida' ? 'selected' : ''; ?>>Emitida</option>
                    <option value="importada" <?php echo ($nota->status ?? '') === 'importada' ? 'selected' : ''; ?>>Importada</option>
                    <option value="cancelada" <?php echo ($nota->status ?? '') === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>
        </div>

        <?php if (!empty($nota->xml_path)): ?>
            <div class="alert alert-light border mt-3 mb-0">
                <strong>XML vinculado:</strong> <?php echo htmlspecialchars($nota->xml_path); ?>
            </div>
        <?php endif; ?>

    </section>

</form>
