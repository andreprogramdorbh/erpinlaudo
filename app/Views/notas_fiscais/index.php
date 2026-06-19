<?php

use App\Core\UI;
use App\Core\Auth;
use App\Services\AsaasService;

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
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/faturamento/notas-fiscais" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0"
                           placeholder="Número, série ou cliente..."
                           value="<?php echo htmlspecialchars($filtros['pesquisa'] ?? ''); ?>">
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="" <?php echo ($filtros['status'] ?? '') === '' ? 'selected' : ''; ?>>Todos</option>
                    <option value="rascunho"    <?php echo ($filtros['status'] ?? '') === 'rascunho'    ? 'selected' : ''; ?>>Rascunho</option>
                    <option value="agendada"    <?php echo ($filtros['status'] ?? '') === 'agendada'    ? 'selected' : ''; ?>>Agendada (Asaas)</option>
                    <option value="emitida"     <?php echo ($filtros['status'] ?? '') === 'emitida'     ? 'selected' : ''; ?>>Emitida</option>
                    <option value="importada"   <?php echo ($filtros['status'] ?? '') === 'importada'   ? 'selected' : ''; ?>>Importada</option>
                    <option value="erro_emissao"<?php echo ($filtros['status'] ?? '') === 'erro_emissao'? 'selected' : ''; ?>>Erro de Emissão</option>
                    <option value="cancelada"   <?php echo ($filtros['status'] ?? '') === 'cancelada'   ? 'selected' : ''; ?>>Cancelada</option>
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
        $headers = ['Emissão', 'NF / Série', 'Cliente', 'Valor', 'Origem', 'Status', 'Ações'];

        $rowRenderer = function ($nf) {
            $status        = $nf->status        ?? 'rascunho';
            $origemEmissao = $nf->origem_emissao ?? 'manual';
            $asaasStatus   = $nf->asaas_status   ?? null;
            $asaasInvoiceId= $nf->asaas_invoice_id ?? null;
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

            return '<tr>'
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

        const toast       = document.getElementById('toastAsaas');
        const toastHeader = document.getElementById('toastAsaasHeader');
        const toastBody   = document.getElementById('toastAsaasBody');

        if (data.success) {
            const statusLabel = data.asaas_status_label || data.asaas_status || '—';
            const isError     = data.asaas_status === 'ERROR';
            const isOk        = data.asaas_status === 'AUTHORIZED';

            toastHeader.className = 'toast-header ' + (isError ? 'bg-danger text-white' : isOk ? 'bg-success text-white' : 'bg-primary text-white');
            toastBody.innerHTML = isError
                ? '<i class="fas fa-exclamation-triangle me-1"></i> <strong>' + statusLabel + '</strong>'
                  + (data.error_desc ? '<br><small class="text-muted">' + data.error_desc + '</small>' : '')
                : '<i class="fas fa-check me-1"></i> Status: <strong>' + statusLabel + '</strong>';

            toast.style.display = 'block';
            setTimeout(function() { toast.style.display = 'none'; location.reload(); }, isError ? 6000 : 3000);
        } else {
            toastHeader.className = 'toast-header bg-warning text-dark';
            toastBody.innerHTML   = '<i class="fas fa-exclamation-circle me-1"></i> ' + (data.message || 'Erro ao consultar');
            toast.style.display   = 'block';
            setTimeout(function() { toast.style.display = 'none'; }, 5000);
        }
    })
    .catch(function(err) {
        alert('Erro de comunicação: ' + err.message);
    })
    .finally(function() {
        btn.disabled  = false;
        btn.innerHTML = orig;
    });
});
</script>
