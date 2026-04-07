/**
 * exames-tabela.js
 * Módulo de Tabela de Exames / Serviços
 * Gerencia: edição, exclusão, configuração (preços, seção, TAGs DICOM)
 */

(function () {
    'use strict';

    // -------------------------------------------------------
    // Utilitários
    // -------------------------------------------------------
    function getCsrfToken() {
        const m = document.querySelector('meta[name="csrf-token"]');
        if (m) return m.getAttribute('content');
        const f = document.querySelector('input[name="csrf_token"]');
        return f ? f.value : '';
    }

    function parseBR(val) {
        if (!val) return 0;
        return parseFloat(String(val).replace(/\./g, '').replace(',', '.')) || 0;
    }

    function formatBR(val) {
        return parseFloat(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function showToast(msg, type) {
        type = type || 'success';
        const colors = { success: '#198754', danger: '#dc3545', warning: '#ffc107' };
        const div = document.createElement('div');
        div.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;min-width:280px;';
        div.innerHTML = '<div class="alert alert-' + type + ' shadow border-0 d-flex align-items-center gap-2 mb-0">'
            + '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i>'
            + '<span>' + msg + '</span></div>';
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 3500);
    }

    function postJSON(url, data, callback) {
        const params = new URLSearchParams();
        params.append('csrf_token', getCsrfToken());
        for (const key in data) {
            if (Array.isArray(data[key])) {
                data[key].forEach((item, i) => {
                    for (const k in item) {
                        params.append(key + '[' + i + '][' + k + ']', item[k]);
                    }
                });
            } else {
                params.append(key, data[key]);
            }
        }
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: params.toString()
        })
        .then(r => r.json())
        .then(callback)
        .catch(e => { console.error(e); showToast('Erro de comunicação com o servidor.', 'danger'); });
    }

    // -------------------------------------------------------
    // Modal Editar Exame
    // -------------------------------------------------------
    document.querySelectorAll('.btn-editar-exame').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit_exame_id').value       = this.dataset.id;
            document.getElementById('edit_nome_exame').value     = this.dataset.nome;
            document.getElementById('edit_modalidade').value     = this.dataset.modalidade;
            document.getElementById('edit_valor_rotina').value   = this.dataset.rotina;
            document.getElementById('edit_valor_urgencia').value = this.dataset.urgencia;
            new bootstrap.Modal(document.getElementById('modalEditarExame')).show();
        });
    });

    document.getElementById('btnSalvarEdicao').addEventListener('click', function () {
        const id = document.getElementById('edit_exame_id').value;
        const data = {
            nome_exame:     document.getElementById('edit_nome_exame').value,
            modalidade:     document.getElementById('edit_modalidade').value,
            valor_rotina:   document.getElementById('edit_valor_rotina').value,
            valor_urgencia: document.getElementById('edit_valor_urgencia').value,
        };
        postJSON('/exames-tabela/' + id + '/update', data, function (res) {
            if (res.success) {
                showToast('Exame atualizado com sucesso!');
                bootstrap.Modal.getInstance(document.getElementById('modalEditarExame')).hide();
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(res.message || 'Erro ao atualizar.', 'danger');
            }
        });
    });

    // -------------------------------------------------------
    // Excluir Exame
    // -------------------------------------------------------
    document.querySelectorAll('.btn-excluir-exame').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id   = this.dataset.id;
            const nome = this.dataset.nome;
            if (!confirm('Confirma a exclusão do exame "' + nome + '"?\n\nEsta ação não pode ser desfeita.')) return;
            postJSON('/exames-tabela/' + id + '/delete', {}, function (res) {
                if (res.success) {
                    showToast('Exame excluído com sucesso!');
                    const row = document.getElementById('row-exame-' + id);
                    if (row) row.remove();
                } else {
                    showToast(res.message || 'Erro ao excluir.', 'danger');
                }
            });
        });
    });

    // -------------------------------------------------------
    // Modal Configuração
    // -------------------------------------------------------
    var configValorRotina   = 0;
    var configValorUrgencia = 0;

    document.querySelectorAll('.btn-config-exame').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            document.getElementById('config_exame_id').value = id;

            // Carregar dados via AJAX
            fetch('/exames-tabela/' + id + '/config', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(function (res) {
                if (!res.success) { showToast('Erro ao carregar configuração.', 'danger'); return; }

                const e = res.exame;
                document.getElementById('configExameNome').textContent = '— ' + e.nome_exame;

                configValorRotina   = parseFloat(e.valor_rotina   || 0);
                configValorUrgencia = parseFloat(e.valor_urgencia || 0);

                // Aba Preços (Médico) — valores DIRETOS
                document.getElementById('preco_nivel').value          = e.nivel || '';
                document.getElementById('preco_valor_rotina').value   = formatBR(e.valor_rotina);
                document.getElementById('preco_valor_urgencia').value = formatBR(e.valor_urgencia);
                atualizarPreviewPrecos();

                // Aba Seção (Venda)
                document.getElementById('sec_icms').value                = formatBR(e.imposto_icms);
                document.getElementById('sec_ipi').value                 = formatBR(e.imposto_ipi);
                document.getElementById('sec_pis_cofins').value          = formatBR(e.imposto_pis_cofins);
                document.getElementById('sec_simples').value             = formatBR(e.imposto_simples);
                document.getElementById('sec_comissao').value            = formatBR(e.custo_comissao);
                document.getElementById('sec_mo_direta').value           = formatBR(e.custo_mao_obra_direta);
                document.getElementById('sec_mo_indireta').value         = formatBR(e.custo_mao_obra_indireta);
                document.getElementById('sec_margem').value              = formatBR(e.margem_lucro);
                document.getElementById('sec_perc_venda_rotina').value   = formatBR(e.perc_venda_rotina);
                document.getElementById('sec_perc_venda_urgencia').value = formatBR(e.perc_venda_urgencia);
                atualizarPreviewSecao();

                // Aba TAGs DICOM
                renderTags(res.tags || []);

                new bootstrap.Modal(document.getElementById('modalConfigExame')).show();
            })
            .catch(() => showToast('Erro de comunicação.', 'danger'));
        });
    });

    // -------------------------------------------------------
    // Preview em tempo real — Aba Preços (Médico) — valores DIRETOS
    // -------------------------------------------------------
    function atualizarPreviewPrecos() {
        const vR = parseBR(document.getElementById('preco_valor_rotina').value);
        const vU = parseBR(document.getElementById('preco_valor_urgencia').value);
        document.getElementById('preview_valor_rotina').textContent   = 'R$ ' + formatBR(vR);
        document.getElementById('preview_valor_urgencia').textContent = 'R$ ' + formatBR(vU);
        configValorRotina   = vR;
        configValorUrgencia = vU;
        atualizarPreviewSecao();
    }

    ['preco_valor_rotina', 'preco_valor_urgencia'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', atualizarPreviewPrecos);
    });

    // -------------------------------------------------------
    // Preview em tempo real — Aba Seção (Venda/Cliente)
    // Base = valor_rotina médico + encargos + margem geral + margens independentes
    // -------------------------------------------------------
    function atualizarPreviewSecao() {
        const icms       = parseBR(document.getElementById('sec_icms').value);
        const ipi        = parseBR(document.getElementById('sec_ipi').value);
        const pisCofins  = parseBR(document.getElementById('sec_pis_cofins').value);
        const simples    = parseBR(document.getElementById('sec_simples').value);
        const comissao   = parseBR(document.getElementById('sec_comissao').value);
        const moDireta   = parseBR(document.getElementById('sec_mo_direta').value);
        const moIndireta = parseBR(document.getElementById('sec_mo_indireta').value);
        const margem     = parseBR(document.getElementById('sec_margem').value);
        const percVendaR = parseBR(document.getElementById('sec_perc_venda_rotina').value);
        const percVendaU = parseBR(document.getElementById('sec_perc_venda_urgencia').value);

        const valorBase  = configValorRotina;
        const totalPerc  = icms + ipi + pisCofins + simples + comissao + moDireta + moIndireta;
        const precoCusto = valorBase + (valorBase * totalPerc / 100);
        const precoVenda = precoCusto + (precoCusto * margem / 100);
        const vendaR     = precoVenda + (precoVenda * percVendaR / 100);
        const vendaU     = precoVenda + (precoVenda * percVendaU / 100);

        document.getElementById('sec_preview_base_medico').textContent    = 'R$ ' + formatBR(valorBase);
        document.getElementById('sec_preview_custo').textContent          = 'R$ ' + formatBR(precoCusto);
        document.getElementById('sec_preview_custo_label').textContent    = '(encargos: ' + formatBR(totalPerc) + '%)';
        document.getElementById('sec_preview_venda').textContent          = 'R$ ' + formatBR(precoVenda);
        document.getElementById('sec_preview_venda_rotina').textContent   = 'Rotina: R$ ' + formatBR(vendaR);
        document.getElementById('sec_preview_venda_urgencia').textContent = 'Urgência: R$ ' + formatBR(vendaU);
    }

    ['sec_icms','sec_ipi','sec_pis_cofins','sec_simples','sec_comissao',
     'sec_mo_direta','sec_mo_indireta','sec_margem',
     'sec_perc_venda_rotina','sec_perc_venda_urgencia'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', atualizarPreviewSecao);
    });

    // -------------------------------------------------------
    // Salvar Preços
    // -------------------------------------------------------
    document.getElementById('btnSalvarPrecos').addEventListener('click', function () {
        const id = document.getElementById('config_exame_id').value;
        postJSON('/exames-tabela/' + id + '/save-precos', {
            nivel:          document.getElementById('preco_nivel').value,
            valor_rotina:   document.getElementById('preco_valor_rotina').value,
            valor_urgencia: document.getElementById('preco_valor_urgencia').value,
        }, function (res) {
            if (res.success) {
                showToast('Preços salvos com sucesso!');
                configValorRotina   = parseBR(document.getElementById('preco_valor_rotina').value);
                configValorUrgencia = parseBR(document.getElementById('preco_valor_urgencia').value);
            } else {
                showToast(res.message || 'Erro ao salvar preços.', 'danger');
            }
        });
    });

    // -------------------------------------------------------
    // Salvar Seção
    // -------------------------------------------------------
    document.getElementById('btnSalvarSecao').addEventListener('click', function () {
        const id = document.getElementById('config_exame_id').value;
        postJSON('/exames-tabela/' + id + '/save-secao', {
            imposto_icms:            document.getElementById('sec_icms').value,
            imposto_ipi:             document.getElementById('sec_ipi').value,
            imposto_pis_cofins:      document.getElementById('sec_pis_cofins').value,
            imposto_simples:         document.getElementById('sec_simples').value,
            custo_comissao:          document.getElementById('sec_comissao').value,
            custo_mao_obra_direta:   document.getElementById('sec_mo_direta').value,
            custo_mao_obra_indireta: document.getElementById('sec_mo_indireta').value,
            margem_lucro:            document.getElementById('sec_margem').value,
            perc_venda_rotina:       document.getElementById('sec_perc_venda_rotina').value,
            perc_venda_urgencia:     document.getElementById('sec_perc_venda_urgencia').value,
        }, function (res) {
            if (res.success) {
                showToast('Seção salva com sucesso!');
            } else {
                showToast(res.message || 'Erro ao salvar seção.', 'danger');
            }
        });
    });

    // -------------------------------------------------------
    // TAGs DICOM
    // -------------------------------------------------------
    function renderTags(tags) {
        const container = document.getElementById('dicom-tags-container');
        container.innerHTML = '';
        if (!tags || tags.length === 0) {
            addTagRow('', '');
            return;
        }
        tags.forEach(function (t) {
            addTagRow(t.tag_nome || '', t.tag_valor || '');
        });
    }

    function addTagRow(nome, valor) {
        const container = document.getElementById('dicom-tags-container');
        const div = document.createElement('div');
        div.className = 'row g-2 mb-2 tag-row align-items-center';
        div.innerHTML = '<div class="col-5">'
            + '<input type="text" class="form-control form-control-sm tag-nome" placeholder="Ex: Modality" value="' + escHtml(nome) + '">'
            + '</div>'
            + '<div class="col-6">'
            + '<input type="text" class="form-control form-control-sm tag-valor" placeholder="Ex: CT" value="' + escHtml(valor) + '">'
            + '</div>'
            + '<div class="col-1 text-end">'
            + '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-tag" title="Remover">'
            + '<i class="fas fa-times"></i></button>'
            + '</div>';
        container.appendChild(div);
        div.querySelector('.btn-remove-tag').addEventListener('click', function () {
            div.remove();
        });
    }

    function escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    document.getElementById('btnAddTag').addEventListener('click', function () {
        addTagRow('', '');
    });

    // Sugestões de tags
    document.querySelectorAll('.btn-tag-sugestao').forEach(function (btn) {
        btn.addEventListener('click', function () {
            addTagRow(this.dataset.tag, this.dataset.valor);
        });
    });

    document.getElementById('btnSalvarTags').addEventListener('click', function () {
        const id = document.getElementById('config_exame_id').value;
        const rows = document.querySelectorAll('#dicom-tags-container .tag-row');
        const tags = [];
        rows.forEach(function (row) {
            const nome  = row.querySelector('.tag-nome').value.trim();
            const valor = row.querySelector('.tag-valor').value.trim();
            if (nome) tags.push({ nome: nome, valor: valor });
        });

        // Montar FormData manualmente para array
        const params = new URLSearchParams();
        params.append('csrf_token', getCsrfToken());
        tags.forEach(function (t, i) {
            params.append('tags[' + i + '][nome]', t.nome);
            params.append('tags[' + i + '][valor]', t.valor);
        });

        fetch('/exames-tabela/' + id + '/save-tags', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: params.toString()
        })
        .then(r => r.json())
        .then(function (res) {
            if (res.success) {
                showToast('Tags DICOM salvas com sucesso!');
            } else {
                showToast(res.message || 'Erro ao salvar tags.', 'danger');
            }
        })
        .catch(() => showToast('Erro de comunicação.', 'danger'));
    });

})();
