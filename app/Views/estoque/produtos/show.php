<?php
$esc    = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$fmtR   = fn($v) => 'R$ ' . number_format((float)($v ?? 0), 2, ',', '.');
$fmtN   = fn($v, $d=2) => number_format((float)($v ?? 0), $d, ',', '.');
$fmtDt  = fn($v) => $v ? date('d/m/Y', strtotime($v)) : '—';

$statusColors = [
    'ativo'          => 'success',
    'em_homologacao' => 'warning',
    'inativo'        => 'secondary',
    'descontinuado'  => 'danger',
];
$statusLabels = [
    'ativo'          => 'Ativo',
    'em_homologacao' => 'Em Homologação',
    'inativo'        => 'Inativo',
    'descontinuado'  => 'Descontinuado',
];
$tipoLabels = [
    'produto' => 'Produto',
    'servico' => 'Serviço',
];
$categLabels = [
    'equipamento_medico'     => 'Equipamento Médico',
    'equipamento_hospitalar' => 'Equipamento Hospitalar',
    'consumivel'             => 'Consumível',
    'reagente'               => 'Reagente',
    'software'               => 'Software',
    'servico_manutencao'     => 'Serviço de Manutenção',
    'servico_instalacao'     => 'Serviço de Instalação',
    'servico_treinamento'    => 'Serviço de Treinamento',
    'servico_consultoria'    => 'Serviço de Consultoria',
    'acessorio'              => 'Acessório',
    'peca_reposicao'         => 'Peça de Reposição',
    'outro'                  => 'Outro',
];
$comTipoLabels = [
    'percentual_venda'   => '% sobre Venda',
    'valor_fixo'         => 'Valor Fixo',
    'percentual_margem'  => '% sobre Margem',
    'percentual_lucro'   => '% sobre Lucro',
];
$statusColor = $statusColors[$produto->status] ?? 'secondary';
$statusLabel = $statusLabels[$produto->status] ?? $produto->status;

// Calcular margem
$precoVenda = (float)($produto->preco_venda ?? 0);
$precoCusto = (float)($produto->preco_custo ?? 0);
$margemBruta = $precoVenda > 0 ? (($precoVenda - $precoCusto) / $precoVenda * 100) : 0;
$markup = $precoCusto > 0 ? (($precoVenda / $precoCusto - 1) * 100) : 0;

