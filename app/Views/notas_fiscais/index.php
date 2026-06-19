<?php

use App\Core\UI;
use App\Core\Auth;

$actions = [];
if (Auth::can('create_notas_fiscais')) {
    $actions[] = [
        'text'  => 'Nova Nota',
        'link'  => '/faturamento/notas-fiscais/create',
        'icon'  => 'fas fa-plus',
        'class' => 'btn-primary',
    ];
}
if (Auth::can('import_notas_fiscais')) {
    $actions[] = [
        'text'  => 'Importar XML',
        'link'  => '/faturamento/notas-fiscais/importar',
        'icon'  => 'fas fa-file-import',
        'class' => 'btn-outline-primary',
    ];
}

UI::sectionHeader('Notas Fiscais', 'Emita, acompanhe e importe suas NF-e', $actions);

// Mapa de labels de status para o banner do filtro ativo
$statusLabels = [
    'rascunho'     => ['label' => 'Rascunho',          'cls' => 'warning text-dark',  'icon' => 'fas fa-pencil-alt'],
    'agendada'     => ['label' => 'Agendada (Asaas)',   'cls' => 'primary',            'icon' => 'fas fa-clock'],
    'emitida'      => ['label' => 'Emitida',            'cls' => 'success',            'icon' => 'fas fa-check-circle'],
    'importada'    => ['label' => 'Importada',          'cls' => 'info text-dark',     'icon' => 'fas fa-file-import'],
    'erro_emissao' => ['label' => 'Erro de Emissão',   'cls' => 'danger',             'icon' => 'fas fa-exclamation-triangle'],
    'cancelada'    => ['label' => 'Cancelada',          'cls' => 'secondary',          'icon' => 'fas fa-ban'],
];

$filtroStatusAtivo = $filtros['status'] ?? '';
$filtroQ           = $filtros['pesquisa'] ?? '';
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/faturamento/notas-fiscais" class="row g-3 align-items-end" id="formFiltroNF">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0"
                           placeholder="Número, série ou cliente..."
                           value="<?php echo htmlspecialchars($filtroQ); ?>">
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select name="status" class="form-select" id="selectStatusNF" onchange="document.getElementById('formFiltroNF').submit()">
                    <option value="" <?php echo $filtroStatusAtivo === '' ? 'selected' : ''; ?>>Todos os status</option>
                    <option value="rascunho"    <?php echo $filtroStatusAtivo === 'rascunho'    ? 'selected' : ''; ?>>Rascunho</option>
                    <option value="agendada"    <?php echo $filtroStatusAtivo === 'agendada'    ? 'selected' : ''; ?>>Agendada (Asaas)</option>
                    <option value="emitida"     <?php echo $filtroStatusAtivo === 'emitida'     ? 'selected' : ''; ?>>Emitida</option>
                    <option value="importada"   <?php echo $filtroStatusAtivo === 'importada'   ? 'selected' : ''; ?>>Importada</option>
                    <option value="erro_emissao"<?php echo $filtroStatusAtivo === 'erro_emissao'? 'selected' : ''; ?>>Erro de Emissão</option>
                    <option value="cancelada"   <?php echo $filtroStatusAtivo === 'cancelada'   ? 'selected' : ''; ?>>Cancelada</option>
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

<?php
// ── Banner de filtro ativo ────────────────────────────────────────────────────
$temFiltro = $filtroStatusAtivo !== '' || $filtroQ !== '';
if ($temFiltro):
    $partes = [];
    if ($filtroStatusAtivo !== '' && isset($statusLabels[$filtroStatusAtivo])) {
        $sl = $statusLabels[$filtroStatusAtivo];
        $partes[] = '<span class="badge bg-' . $sl['cls'] . ' fs-6 px-3 py-2">'
            . '<i class="' . $sl['icon'] . ' me-1"></i>' . htmlspecialchars($sl['label']) . '</span>';
    }
    if ($filtroQ !== '') {
        $partes[] = '<span class="badge bg-light text-dark border fs-6 px-3 py-2">'
            . '<i class="fas fa-search me-1 text-muted"></i>' . htmlspecialchars($filtroQ) . '</span>';
    }
    $totalNotas = count($notas ?? []);
?>
<div class="alert alert-light border d-flex align-items-center justify-content-between mb-4 py-2 px-3" style="background:#f8fafc">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="text-muted small fw-bold"><i class="fas fa-filter me-1"></i>Filtrando por:</span>
        <?php echo implode(' ', $partes); ?>
        <span class="text-muted small ms-1">(<?php echo $totalNotas; ?> resultado<?php echo $totalNotas !== 1 ? 's' : ''; ?>)</span>
    </div>
    <a href="/faturamento/notas-fiscais" class="btn btn-sm btn-outline-secondary" title="Limpar filtros">
        <i class="fas fa-times me-1"></i>Limpar
    </a>
