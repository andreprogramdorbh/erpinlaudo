<?php
$esc     = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$isEntrada = ($tipo ?? 'entrada') === 'entrada';
$isAjuste  = ($tipo ?? '') === 'ajuste';
$corTipo   = $isEntrada ? '#059669' : ($isAjuste ? '#d97706' : '#dc2626');
$iconTipo  = $isEntrada ? 'fa-arrow-down' : ($isAjuste ? 'fa-sliders-h' : 'fa-arrow-up');
$labelTipo = $isEntrada ? 'Entrada de Estoque' : ($isAjuste ? 'Ajuste de Estoque' : 'Saída de Estoque');
?>
<style>
.form-section { background:#fff; border-radius:12px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,.07); margin-bottom:20px; }
.form-section-title { font-size:14px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.5px; margin-bottom:16px; padding-bottom:10px; border-bottom:2px solid #f3f4f6; }
.tipo-badge { display:inline-flex; align-items:center; gap:8px; padding:6px 16px; border-radius:20px; font-weight:700; font-size:14px; }
.produto-search-result { position:absolute; z-index:1000; background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,.1); max-height:280px; overflow-y:auto; width:100%; }
.produto-search-result .item { padding:10px 14px; cursor:pointer; border-bottom:1px solid #f3f4f6; }
.produto-search-result .item:hover { background:#f9fafb; }
.produto-search-result .item:last-child { border-bottom:none; }
.estoque-info { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:10px 14px; font-size:13px; }
</style>

<div class="d-flex align-items-center gap-3 mb-4">
    <div style="width:48px;height:48px;border-radius:12px;background:<?= $corTipo ?>22;display:flex;align-items:center;justify-content:center;">
        <i class="fas <?= $iconTipo ?>" style="color:<?= $corTipo ?>;font-size:20px;"></i>
    </div>
    <div>
        <h4 class="mb-0"><?= $labelTipo ?></h4>
        <small class="text-muted">Registre a movimentação manual de estoque</small>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i>
    <?php
    $erros = [
        'campos_obrigatorios' => 'Preencha todos os campos obrigatórios.',
        'save_failed'         => 'Erro ao salvar. Verifique os logs do sistema.',
    ];
    echo $erros[$_GET['error']] ?? 'Erro desconhecido.';
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" action="/estoque/movimentacoes" id="formMovimentacao">
    <input type="hidden" name="tipo" value="<?= $esc($tipo ?? 'entrada') ?>">

    <!-- Produto -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-box me-2"></i>Produto</div>
        <div class="row g-3">
            <div class="col-12 col-md-8">
                <label class="form-label fw-semibold">Buscar Produto <span class="text-danger">*</span></label>
                <div class="position-relative">
                    <input type="text" id="produtoBusca" class="form-control"
                           placeholder="Digite o código ou nome do produto..."
                           autocomplete="off">
                    <div id="produtoResultados" class="produto-search-result d-none"></div>
                </div>
                <input type="hidden" name="produto_id" id="produto_id" required>
                <div id="produtoInfo" class="estoque-info mt-2 d-none">
                    <div class="row">
                        <div class="col-6">
                            <strong>Estoque atual:</strong> <span id="estoqueAtual">—</span>
                        </div>
                        <div class="col-6">
                            <strong>Estoque mínimo:</strong> <span id="estoqueMin">—</span>
                        </div>
                        <div class="col-6 mt-1">
                            <strong>Preço de custo:</strong> <span id="precoCusto">—</span>
                        </div>
                        <div class="col-6 mt-1">
                            <strong>Preço de venda:</strong> <span id="precoVenda">—</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Unidade</label>
                <input type="text" name="unidade" id="unidade" class="form-control" value="UN" placeholder="UN, KG, L...">
            </div>
        </div>
    </div>

    <!-- Quantidades e Preços -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-calculator me-2"></i>Quantidades e Valores</div>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Quantidade <span class="text-danger">*</span></label>
                <input type="text" name="quantidade" id="quantidade" class="form-control"
                       placeholder="0,00" required>
            </div>
            <?php if ($isEntrada || $isAjuste): ?>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Preço Unitário (Custo)</label>
                <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="text" name="custo_unitario" id="custo_unitario" class="form-control" placeholder="0,00">
                </div>
            </div>
            <?php else: ?>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Preço Unitário (Venda)</label>
                <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="text" name="preco_unitario" id="preco_unitario" class="form-control" placeholder="0,00">
                </div>
            </div>
            <?php endif; ?>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Total Estimado</label>
                <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="text" id="totalEstimado" class="form-control" readonly style="background:#f9fafb;">
                </div>
            </div>
        </div>
    </div>

    <!-- Rastreabilidade -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-barcode me-2"></i>Rastreabilidade</div>
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Lote</label>
                <input type="text" name="lote" class="form-control" placeholder="Número do lote">
            </div>
            <?php if ($isEntrada): ?>
            <div class="col-6 col-md-4">
                <label class="form-label fw-semibold">Data de Fabricação</label>
                <input type="date" name="data_fabricacao" class="form-control">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label fw-semibold">Data de Validade</label>
                <input type="date" name="data_validade" class="form-control">
            </div>
            <?php endif; ?>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Localização</label>
                <input type="text" name="localizacao" class="form-control" placeholder="Prateleira, corredor, depósito...">
            </div>
        </div>
    </div>

    <!-- Motivo -->
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-comment-alt me-2"></i>Motivo e Observações</div>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Motivo <span class="text-danger">*</span></label>
                <select name="motivo" class="form-select" required>
                    <?php if ($isEntrada): ?>
                    <option value="">Selecione...</option>
                    <option value="Compra de fornecedor">Compra de fornecedor</option>
                    <option value="Devolução de cliente">Devolução de cliente</option>
                    <option value="Transferência entre depósitos">Transferência entre depósitos</option>
                    <option value="Ajuste de inventário">Ajuste de inventário</option>
                    <option value="Brinde/Amostra recebida">Brinde/Amostra recebida</option>
                    <option value="Outro">Outro</option>
                    <?php elseif ($isAjuste): ?>
                    <option value="">Selecione...</option>
                    <option value="Inventário físico">Inventário físico</option>
                    <option value="Correção de divergência">Correção de divergência</option>
                    <option value="Perda/Avaria">Perda/Avaria</option>
                    <option value="Vencimento">Vencimento</option>
                    <option value="Outro">Outro</option>
                    <?php else: ?>
                    <option value="">Selecione...</option>
                    <option value="Venda direta">Venda direta</option>
                    <option value="Demonstração">Demonstração</option>
                    <option value="Devolução a fornecedor">Devolução a fornecedor</option>
                    <option value="Transferência entre depósitos">Transferência entre depósitos</option>
                    <option value="Descarte/Vencimento">Descarte/Vencimento</option>
                    <option value="Uso interno">Uso interno</option>
                    <option value="Outro">Outro</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Observações</label>
                <textarea name="observacoes" class="form-control" rows="3"
                          placeholder="Informações adicionais..."></textarea>
            </div>
        </div>
    </div>

    <!-- Botões -->
    <div class="d-flex gap-2 justify-content-end">
        <a href="/estoque/movimentacoes" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fas fa-times me-2"></i>Cancelar
        </a>
        <button type="submit" class="btn rounded-pill px-5 fw-semibold"
                style="background:<?= $corTipo ?>;color:#fff;border:none;">
            <i class="fas fa-save me-2"></i>Registrar <?= $labelTipo ?>
        </button>
    </div>
</form>

<script>
// ── Busca de produto ──────────────────────────────────────────────────────
let debounceTimer;
const inputBusca = document.getElementById('produtoBusca');
const resultados = document.getElementById('produtoResultados');
const produtoIdInput = document.getElementById('produto_id');
const produtoInfo = document.getElementById('produtoInfo');

inputBusca.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const q = this.value.trim();
    if (q.length < 2) { resultados.classList.add('d-none'); return; }
    debounceTimer = setTimeout(() => {
        fetch('/estoque/movimentacoes/buscar-produto?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                resultados.innerHTML = '';
                if (!data.length) {
                    resultados.innerHTML = '<div class="item text-muted">Nenhum produto encontrado.</div>';
                } else {
                    data.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'item';
                        div.innerHTML = `<strong>${p.codigo}</strong> — ${p.nome} <span class="text-muted float-end">Estoque: ${p.estoque_atual}</span>`;
                        div.addEventListener('click', () => selecionarProduto(p));
                        resultados.appendChild(div);
                    });
                }
                resultados.classList.remove('d-none');
            });
    }, 300);
});

