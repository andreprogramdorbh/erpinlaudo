<?php
/**
 * ERP InLaudo - Aba Equipamentos do Formulário de Clientes
 * Gerencia equipamentos vinculados ao cliente (tabela equipamentos_cliente)
 */
$clienteId   = $cliente->id ?? null;
$equipamentos = $equipamentos ?? [];
$produtos     = $produtosEstoque ?? [];
?>

<!-- Cabeçalho da Seção -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="form-section-title mb-0">
            <i class="fas fa-tools section-icon"></i>
            Equipamentos do Cliente
        </h3>
        <p class="form-help mb-0">Gerencie os equipamentos instalados neste cliente. Estes dados são usados na abertura de Ordens de Serviço.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEquipamento" onclick="abrirModalNovoEquip()">
        <i class="fas fa-plus me-1"></i> Novo Equipamento
    </button>
</div>

<!-- Tabela de Equipamentos -->
<div class="form-table-container">
    <table class="form-table" id="tabelaEquipamentos">
        <thead>
            <tr>
                <th>Produto / Equipamento</th>
                <th>Nº de Série</th>
                <th>Marca / Modelo</th>
                <th>Instalação</th>
                <th>Vida Útil</th>
                <th>Próx. Troca</th>
                <th>Status</th>
                <th class="text-end">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($equipamentos)): ?>
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-tools fa-2x mb-2 d-block opacity-25"></i>
                    Nenhum equipamento cadastrado para este cliente.
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($equipamentos as $eq): ?>
            <tr id="equip-row-<?= $eq->id ?>">
                <td>
                    <div class="fw-semibold"><?= htmlspecialchars($eq->produto_nome ?? '—') ?></div>
                    <?php if (!empty($eq->produto_codigo)): ?>
                    <small class="text-muted"><?= htmlspecialchars($eq->produto_codigo) ?></small>
                    <?php endif; ?>
                </td>
                <td><code><?= htmlspecialchars($eq->numero_serie ?? '—') ?></code></td>
                <td>
                    <?php if (!empty($eq->marca)): ?><span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($eq->marca) ?></span><?php endif; ?>
                    <?php if (!empty($eq->modelo)): ?><span class="text-muted small"><?= htmlspecialchars($eq->modelo) ?></span><?php endif; ?>
                    <?php if (empty($eq->marca) && empty($eq->modelo)): ?>—<?php endif; ?>
                </td>
                <td><?= !empty($eq->data_instalacao) ? date('d/m/Y', strtotime($eq->data_instalacao)) : '—' ?></td>
                <td><?= !empty($eq->vida_util_meses) ? $eq->vida_util_meses . ' meses' : '—' ?></td>
                <td>
                    <?php if (!empty($eq->data_proxima_troca)): ?>
                        <?php
                        $hoje = new DateTime();
                        $troca = new DateTime($eq->data_proxima_troca);
                        $diff = $hoje->diff($troca);
                        $atrasado = $troca < $hoje;
                        ?>
                        <span class="badge <?= $atrasado ? 'bg-danger' : ($diff->days <= 90 ? 'bg-warning text-dark' : 'bg-success') ?>">
                            <?= date('d/m/Y', strtotime($eq->data_proxima_troca)) ?>
                        </span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <?php if ((int)($eq->ativo ?? 1) === 1): ?>
                        <span class="form-badge success">Ativo</span>
                    <?php else: ?>
                        <span class="form-badge danger">Inativo</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-xs btn-outline-primary me-1"
                            onclick="editarEquipamento(<?= $eq->id ?>)"
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-danger"
                            onclick="removerEquipamento(<?= $eq->id ?>, '<?= htmlspecialchars(addslashes($eq->produto_nome ?? 'equipamento')) ?>')"
                            title="Remover">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal: Adicionar / Editar Equipamento -->