</div>
<?php endif; ?>

<?php
// ── Aviso especial quando filtrando por erro ──────────────────────────────────
if ($filtroStatusAtivo === 'erro_emissao' && !empty($notas)):
?>
<div class="alert alert-danger border-danger d-flex align-items-center gap-3 mb-4 py-2 px-3">
    <i class="fas fa-exclamation-triangle fa-lg"></i>
    <div>
        <strong><?php echo count($notas); ?> nota<?php echo count($notas) !== 1 ? 's' : ''; ?> com erro de emissão.</strong>
        Notas Asaas com erro podem ser <strong>reemitidas</strong> diretamente pela coluna Ações.
        Notas manuais devem ser editadas e resubmetidas.
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php
        $headers = ['Emissão', 'NF / Série', 'Cliente', 'Valor', 'Origem', 'Status', 'Ações'];

        $rowRenderer = function ($nf) {
            $status        = $nf->status        ?? 'rascunho';
            $origemEmissao = $nf->origem_emissao ?? 'manual';
            $asaasStatus   = $nf->asaas_status   ?? null;
            $asaasInvoiceId= $nf->asaas_invoice_id ?? null;
            $errorDesc     = $nf->asaas_error_desc ?? null;
            $id            = (int)$nf->id;

            // ── Badge de status local ─────────────────────────────────────────
            $localBadge = match ($status) {
                'emitida'      => '<span class="badge bg-success">Emitida</span>',
                'importada'    => '<span class="badge bg-info text-dark">Importada</span>',
                'cancelada'    => '<span class="badge bg-secondary">Cancelada</span>',
                'agendada'     => '<span class="badge bg-primary">Agendada</span>',
                'erro_emissao' => '<span class="badge bg-danger">Erro</span>',
                'pendente'     => '<span class="badge bg-warning text-dark">Pendente</span>',
                default        => '<span class="badge bg-warning text-dark">Rascunho</span>',
            };

            // ── Badge de status Asaas (linha abaixo do local) ─────────────────
            $asaasBadge = '';
            if ($asaasStatus !== null && $asaasStatus !== '') {
                [$asaasBadgeCls, $asaasIcon] = match (strtoupper($asaasStatus)) {
                    'SCHEDULED'               => ['badge bg-light text-dark border',          'fas fa-clock'],
                    'SYNCHRONIZED'            => ['badge bg-primary bg-opacity-25 text-primary border border-primary', 'fas fa-paper-plane'],
                    'AUTHORIZED'              => ['badge bg-success bg-opacity-25 text-success border border-success', 'fas fa-check-circle'],
                    'PROCESSING_CANCELLATION' => ['badge bg-warning text-dark border',        'fas fa-hourglass-half'],
                    'CANCELED'                => ['badge bg-secondary',                        'fas fa-ban'],
                    'CANCELLATION_DENIED'     => ['badge bg-info text-dark border',           'fas fa-times-circle'],
                    'ERROR'                   => ['badge bg-danger',                           'fas fa-exclamation-triangle'],
                    default                   => ['badge bg-light text-muted border',          'fas fa-circle'],
                };
                $label = \App\Services\AsaasService::mapearStatusNfs($asaasStatus);
                $asaasBadge = '<br><span class="' . $asaasBadgeCls . ' mt-1 d-inline-flex align-items-center gap-1" style="font-size:.68rem">'
                    . '<i class="' . $asaasIcon . '"></i> ' . htmlspecialchars($label) . '</span>';

                // Tooltip de erro no badge
                if (strtoupper($asaasStatus) === 'ERROR' && $errorDesc) {
                    $escapedDesc = htmlspecialchars($errorDesc);
                    $asaasBadge .= '<br><small class="text-danger d-block mt-1" style="font-size:.65rem;max-width:200px;white-space:normal" title="' . $escapedDesc . '">'
                        . '<i class="fas fa-info-circle me-1"></i>'
                        . mb_strimwidth($errorDesc, 0, 60, '...') . '</small>';
                }
            }

            // ── Badge de origem ───────────────────────────────────────────────
            $origemBadge = ($origemEmissao === 'asaas')
                ? '<span class="badge" style="background:#00b37e;color:#fff;font-size:.65rem"><i class="fas fa-bolt me-1"></i>Asaas</span>'
                : '<span class="badge bg-light text-muted border" style="font-size:.65rem"><i class="fas fa-user me-1"></i>Manual</span>';

            // ── Ações ─────────────────────────────────────────────────────────
            $acoes = '';

            // Visualizar — sempre disponível
            if (Auth::can('view_notas_fiscais')) {
                $acoes .= '<a href="/faturamento/notas-fiscais/show/' . $id . '" '
                    . 'class="btn btn-sm btn-outline-info me-1" title="Visualizar" style="padding:2px 7px">'
                    . '<i class="fas fa-eye"></i></a>';
            }

            // Editar — bloqueado para NFs do Asaas
            if ($origemEmissao !== 'asaas' && Auth::can('edit_notas_fiscais')) {
                $acoes .= '<a href="/faturamento/notas-fiscais/edit/' . $id . '" '
                    . 'class="btn btn-sm btn-outline-primary me-1" title="Editar" style="padding:2px 7px">'
                    . '<i class="fas fa-edit"></i></a>';
            }

            // Reemitir — apenas para NFs Asaas com erro
            if ($origemEmissao === 'asaas' && $asaasInvoiceId && $status === 'erro_emissao' && Auth::can('edit_notas_fiscais')) {
                $acoes .= '<button type="button" class="btn btn-sm btn-warning me-1 btn-reemitir-asaas" '
                    . 'data-id="' . $id . '" title="Reemitir NF no Asaas" style="padding:2px 7px">'
                    . '<i class="fas fa-redo-alt"></i></button>';
            }

            // Consultar Asaas — apenas para NFs vinculadas ao Asaas
            if ($asaasInvoiceId && Auth::can('view_notas_fiscais')) {
                $acoes .= '<button type="button" class="btn btn-sm btn-outline-secondary me-1 btn-consultar-asaas" '
                    . 'data-id="' . $id . '" title="Consultar Asaas" style="padding:2px 7px">'
                    . '<i class="fas fa-sync-alt"></i></button>';
            }

            // Cancelar
            if (Auth::can('delete_notas_fiscais') && $status !== 'cancelada') {
                $acoes .= '<button type="button" class="btn btn-sm btn-outline-danger" '
                    . 'onclick="confirmDelete(' . $id . ')" title="Cancelar" style="padding:2px 7px">'
                    . '<i class="fas fa-ban"></i></button>';
            }

            $emi   = htmlspecialchars($nf->data_emissao ?? '');
            $num   = htmlspecialchars($nf->numero_nf ?? '—');
            $ser   = htmlspecialchars($nf->serie ?? '');
            $cli   = htmlspecialchars($nf->cliente_nome ?? '');
            $valor = 'R$ ' . number_format((float)($nf->valor_total ?? 0), 2, ',', '.');

            // Linha com highlight para erros
            $trClass = ($status === 'erro_emissao') ? ' class="table-danger"' : '';

            return '<tr' . $trClass . '>'
                . '<td style="white-space:nowrap">' . $emi . '</td>'
                . '<td><strong>' . $num . '</strong>' . ($ser !== '' ? '<br><small class="text-muted">Série ' . $ser . '</small>' : '') . '</td>'
                . '<td>' . $cli . '</td>'
                . '<td class="fw-bold">' . $valor . '</td>'
                . '<td>' . $origemBadge . '</td>'
                . '<td>' . $localBadge . $asaasBadge . '</td>'
                . '<td style="white-space:nowrap">' . $acoes . '</td>'
                . '</tr>';
        };

        UI::render('table', [
            'headers'      => $headers,
            'items'        => $notas ?? [],
            'rowRenderer'  => $rowRenderer,
            'emptyMessage' => 'Nenhuma nota encontrada com os filtros aplicados.',
        ]);
        ?>
    </div>