function selecionarProduto(p) {
    inputBusca.value = p.codigo + ' — ' + p.nome;
    produtoIdInput.value = p.id;
    resultados.classList.add('d-none');
    document.getElementById('unidade').value = p.unidade_medida || 'UN';
    document.getElementById('estoqueAtual').textContent = p.estoque_atual;
    document.getElementById('estoqueMin').textContent = p.estoque_minimo || '—';
    document.getElementById('precoCusto').textContent = 'R$ ' + parseFloat(p.preco_custo || 0).toLocaleString('pt-BR', {minimumFractionDigits:2});
    document.getElementById('precoVenda').textContent = 'R$ ' + parseFloat(p.preco_venda || 0).toLocaleString('pt-BR', {minimumFractionDigits:2});
    produtoInfo.classList.remove('d-none');
    // Preenche preço automaticamente
    const precoInput = document.getElementById('custo_unitario') || document.getElementById('preco_unitario');
    if (precoInput && !precoInput.value) {
        const val = document.getElementById('custo_unitario') ? p.preco_custo : p.preco_venda;
        precoInput.value = parseFloat(val || 0).toLocaleString('pt-BR', {minimumFractionDigits:2});
    }
    calcularTotal();
}

document.addEventListener('click', e => {
    if (!resultados.contains(e.target) && e.target !== inputBusca) {
        resultados.classList.add('d-none');
    }
});

// ── Cálculo de total ──────────────────────────────────────────────────────
function parseBR(v) {
    return parseFloat((v || '0').replace(/\./g,'').replace(',','.')) || 0;
}
function calcularTotal() {
    const qty   = parseBR(document.getElementById('quantidade')?.value);
    const preco = parseBR((document.getElementById('custo_unitario') || document.getElementById('preco_unitario'))?.value);
    const total = qty * preco;
    const el = document.getElementById('totalEstimado');
    if (el) el.value = total.toLocaleString('pt-BR', {minimumFractionDigits:2});
}
document.getElementById('quantidade')?.addEventListener('input', calcularTotal);
document.getElementById('custo_unitario')?.addEventListener('input', calcularTotal);
document.getElementById('preco_unitario')?.addEventListener('input', calcularTotal);
</script>
