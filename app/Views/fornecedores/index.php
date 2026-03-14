<?php

use App\Core\UI;
use App\Core\Auth;

$actions = [];
if (Auth::can('create_fornecedores')) {
    $actions[] = [
        'text' => 'Novo Fornecedor',
        'link' => '/fornecedores/create',
        'icon' => 'fas fa-plus',
        'class' => 'btn-primary'
    ];
}

UI::sectionHeader('Fornecedores', 'Cadastre e gerencie seus fornecedores', $actions);
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/fornecedores" class="row g-3 align-items-end">
            <div class="col-md-7">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Nome, Documento ou Email..."
                        value="<?php echo htmlspecialchars($filtros['pesquisa'] ?? ''); ?>">
                </div>
            </div>

            <div class="col-md-3">
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
        $headers = ['Nome', 'Documento', 'Email', 'Telefone', 'Status', 'Ações'];

        $rowRenderer = function ($f) {
            $acoes = '';

            if (Auth::can('edit_fornecedores')) {
                $acoes .= '<a href="/fornecedores/edit/' . (int)$f->id . '" class="text-primary me-2" title="Editar"><i class="fas fa-edit"></i></a>';
            }

            if (Auth::can('delete_fornecedores')) {
                $acoes .= '<a href="#" class="text-danger" title="Excluir" onclick="confirmDelete(' . (int)$f->id . '); return false;"><i class="fas fa-trash"></i></a>';
            }

            $statusBadge = ($f->status ?? 'ativo') === 'ativo'
                ? '<span class="badge bg-success">Ativo</span>'
                : '<span class="badge bg-secondary">Inativo</span>';

            return '<tr>'
                . '<td><strong>' . htmlspecialchars($f->nome ?? '') . '</strong></td>'
                . '<td>' . htmlspecialchars($f->documento ?? '') . '</td>'
                . '<td>' . htmlspecialchars($f->email ?? '') . '</td>'
                . '<td>' . htmlspecialchars($f->telefone ?? '') . '</td>'
                . '<td>' . $statusBadge . '</td>'
                . '<td>' . $acoes . '</td>'
                . '</tr>';
        };

        UI::render('table', [
            'headers' => $headers,
            'items' => $fornecedores ?? [],
            'rowRenderer' => $rowRenderer,
            'emptyMessage' => 'Nenhum fornecedor encontrado com os filtros aplicados.',
        ]);
        ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Deseja realmente excluir este fornecedor?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/fornecedores/delete/' + id;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
