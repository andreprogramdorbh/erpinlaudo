<?php

use App\Core\UI;
use App\Core\Auth;

$actions = [];
if (Auth::can('create_contas_receber')) {
    $actions[] = [
        'text' => 'Nova Conta',
        'link' => '/financeiro/contas-a-receber/create',
        'icon' => 'fas fa-plus',
        'class' => 'btn-primary'
    ];
}

UI::sectionHeader('Contas a Receber', 'Acompanhe seus recebimentos e vencimentos', $actions);
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/financeiro/contas-a-receber" class="row g-3 align-items-end">
            <div class="col-md-7">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Descrição ou Cliente..."
                        value="<?php echo htmlspecialchars($filtros['pesquisa'] ?? ''); ?>">
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="" <?php echo ($filtros['status'] ?? '') === '' ? 'selected' : ''; ?>>Todos</option>
                    <option value="aberta" <?php echo ($filtros['status'] ?? 'aberta') === 'aberta' ? 'selected' : ''; ?>>Aberta</option>
                    <option value="recebida" <?php echo ($filtros['status'] ?? '') === 'recebida' ? 'selected' : ''; ?>>Recebida</option>
                    <option value="cancelada" <?php echo ($filtros['status'] ?? '') === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>

            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary fw-bold">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php
        $headers = ['Vencimento', 'Descrição', 'Cliente', 'Plano', 'Valor', 'Status', 'Ações'];

        $rowRenderer = function ($c) {
            $status = $c->status ?? 'aberta';

            // ── Formatar data de vencimento para dd/mm/aaaa ──────────────────
            $rawDate = $c->data_vencimento ?? '';
            if ($rawDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
                [$y, $m, $d] = explode('-', $rawDate);
                $vencTexto = "{$d}/{$m}/{$y}"; // data pura — usada no data-venc do botão
            } else {
                $vencTexto = htmlspecialchars($rawDate);
            }

            // Indicador visual de vencimento (HTML com spans — usado apenas na célula da tabela)
            $venc = $vencTexto;
            $hoje = date('Y-m-d');
            if ($status === 'aberta' && $rawDate !== '') {
                if ($rawDate < $hoje) {
                    $venc = '<span class="text-danger fw-bold" title="Vencida">' . $vencTexto . ' <i class="fas fa-exclamation-circle"></i></span>';
                } elseif ($rawDate === $hoje) {
                    $venc = '<span class="text-warning fw-bold" title="Vence hoje">' . $vencTexto . ' <i class="fas fa-clock"></i></span>';
                }
            }

            // ── Badge de status ───────────────────────────────────────────────
            if ($status === 'recebida') {
                $badge = '<span class="badge bg-success">Recebida</span>';
            } elseif ($status === 'cancelada') {
                $badge = '<span class="badge bg-secondary">Cancelada</span>';
            } else {
                $badge = '<span class="badge bg-warning text-dark">Aberta</span>';
            }

            $desc  = htmlspecialchars($c->descricao ?? '');
            $cli   = htmlspecialchars($c->cliente_nome ?? '');
            $plano = htmlspecialchars($c->plano_codigo ?? '');
            $valor = number_format((float)($c->valor ?? 0), 2, ',', '.');
            $id    = (int)$c->id;

            // ── Botões de ação ────────────────────────────────────────────────
            $acoes = '';

            // Botão "Receber Manualmente" — apenas para contas abertas
            if ($status === 'aberta' && \App\Core\Auth::can('edit_contas_receber')) {
                $acoes .= '<button type="button" class="btn btn-sm btn-success me-1 btn-receber-manual" '
                    . 'data-id="' . $id . '" '
                    . 'data-desc="' . htmlspecialchars($c->descricao ?? '', ENT_QUOTES) . '" '
                    . 'data-valor="R$ ' . $valor . '" '
                    . 'data-venc="' . htmlspecialchars($vencTexto, ENT_QUOTES) . '" '
                    . 'title="Receber Manualmente" style="padding:3px 8px">'
                    . '<i class="fas fa-check-circle me-1"></i> Receber</button>';
            }

            if (\App\Core\Auth::can('edit_contas_receber')) {
                $acoes .= '<a href="/financeiro/contas-a-receber/edit/' . $id . '" class="text-primary me-2" title="Editar"><i class="fas fa-edit"></i></a>';
            }

            if (\App\Core\Auth::can('delete_contas_receber')) {
                $acoes .= '<a href="#" class="text-danger" title="Cancelar" onclick="confirmDelete(' . $id . '); return false;"><i class="fas fa-ban"></i></a>';
            }

            return '<tr>'
                . '<td style="white-space:nowrap">' . $venc . '</td>'
                . '<td><strong>' . $desc . '</strong></td>'
                . '<td>' . $cli . '</td>'
                . '<td>' . $plano . '</td>'
                . '<td>R$ ' . $valor . '</td>'
                . '<td>' . $badge . '</td>'
                . '<td style="white-space:nowrap">' . $acoes . '</td>'
                . '</tr>';
        };

        UI::render('table', [
            'headers'      => $headers,
            'items'        => $contas ?? [],
            'rowRenderer'  => $rowRenderer,
            'emptyMessage' => 'Nenhuma conta encontrada com os filtros aplicados.',
        ]);
        ?>
    </div>
</div>

