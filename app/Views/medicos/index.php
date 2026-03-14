<?php

use App\Core\Auth;
use App\Core\UI;

$actions = [];
if (Auth::check()) {
    $actions[] = [
        'text' => 'Novo Medico',
        'link' => '/medicos/create',
        'icon' => 'fas fa-user-md',
        'class' => 'btn-primary',
    ];
}

UI::sectionHeader('Medicos', 'Gerencie o corpo clinico cadastrado no ERP', $actions);
?>

<?php
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

if ($success === 'created') {
    echo '<div class="alert alert-success border-0 shadow-sm">Medico cadastrado com sucesso.</div>';
} elseif ($success === 'updated') {
    echo '<div class="alert alert-success border-0 shadow-sm">Medico atualizado com sucesso.</div>';
}

if ($error === 'not_found') {
    echo '<div class="alert alert-warning border-0 shadow-sm">Medico nao encontrado.</div>';
} elseif ($error === 'unauthorized') {
    echo '<div class="alert alert-danger border-0 shadow-sm">Voce nao tem acesso a este medico.</div>';
}
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/medicos" class="row g-3 align-items-end">
            <div class="col-md-7">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input
                        type="text"
                        name="q"
                        class="form-control border-start-0"
                        placeholder="Nome, CRM ou especialidade"
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
        $headers = ['Nome', 'CRM', 'Especialidade', 'Telefone', 'Status', 'Acoes'];

        $rowRenderer = function ($medico) {
            $status = ($medico->status ?? 'ativo') === 'ativo'
                ? '<span class="badge bg-success">Ativo</span>'
                : '<span class="badge bg-secondary">Inativo</span>';

            $acoes = '<a href="/medicos/edit/' . (int) $medico->id . '" class="text-primary" title="Editar"><i class="fas fa-edit"></i></a>';

            return '<tr>'
                . '<td><strong>' . htmlspecialchars($medico->nome ?? '') . '</strong></td>'
                . '<td>' . htmlspecialchars(($medico->crm ?? '') . '/' . ($medico->uf_crm ?? '')) . '</td>'
                . '<td>' . htmlspecialchars($medico->especialidade_nome ?? '-') . '</td>'
                . '<td>' . htmlspecialchars($medico->telefone ?? '-') . '</td>'
                . '<td>' . $status . '</td>'
                . '<td>' . $acoes . '</td>'
                . '</tr>';
        };

        UI::render('table', [
            'headers' => $headers,
            'items' => $medicos ?? [],
            'rowRenderer' => $rowRenderer,
            'emptyMessage' => 'Nenhum medico encontrado com os filtros aplicados.',
            'emptyIcon' => 'fas fa-user-md',
        ]);
        ?>
    </div>
</div>
