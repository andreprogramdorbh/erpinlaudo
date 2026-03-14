<?php

use App\Core\UI;
use App\Core\View;

UI::sectionHeader('Tabela de Exames', 'Cadastre exames padrao para uso futuro no corpo clinico');

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

if ($success === 'created') {
    echo '<div class="alert alert-success border-0 shadow-sm">Exame cadastrado com sucesso.</div>';
}

if ($error === 'missing_fields') {
    echo '<div class="alert alert-danger border-0 shadow-sm">Preencha nome do exame, modalidade e valor padrao.</div>';
} elseif ($error === 'invalid_modalidade') {
    echo '<div class="alert alert-danger border-0 shadow-sm">Selecione uma modalidade valida.</div>';
} elseif ($error === 'invalid_valor') {
    echo '<div class="alert alert-danger border-0 shadow-sm">Informe um valor padrao valido.</div>';
} elseif ($error === 'db_failure') {
    echo '<div class="alert alert-danger border-0 shadow-sm">Nao foi possivel salvar o exame.</div>';
}
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0">
        <h3 class="h5 mb-1">Novo exame</h3>
        <p class="text-muted small mb-0">Cadastre os exames e seus valores padrao.</p>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="/exames-tabela/store" class="row g-3 align-items-end">
            <?php echo View::csrfField(); ?>

            <div class="col-md-5">
                <label for="nome_exame" class="form-label fw-semibold">Nome do exame</label>
                <input type="text" class="form-control" id="nome_exame" name="nome_exame" required>
            </div>

            <div class="col-md-3">
                <label for="modalidade" class="form-label fw-semibold">Modalidade</label>
                <select class="form-select" id="modalidade" name="modalidade" required>
                    <option value="">Selecione</option>
                    <option value="TC">TC</option>
                    <option value="RM">RM</option>
                    <option value="RX">RX</option>
                    <option value="US">US</option>
                </select>
            </div>

            <div class="col-md-2">
                <label for="valor_padrao" class="form-label fw-semibold">Valor padrao</label>
                <input type="text" class="form-control" id="valor_padrao" name="valor_padrao" placeholder="0,00" required>
            </div>

            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary fw-bold">
                    <i class="fas fa-save me-1"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="/exames-tabela" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label small fw-bold text-muted">Pesquisar</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input
                        type="text"
                        name="q"
                        class="form-control border-start-0"
                        placeholder="Nome do exame"
                        value="<?php echo htmlspecialchars($filtros['pesquisa'] ?? ''); ?>">
                </div>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Modalidade</label>
                <select name="modalidade" class="form-select">
                    <option value="" <?php echo ($filtros['modalidade'] ?? '') === '' ? 'selected' : ''; ?>>Todas</option>
                    <option value="TC" <?php echo ($filtros['modalidade'] ?? '') === 'TC' ? 'selected' : ''; ?>>TC</option>
                    <option value="RM" <?php echo ($filtros['modalidade'] ?? '') === 'RM' ? 'selected' : ''; ?>>RM</option>
                    <option value="RX" <?php echo ($filtros['modalidade'] ?? '') === 'RX' ? 'selected' : ''; ?>>RX</option>
                    <option value="US" <?php echo ($filtros['modalidade'] ?? '') === 'US' ? 'selected' : ''; ?>>US</option>
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
        $headers = ['Nome do exame', 'Modalidade', 'Valor padrao'];

        $rowRenderer = function ($exame) {
            $valor = number_format((float) ($exame->valor_padrao ?? 0), 2, ',', '.');

            return '<tr>'
                . '<td><strong>' . htmlspecialchars($exame->nome_exame ?? '') . '</strong></td>'
                . '<td>' . htmlspecialchars($exame->modalidade ?? '') . '</td>'
                . '<td>R$ ' . $valor . '</td>'
                . '</tr>';
        };

        UI::render('table', [
            'headers' => $headers,
            'items' => $exames ?? [],
            'rowRenderer' => $rowRenderer,
            'emptyMessage' => 'Nenhum exame cadastrado na tabela.',
            'emptyIcon' => 'fas fa-vial',
        ]);
        ?>
    </div>
</div>
