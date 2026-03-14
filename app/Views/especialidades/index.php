<?php

use App\Core\UI;

UI::sectionHeader('Especialidades', 'Cadastre especialidades e subespecialidades do corpo clinico', [
    [
        'text' => 'Nova Especialidade',
        'link' => '/especialidades/create',
        'icon' => 'fas fa-plus',
        'class' => 'btn-primary',
    ],
]);

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

if ($success === 'created') {
    echo '<div class="alert alert-success border-0 shadow-sm">Especialidade cadastrada com sucesso.</div>';
}

if ($error === 'missing_fields') {
    echo '<div class="alert alert-danger border-0 shadow-sm">Informe ao menos o nome da especialidade.</div>';
} elseif ($error === 'db_failure') {
    echo '<div class="alert alert-danger border-0 shadow-sm">Nao foi possivel salvar a especialidade.</div>';
}
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/especialidades" class="row g-3 align-items-end">
            <div class="col-md-10">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input
                        type="text"
                        name="q"
                        class="form-control border-start-0"
                        placeholder="Especialidade, subespecialidade ou RQE"
                        value="<?php echo htmlspecialchars($filtros['pesquisa'] ?? ''); ?>">
                </div>
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
        $headers = ['Especialidade', 'Subespecialidade', 'RQE'];

        $rowRenderer = function ($especialidade) {
            return '<tr>'
                . '<td><strong>' . htmlspecialchars($especialidade->especialidade ?? '') . '</strong></td>'
                . '<td>' . htmlspecialchars($especialidade->subespecialidade ?? '-') . '</td>'
                . '<td>' . htmlspecialchars($especialidade->rqe ?? '-') . '</td>'
                . '</tr>';
        };

        UI::render('table', [
            'headers' => $headers,
            'items' => $especialidades ?? [],
            'rowRenderer' => $rowRenderer,
            'emptyMessage' => 'Nenhuma especialidade cadastrada.',
            'emptyIcon' => 'fas fa-stethoscope',
        ]);
        ?>
    </div>
</div>
