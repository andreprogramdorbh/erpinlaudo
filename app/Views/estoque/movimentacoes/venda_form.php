<?php
$esc    = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$isEdit = !empty($pedido);
$action = $isEdit
    ? '/estoque/vendas/' . (int)$pedido->id . '/update'
    : '/estoque/vendas';
$itens  = $pedido->itens ?? [];
?>
<style>
.form-section { background:#fff; border-radius:12px; padding:24px; box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:20px; }
.form-section-title { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#6b7280; margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid #f3f4f6; }
.itens-table th { background:#f8fafc; font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:#6b7280; font-weight:600; padding:8px 10px; }
.itens-table td { padding:8px 10px; vertical-align:middle; }
.btn-add-item { border:2px dashed #d1d5db; background:transparent; color:#6b7280; border-radius:8px; padding:10px; width:100%; font-size:13px; transition:.2s; }
.btn-add-item:hover { border-color:#10b981; color:#10b981; background:#ecfdf5; }
.btn-rm { background:none; border:none; color:#ef4444; padding:4px 8px; cursor:pointer; }
.btn-rm:hover { color:#b91c1c; }
.total-bar { background:#f8fafc; border-radius:10px; padding:16px 20px; }
</style>

<div class="d-flex align-items-center gap-3 mb-4">
    <div style="width:48px;height:48px;border-radius:12px;background:#d1fae5;display:flex;align-items:center;justify-content:center;">
        <i class="fas fa-shopping-bag" style="color:#059669;font-size:20px;"></i>
    </div>
    <div>
        <h4 class="mb-0"><?= $isEdit ? 'Editar Pedido de Venda' : 'Novo Pedido de Venda' ?></h4>
        <small class="text-muted">Nº <?= $esc($numero ?? '—') ?></small>
    </div>
    <a href="/estoque/vendas" class="btn btn-outline-secondary btn-sm ms-auto">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>
</div>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i>
    <?php
    $msgs = [
        'campos_obrigatorios' => 'Preencha o nome do cliente e adicione ao menos um item.',
        'save_failed'         => 'Erro ao salvar. Tente novamente.',
    ];
    echo $msgs[$_GET['error']] ?? 'Erro desconhecido.';
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>" id="formVenda">
    <input type="hidden" name="csrf_token" value="<?= $esc($_SESSION['csrf_token'] ?? '') ?>">
    <input type="hidden" name="numero" value="<?= $esc($numero ?? '') ?>">
    <input type="hidden" name="valor_produtos"    id="hValorProdutos"   value="<?= $esc($pedido->valor_produtos ?? '0') ?>">
    <input type="hidden" name="valor_total"       id="hValorTotal"      value="<?= $esc($pedido->valor_total ?? '0') ?>">
    <input type="hidden" name="valor_custo_total" id="hValorCustoTotal" value="<?= $esc($pedido->valor_custo_total ?? '0') ?>">
    <input type="hidden" name="margem_total"      id="hMargemTotal"     value="<?= $esc($pedido->margem_total ?? '0') ?>">

    <!-- CLIENTE -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-user me-2"></i>Cliente</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Nome do Cliente <span class="text-danger">*</span></label>
                <input type="text" name="cliente_nome" class="form-control" required
                    placeholder="Nome completo ou razão social"
                    value="<?= $esc($pedido->cliente_nome ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">CPF / CNPJ</label>
                <input type="text" name="cliente_cpf_cnpj" class="form-control"
                    placeholder="000.000.000-00"
                    value="<?= $esc($pedido->cliente_cpf_cnpj ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Telefone</label>
                <input type="text" name="cliente_telefone" class="form-control"
                    placeholder="(00) 00000-0000"
                    value="<?= $esc($pedido->cliente_telefone ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">E-mail</label>
                <input type="email" name="cliente_email" class="form-control"
                    placeholder="cliente@email.com"
                    value="<?= $esc($pedido->cliente_email ?? '') ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold">Endereço de Entrega</label>
                <input type="text" name="endereco_entrega" class="form-control"
                    placeholder="Rua, número, bairro, cidade"
                    value="<?= $esc($pedido->endereco_entrega ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Data do Pedido</label>
                <input type="date" name="data_pedido" class="form-control"
                    value="<?= $esc($pedido->data_pedido ?? date('Y-m-d')) ?>">
            </div>
        </div>
    </div>

    <!-- ITENS -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-list me-2"></i>Itens do Pedido</div>
        <div class="table-responsive mb-3">
            <table class="table itens-table align-middle" id="tabelaItens">
                <thead>
                    <tr>
                        <th style="width:30%">PRODUTO / DESCRIÇÃO</th>
                        <th style="width:8%">UNID.</th>
                        <th style="width:9%">QTD</th>
                        <th style="width:12%">PREÇO VENDA</th>
                        <th style="width:10%">CUSTO UNIT.</th>
                        <th style="width:8%">DESC. %</th>
                        <th style="width:11%">TOTAL</th>
                        <th style="width:7%">MARGEM</th>
                        <th style="width:5%"></th>
                    </tr>
                </thead>
                <tbody id="corpoItens">
                    <?php if (!empty($itens)): ?>
                    <?php foreach ($itens as $item): ?>
                    <tr class="linha-item">
                        <td>
                            <input type="hidden" name="item_produto_id[]" value="<?= (int)($item->produto_id ?? 0) ?>">
                            <input type="text" name="item_descricao[]" class="form-control form-control-sm"
                                placeholder="Descrição" required
                                value="<?= $esc($item->descricao ?? $item->produto_nome ?? '') ?>">
                        </td>
                        <td><input type="text" name="item_unidade[]" class="form-control form-control-sm" value="<?= $esc($item->unidade ?? 'UN') ?>" style="width:60px"></td>
                        <td><input type="text" name="item_quantidade[]" class="form-control form-control-sm item-qty" value="<?= number_format((float)($item->quantidade ?? 1), 3, ',', '') ?>" oninput="recalcularLinha(this)"></td>
                        <td><input type="text" name="item_preco_unitario[]" class="form-control form-control-sm item-preco" value="<?= number_format((float)($item->preco_unitario ?? 0), 2, ',', '.') ?>" oninput="recalcularLinha(this)"></td>
                        <td><input type="text" name="item_preco_custo[]" class="form-control form-control-sm item-custo" value="<?= number_format((float)($item->preco_custo ?? 0), 2, ',', '.') ?>" oninput="recalcularLinha(this)"></td>
                        <td><input type="number" name="item_desconto_perc[]" class="form-control form-control-sm item-desc" value="<?= (float)($item->desconto_perc ?? 0) ?>" min="0" max="100" step="0.01" oninput="recalcularLinha(this)"></td>
                        <td><span class="item-total fw-bold"><?= number_format((float)($item->valor_total ?? 0), 2, ',', '.') ?></span></td>
                        <td><span class="item-margem small"></span></td>
                        <td><button type="button" class="btn-rm" onclick="removerLinha(this)"><i class="fas fa-times"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr class="linha-item">
                        <td>
                            <input type="hidden" name="item_produto_id[]" value="">
                            <input type="text" name="item_descricao[]" class="form-control form-control-sm" placeholder="Descrição" required>
                        </td>
                        <td><input type="text" name="item_unidade[]" class="form-control form-control-sm" value="UN" style="width:60px"></td>
                        <td><input type="text" name="item_quantidade[]" class="form-control form-control-sm item-qty" value="1" oninput="recalcularLinha(this)"></td>
                        <td><input type="text" name="item_preco_unitario[]" class="form-control form-control-sm item-preco" value="0,00" oninput="recalcularLinha(this)"></td>
                        <td><input type="text" name="item_preco_custo[]" class="form-control form-control-sm item-custo" value="0,00" oninput="recalcularLinha(this)"></td>
                        <td><input type="number" name="item_desconto_perc[]" class="form-control form-control-sm item-desc" value="0" min="0" max="100" step="0.01" oninput="recalcularLinha(this)"></td>
                        <td><span class="item-total fw-bold">0,00</span></td>
                        <td><span class="item-margem small"></span></td>
                        <td><button type="button" class="btn-rm" onclick="removerLinha(this)"><i class="fas fa-times"></i></button></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn-add-item" onclick="adicionarLinha()">
            <i class="fas fa-plus me-2"></i> Adicionar Item
        </button>
    </div>

    <!-- VALORES E CONDIÇÕES -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-calculator me-2"></i>Valores e Condições</div>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Frete (R$)</label>
                <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="text" name="valor_frete" id="valorFrete" class="form-control"
                        value="<?= number_format((float)($pedido->valor_frete ?? 0), 2, ',', '.') ?>"
                        oninput="recalcularTotais()">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Desconto Geral (R$)</label>
                <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="text" name="valor_desconto" id="valorDesconto" class="form-control"
                        value="<?= number_format((float)($pedido->valor_desconto ?? 0), 2, ',', '.') ?>"
                        oninput="recalcularTotais()">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Forma de Pagamento</label>
                <select name="forma_pagamento" class="form-select">
                    <option value="">Selecione...</option>
                    <?php
                    $formas = ['dinheiro' => 'Dinheiro', 'pix' => 'PIX', 'cartao_credito' => 'Cartão de Crédito',
                               'cartao_debito' => 'Cartão de Débito', 'boleto' => 'Boleto', 'transferencia' => 'Transferência'];
                    foreach ($formas as $val => $lbl):
                    ?>
                    <option value="<?= $val ?>" <?= ($pedido->forma_pagamento ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="rascunho"   <?= ($pedido->status ?? 'rascunho') === 'rascunho'   ? 'selected' : '' ?>>Rascunho</option>
                    <option value="confirmado" <?= ($pedido->status ?? '') === 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
                    <option value="entregue"   <?= ($pedido->status ?? '') === 'entregue'   ? 'selected' : '' ?>>Entregue</option>
                    <option value="faturado"   <?= ($pedido->status ?? '') === 'faturado'   ? 'selected' : '' ?>>Faturado</option>
                    <option value="cancelado"  <?= ($pedido->status ?? '') === 'cancelado'  ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Condição de Pagamento</label>
                <input type="text" name="condicao_pagamento" class="form-control"
                    placeholder="Ex: À vista, 30 dias, 30/60/90..."
                    value="<?= $esc($pedido->condicao_pagamento ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Observações</label>
                <textarea name="observacoes" class="form-control" rows="1"
                    placeholder="Observações sobre o pedido..."><?= $esc($pedido->observacoes ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <!-- TOTAIS -->
    <div class="total-bar mb-4">
        <div class="row">
            <div class="col-md-8"></div>
            <div class="col-md-4">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted small">Subtotal Produtos:</span>
                    <strong id="dispValorProdutos">R$ 0,00</strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted small">Frete:</span>
                    <span id="dispValorFrete">R$ 0,00</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted small">Desconto:</span>
                    <span id="dispValorDesconto" class="text-danger">- R$ 0,00</span>
                </div>
                <div class="d-flex justify-content-between border-top pt-2 mb-1">
                    <span class="fw-bold">TOTAL:</span>
                    <strong id="dispValorTotal" style="font-size:18px;color:#059669;">R$ 0,00</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted small">Margem Bruta:</span>
                    <span id="dispMargem" class="fw-semibold">R$ 0,00</span>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <a href="/estoque/vendas" class="btn btn-outline-secondary">
            <i class="fas fa-times me-1"></i> Cancelar
        </a>
        <button type="submit" class="btn btn-success fw-bold px-4">
            <i class="fas fa-save me-1"></i>
            <?= $isEdit ? 'Salvar Alterações' : 'Criar Pedido de Venda' ?>
        </button>
    </div>
</form>

<script>
function parseBR(v) {
    return parseFloat(String(v).replace(/\./g, '').replace(',', '.')) || 0;
}
function fmtBR(v) {
    return v.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function recalcularLinha(el) {
    const tr    = el.closest('tr');
    const qty   = parseBR(tr.querySelector('.item-qty').value);
    const preco = parseBR(tr.querySelector('.item-preco').value);
    const custo = parseBR(tr.querySelector('.item-custo').value);
    const desc  = parseFloat(tr.querySelector('.item-desc').value) || 0;
    const tot   = qty * preco * (1 - desc / 100);
    const marg  = tot - (qty * custo);
    tr.querySelector('.item-total').textContent = fmtBR(tot);
    const mEl = tr.querySelector('.item-margem');
    mEl.textContent = 'M: R$ ' + fmtBR(marg);
    mEl.style.color = marg >= 0 ? '#059669' : '#ef4444';
    recalcularTotais();
}

function recalcularTotais() {
    let subtotal = 0, custoTotal = 0;
    document.querySelectorAll('#corpoItens .linha-item').forEach(tr => {
        const qty   = parseBR(tr.querySelector('.item-qty')?.value || '0');
        const preco = parseBR(tr.querySelector('.item-preco')?.value || '0');
        const custo = parseBR(tr.querySelector('.item-custo')?.value || '0');
        const desc  = parseFloat(tr.querySelector('.item-desc')?.value || '0') || 0;
        subtotal   += qty * preco * (1 - desc / 100);
        custoTotal += qty * custo;
    });
    const frete   = parseBR(document.getElementById('valorFrete').value);
    const descont = parseBR(document.getElementById('valorDesconto').value);
    const total   = subtotal + frete - descont;
    const margem  = total - custoTotal;

    document.getElementById('dispValorProdutos').textContent = 'R$ ' + fmtBR(subtotal);
    document.getElementById('dispValorFrete').textContent    = 'R$ ' + fmtBR(frete);
    document.getElementById('dispValorDesconto').textContent = '- R$ ' + fmtBR(descont);
    document.getElementById('dispValorTotal').textContent    = 'R$ ' + fmtBR(total);
    const mEl = document.getElementById('dispMargem');
    mEl.textContent = 'R$ ' + fmtBR(margem);
    mEl.style.color = margem >= 0 ? '#059669' : '#ef4444';

    document.getElementById('hValorProdutos').value   = subtotal.toFixed(2);
    document.getElementById('hValorTotal').value      = total.toFixed(2);
    document.getElementById('hValorCustoTotal').value = custoTotal.toFixed(2);
    document.getElementById('hMargemTotal').value     = margem.toFixed(2);
}

function adicionarLinha() {
    const tbody = document.getElementById('corpoItens');
    const tr = document.createElement('tr');
    tr.className = 'linha-item';
    tr.innerHTML = `
        <td>
            <input type="hidden" name="item_produto_id[]" value="">
            <input type="text" name="item_descricao[]" class="form-control form-control-sm" placeholder="Descrição" required>
        </td>
        <td><input type="text" name="item_unidade[]" class="form-control form-control-sm" value="UN" style="width:60px"></td>
        <td><input type="text" name="item_quantidade[]" class="form-control form-control-sm item-qty" value="1" oninput="recalcularLinha(this)"></td>
        <td><input type="text" name="item_preco_unitario[]" class="form-control form-control-sm item-preco" value="0,00" oninput="recalcularLinha(this)"></td>
        <td><input type="text" name="item_preco_custo[]" class="form-control form-control-sm item-custo" value="0,00" oninput="recalcularLinha(this)"></td>
        <td><input type="number" name="item_desconto_perc[]" class="form-control form-control-sm item-desc" value="0" min="0" max="100" step="0.01" oninput="recalcularLinha(this)"></td>
        <td><span class="item-total fw-bold">0,00</span></td>
        <td><span class="item-margem small"></span></td>
        <td><button type="button" class="btn-rm" onclick="removerLinha(this)"><i class="fas fa-times"></i></button></td>
    `;
    tbody.appendChild(tr);
}

function removerLinha(btn) {
    const linhas = document.querySelectorAll('#corpoItens .linha-item');
    if (linhas.length <= 1) { alert('O pedido deve ter ao menos um item.'); return; }
    btn.closest('tr').remove();
    recalcularTotais();
}

document.addEventListener('DOMContentLoaded', recalcularTotais);
</script>