<!-- Modal de confirmação de recebimento manual -->
<div class="modal fade" id="modalReceberManual" tabindex="-1" aria-labelledby="modalReceberManualLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalReceberManualLabel">
                    <i class="fas fa-check-circle me-2"></i>Confirmar Recebimento Manual
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning d-flex align-items-start gap-3 mb-3">
                    <i class="fas fa-exclamation-triangle fa-lg mt-1 text-warning"></i>
                    <div>
                        <strong>Atenção:</strong> Esta ação marcará o título como <strong>Recebido</strong> e executará as seguintes operações:
                        <ul class="mt-2 mb-0 ps-3">
                            <li>Atualiza o status da conta para <strong>Recebida</strong></li>
                            <li>Registra a data de recebimento como <strong>hoje</strong></li>
                            <li>Libera a <strong>Nota Fiscal</strong> para o cliente no portal</li>
                            <li>Notifica o cliente por <strong>e-mail</strong> (se configurado)</li>
                            <li>Gera a <strong>próxima parcela</strong> (se conta recorrente)</li>
                        </ul>
                    </div>
                </div>

                <div class="card border-success bg-success bg-opacity-10">
                    <div class="card-body py-2 px-3">
                        <div class="row g-1">
                            <div class="col-12">
                                <small class="text-muted">Descrição</small>
                                <div class="fw-bold" id="rm-desc">—</div>
                            </div>
                            <div class="col-6 mt-2">
                                <small class="text-muted">Vencimento</small>
                                <div id="rm-venc">—</div>
                            </div>
                            <div class="col-6 mt-2">
                                <small class="text-muted">Valor</small>
                                <div class="fw-bold text-success" id="rm-valor">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label small fw-bold text-muted">Meio de Pagamento</label>
                    <select class="form-select" id="rm-meio-pagamento">
                        <option value="dinheiro">Dinheiro</option>
                        <option value="pix" selected>PIX</option>
                        <option value="transferencia">Transferência Bancária</option>
                        <option value="boleto">Boleto</option>
                        <option value="cartao">Cartão</option>
                        <option value="cheque">Cheque</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>

                <div class="mt-3">
                    <label class="form-label small fw-bold text-muted">Observações (opcional)</label>
                    <input type="text" class="form-control" id="rm-observacoes" placeholder="Ex: Pago via PIX em 03/04/2026">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-success fw-bold" id="btnConfirmarRecebimento">
                    <i class="fas fa-check-circle me-1"></i>Confirmar Recebimento
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ── Cancelar conta ────────────────────────────────────────────────────────────
function confirmDelete(id) {
    if (confirm('Deseja realmente cancelar esta conta?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/financeiro/contas-a-receber/delete/' + id;
        document.body.appendChild(form);
        form.submit();
    }
}

// ── Receber Manualmente ───────────────────────────────────────────────────────
(function () {
    let contaIdAtual = null;

    // Delegar clique nos botões (funciona com tabelas renderizadas dinamicamente)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-receber-manual');
        if (!btn) return;

        contaIdAtual = btn.dataset.id;

        // Preencher dados no modal
        document.getElementById('rm-desc').textContent  = btn.dataset.desc  || '—';
        document.getElementById('rm-valor').textContent = btn.dataset.valor || '—';

        // Limpar tags HTML da data (pode ter spans de urgência)
        const tmpDiv = document.createElement('div');
        tmpDiv.innerHTML = btn.dataset.venc || '—';
        document.getElementById('rm-venc').textContent = tmpDiv.textContent || '—';

        // Resetar campos
        document.getElementById('rm-meio-pagamento').value = 'pix';
        document.getElementById('rm-observacoes').value    = '';

        const modal = new bootstrap.Modal(document.getElementById('modalReceberManual'));
        modal.show();
    });

    // Confirmar recebimento
    document.getElementById('btnConfirmarRecebimento').addEventListener('click', function () {
        if (!contaIdAtual) return;

        const btn       = this;
        const meioPag   = document.getElementById('rm-meio-pagamento').value;
        const obs       = document.getElementById('rm-observacoes').value.trim();

        btn.disabled    = true;
        btn.innerHTML   = '<i class="fas fa-spinner fa-spin me-1"></i>Processando...';

        fetch('/financeiro/contas-a-receber/receber-manual/' + contaIdAtual, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                meio_pagamento: meioPag,
                observacoes:    obs,
            }),
        })
        .then(r => r.json())
        .then(data => {
            bootstrap.Modal.getInstance(document.getElementById('modalReceberManual')).hide();

            if (data.success) {
                // Feedback visual de sucesso
                const toast = document.createElement('div');
                toast.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3 shadow';
                toast.style.zIndex = '9999';
                toast.innerHTML = '<i class="fas fa-check-circle me-2"></i><strong>Recebimento registrado!</strong> '
                    + (data.message || 'Conta marcada como recebida com sucesso.')
                    + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                document.body.appendChild(toast);
                setTimeout(() => { toast.remove(); location.reload(); }, 2500);
            } else {
                alert('Erro: ' + (data.message || 'Falha ao processar o recebimento.'));
                btn.disabled  = false;
                btn.innerHTML = '<i class="fas fa-check-circle me-1"></i>Confirmar Recebimento';
            }
        })
        .catch(err => {
            alert('Erro de comunicação: ' + err.message);
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-check-circle me-1"></i>Confirmar Recebimento';
        });
    });
})();
</script>
