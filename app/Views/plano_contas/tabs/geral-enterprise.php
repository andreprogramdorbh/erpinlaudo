<?php

$action = $isEdit ? '/financeiro/plano-contas/update/' . ($conta->id ?? '') : '/financeiro/plano-contas';
$contasPai = $contasPai ?? [];
?>

<form id="planoContaFormGeral" action="<?php echo $action; ?>" method="POST" class="enterprise-form-main">

    <section class="form-section">
        <h2 class="form-section-title">
            <i class="fas fa-hashtag section-icon"></i>
            Identificação
        </h2>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="codigo" class="form-label required">Código</label>
                <input type="text" name="codigo" id="codigo" class="form-control" placeholder="Ex.: 1.01" value="<?php echo htmlspecialchars($conta->codigo ?? ''); ?>" required>
                <div class="form-help">Use um padrão consistente (ex.: 1, 1.01, 1.01.001)</div>
            </div>

            <div class="form-group">
                <label for="tipo" class="form-label required">Tipo</label>
                <select name="tipo" id="tipo" class="form-select" required>
                    <option value="" disabled <?php echo empty($conta->tipo) ? 'selected' : ''; ?>>Selecione...</option>
                    <option value="Receita" <?php echo ($conta->tipo ?? '') === 'Receita' ? 'selected' : ''; ?>>Receita</option>
                    <option value="Despesa" <?php echo ($conta->tipo ?? '') === 'Despesa' ? 'selected' : ''; ?>>Despesa</option>
                </select>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="ativo" <?php echo ($conta->status ?? 'ativo') === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo ($conta->status ?? '') === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
        </div>

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label for="nome" class="form-label required">Nome</label>
                <input type="text" name="nome" id="nome" class="form-control" placeholder="Ex.: Receita de Serviços" value="<?php echo htmlspecialchars($conta->nome ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="conta_pai_id" class="form-label">Conta Pai</label>
                <select name="conta_pai_id" id="conta_pai_id" class="form-select">
                    <option value="">Nenhuma (nível 1)</option>
                    <?php foreach ($contasPai as $pai): ?>
                        <option value="<?php echo (int)$pai->id; ?>" <?php echo ((int)($conta->conta_pai_id ?? 0) === (int)$pai->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(($pai->codigo ?? '') . ' - ' . ($pai->nome ?? '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-help">O nível será calculado automaticamente com base na conta pai.</div>
            </div>
        </div>
    </section>

</form>
