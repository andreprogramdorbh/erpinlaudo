<?php

use App\Core\UI;
use App\Core\Auth;

$actions = [];
if (Auth::can('create_notas_fiscais')) {
    $actions[] = [
        'text' => 'Nova Nota',
        'link' => '/faturamento/notas-fiscais/create',
        'icon' => 'fas fa-plus',
        'class' => 'btn-primary'
    ];
}

if (Auth::can('import_notas_fiscais')) {
    $actions[] = [
        'text' => 'Importar XML',
        'link' => '/faturamento/notas-fiscais/importar',
        'icon' => 'fas fa-file-import',
        'class' => 'btn-outline-primary'
    ];
}

UI::sectionHeader('Notas Fiscais', 'Emita, acompanhe e importe suas NF-e', $actions);
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/faturamento/notas-fiscais" class="row g-3 align-items-end">
            <div class="col-md-7">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Número, série ou cliente..."
                        value="<?php echo htmlspecialchars($filtros['pesquisa'] ?? ''); ?>">
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="" <?php echo ($filtros['status'] ?? '') === '' ? 'selected' : ''; ?>>Todos</option>
                    <option value="rascunho" <?php echo ($filtros['status'] ?? '') === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                    <option value="emitida" <?php echo ($filtros['status'] ?? '') === 'emitida' ? 'selected' : ''; ?>>Emitida</option>
                    <option value="importada" <?php echo ($filtros['status'] ?? '') === 'importada' ? 'selected' : ''; ?>>Importada</option>
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
        $headers = ['Emissão', 'Número', 'Série', 'Cliente', 'Valor', 'Status', 'Ações'];

        $rowRenderer = function ($nf) {
            $acoes = '';

            if (Auth::can('edit_notas_fiscais')) {
                $acoes .= '<a href="/faturamento/notas-fiscais/edit/' . (int)$nf->id . '" class="text-primary me-2" title="Editar"><i class="fas fa-edit"></i></a>';
            }

            if (Auth::can('delete_notas_fiscais')) {
                $acoes .= '<a href="#" class="text-danger" title="Cancelar" onclick="confirmDelete(' . (int)$nf->id . '); return false;"><i class="fas fa-ban"></i></a>';
            }

            $status = $nf->status ?? 'rascunho';
            if ($status === 'emitida') {
                $badge = '<span class="badge bg-success">Emitida</span>';
            } elseif ($status === 'importada') {
                $badge = '<span class="badge bg-info text-dark">Importada</span>';
            } elseif ($status === 'cancelada') {
                $badge = '<span class="badge bg-secondary">Cancelada</span>';
            } else {
                $badge = '<span class="badge bg-warning text-dark">Rascunho</span>';
            }

            $emi = htmlspecialchars($nf->data_emissao ?? '');
            $num = htmlspecialchars($nf->numero_nf ?? '');
            $ser = htmlspecialchars($nf->serie ?? '');
            $cli = htmlspecialchars($nf->cliente_nome ?? '');
            $valor = number_format((float)($nf->valor_total ?? 0), 2, ',', '.');

            return '<tr>'
                . '<td>' . $emi . '</td>'
                . '<td><strong>' . $num . '</strong></td>'
                . '<td>' . $ser . '</td>'
                . '<td>' . $cli . '</td>'
                . '<td>R$ ' . $valor . '</td>'
                . '<td>' . $badge . '</td>'
                . '<td>' . $acoes . '</td>'
                . '</tr>';
        };

        UI::render('table', [
            'headers' => $headers,
            'items' => $notas ?? [],
            'rowRenderer' => $rowRenderer,
            'emptyMessage' => 'Nenhuma nota encontrada com os filtros aplicados.',
        ]);
        ?>
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
</script>
