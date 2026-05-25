<?php $esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<style>
.form-section { background:#fff; border-radius:12px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,.07); margin-bottom:20px; }
.form-section-title { font-size:14px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.5px; margin-bottom:16px; padding-bottom:10px; border-bottom:2px solid #f3f4f6; }
.upload-zone { border:2px dashed #d1d5db; border-radius:12px; padding:48px; text-align:center; cursor:pointer; transition:.2s; }
.upload-zone:hover, .upload-zone.drag-over { border-color:#2563eb; background:#eff6ff; }
.upload-zone .icon { font-size:48px; color:#9ca3af; margin-bottom:12px; }
.nfe-card { background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px; padding:16px; }
.nfe-label { font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; }
.nfe-value { font-size:14px; font-weight:600; color:#111827; }
.item-row { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:12px; margin-bottom:8px; }
.item-row .item-header { font-weight:600; font-size:13px; }
.badge-casado { background:#d1fae5; color:#065f46; }
.badge-manual { background:#fef3c7; color:#92400e; }
.badge { display:inline-block; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:600; }
</style>

<div class="d-flex align-items-center gap-3 mb-4">
    <div style="width:48px;height:48px;border-radius:12px;background:#eff6ff;display:flex;align-items:center;justify-content:center;">
        <i class="fas fa-file-code" style="color:#2563eb;font-size:20px;"></i>
    </div>
    <div>
        <h4 class="mb-0">Importar NF-e (XML)</h4>
        <small class="text-muted">Importe o XML da Nota Fiscal Eletrônica para dar entrada automática no estoque</small>
    </div>
</div>

<!-- Passo 1: Upload -->
<div class="form-section" id="passo1">
    <div class="form-section-title"><i class="fas fa-upload me-2"></i>Passo 1 — Selecione o arquivo XML da NF-e</div>

    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('xmlFile').click()">
        <div class="icon"><i class="fas fa-file-code"></i></div>
        <h5 class="mb-1">Arraste o arquivo XML aqui</h5>
        <p class="text-muted mb-3">ou clique para selecionar</p>
        <small class="text-muted">Suporte: XML NF-e padrão nacional (layout 4.00)</small>
    </div>
    <input type="file" id="xmlFile" accept=".xml" class="d-none">

    <div id="uploadProgress" class="d-none mt-3">
        <div class="d-flex align-items-center gap-2">
            <div class="spinner-border spinner-border-sm text-primary"></div>
            <span>Processando XML...</span>
        </div>
    </div>
    <div id="uploadError" class="alert alert-danger mt-3 d-none"></div>
</div>

<!-- Passo 2: Revisão (oculto até upload) -->
<div id="passo2" class="d-none">
    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-file-invoice me-2"></i>Passo 2 — Dados da Nota Fiscal</div>
        <div class="row g-3" id="nfeDados"></div>
    </div>

    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-boxes me-2"></i>Passo 3 — Itens da Nota</div>
        <p class="text-muted small mb-3">
            <i class="fas fa-info-circle me-1"></i>
            Itens com <span class="badge badge-casado">Produto casado</span> foram associados automaticamente pelo código.
            Itens com <span class="badge badge-manual">Associar manualmente</span> precisam ser vinculados a um produto do cadastro.
        </p>
        <div id="nfeItens"></div>
    </div>

    <div class="form-section">
        <div class="form-section-title"><i class="fas fa-check-circle me-2"></i>Passo 4 — Confirmar Importação</div>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Condição de Pagamento</label>
                <input type="text" id="cond_pagamento" class="form-control" placeholder="Ex: 30/60/90 dias">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Observações</label>
                <input type="text" id="obs_importacao" class="form-control" placeholder="Observações sobre a importação">
            </div>
        </div>
        <div class="d-flex gap-2 justify-content-end mt-3">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="reiniciar()">
                <i class="fas fa-redo me-2"></i>Importar outro XML
            </button>
            <button type="button" class="btn btn-success rounded-pill px-5 fw-semibold" id="btnConfirmar" onclick="confirmarImportacao()">
                <i class="fas fa-check me-2"></i>Confirmar Entrada no Estoque
            </button>
        </div>
    </div>
</div>

<!-- Drag & Drop -->
<script>
let dadosNfe = null;

const uploadZone = document.getElementById('uploadZone');
const xmlFile    = document.getElementById('xmlFile');

uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
uploadZone.addEventListener('drop', e => {
    e.preventDefault();
    uploadZone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) processarXml(file);
});
xmlFile.addEventListener('change', () => {
    if (xmlFile.files[0]) processarXml(xmlFile.files[0]);
});

function processarXml(file) {
    if (!file.name.endsWith('.xml')) {
        mostrarErro('O arquivo deve ser um XML de NF-e.');
        return;
    }
    document.getElementById('uploadProgress').classList.remove('d-none');
    document.getElementById('uploadError').classList.add('d-none');

    const fd = new FormData();
    fd.append('xml_nfe', file);

    fetch('/estoque/movimentacoes/importar-xml', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            document.getElementById('uploadProgress').classList.add('d-none');
            if (!res.success) { mostrarErro(res.error || 'Erro ao processar XML.'); return; }
            dadosNfe = res.dados;
            renderizarDados(res.dados);
            document.getElementById('passo1').classList.add('d-none');
            document.getElementById('passo2').classList.remove('d-none');
        })
        .catch(() => {
            document.getElementById('uploadProgress').classList.add('d-none');
            mostrarErro('Erro de comunicação com o servidor.');
        });
}

function mostrarErro(msg) {
    const el = document.getElementById('uploadError');
    el.textContent = msg;
    el.classList.remove('d-none');
}

function renderizarDados(d) {
    // Dados da NF-e
    const campos = [
        ['Número NF-e', d.nfe_numero + ' / Série ' + d.nfe_serie],
        ['Data de Emissão', d.nfe_data_emissao ? new Date(d.nfe_data_emissao).toLocaleDateString('pt-BR') : '—'],
        ['Chave de Acesso', '<small class="font-monospace">' + (d.nfe_chave || '—') + '</small>'],
        ['Emitente', (d.fornecedor_nome || '—') + ' — CNPJ: ' + (d.fornecedor_cnpj || '—')],
        ['Valor Total', 'R$ ' + parseFloat(d.valor_total || 0).toLocaleString('pt-BR', {minimumFractionDigits:2})],
        ['Valor Frete', 'R$ ' + parseFloat(d.valor_frete || 0).toLocaleString('pt-BR', {minimumFractionDigits:2})],
    ];
    document.getElementById('nfeDados').innerHTML = campos.map(([l, v]) => `
        <div class="col-6 col-md-4">
            <div class="nfe-card">
                <div class="nfe-label">${l}</div>
                <div class="nfe-value">${v}</div>
            </div>
        </div>
    `).join('');

    // Itens
    const container = document.getElementById('nfeItens');
    container.innerHTML = '';
    (d.itens || []).forEach((item, i) => {
        const casado = !!item.produto_id;
        const div = document.createElement('div');
        div.className = 'item-row';
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="item-header">${item.codigo_produto || ''} — ${item.descricao || ''}</span>
                    <span class="badge ms-2 ${casado ? 'badge-casado' : 'badge-manual'}">${casado ? '✓ Produto casado: ' + item.produto_nome : 'Associar manualmente'}</span>
                </div>
                <div class="text-end">
                    <strong>Qtd: ${item.quantidade}</strong> ${item.unidade || 'UN'} ×
                    R$ ${parseFloat(item.preco_unitario || 0).toLocaleString('pt-BR', {minimumFractionDigits:2})}
                </div>
            </div>
            ${!casado ? `
            <div class="row g-2 mt-1">
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Vincular ao produto do cadastro</label>
                    <input type="text" class="form-control form-control-sm produto-busca-item"
                           data-index="${i}" placeholder="Buscar produto..." autocomplete="off">
                    <input type="hidden" class="produto-id-item" data-index="${i}" value="">
                </div>
            </div>` : ''}
            <input type="hidden" class="item-produto-id" data-index="${i}" value="${item.produto_id || ''}">
            <input type="hidden" class="item-lote" data-index="${i}" value="${item.lote || ''}">
            <input type="hidden" class="item-validade" data-index="${i}" value="${item.data_validade || ''}">
        `;
        container.appendChild(div);
    });

    // Busca inline para itens não casados
    document.querySelectorAll('.produto-busca-item').forEach(input => {
        let timer;
        input.addEventListener('input', function() {
            clearTimeout(timer);
            const idx = this.dataset.index;
            const q = this.value.trim();
            if (q.length < 2) return;
            timer = setTimeout(() => {
                fetch('/estoque/movimentacoes/buscar-produto?q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(data => {
                        let drop = document.getElementById('drop_' + idx);
                        if (!drop) {
                            drop = document.createElement('div');
                            drop.id = 'drop_' + idx;
                            drop.className = 'list-group position-absolute z-3';
                            drop.style.cssText = 'max-height:200px;overflow-y:auto;width:100%;';
                            input.parentNode.style.position = 'relative';
                            input.parentNode.appendChild(drop);
                        }
                        drop.innerHTML = data.map(p => `
                            <button type="button" class="list-group-item list-group-item-action py-1 px-2 small"
                                    onclick="vincularItem(${idx}, ${p.id}, '${p.codigo} — ${p.nome.replace(/'/g,"\\'")}')">
                                <strong>${p.codigo}</strong> — ${p.nome}
                            </button>
                        `).join('');
                    });
            }, 300);
        });
    });
}

function vincularItem(idx, prodId, nome) {
    document.querySelector(`.item-produto-id[data-index="${idx}"]`).value = prodId;
    document.querySelector(`.produto-busca-item[data-index="${idx}"]`).value = nome;
    const drop = document.getElementById('drop_' + idx);
    if (drop) drop.innerHTML = '';
    // Atualiza dadosNfe
    if (dadosNfe && dadosNfe.itens[idx]) dadosNfe.itens[idx].produto_id = prodId;
}

function confirmarImportacao() {
    if (!dadosNfe) return;

    // Coleta produto_id atualizado dos itens
    document.querySelectorAll('.item-produto-id').forEach(el => {
        const idx = parseInt(el.dataset.index);
        if (dadosNfe.itens[idx]) dadosNfe.itens[idx].produto_id = el.value || null;
    });

    const payload = Object.assign({}, dadosNfe, {
        cond_pagamento: document.getElementById('cond_pagamento').value,
        observacoes:    document.getElementById('obs_importacao').value,
    });

    document.getElementById('btnConfirmar').disabled = true;
    document.getElementById('btnConfirmar').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processando...';

    fetch('/estoque/movimentacoes/importar-xml/confirmar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.href = res.redirect || '/estoque/compras/' + res.pedido_id;
        } else {
            alert('Erro: ' + (res.error || 'Falha ao importar.'));
            document.getElementById('btnConfirmar').disabled = false;
            document.getElementById('btnConfirmar').innerHTML = '<i class="fas fa-check me-2"></i>Confirmar Entrada no Estoque';
        }
    });
}

function reiniciar() {
    dadosNfe = null;
    document.getElementById('passo1').classList.remove('d-none');
    document.getElementById('passo2').classList.add('d-none');
    document.getElementById('xmlFile').value = '';
}
</script>
