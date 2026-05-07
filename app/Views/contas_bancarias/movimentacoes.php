<?php
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
$msgImport = urldecode($_GET['msg'] ?? '');

$origemLabels = [
    'manual'        => ['label' => 'Manual',       'class' => 'bg-secondary'],
    'ofx'           => ['label' => 'OFX',           'class' => 'bg-info text-dark'],
    'ofc'           => ['label' => 'OFC',           'class' => 'bg-info text-dark'],
    'openfinance'   => ['label' => 'Open Finance',  'class' => 'bg-success'],
    'apuracao'      => ['label' => 'Apuração',      'class' => 'bg-primary'],
    'conta_pagar'   => ['label' => 'Conta a Pagar', 'class' => 'bg-danger'],
    'conta_receber' => ['label' => 'Conta a Receber','class' => 'bg-success'],
    'importacao'    => ['label' => 'Importação',    'class' => 'bg-warning text-dark'],
];
?>
<style>
.extrato-header {
    background: linear-gradient(135deg, <?= htmlspecialchars($conta->cor ?? '#4361ee') ?> 0%, #3a0ca3 100%);
    color: #fff;
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 24px;
}
.kpi-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e8ecf0;
    padding: 20px;
    text-align: center;
}
.kpi-card .kpi-valor { font-size: 22px; font-weight: 800; }
.kpi-card .kpi-label { font-size: 12px; color: #8a94a6; margin-top: 2px; }
.kpi-credito { color: #2ecc71; }
.kpi-debito  { color: #e74c3c; }
.kpi-saldo   { color: #4361ee; }

.extrato-linha {
    background: #fff;
    border-radius: 10px;
    border: 1px solid #f0f4ff;
    padding: 14px 18px;
    margin-bottom: 6px;
    transition: all .15s;
    display: flex;
    align-items: center;
    gap: 14px;
}
.extrato-linha:hover { border-color: #4361ee; box-shadow: 0 2px 12px rgba(67,97,238,.08); }
.extrato-linha .tipo-icon {
    width: 40px; height: 40px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.tipo-credito-icon { background: #e8faf0; color: #2ecc71; }
.tipo-debito-icon  { background: #fdecea; color: #e74c3c; }
.extrato-linha .desc { flex: 1; min-width: 0; }
.extrato-linha .desc .titulo { font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.extrato-linha .desc .sub    { font-size: 12px; color: #8a94a6; }
.extrato-linha .valor-col { text-align: right; flex-shrink: 0; }
.extrato-linha .valor-col .valor { font-size: 16px; font-weight: 700; }
.extrato-linha .valor-col .saldo { font-size: 11px; color: #8a94a6; }
.conciliada-badge { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.conciliada-sim { background: #2ecc71; }
.conciliada-nao { background: #e0e0e0; }

.tab-extrato .nav-link {
    border-radius: 8px 8px 0 0;
    font-weight: 600;
    color: #6c757d;
    padding: 10px 20px;
}
.tab-extrato .nav-link.active { background: #4361ee; color: #fff; }

.filtro-panel {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e8ecf0;
    padding: 20px;
    margin-bottom: 20px;
}
.chart-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e8ecf0;
    padding: 20px;
}
</style>

<!-- Cabeçalho da conta -->
<div class="extrato-header">
    <div class="row align-items-center">
        <div class="col">
            <div style="font-size:13px;opacity:.8;">
                <a href="/financeiro/contas" class="text-white text-decoration-none opacity-75"><i class="fas fa-arrow-left me-2"></i>Contas</a>
                &nbsp;/&nbsp; Movimentações
            </div>
            <h4 class="fw-bold mb-1 mt-2"><?= htmlspecialchars($conta->nome) ?></h4>
            <div style="opacity:.8;font-size:14px;">
                <?= htmlspecialchars($conta->banco_nome ?? '') ?>
                <?php if (!empty($conta->agencia)): ?>&nbsp;· Ag: <?= htmlspecialchars($conta->agencia) ?><?php endif; ?>
                <?php if (!empty($conta->conta)): ?>&nbsp;· Conta: <?= htmlspecialchars($conta->conta) ?><?= !empty($conta->conta_digito) ? '-'.$conta->conta_digito : '' ?><?php endif; ?>
            </div>
        </div>
        <div class="col-auto text-end">
            <div style="font-size:13px;opacity:.8;margin-bottom:4px;">Saldo Atual</div>
            <div style="font-size:32px;font-weight:800;">R$ <?= number_format((float)$conta->saldo_atual, 2, ',', '.') ?></div>
            <?php if ($openfinanceAtivo): ?>
                <button class="btn btn-sm btn-light mt-2" onclick="sincronizarOpenFinance()" id="btnSync">
                    <i class="fas fa-sync me-1"></i>Sincronizar
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($success === 'movimentacao_criada'): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>Movimentação registrada com sucesso! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php elseif ($success === 'ofx_importado' && $msgImport): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($msgImport) ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php elseif ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i>Ocorreu um erro. Tente novamente. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- KPIs do período -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-valor kpi-credito">R$ <?= number_format((float)($resumo->total_credito ?? 0), 2, ',', '.') ?></div>
            <div class="kpi-label"><i class="fas fa-arrow-up me-1"></i>Entradas no Período</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-valor kpi-debito">R$ <?= number_format((float)($resumo->total_debito ?? 0), 2, ',', '.') ?></div>
            <div class="kpi-label"><i class="fas fa-arrow-down me-1"></i>Saídas no Período</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-valor kpi-saldo">R$ <?= number_format((float)($resumo->saldo_periodo ?? 0), 2, ',', '.') ?></div>
            <div class="kpi-label"><i class="fas fa-balance-scale me-1"></i>Saldo do Período</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-valor" style="color:#f39c12;"><?= (int)($resumo->total_transacoes ?? 0) ?></div>
            <div class="kpi-label"><i class="fas fa-list me-1"></i>Transações</div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<?php if (!empty($evolucao) || !empty($categorias)): ?>
<div class="row g-3 mb-4">
    <?php if (!empty($evolucao)): ?>
    <div class="col-lg-8">
        <div class="chart-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Evolução do Saldo</h6>
            </div>
            <canvas id="chartEvolucao" height="100"></canvas>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($categorias)): ?>
    <div class="col-lg-4">
        <div class="chart-card">
            <h6 class="fw-bold mb-3">Por Categoria</h6>
            <canvas id="chartCategorias" height="200"></canvas>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Abas: Interna / Open Finance -->
<ul class="nav tab-extrato mb-0" id="tabExtrato">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabInterna">
            <i class="fas fa-list me-2"></i>Extrato Interno
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabOpenFinance">
            <i class="fas fa-link me-2"></i>Open Finance
        </button>
    </li>
</ul>

<div class="tab-content" style="background:#fff;border-radius:0 12px 12px 12px;border:1px solid #e8ecf0;padding:20px;">

    <!-- ABA: Extrato Interno -->
    <div class="tab-pane fade show active" id="tabInterna">

        <!-- Filtros -->
        <form method="GET" class="filtro-panel">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Data Início</label>
                    <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['data_inicio']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Data Fim</label>
                    <input type="date" name="data_fim" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['data_fim']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="credito" <?= $filtros['tipo'] === 'credito' ? 'selected' : '' ?>>Entradas</option>
                        <option value="debito"  <?= $filtros['tipo'] === 'debito'  ? 'selected' : '' ?>>Saídas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Origem</label>
                    <select name="origem" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php foreach ($origemLabels as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $filtros['origem'] === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Conciliação</label>
                    <select name="conciliada" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="1" <?= $filtros['conciliada'] === '1' ? 'selected' : '' ?>>Conciliadas</option>
                        <option value="0" <?= $filtros['conciliada'] === '0' ? 'selected' : '' ?>>Pendentes</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-filter"></i></button>
                        <a href="/financeiro/contas/<?= $conta->id ?>/movimentacoes" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
                    </div>
                </div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Pesquisar por descrição ou categoria..." value="<?= htmlspecialchars($filtros['pesquisa']) ?>">
                </div>
            </div>
        </form>

        <!-- Ações -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="text-muted small">
                <?= number_format($total, 0, ',', '.') ?> transação<?= $total !== 1 ? 'ões' : '' ?> encontrada<?= $total !== 1 ? 's' : '' ?>
            </div>
            <div class="d-flex gap-2">
                <!-- Importar OFX -->
                <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalImportarOFX">
                    <i class="fas fa-file-import me-1"></i>Importar OFX/OFC
                </button>
                <!-- Nova movimentação -->
                <a href="/financeiro/contas/<?= $conta->id ?>/movimentacoes/nova" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>Nova Movimentação
                </a>
            </div>
        </div>

        <!-- Extrato -->
        <?php if (empty($movimentacoes)): ?>
            <div class="text-center py-5">
                <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">Nenhuma movimentação encontrada</h6>
                <p class="text-muted small">Importe um arquivo OFX/OFC ou registre uma movimentação manual.</p>
            </div>
        <?php else: ?>
            <?php
            $dataAtual = '';
            foreach ($movimentacoes as $mov):
                $dataFormatada = date('d/m/Y', strtotime($mov->data_movimentacao));
                if ($dataFormatada !== $dataAtual):
                    $dataAtual = $dataFormatada;
            ?>
                <div class="text-muted small fw-bold mt-3 mb-1 px-1"><?= $dataFormatada ?></div>
            <?php endif; ?>

                <div class="extrato-linha" id="mov-<?= $mov->id ?>">
                    <!-- Indicador de conciliação -->
                    <div class="conciliada-badge <?= (int)$mov->conciliada ? 'conciliada-sim' : 'conciliada-nao' ?>"
                         title="<?= (int)$mov->conciliada ? 'Conciliada' : 'Pendente de conciliação' ?>"
                         onclick="toggleConciliacao(<?= $conta->id ?>, <?= $mov->id ?>, this)"
                         style="cursor:pointer;"></div>

                    <!-- Ícone de tipo -->
                    <div class="tipo-icon <?= $mov->tipo === 'credito' ? 'tipo-credito-icon' : 'tipo-debito-icon' ?>">
                        <i class="fas fa-arrow-<?= $mov->tipo === 'credito' ? 'down' : 'up' ?>"></i>
                    </div>

                    <!-- Descrição -->
                    <div class="desc">
                        <div class="titulo"><?= htmlspecialchars($mov->descricao) ?></div>
                        <div class="sub d-flex align-items-center gap-2 flex-wrap">
                            <?php if (!empty($origemLabels[$mov->origem])): ?>
                                <span class="badge <?= $origemLabels[$mov->origem]['class'] ?>" style="font-size:10px;"><?= $origemLabels[$mov->origem]['label'] ?></span>
                            <?php endif; ?>
                            <?php if (!empty($mov->categoria)): ?>
                                <span class="badge bg-light text-dark border" style="font-size:10px;"><?= htmlspecialchars($mov->categoria) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($mov->plano_nome)): ?>
                                <span class="text-muted"><?= htmlspecialchars($mov->plano_codigo) ?> — <?= htmlspecialchars($mov->plano_nome) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Valor -->
                    <div class="valor-col">
                        <div class="valor <?= $mov->tipo === 'credito' ? 'text-success' : 'text-danger' ?>">
                            <?= $mov->tipo === 'credito' ? '+' : '-' ?> R$ <?= number_format(abs((float)$mov->valor), 2, ',', '.') ?>
                        </div>
                        <?php if (!empty($mov->saldo_apos)): ?>
                            <div class="saldo">Saldo: R$ <?= number_format((float)$mov->saldo_apos, 2, ',', '.') ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Ações -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/financeiro/contas/<?= $conta->id ?>/movimentacoes/<?= $mov->id ?>/editar"><i class="fas fa-edit me-2 text-primary"></i>Editar</a></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="confirmarExcluirMov(<?= $conta->id ?>, <?= $mov->id ?>); return false;"><i class="fas fa-trash me-2"></i>Excluir</a></li>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Paginação -->
            <?php if ($paginas > 1): ?>
            <nav class="mt-4">
                <ul class="pagination pagination-sm justify-content-center">
                    <?php for ($p = 1; $p <= $paginas; $p++): ?>
                        <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($filtros, ['pagina' => $p])) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- ABA: Open Finance -->
    <div class="tab-pane fade" id="tabOpenFinance">
        <div class="py-4">
            <?php if ($openfinanceAtivo): ?>
                <div class="text-center mb-4">
                    <div class="badge bg-success mb-3" style="font-size:14px;padding:10px 20px;">
                        <i class="fas fa-link me-2"></i>Conta vinculada ao Open Finance
                    </div>
                    <p class="text-muted">Sua conta está conectada. Clique em "Sincronizar" para importar as transações mais recentes do banco.</p>
                    <button class="btn btn-success btn-lg" onclick="sincronizarOpenFinance()" id="btnSyncOF">
                        <i class="fas fa-sync me-2"></i>Sincronizar Agora
                    </button>
                    <?php if (!empty($conta->openfinance_last_sync)): ?>
                        <p class="text-muted small mt-3">Última sincronização: <?= date('d/m/Y H:i', strtotime($conta->openfinance_last_sync)) ?></p>
                    <?php endif; ?>
                </div>
                <hr>
                <div class="text-center">
                    <a href="/financeiro/contas/<?= $conta->id ?>/openfinance" class="btn btn-outline-primary">
                        <i class="fas fa-cog me-2"></i>Gerenciar Conexão Open Finance
                    </a>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-university fa-4x text-muted mb-4" style="opacity:.3;"></i>
                    <h5 class="fw-bold mb-2">Conecte sua conta ao Open Finance</h5>
                    <p class="text-muted mb-4">
                        Importe automaticamente todas as transações do seu banco em tempo real.<br>
                        Compatível com mais de 200 instituições financeiras brasileiras via <strong>Pluggy</strong>.
                    </p>
                    <div class="row justify-content-center g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card border-0 bg-light p-3 text-center">
                                <i class="fas fa-sync-alt fa-2x text-primary mb-2"></i>
                                <div class="fw-semibold">Sincronização Automática</div>
                                <div class="text-muted small">Transações importadas em tempo real</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-light p-3 text-center">
                                <i class="fas fa-shield-alt fa-2x text-success mb-2"></i>
                                <div class="fw-semibold">Seguro e Regulamentado</div>
                                <div class="text-muted small">Padrão Open Finance Brasil (BCB)</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-light p-3 text-center">
                                <i class="fas fa-file-import fa-2x text-info mb-2"></i>
                                <div class="fw-semibold">Ou importe por OFX</div>
                                <div class="text-muted small">Arquivo exportado pelo seu banco</div>
                            </div>
                        </div>
                    </div>
                    <a href="/financeiro/contas/<?= $conta->id ?>/openfinance" class="btn btn-primary btn-lg">
                        <i class="fas fa-link me-2"></i>Conectar ao Open Finance
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Modal Importar OFX -->
<div class="modal fade" id="modalImportarOFX" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="fas fa-file-import me-2"></i>Importar OFX / OFC</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/financeiro/contas/<?= $conta->id ?>/importar-ofx" enctype="multipart/form-data">
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Importe o extrato bancário no formato <strong>OFX</strong>, <strong>OFC</strong> ou <strong>QFX</strong>.
                        A maioria dos bancos brasileiros oferece essa opção no internet banking.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Arquivo do Extrato</label>
                        <input type="file" name="arquivo" class="form-control" accept=".ofx,.ofc,.qfx" required>
                        <div class="form-text">Formatos aceitos: .ofx, .ofc, .qfx</div>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Transações já importadas serão ignoradas automaticamente (deduplicação por hash).
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-2"></i>Importar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Excluir Movimentação -->
<div class="modal fade" id="modalExcluirMov" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-danger"><i class="fas fa-trash me-2"></i>Excluir Movimentação</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"><p class="mb-0">Deseja excluir esta movimentação? Esta ação não pode ser desfeita.</p></div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <a id="btnConfirmarExcluirMov" href="#" class="btn btn-danger btn-sm">Excluir</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ---- Gráfico de Evolução de Saldo ----
<?php if (!empty($evolucao)): ?>
(function() {
    const labels = <?= json_encode(array_map(fn($e) => date('d/m', strtotime($e->data)), $evolucao)) ?>;
    const saldos = <?= json_encode(array_map(fn($e) => (float)$e->saldo_dia, $evolucao)) ?>;
    const ctx = document.getElementById('chartEvolucao');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Saldo',
                data: saldos,
                borderColor: '<?= htmlspecialchars($conta->cor ?? '#4361ee') ?>',
                backgroundColor: '<?= htmlspecialchars($conta->cor ?? '#4361ee') ?>22',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { ticks: { callback: v => 'R$ ' + v.toLocaleString('pt-BR', {minimumFractionDigits:2}) } }
            }
        }
    });
})();
<?php endif; ?>

// ---- Gráfico de Categorias ----
<?php if (!empty($categorias)): ?>
(function() {
    const labels = <?= json_encode(array_map(fn($c) => $c->categoria, $categorias)) ?>;
    const totais = <?= json_encode(array_map(fn($c) => (float)$c->total, $categorias)) ?>;
    const ctx = document.getElementById('chartCategorias');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data: totais, backgroundColor: ['#4361ee','#7209b7','#f72585','#4cc9f0','#2ecc71','#e74c3c','#f39c12','#1abc9c','#34495e','#e67e22'] }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }
        }
    });
})();
<?php endif; ?>

// ---- Conciliação ----
function toggleConciliacao(contaId, movId, el) {
    fetch(`/financeiro/contas/${contaId}/movimentacoes/${movId}/conciliar`, { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                el.classList.toggle('conciliada-sim', d.conciliada === 1);
                el.classList.toggle('conciliada-nao', d.conciliada === 0);
                el.title = d.conciliada === 1 ? 'Conciliada' : 'Pendente de conciliação';
            }
        });
}

// ---- Excluir movimentação ----
function confirmarExcluirMov(contaId, movId) {
    document.getElementById('btnConfirmarExcluirMov').href = `/financeiro/contas/${contaId}/movimentacoes/${movId}/excluir`;
    new bootstrap.Modal(document.getElementById('modalExcluirMov')).show();
}

// ---- Sincronizar Open Finance ----
function sincronizarOpenFinance() {
    const btn = document.getElementById('btnSync') || document.getElementById('btnSyncOF');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sincronizando...'; }

    fetch('/financeiro/contas/<?= $conta->id ?>/openfinance/sincronizar', { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert(`✅ ${d.msg}`);
                location.reload();
            } else {
                alert('❌ ' + (d.error || 'Erro ao sincronizar.'));
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-sync me-1"></i>Sincronizar'; }
            }
        })
        .catch(() => {
            alert('❌ Erro de comunicação.');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-sync me-1"></i>Sincronizar'; }
        });
}
</script>
