<?php
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
$bancoIcones = [
    '001' => 'https://logo.clearbit.com/bb.com.br',
    '033' => 'https://logo.clearbit.com/santander.com.br',
    '041' => 'https://logo.clearbit.com/banrisul.com.br',
    '077' => 'https://logo.clearbit.com/bancointer.com.br',
    '104' => 'https://logo.clearbit.com/caixa.gov.br',
    '237' => 'https://logo.clearbit.com/bradesco.com.br',
    '260' => 'https://logo.clearbit.com/nubank.com.br',
    '341' => 'https://logo.clearbit.com/itau.com.br',
    '748' => 'https://logo.clearbit.com/sicredi.com.br',
    '756' => 'https://logo.clearbit.com/sicoob.com.br',
];
$tipoLabels = [
    'corrente'     => 'Conta Corrente',
    'poupanca'     => 'Poupança',
    'investimento' => 'Investimento',
    'caixa'        => 'Caixa',
    'outro'        => 'Outro',
];
?>
<style>
.conta-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e8ecf0;
    padding: 24px;
    transition: all .2s;
    position: relative;
    overflow: hidden;
}
.conta-card:hover {
    box-shadow: 0 8px 32px rgba(0,0,0,.10);
    transform: translateY(-2px);
}
.conta-card .stripe {
    position: absolute;
    top: 0; left: 0;
    width: 5px;
    height: 100%;
    border-radius: 16px 0 0 16px;
}
.conta-card .banco-logo {
    width: 40px; height: 40px;
    border-radius: 10px;
    object-fit: contain;
    background: #f4f6fa;
    padding: 4px;
}
.conta-card .banco-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    background: #f4f6fa;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    color: #6c757d;
}
.conta-card .saldo-label { font-size: 12px; color: #8a94a6; margin-bottom: 2px; }
.conta-card .saldo-valor { font-size: 24px; font-weight: 700; }
.conta-card .saldo-positivo { color: #2ecc71; }
.conta-card .saldo-negativo { color: #e74c3c; }
.conta-card .conta-info { font-size: 13px; color: #6c757d; }
.conta-card .badge-tipo {
    font-size: 11px;
    padding: 3px 10px;
    border-radius: 20px;
    background: #f0f4ff;
    color: #4361ee;
    font-weight: 600;
}
.conta-card .btn-movimentacoes {
    background: #4361ee;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 18px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background .2s;
}
.conta-card .btn-movimentacoes:hover { background: #3451d1; color: #fff; }
.saldo-total-card {
    background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
    color: #fff;
    border-radius: 16px;
    padding: 28px 32px;
}
.conta-inativa { opacity: .55; }
</style>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Contas</h4>
        <p class="text-muted mb-0">Gerencie as contas bancárias da empresa</p>
    </div>
    <a href="/financeiro/contas/create" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Nova Conta
    </a>
</div>

<?php if ($success === 'conta_criada'): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>Conta criada com sucesso! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php elseif ($success === 'conta_atualizada'): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>Conta atualizada com sucesso! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php elseif ($success === 'conta_excluida'): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>Conta excluída com sucesso! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php elseif ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i>Ocorreu um erro. Tente novamente. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Saldo Total -->
<div class="saldo-total-card mb-4">
    <div class="row align-items-center">
        <div class="col">
            <div style="font-size:13px;opacity:.8;margin-bottom:4px;">Saldo Total em Contas</div>
            <div style="font-size:36px;font-weight:800;">
                R$ <?= number_format($saldoTotal ?? 0, 2, ',', '.') ?>
            </div>
            <div style="font-size:13px;opacity:.7;margin-top:4px;">
                <?= count($contas) ?> conta<?= count($contas) !== 1 ? 's' : '' ?> cadastrada<?= count($contas) !== 1 ? 's' : '' ?>
            </div>
        </div>
        <div class="col-auto">
            <i class="fas fa-university" style="font-size:56px;opacity:.2;"></i>
        </div>
    </div>
</div>

<!-- Filtro -->
<form method="GET" class="row g-2 mb-4">
    <div class="col-md-6">
        <input type="text" name="q" class="form-control" placeholder="Pesquisar por nome, banco ou número..." value="<?= htmlspecialchars($filtros['pesquisa'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <select name="ativa" class="form-select">
            <option value="">Todas as situações</option>
            <option value="1" <?= ($filtros['ativa'] ?? '') === '1' ? 'selected' : '' ?>>Ativas</option>
            <option value="0" <?= ($filtros['ativa'] ?? '') === '0' ? 'selected' : '' ?>>Inativas</option>
        </select>
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-outline-primary w-100"><i class="fas fa-search me-2"></i>Filtrar</button>
    </div>
</form>

<!-- Cards de Contas -->
<?php if (empty($contas)): ?>
    <div class="text-center py-5">
        <i class="fas fa-university fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Nenhuma conta cadastrada</h5>
        <p class="text-muted">Clique em "Nova Conta" para adicionar sua primeira conta bancária.</p>
        <a href="/financeiro/contas/create" class="btn btn-primary mt-2"><i class="fas fa-plus me-2"></i>Cadastrar Conta</a>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($contas as $c): ?>
        <div class="col-md-6 col-xl-4">
            <div class="conta-card <?= !(int)$c->ativa ? 'conta-inativa' : '' ?>">
                <div class="stripe" style="background: <?= htmlspecialchars($c->cor ?? '#4361ee') ?>;"></div>
                <div class="d-flex align-items-start justify-content-between mb-3" style="padding-left:12px;">
                    <div class="d-flex align-items-center gap-3">
                        <?php if (!empty($bancoIcones[$c->banco_codigo ?? ''])): ?>
                            <img src="<?= $bancoIcones[$c->banco_codigo] ?>" alt="" class="banco-logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                            <div class="banco-icon" style="display:none;"><i class="<?= htmlspecialchars($c->icone ?? 'fas fa-university') ?>"></i></div>
                        <?php else: ?>
                            <div class="banco-icon"><i class="<?= htmlspecialchars($c->icone ?? 'fas fa-university') ?>"></i></div>
                        <?php endif; ?>
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($c->nome) ?></div>
                            <div class="conta-info"><?= htmlspecialchars($c->banco_nome ?? 'Banco não informado') ?></div>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/financeiro/contas/edit/<?= $c->id ?>"><i class="fas fa-edit me-2 text-primary"></i>Editar</a></li>
                            <li><a class="dropdown-item" href="/financeiro/contas/<?= $c->id ?>/openfinance"><i class="fas fa-link me-2 text-success"></i>Open Finance</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="confirmarExclusao(<?= $c->id ?>, '<?= htmlspecialchars(addslashes($c->nome)) ?>'); return false;"><i class="fas fa-trash me-2"></i>Excluir</a></li>
                        </ul>
                    </div>
                </div>

                <div style="padding-left:12px;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge-tipo"><?= $tipoLabels[$c->tipo] ?? $c->tipo ?></span>
                        <?php if (!(int)$c->ativa): ?>
                            <span class="badge bg-secondary" style="font-size:11px;">Inativa</span>
                        <?php endif; ?>
                        <?php if (!empty($c->openfinance_account_id)): ?>
                            <span class="badge bg-success" style="font-size:11px;"><i class="fas fa-link me-1"></i>Open Finance</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($c->agencia) || !empty($c->conta)): ?>
                    <div class="conta-info mb-3">
                        <?php if (!empty($c->agencia)): ?>Ag: <?= htmlspecialchars($c->agencia) ?><?= !empty($c->agencia_digito) ? '-'.$c->agencia_digito : '' ?><?php endif; ?>
                        <?php if (!empty($c->conta)): ?>&nbsp;&nbsp;Conta: <?= htmlspecialchars($c->conta) ?><?= !empty($c->conta_digito) ? '-'.$c->conta_digito : '' ?><?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="saldo-label">Saldo Atual</div>
                    <div class="saldo-valor <?= (float)$c->saldo_atual >= 0 ? 'saldo-positivo' : 'saldo-negativo' ?> mb-3">
                        R$ <?= number_format((float)$c->saldo_atual, 2, ',', '.') ?>
                    </div>

                    <a href="/financeiro/contas/<?= $c->id ?>/movimentacoes" class="btn-movimentacoes">
                        <i class="fas fa-exchange-alt"></i>Movimentações
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-danger"><i class="fas fa-trash me-2"></i>Excluir Conta</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Deseja excluir a conta <strong id="nomeConta"></strong>? Todas as movimentações vinculadas serão removidas.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <a id="btnConfirmarExclusao" href="#" class="btn btn-danger btn-sm">Excluir</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id, nome) {
    document.getElementById('nomeConta').textContent = nome;
    document.getElementById('btnConfirmarExclusao').href = '/financeiro/contas/delete/' + id;
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}
</script>
