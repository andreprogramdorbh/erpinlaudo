<?php
$editando = !empty($conta);
$action   = $editando ? "/financeiro/contas/{$conta->id}/update" : '/financeiro/contas';
$error    = $_GET['error'] ?? '';

$tipoOptions = [
    'corrente'     => 'Conta Corrente',
    'poupanca'     => 'Poupança',
    'investimento' => 'Investimento',
    'caixa'        => 'Caixa',
    'outro'        => 'Outro',
];

$cores = [
    '#4361ee', '#3a0ca3', '#7209b7', '#f72585',
    '#4cc9f0', '#2ecc71', '#e74c3c', '#f39c12',
    '#1abc9c', '#34495e', '#e67e22', '#9b59b6',
];
?>
<style>
.form-section {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e8ecf0;
    padding: 28px;
    margin-bottom: 20px;
}
.form-section h6 {
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f4ff;
}
.color-picker-grid {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.color-option {
    width: 32px; height: 32px;
    border-radius: 50%;
    cursor: pointer;
    border: 3px solid transparent;
    transition: all .2s;
}
.color-option.selected,
.color-option:hover {
    border-color: #2d3748;
    transform: scale(1.15);
}
.preview-card {
    background: linear-gradient(135deg, var(--cor, #4361ee) 0%, #3a0ca3 100%);
    color: #fff;
    border-radius: 14px;
    padding: 24px;
    margin-top: 16px;
}
</style>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold"><?= $editando ? 'Editar Conta' : 'Nova Conta' ?></h4>
        <p class="text-muted mb-0"><?= $editando ? 'Atualize os dados da conta bancária' : 'Cadastre uma nova conta bancária da empresa' ?></p>
    </div>
    <a href="/financeiro/contas" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Voltar</a>
</div>

<?php if ($error === 'nome_obrigatorio'): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>O nome da conta é obrigatório.</div>
<?php elseif ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Ocorreu um erro. Tente novamente.</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>">
<div class="row g-4">
    <div class="col-lg-8">

        <!-- Identificação -->
        <div class="form-section">
            <h6><i class="fas fa-id-card me-2 text-primary"></i>Identificação</h6>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Nome / Apelido da Conta <span class="text-danger">*</span></label>
                    <input type="text" name="nome" class="form-control" placeholder="Ex: Conta Principal Itaú" required
                           value="<?= htmlspecialchars($conta->nome ?? '') ?>">
                    <div class="form-text">Nome de identificação interna da conta.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Banco</label>
                    <select name="banco_codigo" id="selectBanco" class="form-select" onchange="preencherBanco(this)">
                        <option value="">Selecione o banco...</option>
                        <?php foreach ($bancos as $b): ?>
                            <option value="<?= $b['codigo'] ?>"
                                    data-nome="<?= htmlspecialchars($b['nome']) ?>"
                                    <?= ($conta->banco_codigo ?? '') === $b['codigo'] ? 'selected' : '' ?>>
                                <?= $b['codigo'] ?> — <?= htmlspecialchars($b['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nome do Banco</label>
                    <input type="text" name="banco_nome" id="inputBancoNome" class="form-control" placeholder="Ex: Itaú Unibanco"
                           value="<?= htmlspecialchars($conta->banco_nome ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tipo de Conta</label>
                    <select name="tipo" class="form-select">
                        <?php foreach ($tipoOptions as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($conta->tipo ?? 'corrente') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Agência</label>
                    <div class="input-group">
                        <input type="text" name="agencia" class="form-control" placeholder="0000" value="<?= htmlspecialchars($conta->agencia ?? '') ?>">
                        <span class="input-group-text">-</span>
                        <input type="text" name="agencia_digito" class="form-control" style="max-width:60px;" placeholder="X" value="<?= htmlspecialchars($conta->agencia_digito ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Conta</label>
                    <div class="input-group">
                        <input type="text" name="conta" class="form-control" placeholder="00000-0" value="<?= htmlspecialchars($conta->conta ?? '') ?>">
                        <span class="input-group-text">-</span>
                        <input type="text" name="conta_digito" class="form-control" style="max-width:60px;" placeholder="X" value="<?= htmlspecialchars($conta->conta_digito ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Titular -->
        <div class="form-section">
            <h6><i class="fas fa-user me-2 text-primary"></i>Titular</h6>
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label fw-semibold">Nome do Titular</label>
                    <input type="text" name="titular" class="form-control" placeholder="Razão social ou nome completo"
                           value="<?= htmlspecialchars($conta->titular ?? '') ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">CPF / CNPJ</label>
                    <input type="text" name="cpf_cnpj" class="form-control" placeholder="00.000.000/0000-00"
                           value="<?= htmlspecialchars($conta->cpf_cnpj ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Saldo e Configurações -->
        <div class="form-section">
            <h6><i class="fas fa-dollar-sign me-2 text-primary"></i>Saldo e Configurações</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Saldo Inicial</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" name="saldo_inicial" class="form-control" placeholder="0,00"
                               value="<?= number_format((float)($conta->saldo_inicial ?? 0), 2, ',', '.') ?>">
                    </div>
                    <div class="form-text">Saldo no momento do cadastro.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Moeda</label>
                    <select name="moeda" class="form-select">
                        <option value="BRL" <?= ($conta->moeda ?? 'BRL') === 'BRL' ? 'selected' : '' ?>>BRL — Real Brasileiro</option>
                        <option value="USD" <?= ($conta->moeda ?? '') === 'USD' ? 'selected' : '' ?>>USD — Dólar Americano</option>
                        <option value="EUR" <?= ($conta->moeda ?? '') === 'EUR' ? 'selected' : '' ?>>EUR — Euro</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Situação</label>
                    <select name="ativa" class="form-select">
                        <option value="1" <?= ($conta->ativa ?? 1) ? 'selected' : '' ?>>Ativa</option>
                        <option value="0" <?= isset($conta->ativa) && !(int)$conta->ativa ? 'selected' : '' ?>>Inativa</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Observações</label>
                    <textarea name="observacoes" class="form-control" rows="2" placeholder="Observações internas..."><?= htmlspecialchars($conta->observacoes ?? '') ?></textarea>
                </div>
            </div>
        </div>

    </div>

    <div class="col-lg-4">
        <!-- Cor e Ícone -->
        <div class="form-section">
            <h6><i class="fas fa-palette me-2 text-primary"></i>Aparência</h6>
            <label class="form-label fw-semibold">Cor de Identificação</label>
            <div class="color-picker-grid mb-3" id="colorGrid">
                <?php foreach ($cores as $cor): ?>
                    <div class="color-option <?= ($conta->cor ?? '#4361ee') === $cor ? 'selected' : '' ?>"
                         style="background:<?= $cor ?>;"
                         onclick="selecionarCor('<?= $cor ?>', this)"></div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="cor" id="inputCor" value="<?= htmlspecialchars($conta->cor ?? '#4361ee') ?>">

            <!-- Preview do card -->
            <div class="preview-card" id="previewCard" style="--cor: <?= htmlspecialchars($conta->cor ?? '#4361ee') ?>;">
                <div style="font-size:12px;opacity:.8;margin-bottom:4px;">Preview</div>
                <div style="font-size:18px;font-weight:700;" id="previewNome"><?= htmlspecialchars($conta->nome ?? 'Nome da Conta') ?></div>
                <div style="font-size:13px;opacity:.8;" id="previewBanco"><?= htmlspecialchars($conta->banco_nome ?? 'Banco') ?></div>
                <div style="font-size:22px;font-weight:800;margin-top:12px;">
                    R$ <?= number_format((float)($conta->saldo_atual ?? $conta->saldo_inicial ?? 0), 2, ',', '.') ?>
                </div>
            </div>
        </div>

        <!-- Ações -->
        <div class="form-section">
            <button type="submit" class="btn btn-primary w-100 mb-2">
                <i class="fas fa-save me-2"></i><?= $editando ? 'Salvar Alterações' : 'Cadastrar Conta' ?>
            </button>
            <a href="/financeiro/contas" class="btn btn-outline-secondary w-100">Cancelar</a>
        </div>
    </div>
</div>
</form>

<script>
function preencherBanco(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('inputBancoNome').value = opt.dataset.nome || '';
    document.getElementById('previewBanco').textContent = opt.dataset.nome || 'Banco';
}

function selecionarCor(cor, el) {
    document.querySelectorAll('.color-option').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('inputCor').value = cor;
    document.getElementById('previewCard').style.setProperty('--cor', cor);
    document.getElementById('previewCard').style.background = `linear-gradient(135deg, ${cor} 0%, #3a0ca3 100%)`;
}

// Atualiza preview ao digitar nome
document.querySelector('[name="nome"]').addEventListener('input', function() {
    document.getElementById('previewNome').textContent = this.value || 'Nome da Conta';
});
document.querySelector('[name="banco_nome"]').addEventListener('input', function() {
    document.getElementById('previewBanco').textContent = this.value || 'Banco';
});
</script>
