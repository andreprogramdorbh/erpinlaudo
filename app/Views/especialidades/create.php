<?php

use App\Core\View;

$error = $_GET['error'] ?? '';

$errorMessages = [
    'missing_fields' => 'Informe a especialidade para continuar.',
    'db_failure' => 'Nao foi possivel salvar a especialidade.',
    'fatal' => 'Ocorreu um erro inesperado ao salvar a especialidade.',
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 mb-1 fw-bold text-dark">Nova Especialidade</h2>
        <p class="text-muted small mb-0">Cadastre especialidades para uso no modulo de medicos.</p>
    </div>
    <a href="/especialidades" class="btn btn-light border">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>
</div>

<?php if (isset($errorMessages[$error])): ?>
    <div class="alert alert-danger border-0 shadow-sm"><?php echo $errorMessages[$error]; ?></div>
<?php endif; ?>

<form method="POST" action="/especialidades/store">
    <?php echo View::csrfField(); ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="especialidade" class="form-label fw-semibold">Especialidade</label>
                    <input type="text" class="form-control" id="especialidade" name="especialidade" required>
                </div>

                <div class="col-md-4">
                    <label for="subespecialidade" class="form-label fw-semibold">Subespecialidade</label>
                    <input type="text" class="form-control" id="subespecialidade" name="subespecialidade">
                </div>

                <div class="col-md-4">
                    <label for="rqe" class="form-label fw-semibold">RQE</label>
                    <input type="text" class="form-control" id="rqe" name="rqe">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="/especialidades" class="btn btn-light border">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> Cadastrar especialidade
        </button>
    </div>
</form>