</div>

<!-- Toast de feedback -->
<div id="toastAsaas" class="position-fixed top-0 end-0 m-3" style="z-index:9999;min-width:320px;display:none">
    <div class="toast show shadow" role="alert">
        <div class="toast-header" id="toastAsaasHeader">
            <i class="fas fa-sync-alt me-2"></i>
            <strong class="me-auto">Asaas</strong>
            <button type="button" class="btn-close" onclick="document.getElementById('toastAsaas').style.display='none'"></button>
        </div>
        <div class="toast-body fw-bold" id="toastAsaasBody"></div>
    </div>
</div>

<!-- Modal de confirmação de reemissão -->
<div class="modal fade" id="modalReemitir" tabindex="-1" aria-labelledby="modalReemitirLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalReemitirLabel">
                    <i class="fas fa-redo-alt text-warning me-2"></i>Reemitir Nota Fiscal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Deseja reemitir esta nota fiscal no Asaas?</p>
                <div class="alert alert-warning py-2 px-3 small mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Será criada uma nova NF no Asaas com os mesmos dados de serviço.
                    O registro atual será atualizado com o novo ID gerado.
                </div>
                <div id="reemitirErroMsg" class="alert alert-danger py-2 px-3 small mt-3 mb-0" style="display:none"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning fw-bold" id="btnConfirmarReemitir">
                    <i class="fas fa-redo-alt me-1"></i>Reemitir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Deseja realmente cancelar esta nota fiscal?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/faturamento/notas-fiscais/delete/' + id;
        document.body.appendChild(form);
        form.submit();
    }
}

