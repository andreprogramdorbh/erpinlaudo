<?php
$editando = !empty($movimentacao);
$action   = $editando
    ? "/financeiro/contas/{$conta->id}/movimentacoes/{$movimentacao->id}/atualizar"
    : "/financeiro/contas/{$conta->id}/movimentacoes/salvar";
$error = $_GET['error'] ?? '';
?>
<style>
.form-section {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e8ecf0;
    padding: 28px;
    margin-bottom: 20px;
}
.form-section h6 {
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f4ff;
}
.tipo-btn {
    flex: 1;
    padding: 16px;
    border-radius: 10px;
    border: 2px solid #e8ecf0;
    background: #fff;
    cursor: pointer;
    text-align: center;
    transition: all .2s;
    font-weight: 600;
}
.tipo-btn.selected-credito { border-color: #2ecc71; background: #e8faf0; color: #27ae60; }
.tipo-btn.selected-debito  { border-color: #e74c3c; background: #fdecea; color: #c0392b; }
.tipo-btn:not(.selected-credito):not(.selected-debito):hover { border-color: #4361ee; }
</style>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><?= $editando ? 'Editar Movimentação' : 'Nova Movimentação' ?></h4>
        <p class="text-muted mb-0">Conta: <strong><?= htmlspecialchars($conta->nome) ?></strong></p>
    </div>
    <a href="/financeiro/contas/<?= $conta->id ?>/movimentacoes" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Voltar
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Preencha todos os campos obrigatórios.</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>">
<div class="row g-4">
    <div class="col-lg-8">
        <div class="form-section">
            <h6><i class="fas fa-exchange-alt me-2 text-primary"></i>Dados da Movimentação</h6>

            <!-- Tipo -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                <div class="d-flex gap-3">
                    <div class="tipo-btn <?= ($movimentacao->tipo ?? 'credito') === 'credito' ? 'selected-credito' : '' ?>"
                         onclick="selecionarTipo('credito', this)" id="btnCredito">
                        <i class="fas fa-arrow-down fa-lg mb-2 d-block"></i>Entrada (Crédito)
                    </div>
                    <div class="tipo-btn <?= ($movimentacao->tipo ?? '') === 'debito' ? 'selected-debito' : '' ?>"
                         onclick="selecionarTipo('debito', this)" id="btnDebito">
                        <i class="fas fa-arrow-up fa-lg mb-2 d-block"></i>Saída (Débito)
                    </div>
                </div>
                <input type="hidden" name="tipo" id="inputTipo" value="<?= htmlspecialchars($movimentacao->tipo ?? 'credito') ?>">
            </div>

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Descrição <span class="text-danger">*</span></label>
                    <input type="text" name="descricao" class="form-control" required placeholder="Ex: Pagamento fornecedor, Recebimento cliente..."
                           value="<?= htmlspecialchars($movimentacao->descricao ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Valor <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" name="valor" class="form-control" required placeholder="0,00"
                               value="<?= !empty($movimentacao->valor) ? number_format(abs((float)$movimentacao->valor), 2, ',', '.') : '' ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Data <span class="text-danger">*</span></label>
                    <input type="date" name="data_movimentacao" class="form-control" required
                           value="<?= htmlspecialchars($movimentacao->data_movimentacao ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Categoria</label>
                    <input type="text" name="categoria" class="form-control" placeholder="Ex: Aluguel, Salário..."
                           value="<?= htmlspecialchars($movimentacao->categoria ?? '') ?>" list="listCategorias">
                    <datalist id="listCategorias">
                        <?php foreach ($categoriasSugestoes ?? [] as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Número do Documento</label>
                    <input type="text" name="numero_documento" class="form-control" placeholder="Ex: NF-001, Boleto..."
                           value="<?= htmlspecialchars($movimentacao->numero_documento ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Observações</label>
                    <textarea name="observacoes" class="form-control" rows="2" placeholder="Observações internas..."><?= htmlspecialchars($movimentacao->observacoes ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-2">
                        <input type="checkbox" name="conciliada" class="form-check-input" id="checkConciliada" value="1"
                               <?= !empty($movimentacao->conciliada) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="checkConciliada">Marcar como conciliada</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-section">
            <h6><i class="fas fa-info-circle me-2 text-primary"></i>Resumo</h6>
            <div class="mb-3">
                <div class="text-muted small">Conta</div>
                <div class="fw-bold"><?= htmlspecialchars($conta->nome) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($conta->banco_nome ?? '') ?></div>
            </div>
            <div class="mb-3">
                <div class="text-muted small">Saldo Atual</div>
                <div class="fw-bold <?= (float)$conta->saldo_atual >= 0 ? 'text-success' : 'text-danger' ?>">
                    R$ <?= number_format((float)$conta->saldo_atual, 2, ',', '.') ?>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-2">
                <i class="fas fa-save me-2"></i><?= $editando ? 'Salvar Alterações' : 'Registrar Movimentação' ?>
            </button>
            <a href="/financeiro/contas/<?= $conta->id ?>/movimentacoes" class="btn btn-outline-secondary w-100">Cancelar</a>
        </div>
    </div>
</div>
</form>

<script>
function selecionarTipo(tipo, el) {
    document.getElementById('btnCredito').className = 'tipo-btn';
    document.getElementById('btnDebito').className  = 'tipo-btn';
    if (tipo === 'credito') el.classList.add('selected-credito');
    else                    el.classList.add('selected-debito');
    document.getElementById('inputTipo').value = tipo;
}
</script>