// Alerta de estoque baixo
$estoqueBaixo = $produto->controla_estoque && (float)$produto->estoque_atual <= (float)$produto->estoque_minimo;
?>
<style>
.prod-show-header { background:linear-gradient(135deg,#1e40af,#2563eb); border-radius:16px; padding:28px 32px; color:#fff; margin-bottom:24px; }
.prod-show-header .badge-status { font-size:13px; padding:6px 14px; border-radius:20px; }
.prod-show-header .preco-destaque { font-size:32px; font-weight:800; }
.prod-show-header .preco-custo-sm { font-size:14px; opacity:.8; }
.info-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; height:100%; }
.info-card .card-title { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#6b7280; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.info-card .card-title i { color:#2563eb; }
.info-row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f9fafb; font-size:14px; }
.info-row:last-child { border-bottom:none; }
.info-row .label { color:#6b7280; }
.info-row .value { font-weight:500; text-align:right; max-width:60%; }
.kpi-mini { background:#f0f9ff; border-radius:10px; padding:12px 16px; text-align:center; }
.kpi-mini .kpi-val { font-size:20px; font-weight:800; color:#0369a1; }
.kpi-mini .kpi-lbl { font-size:11px; color:#6b7280; margin-top:2px; }
.comp-item { background:#f9fafb; border-radius:10px; padding:12px 14px; display:flex; align-items:center; gap:12px; margin-bottom:8px; }
.comp-img { width:36px; height:36px; border-radius:8px; object-fit:cover; background:#e5e7eb; display:flex; align-items:center; justify-content:center; color:#9ca3af; flex-shrink:0; }
.timeline-item { display:flex; gap:12px; padding:8px 0; border-bottom:1px solid #f3f4f6; }
.timeline-dot { width:10px; height:10px; border-radius:50%; background:#2563eb; margin-top:5px; flex-shrink:0; }
.score-bar { height:8px; border-radius:4px; background:#e5e7eb; overflow:hidden; }
.score-fill { height:100%; background:linear-gradient(90deg,#2563eb,#7c3aed); border-radius:4px; }
.deprec-progress { height:12px; border-radius:6px; background:#e5e7eb; overflow:hidden; }
.deprec-fill { height:100%; background:linear-gradient(90deg,#10b981,#f59e0b,#ef4444); border-radius:6px; }
.tag-badge { display:inline-block; background:#ede9fe; color:#5b21b6; border-radius:20px; padding:3px 10px; font-size:12px; margin:2px; }
</style>

<!-- Cabeçalho do produto -->
<div class="prod-show-header">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div class="d-flex gap-4 align-items-start">
            <?php if ($produto->imagem_principal): ?>
            <img src="<?= $esc($produto->imagem_principal) ?>" alt="" style="width:80px;height:80px;border-radius:12px;object-fit:cover;border:2px solid rgba(255,255,255,.3);">
            <?php else: ?>
            <div style="width:80px;height:80px;border-radius:12px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:28px;">
                <i class="fas fa-<?= $produto->tipo === 'servico' ? 'tools' : 'box' ?>"></i>
            </div>
            <?php endif; ?>
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span style="background:rgba(255,255,255,.2);border-radius:6px;padding:2px 10px;font-size:12px;font-weight:600;"><?= $esc($produto->codigo) ?></span>
                    <span class="badge bg-<?= $statusColor ?> badge-status"><?= $statusLabel ?></span>
                    <span style="background:rgba(255,255,255,.15);border-radius:6px;padding:2px 10px;font-size:12px;"><?= $esc($tipoLabels[$produto->tipo] ?? $produto->tipo) ?></span>
                </div>
                <h4 class="fw-bold mb-1"><?= $esc($produto->nome) ?></h4>
                <?php if ($produto->nome_tecnico): ?>
                <div style="opacity:.8;font-size:14px;"><?= $esc($produto->nome_tecnico) ?></div>
                <?php endif; ?>
                <?php if ($produto->marca || $produto->modelo): ?>
                <div style="opacity:.7;font-size:13px;margin-top:4px;">
                    <?= $esc($produto->marca) ?><?= ($produto->marca && $produto->modelo) ? ' · ' : '' ?><?= $esc($produto->modelo) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-end">
            <div class="preco-custo-sm">Preço de Venda</div>
            <div class="preco-destaque"><?= $fmtR($produto->preco_venda) ?></div>
            <div style="opacity:.8;font-size:13px;">Markup: <?= $fmtN($markup) ?>% · Margem: <?= $fmtN($margemBruta) ?>%</div>
            <?php if ($produto->preco_sugerido && abs($precoVenda - (float)$produto->preco_sugerido) > 0.01): ?>
            <div style="background:rgba(255,255,255,.15);border-radius:8px;padding:4px 10px;font-size:12px;margin-top:6px;">
                <i class="fas fa-lightbulb me-1"></i>Sugerido: <?= $fmtR($produto->preco_sugerido) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Alertas -->
<?php if ($estoqueBaixo): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>Estoque abaixo do mínimo!</strong> Atual: <?= $fmtN($produto->estoque_atual) ?> <?= $esc($produto->unidade_medida) ?> · Mínimo: <?= $fmtN($produto->estoque_minimo) ?>
</div>
<?php endif; ?>

<!-- Botões de ação -->
<div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="/estoque/produtos/<?= $produto->id ?>/edit" class="btn btn-primary">
        <i class="fas fa-edit me-1"></i> Editar
    </a>
    <a href="/estoque/produtos/<?= $produto->id ?>/edit?tab=componentes" class="btn btn-outline-primary">
        <i class="fas fa-puzzle-piece me-1"></i> Componentes
    </a>
    <a href="/estoque/produtos/<?= $produto->id ?>/edit?tab=comissao" class="btn btn-outline-success">
        <i class="fas fa-hand-holding-usd me-1"></i> Comissão
    </a>
    <button type="button" class="btn btn-outline-secondary" onclick="duplicarProduto()">
        <i class="fas fa-copy me-1"></i> Duplicar
    </button>
    <button type="button" class="btn btn-outline-danger ms-auto" onclick="excluirProduto()">
        <i class="fas fa-trash me-1"></i> Excluir
    </button>
</div>

<!-- KPIs rápidos -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="kpi-mini">
            <div class="kpi-val"><?= $fmtN($produto->estoque_atual) ?></div>
            <div class="kpi-lbl"><?= $esc($produto->unidade_medida) ?> em Estoque</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-mini">
            <div class="kpi-val"><?= $fmtN($produto->total_vendido) ?></div>
            <div class="kpi-lbl">Total Vendido</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-mini">
            <div class="kpi-val"><?= $fmtR($produto->receita_total) ?></div>
            <div class="kpi-lbl">Receita Total</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-mini">
            <div class="kpi-val"><?= $fmtN($produto->taxa_conversao, 1) ?>%</div>
            <div class="kpi-lbl">Taxa de Conversão</div>
        </div>
    </div>
</div>

<!-- Corpo principal -->
<div class="row g-3">

    <!-- Coluna esquerda -->
    <div class="col-md-8">

        <!-- Identificação -->
        <div class="info-card mb-3">
            <div class="card-title"><i class="fas fa-id-card"></i> Identificação</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row"><span class="label">Código</span><span class="value"><?= $esc($produto->codigo) ?></span></div>
                    <div class="info-row"><span class="label">Tipo</span><span class="value"><?= $esc($tipoLabels[$produto->tipo] ?? $produto->tipo) ?></span></div>
                    <div class="info-row"><span class="label">Categoria</span><span class="value"><?= $esc($categLabels[$produto->categoria] ?? $produto->categoria) ?></span></div>
                    <div class="info-row"><span class="label">Marca</span><span class="value"><?= $esc($produto->marca ?: '—') ?></span></div>
                    <div class="info-row"><span class="label">Modelo</span><span class="value"><?= $esc($produto->modelo ?: '—') ?></span></div>
                </div>
                <div class="col-md-6">
                    <div class="info-row"><span class="label">Unidade</span><span class="value"><?= $esc($produto->unidade_medida) ?></span></div>
                    <div class="info-row"><span class="label">País de Origem</span><span class="value"><?= $esc($produto->pais_origem ?: '—') ?></span></div>
                    <div class="info-row"><span class="label">NCM</span><span class="value"><?= $esc($produto->ncm ?: '—') ?></span></div>
                    <div class="info-row"><span class="label">Fabricante</span><span class="value"><?= $esc($produto->fabricante_nome ?: '—') ?></span></div>
                    <div class="info-row"><span class="label">Cadastrado em</span><span class="value"><?= $fmtDt($produto->created_at) ?></span></div>
                </div>
            </div>
            <?php if ($produto->descricao_curta): ?>
            <div class="mt-3 p-3 bg-light rounded" style="font-size:14px;"><?= $esc($produto->descricao_curta) ?></div>
            <?php endif; ?>
            <?php if ($produto->descricao_completa): ?>
            <div class="mt-2" style="font-size:14px;white-space:pre-line;"><?= $esc($produto->descricao_completa) ?></div>
            <?php endif; ?>
        </div>

        <!-- Preços -->
        <div class="info-card mb-3">
            <div class="card-title"><i class="fas fa-tag"></i> Formação de Preço</div>
            <div class="row">
                <div class="col-md-4">
                    <div class="info-row"><span class="label">Custo</span><span class="value text-danger"><?= $fmtR($produto->preco_custo) ?></span></div>
                    <div class="info-row"><span class="label">Despesas Acess.</span><span class="value"><?= $fmtR($produto->despesas_acessorias) ?></span></div>
                    <div class="info-row"><span class="label">Impostos (%)</span><span class="value"><?= $fmtN($produto->impostos_percentual) ?>%</span></div>
                </div>
                <div class="col-md-4">
                    <div class="info-row"><span class="label">Markup</span><span class="value text-primary fw-bold"><?= $fmtN($produto->markup_percentual) ?>%</span></div>
                    <div class="info-row"><span class="label">Margem Bruta</span><span class="value fw-bold"><?= $fmtN($margemBruta) ?>%</span></div>
                    <div class="info-row"><span class="label">Preço Mínimo</span><span class="value"><?= $fmtR($produto->preco_minimo_venda) ?></span></div>
                </div>
                <div class="col-md-4">
                    <div class="info-row"><span class="label">Preço de Venda</span><span class="value text-success fw-bold"><?= $fmtR($produto->preco_venda) ?></span></div>
                    <div class="info-row"><span class="label">Preço Sugerido</span><span class="value text-warning fw-bold"><?= $fmtR($produto->preco_sugerido) ?></span></div>
                    <div class="info-row"><span class="label">Moeda</span><span class="value"><?= $esc($produto->moeda ?: 'BRL') ?></span></div>
                </div>
            </div>
        </div>

        <!-- Estoque -->
        <?php if ($produto->controla_estoque): ?>
        <div class="info-card mb-3">
            <div class="card-title"><i class="fas fa-warehouse"></i> Estoque</div>
            <div class="row">
                <div class="col-md-3">
                    <div class="info-row"><span class="label">Atual</span><span class="value fw-bold <?= $estoqueBaixo ? 'text-danger' : 'text-success' ?>"><?= $fmtN($produto->estoque_atual) ?></span></div>
                </div>
                <div class="col-md-3">
                    <div class="info-row"><span class="label">Mínimo</span><span class="value"><?= $fmtN($produto->estoque_minimo) ?></span></div>
                </div>
                <div class="col-md-3">
                    <div class="info-row"><span class="label">Máximo</span><span class="value"><?= $fmtN($produto->estoque_maximo) ?></span></div>
                </div>
                <div class="col-md-3">
                    <div class="info-row"><span class="label">Reposição</span><span class="value"><?= $fmtN($produto->ponto_reposicao) ?></span></div>
                </div>
                <div class="col-md-4">
                    <div class="info-row"><span class="label">Lead Time</span><span class="value"><?= $produto->lead_time_dias ? $produto->lead_time_dias . ' dias' : '—' ?></span></div>
                </div>
                <div class="col-md-4">
                    <div class="info-row"><span class="label">Localização</span><span class="value"><?= $esc($produto->localizacao_estoque ?: '—') ?></span></div>
                </div>
                <div class="col-md-4">
                    <div class="info-row"><span class="label">Unid. Compra</span><span class="value"><?= $esc($produto->unidade_compra ?: '—') ?></span></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Componentes -->
        <?php if (!empty($componentes)): ?>
        <div class="info-card mb-3">
            <div class="card-title"><i class="fas fa-puzzle-piece"></i> Componentes (<?= count($componentes) ?>)</div>
            <?php foreach ($componentes as $c): ?>
            <div class="comp-item">
                <div class="comp-img">
                    <?php if ($c->comp_imagem): ?>
                    <img src="<?= $esc($c->comp_imagem) ?>" style="width:36px;height:36px;object-fit:cover;border-radius:8px;" alt="">
                    <?php else: ?>
                    <i class="fas fa-box"></i>
                    <?php endif; ?>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold" style="font-size:14px;"><?= $esc($c->comp_nome) ?></div>
                    <div class="text-muted" style="font-size:12px;"><?= $esc($c->comp_codigo) ?></div>
                </div>
                <div class="text-end">
                    <div style="font-size:13px;">Qtd: <strong><?= $fmtN($c->quantidade) ?></strong></div>
                    <?php if ($c->preco_venda_proprio): ?>
                    <div class="text-success fw-semibold" style="font-size:13px;"><?= $fmtR($c->preco_venda_proprio) ?></div>
                    <?php endif; ?>
                    <div>
                        <span class="badge bg-<?= $c->obrigatorio ? 'primary' : 'secondary' ?> badge-sm"><?= $c->obrigatorio ? 'Obrigatório' : 'Opcional' ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Comissões -->
        <?php if (!empty($comissoes)): ?>
        <div class="info-card mb-3">
            <div class="card-title"><i class="fas fa-hand-holding-usd"></i> Regras de Comissão (<?= count($comissoes) ?>)</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Descrição</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Colaborador</th>
                            <th>Vigência</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($comissoes as $c): ?>
                    <tr>
                        <td><?= $esc($c->descricao) ?></td>
                        <td><small><?= $esc($comTipoLabels[$c->tipo] ?? $c->tipo) ?></small></td>
                        <td class="fw-semibold"><?= $fmtN($c->valor) ?><?= $c->tipo === 'valor_fixo' ? '' : '%' ?></td>
                        <td><small><?= $esc($c->colaborador_nome ?? 'Todos') ?></small></td>
                        <td><small><?= $c->vigencia_inicio ? $fmtDt($c->vigencia_inicio) . ' — ' . $fmtDt($c->vigencia_fim) : '—' ?></small></td>
                        <td><span class="badge bg-<?= $c->ativo ? 'success' : 'secondary' ?>"><?= $c->ativo ? 'Ativa' : 'Inativa' ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Histórico de movimentações -->
        <?php if (!empty($movimentacoes)): ?>
        <div class="info-card mb-3">
            <div class="card-title"><i class="fas fa-exchange-alt"></i> Últimas Movimentações</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Tipo</th><th>Qtd</th><th>Saldo</th><th>Motivo</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($movimentacoes as $m): ?>
                    <tr>
                        <td><small><?= $fmtDt($m->created_at) ?></small></td>
                        <td><span class="badge bg-<?= in_array($m->tipo, ['entrada','ajuste_entrada']) ? 'success' : 'danger' ?>"><?= $esc($m->tipo) ?></span></td>
                        <td class="<?= $m->quantidade > 0 ? 'text-success' : 'text-danger' ?> fw-semibold"><?= ($m->quantidade > 0 ? '+' : '') . $fmtN($m->quantidade) ?></td>
                        <td><?= $fmtN($m->saldo_apos) ?></td>
                        <td><small><?= $esc($m->motivo) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Coluna direita -->
    <div class="col-md-4">

        <!-- ANVISA -->
        <?php if ($produto->anvisa_registro || $produto->requer_anvisa): ?>
        <div class="info-card mb-3">
            <div class="card-title"><i class="fas fa-shield-alt"></i> Registro ANVISA</div>
            <div class="info-row"><span class="label">Nº Registro</span><span class="value"><?= $esc($produto->anvisa_registro ?: '—') ?></span></div>
            <div class="info-row"><span class="label">Classe de Risco</span><span class="value"><?= $produto->anvisa_classe ? 'Classe ' . $esc($produto->anvisa_classe) : '—' ?></span></div>
            <div class="info-row"><span class="label">Validade</span>
                <span class="value <?= ($produto->anvisa_validade && strtotime($produto->anvisa_validade) < time()) ? 'text-danger fw-bold' : '' ?>">
                    <?= $fmtDt($produto->anvisa_validade) ?>
                    <?= ($produto->anvisa_validade && strtotime($produto->anvisa_validade) < time()) ? ' ⚠️ Vencido' : '' ?>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Garantia -->
        <?php if ($produto->garantia_meses): ?>
        <div class="info-card mb-3">
            <div class="card-title"><i class="fas fa-certificate"></i> Garantia</div>
            <div class="info-row"><span class="label">Garantia</span><span class="value"><?= $produto->garantia_meses ?> meses</span></div>
            <?php if ($produto->garantia_estendida_meses): ?>
            <div class="info-row"><span class="label">Garantia Estendida</span><span class="value"><?= $produto->garantia_estendida_meses ?> meses</span></div>
            <?php endif; ?>
            <?php if ($produto->assistencia_tecnica): ?>
            <div class="info-row"><span class="label">Assistência</span><span class="value"><?= $esc($produto->assistencia_tecnica) ?></span></div>
            <?php endif; ?>
            <div class="d-flex gap-2 mt-2 flex-wrap">
                <?php if ($produto->manual_url): ?>
                <a href="<?= $esc($produto->manual_url) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-book me-1"></i>Manual</a>
                <?php endif; ?>
                <?php if ($produto->ficha_tecnica_url): ?>
                <a href="<?= $esc($produto->ficha_tecnica_url) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-alt me-1"></i>Ficha Técnica</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Depreciação -->
        <?php if ($produto->controla_depreciacao): ?>
        <div class="info-card mb-3" style="border-color:#fed7aa;">
            <div class="card-title"><i class="fas fa-chart-line" style="color:#f59e0b;"></i> Depreciação</div>
            <div class="info-row"><span class="label">Vida Útil</span><span class="value"><?= $produto->vida_util_meses ?> meses</span></div>
            <div class="info-row"><span class="label">Deprec. Mensal</span><span class="value text-warning fw-bold"><?= $fmtR($produto->depreciacao_mensal) ?></span></div>
            <div class="info-row"><span class="label">Valor Residual</span><span class="value"><?= $fmtR($produto->valor_residual) ?></span></div>
            <div class="info-row"><span class="label">Método</span><span class="value"><?= $esc($produto->metodo_depreciacao) ?></span></div>
            <?php if ($produto->alerta_substituicao_meses): ?>
            <div class="info-row"><span class="label">Alerta Substituição</span><span class="value"><?= $produto->alerta_substituicao_meses ?> meses antes</span></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Inteligência de Vendas -->
        <div class="info-card mb-3" style="border-color:#c4b5fd;">
            <div class="card-title"><i class="fas fa-brain" style="color:#7c3aed;"></i> Inteligência de Vendas</div>
            <?php if ($produto->score_venda): ?>
            <div class="mb-2">
                <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                    <span>Score de Venda</span>
                    <strong><?= (int)$produto->score_venda ?>/100</strong>
                </div>
                <div class="score-bar">
                    <div class="score-fill" style="width:<?= (int)$produto->score_venda ?>%"></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($produto->ciclo_venda_dias): ?>
            <div class="info-row"><span class="label">Ciclo de Venda</span><span class="value"><?= $produto->ciclo_venda_dias ?> dias</span></div>
            <?php endif; ?>
            <div class="info-row"><span class="label">Última Venda</span><span class="value"><?= $fmtDt($produto->ultima_venda_em) ?></span></div>
            <?php if ($produto->palavras_chave): ?>
            <div class="mt-2">
                <?php foreach (explode(',', $produto->palavras_chave) as $tag): ?>
                <span class="tag-badge"><?= $esc(trim($tag)) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($produto->diferenciais): ?>
            <div class="mt-2" style="font-size:13px;color:#5b21b6;background:#ede9fe;border-radius:8px;padding:8px 12px;">
                <strong>Diferenciais:</strong><br><?= $esc($produto->diferenciais) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Serviços adicionais -->
        <?php if ($produto->requer_instalacao || $produto->requer_treinamento): ?>
        <div class="info-card mb-3">
            <div class="card-title"><i class="fas fa-tasks"></i> Serviços Necessários</div>
            <?php if ($produto->requer_instalacao): ?>
            <div class="d-flex align-items-center gap-2 mb-1"><i class="fas fa-check-circle text-success"></i> Requer Instalação</div>
            <?php endif; ?>
            <?php if ($produto->requer_treinamento): ?>
            <div class="d-flex align-items-center gap-2"><i class="fas fa-check-circle text-success"></i> Requer Treinamento</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Visibilidade -->
        <div class="info-card mb-3">
            <div class="card-title"><i class="fas fa-eye"></i> Visibilidade</div>
            <div class="info-row">
                <span class="label">Em Propostas</span>
                <span class="value"><span class="badge bg-<?= $produto->visivel_proposta ? 'success' : 'secondary' ?>"><?= $produto->visivel_proposta ? 'Visível' : 'Oculto' ?></span></span>
            </div>
            <div class="info-row">
                <span class="label">No Catálogo</span>
                <span class="value"><span class="badge bg-<?= $produto->visivel_catalogo ? 'success' : 'secondary' ?>"><?= $produto->visivel_catalogo ? 'Visível' : 'Oculto' ?></span></span>
            </div>
            <?php if ($produto->video_url): ?>
            <a href="<?= $esc($produto->video_url) ?>" target="_blank" class="btn btn-sm btn-outline-danger w-100 mt-2">
                <i class="fab fa-youtube me-1"></i> Assistir Vídeo
            </a>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Modal de movimentação de estoque -->
<div class="modal fade" id="modalMovimentacao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i>Movimentação de Estoque</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <select id="movTipo" class="form-select">
                        <option value="entrada">Entrada</option>
                        <option value="saida">Saída</option>
                        <option value="ajuste_entrada">Ajuste de Entrada</option>
                        <option value="ajuste_saida">Ajuste de Saída</option>
                        <option value="transferencia">Transferência</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Quantidade</label>
                    <input type="number" id="movQtd" class="form-control" min="0.001" step="0.001" value="1">
                </div>
                <div class="mb-3">
                    <label class="form-label">Custo Unitário (R$)</label>
                    <input type="text" id="movCusto" class="form-control" placeholder="Opcional">
                </div>
                <div class="mb-3">
                    <label class="form-label">Motivo / Documento</label>
                    <input type="text" id="movMotivo" class="form-control" placeholder="Ex: NF 12345, Ajuste inventário">
                </div>
                <div class="mb-3">
                    <label class="form-label">Observações</label>
                    <textarea id="movObs" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarMovimentacao()">
                    <i class="fas fa-save me-1"></i> Registrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const PROD_ID = <?= $produto->id ?>;

function excluirProduto() {
    if (!confirm('Excluir este produto permanentemente? Esta ação não pode ser desfeita.')) return;
    fetch('/estoque/produtos/' + PROD_ID + '/delete', { method: 'POST' })
        .then(r => r.json())
        .then(res => {
            if (res.success) window.location.href = '/estoque/produtos';
            else alert(res.message || 'Erro ao excluir.');
        });
}

function duplicarProduto() {
    if (!confirm('Duplicar este produto? Um novo produto será criado com os mesmos dados.')) return;
    fetch('/estoque/produtos/' + PROD_ID + '/duplicar', { method: 'POST' })
        .then(r => r.json())
        .then(res => {
            if (res.success) window.location.href = '/estoque/produtos/' + res.novo_id + '/edit';
            else alert(res.message || 'Erro ao duplicar.');
        });
}

function salvarMovimentacao() {
    const data = new FormData();
    data.append('tipo',       document.getElementById('movTipo').value);
    data.append('quantidade', document.getElementById('movQtd').value);
    data.append('custo',      document.getElementById('movCusto').value);
    data.append('motivo',     document.getElementById('movMotivo').value);
    data.append('observacoes',document.getElementById('movObs').value);

    fetch('/estoque/produtos/' + PROD_ID + '/movimentacao', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalMovimentacao')).hide();
                location.reload();
            } else {
                alert(res.message || 'Erro ao registrar movimentação.');
            }
        });
}
</script>