function showToast(msg, type) {
    const toast  = document.getElementById('toastAsaas');
    const header = document.getElementById('toastAsaasHeader');
    const body   = document.getElementById('toastAsaasBody');
    const clsMap = { success: 'bg-success text-white', danger: 'bg-danger text-white', warning: 'bg-warning text-dark', info: 'bg-info text-dark' };
    header.className = 'toast-header ' + (clsMap[type] || '');
    body.innerHTML   = msg;
    toast.style.display = 'block';
    setTimeout(function() { toast.style.display = 'none'; }, type === 'danger' ? 7000 : 3500);
}

// ── Consultar status no Asaas ────────────────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-consultar-asaas');
    if (!btn) return;

    const id    = btn.dataset.id;
    const orig  = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch('/faturamento/notas-fiscais/consultar-asaas/' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(function(r) {
        const ct = r.headers.get('Content-Type') || '';
        if (ct.includes('application/json')) return r.json();
        if (r.status === 401) return { success: false, _sessionExpired: true, message: 'Sessão expirada.' };
        return r.text().then(function() { throw new Error('Resposta inesperada (HTTP ' + r.status + ')'); });
    })
    .then(function(data) {
        if (data._sessionExpired) {
            if (confirm('Sessão expirada. Ir para login?')) window.location.href = '/login';
            return;
        }
        if (data.success) {
            const statusLabel = data.asaas_status_label || data.asaas_status || '—';
            const isError     = data.asaas_status === 'ERROR';
            const isOk        = data.asaas_status === 'AUTHORIZED';
            showToast(
                (isError ? '<i class="fas fa-exclamation-triangle me-1"></i>' : '<i class="fas fa-check me-1"></i>')
                + ' Status: <strong>' + statusLabel + '</strong>'
                + (isError && data.error_desc ? '<br><small>' + data.error_desc + '</small>' : ''),
                isError ? 'danger' : (isOk ? 'success' : 'info')
            );
            setTimeout(function() { location.reload(); }, isError ? 6000 : 3000);
        } else {
            showToast('<i class="fas fa-exclamation-circle me-1"></i> ' + (data.message || 'Erro ao consultar'), 'warning');
        }
    })
    .catch(function(err) { alert('Erro de comunicação: ' + err.message); })
    .finally(function() { btn.disabled = false; btn.innerHTML = orig; });
});

// ── Reemitir NF no Asaas ─────────────────────────────────────────────────────
var reemitirIdAtual = null;
var modalReemitir = null;

document.addEventListener('DOMContentLoaded', function() {
    modalReemitir = new bootstrap.Modal(document.getElementById('modalReemitir'));
});

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-reemitir-asaas');
    if (!btn) return;
    reemitirIdAtual = btn.dataset.id;
    document.getElementById('reemitirErroMsg').style.display = 'none';
    document.getElementById('btnConfirmarReemitir').disabled = false;
    document.getElementById('btnConfirmarReemitir').innerHTML = '<i class="fas fa-redo-alt me-1"></i>Reemitir';
    if (modalReemitir) modalReemitir.show();
});

document.getElementById('btnConfirmarReemitir').addEventListener('click', function() {
    if (!reemitirIdAtual) return;
    const btn    = this;
    const erroEl = document.getElementById('reemitirErroMsg');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Reemitindo...';
    erroEl.style.display = 'none';

    fetch('/faturamento/notas-fiscais/reemitir-asaas/' + reemitirIdAtual, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(function(r) {
        const ct = r.headers.get('Content-Type') || '';
        if (ct.includes('application/json')) return r.json();
        if (r.status === 401) return { success: false, _sessionExpired: true };
        throw new Error('HTTP ' + r.status);
    })
    .then(function(data) {
        if (data._sessionExpired) {
            if (confirm('Sessão expirada. Ir para login?')) window.location.href = '/login';
            return;
        }
        if (data.success) {
            if (modalReemitir) modalReemitir.hide();
            showToast('<i class="fas fa-redo-alt me-1"></i> NF reemitida! Status: <strong>' + (data.asaas_status_label || data.asaas_status || '—') + '</strong>', 'success');
            setTimeout(function() { location.reload(); }, 2500);
        } else {
            erroEl.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>' + (data.message || 'Erro ao reemitir. Tente novamente.');
            erroEl.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-redo-alt me-1"></i>Reemitir';
        }
    })
    .catch(function(err) {
        erroEl.textContent = 'Erro de comunicação: ' + err.message;
        erroEl.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-redo-alt me-1"></i>Reemitir';
    });
});
</script>
