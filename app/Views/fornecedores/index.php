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

// ─── Helpers de formatação ────────────────────────────────────────────────────
function fmtDocumentoForn(?string $doc): string {
    if ($doc === null || $doc === '') return '<span class="text-muted">-</span>';
    $d = preg_replace('/\D/', '', $doc);
    if (strlen($d) === 14) {
        $fmt = preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $d);
        return '<code class="text-dark">' . $fmt . '</code>';
    }
    if (strlen($d) === 11) {
        $fmt = preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $d);
        return '<code class="text-dark">' . $fmt . '</code>';
    }
    return '<span>' . htmlspecialchars($doc) . '</span>';
}

function fmtTelefoneForn(?string $tel): string {
    if ($tel === null || $tel === '') return '<span class="text-muted">-</span>';
    $t = preg_replace('/\D/', '', $tel);
    if (strlen($t) === 13) { // +55 (XX) XXXXX-XXXX
        $fmt = preg_replace('/^55(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $t);
        return htmlspecialchars($fmt);
    }
    if (strlen($t) === 11) { // (XX) XXXXX-XXXX
        $fmt = preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $t);
        return htmlspecialchars($fmt);
    }
    if (strlen($t) === 10) { // (XX) XXXX-XXXX
        $fmt = preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $t);
        return htmlspecialchars($fmt);
    }
    return htmlspecialchars($tel);
}
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

            $email = ($f->email ?? '') !== ''
                ? '<a href="mailto:' . htmlspecialchars($f->email) . '" class="text-decoration-none">' . htmlspecialchars($f->email) . '</a>'
                : '<span class="text-muted">-</span>';

            return '<tr>'
                . '<td><strong>' . htmlspecialchars($f->nome ?? '') . '</strong></td>'
                . '<td>' . fmtDocumentoForn($f->documento ?? null) . '</td>'
                . '<td>' . $email . '</td>'
                . '<td>' . fmtTelefoneForn($f->telefone ?? null) . '</td>'
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
