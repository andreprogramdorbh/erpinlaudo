<?php
$esc    = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$isEdit = !empty($pedido);
$action = $isEdit
    ? '/estoque/vendas/' . (int)$pedido->id . '/update'
    : '/estoque/vendas';
$itens  = $pedido->itens ?? [];
$produtos = $produtos ?? [];

// Montar JSON de produtos para o autocomplete JS
$produtosJs = [];
foreach ($produtos as $p) {
    $produtosJs[] = [
        'id'     => (int)$p->id,
        'codigo' => $p->codigo ?? '',
        'nome'   => $p->nome ?? '',
        'tipo'   => $p->tipo ?? '',
        'unidade'=> $p->unidade_medida ?? 'UN',
        'preco'  => (float)($p->preco_venda ?? 0),
        'custo'  => (float)($p->preco_custo ?? 0),
    ];
}
$produtosJsonStr = json_encode($produtosJs, JSON_UNESCAPED_UNICODE);

// Parcelas salvas (para edição)
$parcelasSalvas = $pedido->parcelas ?? [];
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
/* Autocomplete produto */
.prod-autocomplete-wrap { position:relative; }
.prod-autocomplete-wrap .prod-suggestions { position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #d1d5db; border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,.1); z-index:1000; max-height:220px; overflow-y:auto; display:none; }
.prod-suggestions .prod-item { padding:8px 12px; cursor:pointer; font-size:13px; border-bottom:1px solid #f3f4f6; }
.prod-suggestions .prod-item:hover { background:#f0fdf4; }
.prod-suggestions .prod-item .prod-codigo { font-size:11px; color:#6b7280; }
/* Parcelamento */
.parcela-row { background:#f8fafc; border-radius:8px; padding:10px 14px; margin-bottom:8px; }
.parcela-row:nth-child(even) { background:#f0fdf4; }
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
            <?php $clientes = $clientes ?? []; ?>
            <?php if (!empty($clientes)): ?>
            <div class="col-12">
                <label class="form-label fw-semibold text-primary">
                    <i class="fas fa-search me-1"></i>Buscar Cliente Cadastrado
                </label>
                <select id="selectClienteVenda" class="form-select"
                    style="border-color:#10b981;background-color:#f0fdf4;">
                    <option value="">— Digite para buscar ou selecione um cliente cadastrado —</option>
                    <?php foreach ($clientes as $c):
                        $nome = htmlspecialchars($c->razao_social ?: $c->nome_fantasia ?: '', ENT_QUOTES, 'UTF-8');
                        $cpf  = htmlspecialchars($c->cpf_cnpj ?? '', ENT_QUOTES, 'UTF-8');
                        $tel  = htmlspecialchars($c->telefone ?? $c->celular ?? '', ENT_QUOTES, 'UTF-8');
                        $eml  = htmlspecialchars($c->email ?? '', ENT_QUOTES, 'UTF-8');
                        $end  = htmlspecialchars(trim(($c->endereco ?? '') . ($c->numero ? ', ' . $c->numero : '') . ($c->bairro ? ' - ' . $c->bairro : '') . ($c->cidade ? ', ' . $c->cidade : '')), ENT_QUOTES, 'UTF-8');
                        $cid  = htmlspecialchars($c->cidade ?? '', ENT_QUOTES, 'UTF-8');
                        $est  = htmlspecialchars($c->estado ?? '', ENT_QUOTES, 'UTF-8');
                        $sel  = $isEdit && !empty($pedido->cliente_cpf_cnpj) && $pedido->cliente_cpf_cnpj === $c->cpf_cnpj ? 'selected' : '';
                    ?>
                    <option value="<?= (int)$c->id ?>" <?= $sel ?>
                        data-nome="<?= $nome ?>"
                        data-cpf="<?= $cpf ?>"
                        data-tel="<?= $tel ?>"
                        data-email="<?= $eml ?>"
                        data-end="<?= $end ?>"
                        data-cidade="<?= $cid ?>"
                        data-estado="<?= $est ?>">
                        <?= $nome ?> <?= $cpf ? '— ' . $cpf : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Selecione um cliente para preencher os campos automaticamente, ou preencha manualmente abaixo.</small>
            </div>
            <?php endif; ?>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Nome do Cliente <span class="text-danger">*</span></label>
                <input type="text" id="vendaClienteNome" name="cliente_nome" class="form-control" required
                    placeholder="Nome completo ou razão social"
                    value="<?= $esc($pedido->cliente_nome ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">CPF / CNPJ</label>
                <input type="text" id="vendaClienteCpf" name="cliente_cpf_cnpj" class="form-control"
                    placeholder="000.000.000-00"
                    value="<?= $esc($pedido->cliente_cpf_cnpj ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Telefone</label>
                <input type="text" id="vendaClienteTel" name="cliente_telefone" class="form-control"
                    placeholder="(00) 00000-0000"
                    value="<?= $esc($pedido->cliente_telefone ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">E-mail</label>
                <input type="email" id="vendaClienteEmail" name="cliente_email" class="form-control"
                    placeholder="cliente@email.com"
                    value="<?= $esc($pedido->cliente_email ?? '') ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold">Endereço de Entrega</label>
                <input type="text" id="vendaClienteEnd" name="endereco_entrega" class="form-control"
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
                            <input type="hidden" name="item_produto_id[]" class="item-produto-id" value="<?= (int)($item->produto_id ?? 0) ?>">
                            <div class="prod-autocomplete-wrap">
                                <input type="text" name="item_descricao[]" class="form-control form-control-sm item-busca-prod"
                                    placeholder="Digite para buscar produto/serviço..." required autocomplete="off"
                                    value="<?= $esc($item->descricao ?? $item->produto_nome ?? '') ?>">
                                <div class="prod-suggestions"></div>
                            </div>
                        </td>
                        <td><input type="text" name="item_unidade[]" class="form-control form-control-sm item-unidade" value="<?= $esc($item->unidade ?? 'UN') ?>" style="width:60px"></td>
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
                            <input type="hidden" name="item_produto_id[]" class="item-produto-id" value="">
                            <div class="prod-autocomplete-wrap">
                                <input type="text" name="item_descricao[]" class="form-control form-control-sm item-busca-prod"
                                    placeholder="Digite para buscar produto/serviço..." required autocomplete="off">
                                <div class="prod-suggestions"></div>
                            </div>
                        </td>
                        <td><input type="text" name="item_unidade[]" class="form-control form-control-sm item-unidade" value="UN" style="width:60px"></td>
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

    <!-- PARCELAMENTO -->
    <div class="form-section" id="secaoParcelamento">
        <div class="form-section-title"><i class="fas fa-credit-card me-2"></i>Parcelamento
            <span class="text-muted fw-normal ms-2" style="font-size:11px;text-transform:none;">Defina as parcelas — cada uma gera uma entrada em Contas a Receber</span>
        </div>
        <div class="row g-2 align-items-end mb-3">
            <div class="col-auto">
                <label class="form-label fw-semibold mb-1">Número de Parcelas</label>
                <input type="number" id="numParcelas" class="form-control" min="1" max="60" value="1" style="width:100px">
            </div>
            <div class="col-auto">
                <label class="form-label fw-semibold mb-1">1ª Data de Vencimento</label>
                <input type="date" id="primeiroVencimento" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-outline-success btn-sm" onclick="gerarParcelas()">
                    <i class="fas fa-magic me-1"></i> Gerar Parcelas
                </button>
            </div>
            <div class="col-auto">
                <small class="text-muted">Intervalo: 30 dias entre parcelas. Ajuste as datas individualmente se necessário.</small>
            </div>
        </div>

        <div id="listaParcelas">
            <?php if (!empty($parcelasSalvas)): ?>
            <?php foreach ($parcelasSalvas as $i => $parc): ?>
            <div class="parcela-row row g-2 align-items-center">
                <div class="col-auto"><span class="badge bg-secondary"><?= (int)($parc->numero_parcela ?? $i+1) ?>ª</span></div>
                <div class="col-md-2">
                    <label class="form-label mb-0" style="font-size:11px">Valor (R$)</label>
                    <input type="text" name="parcela_valor[]" class="form-control form-control-sm parcela-valor"
                        value="<?= number_format((float)($parc->valor ?? 0), 2, ',', '.') ?>" oninput="validarParcelas()">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-0" style="font-size:11px">Forma de Pagamento</label>
                    <select name="parcela_forma[]" class="form-select form-select-sm">
                        <?php
                        $formasParcela = ['pix'=>'PIX','boleto'=>'Boleto','dinheiro'=>'Dinheiro','cartao_credito'=>'Cartão Crédito','cartao_debito'=>'Cartão Débito','transferencia'=>'Transferência','cheque'=>'Cheque'];
                        foreach ($formasParcela as $fv => $fl):
                        ?>
                        <option value="<?= $fv ?>" <?= ($parc->meio_pagamento ?? '') === $fv ? 'selected' : '' ?>><?= $fl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-0" style="font-size:11px">Vencimento</label>
                    <input type="date" name="parcela_vencimento[]" class="form-control form-control-sm"
                        value="<?= $esc($parc->data_vencimento ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-0" style="font-size:11px">Descrição (opcional)</label>
                    <input type="text" name="parcela_descricao[]" class="form-control form-control-sm"
                        value="<?= $esc($parc->descricao ?? '') ?>" placeholder="Ex: Entrada, Parcela 1...">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn-rm mt-3" onclick="removerParcela(this)"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <!-- Parcela padrão inicial -->
            <div class="parcela-row row g-2 align-items-center">
                <div class="col-auto"><span class="badge bg-secondary">1ª</span></div>
                <div class="col-md-2">
                    <label class="form-label mb-0" style="font-size:11px">Valor (R$)</label>
                    <input type="text" name="parcela_valor[]" class="form-control form-control-sm parcela-valor" value="0,00" oninput="validarParcelas()">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-0" style="font-size:11px">Forma de Pagamento</label>
                    <select name="parcela_forma[]" class="form-select form-select-sm">
                        <option value="pix">PIX</option>
                        <option value="boleto">Boleto</option>
                        <option value="dinheiro">Dinheiro</option>
                        <option value="cartao_credito">Cartão Crédito</option>
                        <option value="cartao_debito">Cartão Débito</option>
                        <option value="transferencia">Transferência</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-0" style="font-size:11px">Vencimento</label>
                    <input type="date" name="parcela_vencimento[]" class="form-control form-control-sm"
                        value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-0" style="font-size:11px">Descrição (opcional)</label>
                    <input type="text" name="parcela_descricao[]" class="form-control form-control-sm" placeholder="Ex: Entrada, Parcela 1...">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn-rm mt-3" onclick="removerParcela(this)"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div id="alertaParcelas" class="alert alert-warning mt-2 py-2 d-none" style="font-size:13px">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <span id="alertaParcelasTexto"></span>
        </div>

        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="adicionarParcela()">
            <i class="fas fa-plus me-1"></i> Adicionar Parcela Manualmente
        </button>
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
const PRODUTOS_ESTOQUE = <?= $produtosJsonStr ?? '[]' ?>;

function parseBR(v) {
    return parseFloat(String(v).replace(/\./g, '').replace(',', '.')) || 0;
}
function fmtBR(v) {
    return v.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
}

// ── Autocomplete de produto por linha ──────────────────────────────────────
function initProdAutoComplete(input) {
    const wrap = input.closest('.prod-autocomplete-wrap');
    const sugg = wrap.querySelector('.prod-suggestions');

    input.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        sugg.innerHTML = '';
        if (!q) { sugg.style.display = 'none'; return; }
        const matches = PRODUTOS_ESTOQUE.filter(p =>
            p.nome.toLowerCase().includes(q) ||
            p.codigo.toLowerCase().includes(q) ||
            (p.tipo || '').toLowerCase().includes(q)
        ).slice(0, 15);
        if (!matches.length) { sugg.style.display = 'none'; return; }
        matches.forEach(p => {
            const div = document.createElement('div');
            div.className = 'prod-item';
            div.innerHTML = `<strong>${p.nome}</strong> <span class="prod-codigo">[${p.codigo}] ${p.tipo}</span>`;
            div.addEventListener('mousedown', function(e) {
                e.preventDefault();
                preencherLinhaProduto(input, p);
                sugg.style.display = 'none';
            });
            sugg.appendChild(div);
        });
        sugg.style.display = 'block';
    });

    input.addEventListener('blur', function() {
        setTimeout(() => { sugg.style.display = 'none'; }, 150);
    });
    input.addEventListener('focus', function() {
        if (this.value.trim()) this.dispatchEvent(new Event('input'));
    });
}

function preencherLinhaProduto(input, p) {
    const tr = input.closest('tr');
    input.value = p.nome;
    tr.querySelector('.item-produto-id').value = p.id;
    tr.querySelector('.item-unidade').value    = p.unidade || 'UN';
    const precoEl = tr.querySelector('.item-preco');
    const custoEl = tr.querySelector('.item-custo');
    precoEl.value = fmtBR(p.preco);
    custoEl.value = fmtBR(p.custo);
    recalcularLinha(precoEl);
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

    // Atualizar parcela única se só houver 1 parcela e valor 0
    const parcelasValor = document.querySelectorAll('input[name="parcela_valor[]"]');
    if (parcelasValor.length === 1 && parseBR(parcelasValor[0].value) === 0) {
        parcelasValor[0].value = fmtBR(total);
    }
}

function adicionarLinha() {
    const tbody = document.getElementById('corpoItens');
    const tr = document.createElement('tr');
    tr.className = 'linha-item';
    tr.innerHTML = `
        <td>
            <input type="hidden" name="item_produto_id[]" class="item-produto-id" value="">
            <div class="prod-autocomplete-wrap">
                <input type="text" name="item_descricao[]" class="form-control form-control-sm item-busca-prod"
                    placeholder="Digite para buscar produto/serviço..." required autocomplete="off">
                <div class="prod-suggestions"></div>
            </div>
        </td>
        <td><input type="text" name="item_unidade[]" class="form-control form-control-sm item-unidade" value="UN" style="width:60px"></td>
        <td><input type="text" name="item_quantidade[]" class="form-control form-control-sm item-qty" value="1" oninput="recalcularLinha(this)"></td>
        <td><input type="text" name="item_preco_unitario[]" class="form-control form-control-sm item-preco" value="0,00" oninput="recalcularLinha(this)"></td>
        <td><input type="text" name="item_preco_custo[]" class="form-control form-control-sm item-custo" value="0,00" oninput="recalcularLinha(this)"></td>
        <td><input type="number" name="item_desconto_perc[]" class="form-control form-control-sm item-desc" value="0" min="0" max="100" step="0.01" oninput="recalcularLinha(this)"></td>
        <td><span class="item-total fw-bold">0,00</span></td>
        <td><span class="item-margem small"></span></td>
        <td><button type="button" class="btn-rm" onclick="removerLinha(this)"><i class="fas fa-times"></i></button></td>
    `;
    tbody.appendChild(tr);
    // Inicializar autocomplete na nova linha
    initProdAutoComplete(tr.querySelector('.item-busca-prod'));
}

function removerLinha(btn) {
    const linhas = document.querySelectorAll('#corpoItens .linha-item');
    if (linhas.length <= 1) { alert('O pedido deve ter ao menos um item.'); return; }
    btn.closest('tr').remove();
    recalcularTotais();
}

// ── Parcelamento ───────────────────────────────────────────────────────────
function _parcelasFormasHtml(selecionada) {
    const formas = {pix:'PIX',boleto:'Boleto',dinheiro:'Dinheiro',cartao_credito:'Cartão Crédito',cartao_debito:'Cartão Débito',transferencia:'Transferência',cheque:'Cheque'};
    return Object.entries(formas).map(([v,l]) =>
        `<option value="${v}"${v===selecionada?' selected':''}>${l}</option>`
    ).join('');
}

function _addDays(dateStr, days) {
    const d = new Date(dateStr + 'T12:00:00');
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
}

function gerarParcelas() {
    const n = parseInt(document.getElementById('numParcelas').value) || 1;
    const primeiroVenc = document.getElementById('primeiroVencimento').value;
    if (!primeiroVenc) { alert('Informe a 1ª data de vencimento.'); return; }

    // Calcular valor total do pedido
    const totalEl = document.getElementById('hValorTotal');
    const total = parseFloat(totalEl ? totalEl.value : 0) || 0;
    const valorParcela = total > 0 ? (total / n) : 0;

    const lista = document.getElementById('listaParcelas');
    lista.innerHTML = '';

    for (let i = 0; i < n; i++) {
        const venc = _addDays(primeiroVenc, i * 30);
        // Ajuste centavos na última parcela
        let val = valorParcela;
        if (i === n - 1 && total > 0) {
            const soma = Math.round(valorParcela * (n - 1) * 100) / 100;
            val = Math.round((total - soma) * 100) / 100;
        }
        const div = document.createElement('div');
        div.className = 'parcela-row row g-2 align-items-center';
        div.innerHTML = `
            <div class="col-auto"><span class="badge bg-secondary">${i+1}ª</span></div>
            <div class="col-md-2">
                <label class="form-label mb-0" style="font-size:11px">Valor (R$)</label>
                <input type="text" name="parcela_valor[]" class="form-control form-control-sm parcela-valor"
                    value="${fmtBR(val)}" oninput="validarParcelas()">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0" style="font-size:11px">Forma de Pagamento</label>
                <select name="parcela_forma[]" class="form-select form-select-sm">${_parcelasFormasHtml('pix')}</select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-0" style="font-size:11px">Vencimento</label>
                <input type="date" name="parcela_vencimento[]" class="form-control form-control-sm" value="${venc}">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0" style="font-size:11px">Descrição (opcional)</label>
                <input type="text" name="parcela_descricao[]" class="form-control form-control-sm"
                    placeholder="Ex: Parcela ${i+1}/${n}" value="Parcela ${i+1}/${n}">
            </div>
            <div class="col-auto">
                <button type="button" class="btn-rm mt-3" onclick="removerParcela(this)"><i class="fas fa-times"></i></button>
            </div>
        `;
        lista.appendChild(div);
    }
    validarParcelas();
}

function adicionarParcela() {
    const lista = document.getElementById('listaParcelas');
    const n = lista.querySelectorAll('.parcela-row').length + 1;
    const div = document.createElement('div');
    div.className = 'parcela-row row g-2 align-items-center';
    div.innerHTML = `
        <div class="col-auto"><span class="badge bg-secondary">${n}ª</span></div>
        <div class="col-md-2">
            <label class="form-label mb-0" style="font-size:11px">Valor (R$)</label>
            <input type="text" name="parcela_valor[]" class="form-control form-control-sm parcela-valor" value="0,00" oninput="validarParcelas()">
        </div>
        <div class="col-md-2">
            <label class="form-label mb-0" style="font-size:11px">Forma de Pagamento</label>
            <select name="parcela_forma[]" class="form-select form-select-sm">${_parcelasFormasHtml('pix')}</select>
        </div>
        <div class="col-md-2">
            <label class="form-label mb-0" style="font-size:11px">Vencimento</label>
            <input type="date" name="parcela_vencimento[]" class="form-control form-control-sm">
        </div>
        <div class="col-md-3">
            <label class="form-label mb-0" style="font-size:11px">Descrição (opcional)</label>
            <input type="text" name="parcela_descricao[]" class="form-control form-control-sm" placeholder="Ex: Entrada, Parcela...">
        </div>
        <div class="col-auto">
            <button type="button" class="btn-rm mt-3" onclick="removerParcela(this)"><i class="fas fa-times"></i></button>
        </div>
    `;
    lista.appendChild(div);
    validarParcelas();
}

function removerParcela(btn) {
    const lista = document.getElementById('listaParcelas');
    if (lista.querySelectorAll('.parcela-row').length <= 1) { alert('Deve haver ao menos uma parcela.'); return; }
    btn.closest('.parcela-row').remove();
    validarParcelas();
}

function validarParcelas() {
    const totalEl = document.getElementById('hValorTotal');
    const total = parseFloat(totalEl ? totalEl.value : 0) || 0;
    if (total <= 0) return;
    let somaParcelas = 0;
    document.querySelectorAll('input[name="parcela_valor[]"]').forEach(el => {
        somaParcelas += parseBR(el.value);
    });
    const diff = Math.abs(total - somaParcelas);
    const alerta = document.getElementById('alertaParcelas');
    const alertaTxt = document.getElementById('alertaParcelasTexto');
    if (diff > 0.02) {
        alertaTxt.textContent = `Soma das parcelas (R$ ${fmtBR(somaParcelas)}) difere do total do pedido (R$ ${fmtBR(total)}). Diferença: R$ ${fmtBR(diff)}.`;
        alerta.classList.remove('d-none');
    } else {
        alerta.classList.add('d-none');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    recalcularTotais();

    // Inicializar autocomplete em todas as linhas existentes
    document.querySelectorAll('.item-busca-prod').forEach(initProdAutoComplete);

    // ── Autocomplete de cliente cadastrado ──────────────────────────────────
    const selCliente = document.getElementById('selectClienteVenda');
    if (!selCliente) return;

    const buscaInput = document.createElement('input');
    buscaInput.type = 'text';
    buscaInput.className = 'form-control form-control-sm mb-1';
    buscaInput.placeholder = '🔍 Digite nome, razão social ou CPF/CNPJ para filtrar...';
    buscaInput.style.borderColor = '#10b981';
    selCliente.parentNode.insertBefore(buscaInput, selCliente);

    const todasOpcoes = Array.from(selCliente.options);

    buscaInput.addEventListener('input', function() {
        const termo = this.value.toLowerCase().trim();
        selCliente.innerHTML = '';
        todasOpcoes.forEach(function(opt) {
            if (!termo || opt.value === '' || opt.text.toLowerCase().includes(termo) ||
                (opt.dataset.cpf || '').toLowerCase().includes(termo)) {
                selCliente.appendChild(opt.cloneNode(true));
            }
        });
        const opcoesFiltradas = Array.from(selCliente.options).filter(o => o.value !== '');
        if (opcoesFiltradas.length === 1) {
            selCliente.value = opcoesFiltradas[0].value;
            selCliente.dispatchEvent(new Event('change'));
        }
    });

    selCliente.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (!opt || !opt.value) return;
        const nomeEl  = document.getElementById('vendaClienteNome');
        const cpfEl   = document.getElementById('vendaClienteCpf');
        const telEl   = document.getElementById('vendaClienteTel');
        const emailEl = document.getElementById('vendaClienteEmail');
        const endEl   = document.getElementById('vendaClienteEnd');
        if (nomeEl)  nomeEl.value  = opt.dataset.nome  || '';
        if (cpfEl)   cpfEl.value   = opt.dataset.cpf   || '';
        if (telEl)   telEl.value   = opt.dataset.tel   || '';
        if (emailEl) emailEl.value = opt.dataset.email || '';
        if (endEl)   endEl.value   = opt.dataset.end   || '';
        [nomeEl, cpfEl, telEl, emailEl, endEl].forEach(function(el) {
            if (!el) return;
            el.style.borderColor = '#10b981';
            el.style.backgroundColor = '#f0fdf4';
            setTimeout(function() { el.style.borderColor = ''; el.style.backgroundColor = ''; }, 2000);
        });
    });
});
</script>