<div class="modal fade" id="modalEquipamento" tabindex="-1" aria-labelledby="modalEquipamentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEquipamentoLabel">
                    <i class="fas fa-tools me-2"></i>
                    <span id="modalEquipTitulo">Novo Equipamento</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="equipId" value="">
                <input type="hidden" id="equipClienteId" value="<?= (int)$clienteId ?>">

                <div class="row g-3">
                    <!-- Produto do estoque -->
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Produto / Equipamento (do Estoque)</label>
                        <select id="equipSelectProduto" class="form-select form-select-sm">
                            <option value="">— Selecionar produto do estoque (opcional) —</option>
                            <?php foreach ($produtos as $p): ?>
                            <option value="<?= $p->id ?>"
                                data-nome="<?= htmlspecialchars($p->nome) ?>"
                                data-codigo="<?= htmlspecialchars($p->codigo ?? '') ?>"
                                data-marca="<?= htmlspecialchars($p->marca ?? '') ?>"
                                data-modelo="<?= htmlspecialchars($p->modelo ?? '') ?>"
                                data-vida="<?= (int)($p->vida_util_meses ?? 0) ?>"
                                data-deprec="<?= number_format((float)($p->depreciacao_mensal ?? 0), 4, '.', '') ?>">
                                <?= htmlspecialchars($p->codigo ? "[{$p->codigo}] " : '') . htmlspecialchars($p->nome) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Ao selecionar, os campos abaixo serão preenchidos automaticamente.</small>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold required">Nome do Equipamento <span class="text-danger">*</span></label>
                        <input type="text" id="equipNome" class="form-control form-control-sm" placeholder="Ex: Analisador Hematológico XN-1000" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Código</label>
                        <input type="text" id="equipCodigo" class="form-control form-control-sm" placeholder="PRD-00001">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold required">Número de Série <span class="text-danger">*</span></label>
                        <input type="text" id="equipNumeroSerie" class="form-control form-control-sm" placeholder="S/N do equipamento" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Marca</label>
                        <input type="text" id="equipMarca" class="form-control form-control-sm" placeholder="Ex: Sysmex">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Modelo</label>
                        <input type="text" id="equipModelo" class="form-control form-control-sm" placeholder="Ex: XN-1000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Data de Instalação</label>
                        <input type="date" id="equipDataInstalacao" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Vida Útil (meses)</label>
                        <input type="number" id="equipVidaUtil" class="form-control form-control-sm" min="0" placeholder="Ex: 60">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Depreciação Mensal (R$)</label>
                        <input type="text" id="equipDepreciacao" class="form-control form-control-sm money-mask-equip" placeholder="0,00">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Observações</label>
                        <textarea id="equipObservacoes" class="form-control form-control-sm" rows="2" placeholder="Condições do equipamento, histórico relevante..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarEquip" onclick="salvarEquipamento()">
                    <i class="fas fa-save me-1"></i> Salvar Equipamento
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.btn-xs { padding: .2rem .45rem; font-size: .75rem; }
.form-badge.success { background: #dcfce7; color: #166534; padding: .2rem .6rem; border-radius: 999px; font-size: .75rem; font-weight: 600; }
.form-badge.danger  { background: #fee2e2; color: #991b1b; padding: .2rem .6rem; border-radius: 999px; font-size: .75rem; font-weight: 600; }
</style>

<script>
(function() {
    const clienteId = <?= (int)$clienteId ?>;

    // Preencher campos ao selecionar produto do estoque
    document.getElementById('equipSelectProduto').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (!opt.value) return;
        document.getElementById('equipNome').value    = opt.dataset.nome   || '';
        document.getElementById('equipCodigo').value  = opt.dataset.codigo || '';
        document.getElementById('equipMarca').value   = opt.dataset.marca  || '';
        document.getElementById('equipModelo').value  = opt.dataset.modelo || '';
        if (opt.dataset.vida && parseInt(opt.dataset.vida) > 0) {
            document.getElementById('equipVidaUtil').value = opt.dataset.vida;
        }
        if (opt.dataset.deprec && parseFloat(opt.dataset.deprec) > 0) {
            const v = parseFloat(opt.dataset.deprec).toFixed(2).replace('.', ',');
            document.getElementById('equipDepreciacao').value = v;
        }
    });

    // Money mask para depreciação
    document.querySelectorAll('.money-mask-equip').forEach(function(el) {
        el.addEventListener('input', function() {
            let v = this.value.replace(/\D/g, '');
            if (!v) { this.value = '0,00'; return; }
            v = (parseInt(v, 10) / 100).toFixed(2);
            this.value = v.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
    });

    // Abrir modal para novo equipamento
    window.abrirModalNovoEquip = function() {
        document.getElementById('equipId').value           = '';
        document.getElementById('equipSelectProduto').value = '';
        document.getElementById('equipNome').value          = '';
        document.getElementById('equipCodigo').value        = '';
        document.getElementById('equipNumeroSerie').value   = '';
        document.getElementById('equipMarca').value         = '';
        document.getElementById('equipModelo').value        = '';
        document.getElementById('equipDataInstalacao').value = '';
        document.getElementById('equipVidaUtil').value      = '';
        document.getElementById('equipDepreciacao').value   = '0,00';
        document.getElementById('equipObservacoes').value   = '';
        document.getElementById('modalEquipTitulo').textContent = 'Novo Equipamento';
        document.getElementById('btnSalvarEquip').disabled  = false;
    };

    // Editar equipamento existente
    window.editarEquipamento = function(id) {
        fetch('/clientes/equipamentos/get?id=' + id)
            .then(r => r.json())
            .then(d => {
                if (!d.success) { alert(d.error || 'Erro ao carregar equipamento.'); return; }
                const eq = d.equipamento;
                document.getElementById('equipId').value              = eq.id;
                document.getElementById('equipSelectProduto').value   = eq.produto_id || '';
                document.getElementById('equipNome').value            = eq.produto_nome || '';
                document.getElementById('equipCodigo').value          = eq.produto_codigo || '';
                document.getElementById('equipNumeroSerie').value     = eq.numero_serie || '';
                document.getElementById('equipMarca').value           = eq.marca || '';
                document.getElementById('equipModelo').value          = eq.modelo || '';
                document.getElementById('equipDataInstalacao').value  = eq.data_instalacao || '';
                document.getElementById('equipVidaUtil').value        = eq.vida_util_meses || '';
                const dep = parseFloat(eq.depreciacao_mensal || 0).toFixed(2).replace('.', ',');
                document.getElementById('equipDepreciacao').value     = dep;
                document.getElementById('equipObservacoes').value     = eq.observacoes || '';
                document.getElementById('modalEquipTitulo').textContent = 'Editar Equipamento';
                new bootstrap.Modal(document.getElementById('modalEquipamento')).show();
            })
            .catch(() => alert('Erro de comunicação.'));
    };

    // Salvar equipamento (criar ou atualizar)
    window.salvarEquipamento = function() {
        const nome   = document.getElementById('equipNome').value.trim();
        const serie  = document.getElementById('equipNumeroSerie').value.trim();
        if (!nome)  { alert('Informe o nome do equipamento.'); return; }
        if (!serie) { alert('Informe o número de série.'); return; }

        const btn = document.getElementById('btnSalvarEquip');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Salvando...';

        const deprecRaw = document.getElementById('equipDepreciacao').value.replace(/\./g, '').replace(',', '.');
        const deprec    = isNaN(parseFloat(deprecRaw)) ? 0 : parseFloat(deprecRaw);

        const payload = {
            id:                  document.getElementById('equipId').value || null,
            cliente_id:          clienteId,
            produto_id:          document.getElementById('equipSelectProduto').value || null,
            produto_nome:        nome,
            produto_codigo:      document.getElementById('equipCodigo').value.trim() || null,
            numero_serie:        serie,
            marca:               document.getElementById('equipMarca').value.trim() || null,
            modelo:              document.getElementById('equipModelo').value.trim() || null,
            data_instalacao:     document.getElementById('equipDataInstalacao').value || null,
            vida_util_meses:     document.getElementById('equipVidaUtil').value || null,
            depreciacao_mensal:  deprec,
            observacoes:         document.getElementById('equipObservacoes').value.trim() || null,
        };

        fetch('/clientes/equipamentos/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalEquipamento')).hide();
                location.reload();
            } else {
                alert(d.error || 'Erro ao salvar equipamento.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i> Salvar Equipamento';
            }
        })
        .catch(() => {
            alert('Erro de comunicação com o servidor.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> Salvar Equipamento';
        });
    };

    // Remover equipamento
    window.removerEquipamento = function(id, nome) {
        if (!confirm('Remover o equipamento "' + nome + '"?\nEsta ação não pode ser desfeita.')) return;
        fetch('/clientes/equipamentos/remove', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const row = document.getElementById('equip-row-' + id);
                if (row) row.remove();
            } else {
                alert(d.error || 'Erro ao remover equipamento.');
            }
        })
        .catch(() => alert('Erro de comunicação.'));
    };
})();
</script>
