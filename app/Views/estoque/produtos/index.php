<?php
// Helpers
$fmt  = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$fmtN = fn($v, $dec=2) => number_format((float)$v, $dec, ',', '.');
$esc  = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$categLabels = [
    'equipamento_medico'      => 'Equip. Médico',
    'equipamento_hospitalar'  => 'Equip. Hospitalar',
    'consumivel'              => 'Consumível',
    'reagente'                => 'Reagente',
    'software'                => 'Software',
    'servico_manutencao'      => 'Manutenção',
    'servico_instalacao'      => 'Instalação',
    'servico_treinamento'     => 'Treinamento',
    'servico_consultoria'     => 'Consultoria',
    'acessorio'               => 'Acessório',
    'peca_reposicao'          => 'Peça Reposição',
    'outro'                   => 'Outro',
];
$statusLabels = [
    'ativo'           => ['label' => 'Ativo',          'class' => 'badge-success'],
    'inativo'         => ['label' => 'Inativo',         'class' => 'badge-secondary'],
    'descontinuado'   => ['label' => 'Descontinuado',   'class' => 'badge-danger'],
    'em_homologacao'  => ['label' => 'Em Homologação',  'class' => 'badge-warning'],
];

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>
<style>
.kpi-card { border-radius:12px; padding:20px 24px; display:flex; align-items:center; gap:16px; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,.07); }
.kpi-icon { width:52px; height:52px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
.kpi-val  { font-size:26px; font-weight:700; line-height:1; }
.kpi-lbl  { font-size:12px; color:#6b7280; margin-top:3px; }
.badge-success    { background:#d1fae5; color:#065f46; }
.badge-secondary  { background:#f3f4f6; color:#374151; }
.badge-danger     { background:#fee2e2; color:#991b1b; }
.badge-warning    { background:#fef3c7; color:#92400e; }
.badge-info       { background:#dbeafe; color:#1e40af; }
.badge-purple     { background:#ede9fe; color:#5b21b6; }
.badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.prod-table th { background:#f8fafc; font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#6b7280; font-weight:600; padding:10px 14px; }
.prod-table td { padding:12px 14px; vertical-align:middle; }
.prod-table tr:hover td { background:#f9fafb; }
.prod-img { width:44px; height:44px; object-fit:cover; border-radius:8px; border:1px solid #e5e7eb; }
.prod-img-placeholder { width:44px; height:44px; border-radius:8px; background:#f3f4f6; display:flex; align-items:center; justify-content:center; color:#9ca3af; font-size:18px; border:1px solid #e5e7eb; }
.estoque-critico { color:#dc2626; font-weight:700; }
.estoque-ok      { color:#059669; }
.filtro-bar { background:#fff; border-radius:12px; padding:16px 20px; box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:20px; }
.markup-badge { background:#eff6ff; color:#1d4ed8; border-radius:6px; padding:2px 8px; font-size:12px; font-weight:600; }
.margem-badge { border-radius:6px; padding:2px 8px; font-size:12px; font-weight:600; }
.margem-alta  { background:#d1fae5; color:#065f46; }
.margem-media { background:#fef3c7; color:#92400e; }
.margem-baixa { background:#fee2e2; color:#991b1b; }
</style>

<?php if ($success === 'created'): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> Produto cadastrado com sucesso!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php elseif ($success === 'updated'): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> Produto atualizado com sucesso!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php elseif ($success === 'deleted'): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> Produto excluído.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php elseif ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i> Ocorreu um erro. Tente novamente.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#eff6ff;color:#2563eb;"><i class="fas fa-boxes"></i></div>
            <div>
                <div class="kpi-val"><?= $fmtN($kpis->total ?? 0, 0) ?></div>
                <div class="kpi-lbl">Total Cadastros</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="kpi-val"><?= $fmtN($kpis->total_ativos ?? 0, 0) ?></div>
                <div class="kpi-lbl">Ativos</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fef2f2;color:#dc2626;"><i class="fas fa-exclamation-triangle"></i></div>
            <div>
                <div class="kpi-val"><?= $fmtN($kpis->estoque_critico ?? 0, 0) ?></div>
                <div class="kpi-lbl">Estoque Crítico</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fdf4ff;color:#9333ea;"><i class="fas fa-calendar-times"></i></div>
            <div>
                <div class="kpi-val"><?= $fmtN($kpis->com_validade ?? 0, 0) ?></div>
                <div class="kpi-lbl">Com Validade</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fff7ed;color:#ea580c;"><i class="fas fa-percentage"></i></div>
            <div>
                <div class="kpi-val"><?= $fmtN($kpis->markup_medio ?? 0, 1) ?>%</div>
                <div class="kpi-lbl">Markup Médio</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#f0fdf4;color:#059669;"><i class="fas fa-dollar-sign"></i></div>
            <div>
                <div class="kpi-val" style="font-size:18px;"><?= $fmt($kpis->valor_estoque_venda ?? 0) ?></div>
                <div class="kpi-lbl">Valor em Estoque</div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="filtro-bar">
    <form method="GET" action="/estoque/produtos" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label small mb-1">Buscar</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" name="q" class="form-control" placeholder="Nome, código, marca, modelo…" value="<?= $esc($filtros['q']) ?>">
            </div>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Tipo</label>
            <select name="tipo" class="form-select form-select-sm">
                <option value="">Todos</option>
                <option value="produto"  <?= $filtros['tipo']==='produto'  ? 'selected':'' ?>>Produto</option>
                <option value="servico"  <?= $filtros['tipo']==='servico'  ? 'selected':'' ?>>Serviço</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Categoria</label>
            <select name="categoria" class="form-select form-select-sm">
                <option value="">Todas</option>
                <?php foreach ($categLabels as $k => $v): ?>
                <option value="<?= $k ?>" <?= $filtros['categoria']===$k ? 'selected':'' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Todos</option>
                <option value="ativo"          <?= $filtros['status']==='ativo'          ? 'selected':'' ?>>Ativo</option>
                <option value="inativo"        <?= $filtros['status']==='inativo'        ? 'selected':'' ?>>Inativo</option>
                <option value="descontinuado"  <?= $filtros['status']==='descontinuado'  ? 'selected':'' ?>>Descontinuado</option>
                <option value="em_homologacao" <?= $filtros['status']==='em_homologacao' ? 'selected':'' ?>>Em Homologação</option>
            </select>
        </div>
        <div class="col-6 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter me-1"></i>Filtrar</button>
            <a href="/estoque/produtos" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
        </div>
        <div class="col-12 d-flex gap-2 align-items-center flex-wrap">
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="checkbox" name="estoque_baixo" id="chkEstoqueBaixo" value="1" <?= !empty($filtros['estoque_baixo']) ? 'checked':'' ?> onchange="this.form.submit()">
                <label class="form-check-label small" for="chkEstoqueBaixo"><span class="text-danger fw-semibold">Apenas estoque crítico</span></label>
            </div>
        </div>
    </form>
</div>

<!-- Tabela -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-4">
        <div>
            <h6 class="mb-0 fw-semibold">Produtos e Serviços</h6>
            <small class="text-muted"><?= count($produtos) ?> registro(s)</small>
        </div>
        <a href="/estoque/produtos/create" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Novo Produto
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($produtos)): ?>
        <div class="text-center py-5">
            <i class="fas fa-boxes fa-3x text-muted mb-3"></i>
            <p class="text-muted">Nenhum produto encontrado.</p>
            <a href="/estoque/produtos/create" class="btn btn-primary btn-sm">Cadastrar primeiro produto</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0 prod-table">
                <thead>
                    <tr>
                        <th style="width:50px"></th>
                        <th>Código</th>
                        <th>Nome / Modelo</th>
                        <th>Categoria</th>
                        <th class="text-end">Custo</th>
                        <th class="text-end">Venda</th>
                        <th class="text-center">Markup</th>
                        <th class="text-center">Margem</th>
                        <th class="text-center">Estoque</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($produtos as $p):
                    $margemBruta = (float)$p->margem_lucro_bruta;
                    $margemClass = $margemBruta >= 30 ? 'margem-alta' : ($margemBruta >= 15 ? 'margem-media' : 'margem-baixa');
                    $estoqueClass = ((float)$p->estoque_atual <= (float)$p->estoque_minimo && $p->controla_estoque && (float)$p->estoque_minimo > 0)
                                    ? 'estoque-critico' : 'estoque-ok';
                    $st = $statusLabels[$p->status] ?? ['label'=>$p->status,'class'=>'badge-secondary'];
                ?>
                <tr>
                    <td>
                        <?php if ($p->imagem_principal): ?>
                        <img src="<?= $esc($p->imagem_principal) ?>" class="prod-img" alt="">
                        <?php else: ?>
                        <div class="prod-img-placeholder"><i class="fas fa-<?= $p->tipo==='servico' ? 'tools' : 'box' ?>"></i></div>
                        <?php endif; ?>
                    </td>
                    <td><code class="small"><?= $esc($p->codigo) ?></code></td>
                    <td>
                        <div class="fw-semibold"><?= $esc($p->nome) ?></div>
                        <?php if ($p->marca || $p->modelo): ?>
                        <small class="text-muted"><?= $esc(trim($p->marca . ' ' . $p->modelo)) ?></small>
                        <?php endif; ?>
                        <?php if ($p->tipo === 'servico'): ?>
                        <span class="badge badge-purple ms-1">Serviço</span>
                        <?php endif; ?>
                        <?php if ($p->requer_anvisa && $p->anvisa_registro): ?>
                        <span class="badge badge-info ms-1" title="ANVISA: <?= $esc($p->anvisa_registro) ?>"><i class="fas fa-shield-alt"></i> ANVISA</span>
                        <?php endif; ?>
                    </td>
                    <td><small><?= $esc($categLabels[$p->categoria] ?? $p->categoria) ?></small></td>
                    <td class="text-end"><small><?= $fmt($p->preco_custo) ?></small></td>
                    <td class="text-end fw-semibold"><?= $fmt($p->preco_venda) ?></td>
                    <td class="text-center"><span class="markup-badge"><?= $fmtN($p->markup_percentual, 1) ?>%</span></td>
                    <td class="text-center"><span class="margem-badge <?= $margemClass ?>"><?= $fmtN($margemBruta, 1) ?>%</span></td>
                    <td class="text-center">
                        <?php if ($p->controla_estoque): ?>
                        <span class="<?= $estoqueClass ?> fw-semibold"><?= $fmtN($p->estoque_atual, 2) ?></span>
                        <small class="text-muted"> <?= $esc($p->unidade_medida) ?></small>
                        <?php if ((float)$p->estoque_atual <= (float)$p->estoque_minimo && (float)$p->estoque_minimo > 0): ?>
                        <i class="fas fa-exclamation-triangle text-danger ms-1" title="Estoque abaixo do mínimo!"></i>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="/estoque/produtos/<?= $p->id ?>" class="btn btn-outline-secondary" title="Visualizar"><i class="fas fa-eye"></i></a>
                            <a href="/estoque/produtos/<?= $p->id ?>/edit" class="btn btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>
                            <button type="button" class="btn btn-outline-danger" title="Excluir"
                                onclick="confirmarExclusao(<?= $p->id ?>, '<?= $esc($p->nome) ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger"><i class="fas fa-trash me-2"></i>Excluir Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o produto <strong id="nomeProdutoExcluir"></strong>?</p>
                <p class="text-muted small">Esta ação não pode ser desfeita. Produtos com movimentações de estoque não podem ser excluídos.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formExcluir" method="POST" style="display:inline">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id, nome) {
    document.getElementById('nomeProdutoExcluir').textContent = nome;
    document.getElementById('formExcluir').action = '/estoque/produtos/' + id + '/delete';
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}
</script>
