<?php

$action = $isEdit ? '/fornecedores/update/' . ($fornecedor->id ?? '') : '/fornecedores';
?>

<form id="fornecedorFormGeral" action="<?php echo $action; ?>" method="POST" class="enterprise-form-main">

    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-id-card section-icon"></i>
            Identificação
        </h2>

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label for="nome" class="form-label required">Nome</label>
                <input type="text" name="nome" id="nome" class="form-control" placeholder="Nome do fornecedor" value="<?php echo htmlspecialchars($fornecedor->nome ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="documento" class="form-label">Documento</label>
                <input type="text" name="documento" id="documento" class="form-control" placeholder="CPF/CNPJ (opcional)" value="<?php echo htmlspecialchars($fornecedor->documento ?? ''); ?>">
            </div>
        </div>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="financeiro@fornecedor.com" value="<?php echo htmlspecialchars($fornecedor->email ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="telefone" class="form-label">Telefone</label>
                <input type="text" name="telefone" id="telefone" class="form-control" placeholder="(00) 0000-0000" value="<?php echo htmlspecialchars($fornecedor->telefone ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="ativo" <?php echo ($fornecedor->status ?? 'ativo') === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo ($fornecedor->status ?? '') === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
        </div>
    </section>

</form>
