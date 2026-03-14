<?php

use App\Core\View;

$medico = $medico ?? null;
$especialidades = $especialidades ?? [];
$isEdit = ($formMode ?? 'create') === 'edit';
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

$errorMessages = [
    'missing_fields' => 'Preencha todos os campos obrigatorios do medico.',
    'invalid_uf' => 'Informe uma UF valida para o CRM.',
    'invalid_email' => 'Informe um e-mail valido.',
    'invalid_especialidade' => 'Selecione uma especialidade valida.',
    'invalid_upload' => 'Nao foi possivel processar o upload da assinatura.',
    'invalid_file_type' => 'A assinatura deve ser PNG, JPG ou PDF.',
    'file_too_large' => 'A assinatura digital deve ter no maximo 5 MB.',
    'db_failure' => 'Nao foi possivel salvar o cadastro no momento.',
    'fatal' => 'Ocorreu um erro inesperado ao salvar o medico.',
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 mb-1 fw-bold text-dark"><?php echo $pageTitle; ?></h2>
        <p class="text-muted small mb-0">Cadastre dados basicos e profissionais do medico.</p>
    </div>
    <a href="/medicos" class="btn btn-light border">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>
</div>

<?php if ($success === 'created'): ?>
    <div class="alert alert-success border-0 shadow-sm">Medico cadastrado com sucesso.</div>
<?php elseif ($success === 'updated'): ?>
    <div class="alert alert-success border-0 shadow-sm">Medico atualizado com sucesso.</div>
<?php endif; ?>

<?php if (isset($errorMessages[$error])): ?>
    <div class="alert alert-danger border-0 shadow-sm"><?php echo $errorMessages[$error]; ?></div>
<?php endif; ?>

<form method="POST" action="<?php echo htmlspecialchars($formAction); ?>" enctype="multipart/form-data">
    <?php echo View::csrfField(); ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pb-0">
            <h3 class="h5 mb-1">Dados basicos</h3>
            <p class="text-muted small mb-0">Informacoes principais para o cadastro do medico.</p>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nome" class="form-label fw-semibold">Nome</label>
                    <input type="text" class="form-control" id="nome" name="nome" required
                        value="<?php echo htmlspecialchars($medico->nome ?? ''); ?>">
                </div>

                <div class="col-md-3">
                    <label for="crm" class="form-label fw-semibold">CRM</label>
                    <input type="text" class="form-control" id="crm" name="crm" required
                        value="<?php echo htmlspecialchars($medico->crm ?? ''); ?>">
                </div>

                <div class="col-md-3">
                    <label for="uf_crm" class="form-label fw-semibold">UF CRM</label>
                    <input type="text" class="form-control text-uppercase" id="uf_crm" name="uf_crm" maxlength="2" required
                        value="<?php echo htmlspecialchars($medico->uf_crm ?? ''); ?>">
                </div>

                <div class="col-md-4">
                    <label for="cpf" class="form-label fw-semibold">CPF</label>
                    <input type="text" class="form-control" id="cpf" name="cpf" required
                        value="<?php echo htmlspecialchars($medico->cpf ?? ''); ?>">
                </div>

                <div class="col-md-4">
                    <label for="email" class="form-label fw-semibold">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required
                        value="<?php echo htmlspecialchars($medico->email ?? ''); ?>">
                </div>

                <div class="col-md-4">
                    <label for="telefone" class="form-label fw-semibold">Telefone</label>
                    <input type="text" class="form-control" id="telefone" name="telefone" required
                        value="<?php echo htmlspecialchars($medico->telefone ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pb-0">
            <h3 class="h5 mb-1">Dados profissionais</h3>
            <p class="text-muted small mb-0">Especialidade, RQE e assinatura digital.</p>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="especialidade_id" class="form-label fw-semibold">Especialidade</label>
                    <select class="form-select" id="especialidade_id" name="especialidade_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($especialidades as $especialidade): ?>
                            <option
                                value="<?php echo (int) $especialidade->id; ?>"
                                <?php echo ((int) ($medico->especialidade_id ?? 0) === (int) $especialidade->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($especialidade->especialidade ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="subespecialidade" class="form-label fw-semibold">Subespecialidade</label>
                    <input type="text" class="form-control" id="subespecialidade" name="subespecialidade"
                        value="<?php echo htmlspecialchars($medico->subespecialidade ?? ''); ?>">
                </div>

                <div class="col-md-4">
                    <label for="rqe" class="form-label fw-semibold">RQE</label>
                    <input type="text" class="form-control" id="rqe" name="rqe"
                        value="<?php echo htmlspecialchars($medico->rqe ?? ''); ?>">
                </div>

                <div class="col-md-4">
                    <label for="assinatura_digital" class="form-label fw-semibold">Assinatura digital</label>
                    <input
                        type="file"
                        class="form-control"
                        id="assinatura_digital"
                        name="assinatura_digital"
                        accept=".png,.jpg,.jpeg,.pdf,image/png,image/jpeg,application/pdf">
                    <div class="form-text">Formatos aceitos: PNG, JPG ou PDF.</div>
                </div>

                <div class="col-md-4">
                    <label for="status" class="form-label fw-semibold">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="ativo" <?php echo ($medico->status ?? 'ativo') === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="inativo" <?php echo ($medico->status ?? '') === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                </div>
            </div>

            <?php if (!empty($medico->assinatura_digital)): ?>
                <div class="alert alert-light border mt-3 mb-0">
                    Arquivo atual:
                    <strong><?php echo htmlspecialchars(basename((string) $medico->assinatura_digital)); ?></strong>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="/medicos" class="btn btn-light border">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>
            <?php echo $isEdit ? 'Salvar alteracoes' : 'Cadastrar medico'; ?>
        </button>
    </div>
</form>
