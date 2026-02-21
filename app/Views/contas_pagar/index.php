<?php

use App\Core\UI;
use App\Core\Auth;

$actions = [];
if (Auth::can('create_contas_pagar')) {
    $actions[] = [
        'text' => 'Nova Conta',
        'link' => '/financeiro/contas-a-pagar/create',
        'icon' => 'fas fa-plus',
        'class' => 'btn-primary'
    ];
}

UI::sectionHeader('Contas a Pagar', 'Gerencie suas contas e vencimentos', $actions);
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/financeiro/contas-a-pagar" class="row g-3 align-items-end">
            <div class="col-md-7">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Descrição ou Fornecedor..."
                        value="<?php echo htmlspecialchars($filtros['pesquisa'] ?? ''); ?>">
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="" <?php echo ($filtros['status'] ?? '') === '' ? 'selected' : ''; ?>>Todos</option>
                    <option value="aberta" <?php echo ($filtros['status'] ?? 'aberta') === 'aberta' ? 'selected' : ''; ?>>Aberta</option>
                    <option value="paga" <?php echo ($filtros['status'] ?? '') === 'paga' ? 'selected' : ''; ?>>Paga</option>
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
        $headers = ['Vencimento', 'Descrição', 'Fornecedor', 'Plano', 'Valor', 'Status', 'Ações'];

        $rowRenderer = function ($c) {
            $acoes = '';

            if (Auth::can('edit_contas_pagar')) {
                $acoes .= '<a href="/financeiro/contas-a-pagar/edit/' . (int)$c->id . '" class="text-primary me-2" title="Editar"><i class="fas fa-edit"></i></a>';
            }

            if (Auth::can('delete_contas_pagar')) {
                $acoes .= '<a href="#" class="text-danger" title="Cancelar" onclick="confirmDelete(' . (int)$c->id . '); return false;"><i class="fas fa-ban"></i></a>';
            }

            $status = $c->status ?? 'aberta';
            if ($status === 'paga') {
                $badge = '<span class="badge bg-success">Paga</span>';
            } elseif ($status === 'cancelada') {
                $badge = '<span class="badge bg-secondary">Cancelada</span>';
            } else {
                $badge = '<span class="badge bg-warning text-dark">Aberta</span>';
            }

            $venc = htmlspecialchars($c->data_vencimento ?? '');
            $desc = htmlspecialchars($c->descricao ?? '');
            $forn = htmlspecialchars($c->fornecedor_nome ?? '');
            $plano = htmlspecialchars($c->plano_codigo ?? '');
            $valor = number_format((float)($c->valor ?? 0), 2, ',', '.');

            return '<tr>'
                . '<td>' . $venc . '</td>'
                . '<td><strong>' . $desc . '</strong></td>'
                . '<td>' . $forn . '</td>'
                . '<td>' . $plano . '</td>'
                . '<td>R$ ' . $valor . '</td>'
                . '<td>' . $badge . '</td>'
                . '<td>' . $acoes . '</td>'
                . '</tr>';
        };

        UI::render('table', [
            'headers' => $headers,
            'items' => $contas ?? [],
            'rowRenderer' => $rowRenderer,
            'emptyMessage' => 'Nenhuma conta encontrada com os filtros aplicados.',
        ]);
        ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Deseja realmente cancelar esta conta?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/financeiro/contas-a-pagar/delete/' + id;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
