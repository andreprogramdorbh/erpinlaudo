/**
 * contratos.js — Módulo de Contratos
 * Lógica de abas, busca de parte (médico/cliente), upload de anexos e apuração.
 */
(function () {
    'use strict';

    // =========================================================
    // ABAS DO FORMULÁRIO
    // =========================================================
    function initTabs() {
        const tabLinks = document.querySelectorAll('[data-contrato-tab]');
        tabLinks.forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const target = this.getAttribute('data-contrato-tab');
                // Desativa todas as abas
                document.querySelectorAll('[data-contrato-tab]').forEach(function (l) {
                    l.classList.remove('active');
                });
                document.querySelectorAll('[data-contrato-pane]').forEach(function (p) {
                    p.classList.add('d-none');
                });
                // Ativa a aba clicada
                this.classList.add('active');
                const pane = document.querySelector('[data-contrato-pane="' + target + '"]');
                if (pane) pane.classList.remove('d-none');
            });
        });
    }

    // =========================================================
    // TIPO DE PARTE: MÉDICO / CLIENTE
    // =========================================================
    function initTipoParte() {
        const selectTipo = document.getElementById('tipo_parte');
        if (!selectTipo) return;

        function toggleParte() {
            const val = selectTipo.value;
            const medicoBlock  = document.getElementById('bloco-medico');
            const clienteBlock = document.getElementById('bloco-cliente');
            const abaApuracao  = document.querySelector('[data-contrato-tab="apuracao"]');

            if (medicoBlock)  medicoBlock.classList.toggle('d-none', val !== 'medico');
            if (clienteBlock) clienteBlock.classList.toggle('d-none', val !== 'cliente');
            if (abaApuracao)  abaApuracao.parentElement.classList.toggle('d-none', val !== 'medico');
        }

        selectTipo.addEventListener('change', toggleParte);
        toggleParte(); // executar na carga
    }

    // =========================================================
    // CÁLCULO DE VIGÊNCIA
    // =========================================================
    function initVigencia() {
        const dtInicio = document.getElementById('data_inicio');
        const dtFim    = document.getElementById('data_fim');
        const vigencia = document.getElementById('vigencia_display');
        if (!dtInicio || !dtFim || !vigencia) return;

        function calcVigencia() {
            const ini = new Date(dtInicio.value);
            const fim = new Date(dtFim.value);
            if (isNaN(ini) || isNaN(fim) || fim < ini) {
                vigencia.textContent = '—';
                return;
            }
            const diffMs   = fim - ini;
            const diffDias = Math.round(diffMs / (1000 * 60 * 60 * 24));
            const meses    = Math.floor(diffDias / 30);
            const dias     = diffDias % 30;
            let txt = '';
            if (meses > 0) txt += meses + ' mês(es) ';
            if (dias  > 0) txt += dias  + ' dia(s)';
            vigencia.textContent = txt.trim() || '0 dias';
        }

        dtInicio.addEventListener('change', calcVigencia);
        dtFim.addEventListener('change', calcVigencia);
        calcVigencia();
    }

    // =========================================================
    // UPLOAD DE ANEXO
    // =========================================================
    function initUploadAnexo() {
        const form = document.getElementById('form-upload-anexo');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(form);
            const btn = form.querySelector('button[type=submit]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enviando...';

            fetch('/contratos/upload-anexo', {
                method: 'POST',
                body: formData
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    // Adiciona linha na tabela de anexos sem recarregar
                    const tbody = document.getElementById('tbody-anexos');
                    if (tbody) {
                        const tr = document.createElement('tr');
                        tr.innerHTML =
                            '<td><i class="fas fa-paperclip me-1 text-muted"></i>' + escHtml(data.nome_original) + '</td>' +
                            '<td class="text-muted small">' + escHtml(data.tamanho_fmt) + '</td>' +
                            '<td class="text-muted small">' + escHtml(data.criado_em) + '</td>' +
                            '<td class="text-center">' +
                            '  <a href="' + escHtml(data.url) + '" target="_blank" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-download"></i></a>' +
                            '  <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteAnexo(' + data.id + ', this)"><i class="fas fa-trash"></i></button>' +
                            '</td>';
                        tbody.appendChild(tr);
                    }
                    // Limpa o input de arquivo
                    form.reset();
                    showToast('Anexo enviado com sucesso!', 'success');
                } else {
                    showToast(data.message || 'Erro ao enviar anexo.', 'danger');
                }
            })
            .catch(function () {
                showToast('Erro de comunicação com o servidor.', 'danger');
            })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }

    // Exclusão de anexo via AJAX
    window.deleteAnexo = function (id, btn) {
        if (!confirm('Excluir este anexo? Esta ação não pode ser desfeita.')) return;
        const tr = btn.closest('tr');
        fetch('/contratos/delete-anexo/' + id)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    tr.remove();
                    showToast('Anexo excluído.', 'success');
                } else {
                    showToast(data.message || 'Erro ao excluir.', 'danger');
                }
            })
            .catch(function () {
                showToast('Erro de comunicação.', 'danger');
            });
    };

    // =========================================================
    // APURAÇÃO: NOVA APURAÇÃO / IMPORTAR / EXECUTAR
    // =========================================================
    function initApuracao() {
        // Botão Nova Apuração
        const btnNova = document.getElementById('btn-nova-apuracao');
        if (btnNova) {
            btnNova.addEventListener('click', function () {
                const contratoId = this.dataset.contratoId;
                fetch('/contratos/nova-apuracao', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'contrato_id=' + contratoId
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        document.getElementById('apuracao-numero').textContent = data.numero;
                        document.getElementById('apuracao-id').value = data.id;
                        document.getElementById('bloco-importacao').classList.remove('d-none');
                        document.getElementById('bloco-nova-apuracao').classList.add('d-none');
                        showToast('Apuração #' + data.numero + ' criada.', 'success');
                    } else {
                        showToast(data.message || 'Erro ao criar apuração.', 'danger');
                    }
                });
            });
        }

        // Formulário de importação
        const formImport = document.getElementById('form-importar-apuracao');
        if (formImport) {
            formImport.addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(formImport);
                const btn = formImport.querySelector('button[type=submit]');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Importando...';

                fetch('/contratos/importar-apuracao', {
                    method: 'POST',
                    body: formData
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        renderPreviewApuracao(data.preview, data.total);
                        document.getElementById('bloco-executar').classList.remove('d-none');
                        showToast(data.total + ' registros importados para pré-visualização.', 'success');
                    } else {
                        showToast(data.message || 'Erro ao importar arquivo.', 'danger');
                    }
                })
                .catch(function () {
                    showToast('Erro ao processar o arquivo.', 'danger');
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-upload me-1"></i> Importar';
                });
            });
        }

        // Botão Executar Apuração
        const btnExecutar = document.getElementById('btn-executar-apuracao');
        if (btnExecutar) {
            btnExecutar.addEventListener('click', function () {
                const apuracaoId = document.getElementById('apuracao-id').value;
                if (!apuracaoId) { showToast('Importe um arquivo primeiro.', 'warning'); return; }

                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processando...';

                fetch('/contratos/executar-apuracao', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'apuracao_id=' + apuracaoId
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        document.getElementById('resultado-apuracao').innerHTML =
                            '<div class="alert alert-success border-0 shadow-sm">' +
                            '<i class="fas fa-check-circle me-2"></i><strong>Apuração concluída com sucesso!</strong><br>' +
                            'Total de exames: <strong>' + data.total_exames + '</strong> | ' +
                            'Valor total: <strong>R$ ' + data.valor_total + '</strong>' +
                            '</div>';
                        // Recarrega a tabela de histórico
                        if (data.redirect) window.location.href = data.redirect;
                    } else {
                        document.getElementById('resultado-apuracao').innerHTML =
                            '<div class="alert alert-danger border-0 shadow-sm">' +
                            '<i class="fas fa-exclamation-triangle me-2"></i>' + (data.message || 'Erro na apuração.') +
                            '</div>';
                    }
                })
                .catch(function () {
                    showToast('Erro de comunicação com o servidor.', 'danger');
                })
                .finally(function () {
                    btnExecutar.disabled = false;
                    btnExecutar.innerHTML = '<i class="fas fa-play me-1"></i> Executar Apuração';
                });
            });
        }
    }

    function renderPreviewApuracao(rows, total) {
        const container = document.getElementById('preview-apuracao');
        if (!container) return;
        if (!rows || rows.length === 0) {
            container.innerHTML = '<p class="text-muted">Nenhum registro encontrado.</p>';
            return;
        }
        let html = '<div class="table-responsive"><table class="table table-sm table-hover align-middle">' +
            '<thead class="table-light"><tr>' +
            '<th>#</th><th>Médico</th><th>Modalidade</th><th>Exame</th><th>Prioridade</th><th>Data</th><th class="text-end">Valor</th>' +
            '</tr></thead><tbody>';
        rows.forEach(function (r, i) {
            const urgente = (r.prioridade || '').toUpperCase().includes('URGENT') || (r.prioridade || '').toUpperCase() === 'U';
            html += '<tr>' +
                '<td class="text-muted small">' + (i + 1) + '</td>' +
                '<td>' + escHtml(r.medico || '—') + '</td>' +
                '<td><span class="badge bg-secondary">' + escHtml(r.modalidade || '—') + '</span></td>' +
                '<td class="small">' + escHtml(r.study_description || '—') + '</td>' +
                '<td><span class="badge bg-' + (urgente ? 'danger' : 'success') + '">' + escHtml(r.prioridade || 'Normal') + '</span></td>' +
                '<td class="small text-muted">' + escHtml(r.data_conclusao || '—') + '</td>' +
                '<td class="text-end">R$ ' + parseFloat(r.valor_exame || 0).toFixed(2).replace('.', ',') + '</td>' +
                '</tr>';
        });
        html += '</tbody></table></div>';
        if (total > rows.length) {
            html += '<p class="text-muted small mt-1">Exibindo ' + rows.length + ' de ' + total + ' registros.</p>';
        }
        container.innerHTML = html;
    }

    // =========================================================
    // UTILITÁRIOS
    // =========================================================
    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function showToast(msg, type) {
        type = type || 'info';
        // Usa SweetAlert2 se disponível, senão alert simples
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type === 'success' ? 'success' : (type === 'danger' ? 'error' : 'info'),
                title: msg,
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true
            });
        } else {
            alert(msg);
        }
    }

    // Confirmação de exclusão
    window.confirmarExclusao = function (url, msg) {
        if (window.Swal) {
            Swal.fire({
                title: 'Confirmar exclusão',
                text: msg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (result.isConfirmed) window.location.href = url;
            });
        } else {
            if (confirm(msg + '\n\nEsta ação não pode ser desfeita.')) {
                window.location.href = url;
            }
        }
    };

    // =========================================================
    // INIT
    // =========================================================
    document.addEventListener('DOMContentLoaded', function () {
        initTabs();
        initTipoParte();
        initVigencia();
        initUploadAnexo();
        initApuracao();

        // Flatpickr em campos de data
        if (window.flatpickr) {
            flatpickr('.flatpickr-date', {
                locale: 'pt',
                dateFormat: 'Y-m-d',
                allowInput: true
            });
        }

        // Ativar aba inicial via URL hash ou parâmetro
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab) {
            const tabLink = document.querySelector('[data-contrato-tab="' + tab + '"]');
            if (tabLink) tabLink.click();
        }
    });
})();
