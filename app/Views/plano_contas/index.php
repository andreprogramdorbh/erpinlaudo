<?php

use App\Core\UI;
use App\Core\Auth;

$actions = [];
if (Auth::can('create_plano_contas')) {
    $actions[] = [
        'text' => 'Nova Conta',
        'link' => '/financeiro/plano-contas/create',
        'icon' => 'fas fa-plus',
        'class' => 'btn-primary'
    ];
}

UI::sectionHeader('Plano de Contas', 'Cadastre e organize suas contas de Receita e Despesa', $actions);
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/financeiro/plano-contas" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Código ou Nome..."
                        value="<?php echo htmlspecialchars($filtros['pesquisa'] ?? ''); ?>">
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="" <?php echo ($filtros['tipo'] ?? '') === '' ? 'selected' : ''; ?>>Todos</option>
                    <option value="Receita" <?php echo ($filtros['tipo'] ?? '') === 'Receita' ? 'selected' : ''; ?>>Receita</option>
                    <option value="Despesa" <?php echo ($filtros['tipo'] ?? '') === 'Despesa' ? 'selected' : ''; ?>>Despesa</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="" <?php echo ($filtros['status'] ?? '') === '' ? 'selected' : ''; ?>>Todos</option>
                    <option value="ativo" <?php echo ($filtros['status'] ?? 'ativo') === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo ($filtros['status'] ?? '') === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
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
        $headers = ['Código', 'Nome', 'Tipo', 'Nível', 'Status', 'Ações'];

        $rowRenderer = function ($conta) {
            $acoes = '';

            if (Auth::can('edit_plano_contas')) {
                $acoes .= '<a href="/financeiro/plano-contas/edit/' . (int)$conta->id . '" class="text-primary me-2" title="Editar"><i class="fas fa-edit"></i></a>';
            }

            if (Auth::can('delete_plano_contas')) {
                $acoes .= '<a href="#" class="text-danger" title="Excluir" onclick="confirmDelete(' . (int)$conta->id . '); return false;"><i class="fas fa-trash"></i></a>';
            }

            $statusBadge = ($conta->status ?? 'ativo') === 'ativo'
                ? '<span class="badge bg-success">Ativo</span>'
                : '<span class="badge bg-secondary">Inativo</span>';

            $codigo = htmlspecialchars($conta->codigo ?? '');
            $nome = htmlspecialchars($conta->nome ?? '');
            $tipo = htmlspecialchars($conta->tipo ?? '');
            $nivel = (int)($conta->nivel ?? 1);

            return '<tr>'
                . '<td><strong>' . $codigo . '</strong></td>'
                . '<td>' . $nome . '</td>'
                . '<td>' . $tipo . '</td>'
                . '<td>' . $nivel . '</td>'
                . '<td>' . $statusBadge . '</td>'
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
    if (confirm('Deseja realmente excluir esta conta?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/financeiro/plano-contas/delete/' + id;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
