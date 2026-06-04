<?php
$isEdit  = $produto !== null;
$action  = $isEdit ? "/estoque/produtos/{$produto->id}/update" : "/estoque/produtos";
$esc     = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$val     = fn($k) => $esc($produto->$k ?? '');
$fmtNum  = fn($v, $dec=2) => $v !== null && $v !== '' ? number_format((float)$v, $dec, ',', '.') : '';
$checked = fn($k, $def=0) => ((int)($produto->$k ?? $def)) ? 'checked' : '';
$selOpt  = fn($k, $opt) => ($produto->$k ?? '') === $opt ? 'selected' : '';
$activeTab = $tab ?? 'dados';

$categOptions = [
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
?>
<style>
.prod-tabs { display:flex; gap:4px; border-bottom:2px solid #e5e7eb; margin-bottom:24px; flex-wrap:wrap; }
.prod-tab  { padding:10px 20px; border:none; background:none; color:#6b7280; font-size:14px; font-weight:500; cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; transition:.2s; display:flex; align-items:center; gap:6px; }
.prod-tab:hover { color:#2563eb; }
.prod-tab.active { color:#2563eb; border-bottom-color:#2563eb; font-weight:600; }
.prod-tab-pane { display:none; }
.prod-tab-pane.active { display:block; }
.section-title { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#374151; margin:24px 0 14px; padding-bottom:6px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; gap:8px; }
.section-title i { color:#2563eb; }
.calc-box { background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; padding:16px; }
.calc-row { display:flex; justify-content:space-between; align-items:center; padding:4px 0; font-size:14px; }
.calc-row.total { font-weight:700; font-size:16px; border-top:1px solid #bae6fd; margin-top:8px; padding-top:8px; }
.calc-val { font-weight:600; color:#0369a1; }
.preco-sugerido-box { background:#fef3c7; border:1px solid #fcd34d; border-radius:10px; padding:14px 16px; margin-top:12px; }
.preco-sugerido-val { font-size:22px; font-weight:800; color:#92400e; }
.comp-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px 16px; display:flex; align-items:center; gap:12px; }
.comp-card-img { width:40px; height:40px; border-radius:8px; object-fit:cover; background:#f3f4f6; display:flex; align-items:center; justify-content:center; color:#9ca3af; flex-shrink:0; }
.badge-obrig { background:#dbeafe; color:#1e40af; border-radius:20px; padding:2px 8px; font-size:11px; }
.badge-opc   { background:#f3f4f6; color:#374151; border-radius:20px; padding:2px 8px; font-size:11px; }
.com-rule-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px 16px; }
.depreciacao-box { background:#fff7ed; border:1px solid #fed7aa; border-radius:10px; padding:16px; }
.ia-box { background:linear-gradient(135deg,#ede9fe,#dbeafe); border-radius:12px; padding:20px; border:1px solid #c4b5fd; }
.ia-box h6 { color:#5b21b6; font-weight:700; }
.score-bar { height:10px; border-radius:5px; background:#e5e7eb; overflow:hidden; }
.score-fill { height:100%; background:linear-gradient(90deg,#2563eb,#7c3aed); border-radius:5px; transition:.5s; }
.img-preview-box { width:120px; height:120px; border-radius:12px; border:2px dashed #d1d5db; display:flex; align-items:center; justify-content:center; overflow:hidden; cursor:pointer; position:relative; }
.img-preview-box img { width:100%; height:100%; object-fit:cover; }
.img-preview-box .overlay { position:absolute; inset:0; background:rgba(0,0,0,.4); display:flex; align-items:center; justify-content:center; color:#fff; opacity:0; transition:.2s; }
.img-preview-box:hover .overlay { opacity:1; }
</style>

<form method="POST" action="<?= $action ?>" enctype="multipart/form-data" id="formProduto">

<!-- Abas -->
<div class="prod-tabs">
    <button type="button" class="prod-tab <?= $activeTab==='dados' ? 'active':'' ?>" onclick="switchTab('dados')">
        <i class="fas fa-info-circle"></i> Dados Gerais
    </button>
    <button type="button" class="prod-tab <?= $activeTab==='precos' ? 'active':'' ?>" onclick="switchTab('precos')">
        <i class="fas fa-tag"></i> Preços e Estoque
    </button>
    <?php if ($isEdit): ?>
    <button type="button" class="prod-tab <?= $activeTab==='componentes' ? 'active':'' ?>" onclick="switchTab('componentes')">
        <i class="fas fa-puzzle-piece"></i> Componentes
        <?php if (count($componentes)): ?><span class="badge bg-primary ms-1"><?= count($componentes) ?></span><?php endif; ?>
    </button>
    <button type="button" class="prod-tab <?= $activeTab==='comissao' ? 'active':'' ?>" onclick="switchTab('comissao')">
        <i class="fas fa-hand-holding-usd"></i> Comissão
        <?php if (count($comissoes)): ?><span class="badge bg-success ms-1"><?= count($comissoes) ?></span><?php endif; ?>
    </button>
    <?php endif; ?>
    <button type="button" class="prod-tab <?= $activeTab==='tecnico' ? 'active':'' ?>" onclick="switchTab('tecnico')">
        <i class="fas fa-cogs"></i> Técnico
    </button>
    <button type="button" class="prod-tab <?= $activeTab==='ia' ? 'active':'' ?>" onclick="switchTab('ia')">
        <i class="fas fa-brain"></i> Inteligência
    </button>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     ABA 1 — DADOS GERAIS
════════════════════════════════════════════════════════════════════════════ -->
<div class="prod-tab-pane <?= $activeTab==='dados' ? 'active':'' ?>" id="tab-dados">
    <div class="row g-4">
        <div class="col-md-8">
            <!-- Identificação -->
            <div class="section-title"><i class="fas fa-id-card"></i> Identificação</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Código <span class="text-danger">*</span></label>
                    <input type="text" name="codigo" class="form-control" value="<?= $esc($proximo_codigo) ?>" readonly>
                    <small class="text-muted">Gerado automaticamente</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo <span class="text-danger">*</span></label>
                    <select name="tipo" class="form-select" id="selectTipo" onchange="toggleTipo()">
                        <option value="produto"  <?= $selOpt('tipo','produto') ?>>Produto</option>
                        <option value="servico"  <?= $selOpt('tipo','servico') ?>>Serviço</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Categoria <span class="text-danger">*</span></label>
                    <select name="categoria" class="form-select">
                        <?php foreach ($categOptions as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $selOpt('categoria',$k) ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Nome do Produto/Serviço <span class="text-danger">*</span></label>
                    <input type="text" name="nome" class="form-control" value="<?= $val('nome') ?>" required placeholder="Ex: Monitor Multiparamétrico Adulto">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nome Técnico / Científico</label>
                    <input type="text" name="nome_tecnico" class="form-control" value="<?= $val('nome_tecnico') ?>" placeholder="Ex: Monitor de Sinais Vitais">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Marca</label>
                    <input type="text" name="marca" class="form-control" value="<?= $val('marca') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Modelo</label>
                    <input type="text" name="modelo" class="form-control" value="<?= $val('modelo') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Descrição Curta</label>
                    <input type="text" name="descricao_curta" class="form-control" maxlength="500" value="<?= $val('descricao_curta') ?>" placeholder="Resumo para propostas e catálogo (máx. 500 caracteres)">
                </div>
                <div class="col-12">
                    <label class="form-label">Descrição Completa</label>
                    <textarea name="descricao_completa" class="form-control" rows="4" placeholder="Descrição detalhada, especificações, diferenciais…"><?= $val('descricao_completa') ?></textarea>
                </div>
            </div>

            <!-- Fabricante / Fornecedor -->
            <div class="section-title"><i class="fas fa-industry"></i> Fabricante / Fornecedor</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Fabricante (cadastrado)</label>
                    <select name="fabricante_id" class="form-select" onchange="preencherFabricante(this)">
                        <option value="">— Selecionar —</option>
                        <?php foreach ($fornecedores as $f): ?>
                        <option value="<?= $f->id ?>" <?= (($produto->fabricante_id ?? '') == $f->id) ? 'selected':'' ?>><?= $esc($f->nome) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nome do Fabricante (manual)</label>
                    <input type="text" name="fabricante_nome" class="form-control" id="fabricante_nome" value="<?= $val('fabricante_nome') ?>" placeholder="Ou digitar diretamente">
                </div>
                <div class="col-md-4">
                    <label class="form-label">País de Origem</label>
                    <input type="text" name="pais_origem" class="form-control" value="<?= $val('pais_origem') ?>" placeholder="Ex: Brasil, EUA, Alemanha">
                </div>
                <div class="col-md-4">
                    <label class="form-label">NCM</label>
                    <input type="text" name="ncm" class="form-control" value="<?= $val('ncm') ?>" placeholder="0000.00.00" maxlength="10">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Unidade de Medida <span class="text-danger">*</span></label>
                    <select name="unidade_medida" class="form-select">
                        <?php foreach (['UN','KG','G','L','ML','M','CM','CX','KIT','PAR','PCT','HR','MÊS'] as $u): ?>
                        <option value="<?= $u ?>" <?= $selOpt('unidade_medida',$u) ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- ANVISA (apenas para equipamentos) -->
            <div class="section-title" id="anvisaSection"><i class="fas fa-shield-alt"></i> Registro ANVISA</div>
            <div class="row g-3" id="anvisaFields">
                <div class="col-md-4">
                    <label class="form-label">Nº Registro ANVISA</label>
                    <input type="text" name="anvisa_registro" class="form-control" value="<?= $val('anvisa_registro') ?>" placeholder="Ex: 80000000000">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Classe de Risco</label>
                    <select name="anvisa_classe" class="form-select">
                        <option value="">—</option>
                        <?php foreach (['I','II','III','IV'] as $c): ?>
                        <option value="<?= $c ?>" <?= $selOpt('anvisa_classe',$c) ?>>Classe <?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Validade do Registro</label>
                    <input type="date" name="anvisa_validade" class="form-control" value="<?= $val('anvisa_validade') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="requer_anvisa" id="requerAnvisa" value="1" <?= $checked('requer_anvisa') ?>>
                        <label class="form-check-label" for="requerAnvisa">Obrigatório</label>
                    </div>
                </div>
            </div>

            <!-- Garantia -->
            <div class="section-title"><i class="fas fa-certificate"></i> Garantia e Suporte</div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Garantia (meses)</label>
                    <input type="number" name="garantia_meses" class="form-control" min="0" value="<?= $val('garantia_meses') ?>" placeholder="12">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Garantia Estendida (meses)</label>
                    <input type="number" name="garantia_estendida_meses" class="form-control" min="0" value="<?= $val('garantia_estendida_meses') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Assistência Técnica</label>
                    <input type="text" name="assistencia_tecnica" class="form-control" value="<?= $val('assistencia_tecnica') ?>" placeholder="Nome/contato da assistência técnica autorizada">
                </div>
                <div class="col-md-6">
                    <label class="form-label">URL do Manual</label>
                    <input type="url" name="manual_url" class="form-control" value="<?= $val('manual_url') ?>" placeholder="https://…">
                </div>
                <div class="col-md-6">
                    <label class="form-label">URL da Ficha Técnica</label>
                    <input type="url" name="ficha_tecnica_url" class="form-control" value="<?= $val('ficha_tecnica_url') ?>" placeholder="https://…">
                </div>
            </div>

            <!-- Flags de serviços adicionais -->
            <div class="section-title"><i class="fas fa-tasks"></i> Serviços Adicionais Necessários</div>
            <div class="row g-3">
                <div class="col-auto">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="requer_instalacao" id="requerInstalacao" value="1" <?= $checked('requer_instalacao') ?>>
                        <label class="form-check-label" for="requerInstalacao">Requer Instalação</label>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="requer_treinamento" id="requerTreinamento" value="1" <?= $checked('requer_treinamento') ?>>
                        <label class="form-check-label" for="requerTreinamento">Requer Treinamento</label>
                    </div>
                </div>
            </div>

            <!-- Observações internas -->
            <div class="section-title"><i class="fas fa-lock"></i> Observações Internas</div>
            <textarea name="observacoes_internas" class="form-control" rows="3" placeholder="Notas internas (não visíveis ao cliente)"><?= $val('observacoes_internas') ?></textarea>
        </div>

        <!-- Coluna lateral: imagem + status -->
        <div class="col-md-4">
            <div class="section-title"><i class="fas fa-image"></i> Imagem Principal</div>
            <div class="d-flex flex-column align-items-center gap-3">
                <div class="img-preview-box" onclick="document.getElementById('inputImagem').click()">
                    <?php if ($produto && $produto->imagem_principal): ?>
                    <img src="<?= $esc($produto->imagem_principal) ?>" id="imgPreview" alt="">
                    <?php else: ?>
                    <div id="imgPlaceholder" style="text-align:center;color:#9ca3af;">
                        <i class="fas fa-camera fa-2x mb-2"></i><br><small>Clique para adicionar</small>
                    </div>
                    <img src="" id="imgPreview" alt="" style="display:none">
                    <?php endif; ?>
                    <div class="overlay"><i class="fas fa-camera fa-lg"></i></div>
                </div>
                <input type="file" name="imagem_principal" id="inputImagem" accept="image/*" class="d-none" onchange="previewImagem(this)">
                <small class="text-muted text-center">PNG, JPG ou WEBP — máx. 2MB</small>
            </div>

            <div class="section-title mt-4"><i class="fas fa-toggle-on"></i> Status e Visibilidade</div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="ativo"          <?= $selOpt('status','ativo') ?>>Ativo</option>
                    <option value="em_homologacao" <?= $selOpt('status','em_homologacao') ?>>Em Homologação</option>
                    <option value="inativo"        <?= $selOpt('status','inativo') ?>>Inativo</option>
                    <option value="descontinuado"  <?= $selOpt('status','descontinuado') ?>>Descontinuado</option>
                </select>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="visivel_proposta" id="visivelProposta" value="1" <?= $checked('visivel_proposta',1) ?>>
                <label class="form-check-label" for="visivelProposta">Visível em Propostas</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="visivel_catalogo" id="visivelCatalogo" value="1" <?= $checked('visivel_catalogo') ?>>
                <label class="form-check-label" for="visivelCatalogo">Visível no Catálogo</label>
            </div>

            <div class="section-title mt-4"><i class="fas fa-link"></i> Mídia e Links</div>
            <div class="mb-3">
                <label class="form-label small">URL do Vídeo (YouTube/Vimeo)</label>
                <input type="url" name="video_url" class="form-control form-control-sm" value="<?= $val('video_url') ?>" placeholder="https://youtube.com/…">
            </div>
            <div class="mb-3">
                <label class="form-label small">URL do Catálogo PDF</label>
                <input type="url" name="catalogo_pdf_url" class="form-control form-control-sm" value="<?= $val('catalogo_pdf_url') ?>" placeholder="https://…">
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     ABA 2 — PREÇOS E ESTOQUE
════════════════════════════════════════════════════════════════════════════ -->
<div class="prod-tab-pane <?= $activeTab==='precos' ? 'active':'' ?>" id="tab-precos">
    <div class="row g-4">
        <div class="col-md-7">
            <!-- Preços -->
            <div class="section-title"><i class="fas fa-dollar-sign"></i> Formação de Preço</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Preço de Custo (R$) <span class="text-danger">*</span></label>
                    <input type="text" name="preco_custo" id="precoCusto" class="form-control money-mask" value="<?= $fmtNum($produto->preco_custo ?? 0) ?>" oninput="calcularPrecos()" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Despesas Acessórias (R$)</label>
                    <input type="text" name="despesas_acessorias" id="despesasAcess" class="form-control money-mask" value="<?= $fmtNum($produto->despesas_acessorias ?? 0) ?>" oninput="calcularPrecos()" placeholder="Frete, seguro, impostos entrada">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Impostos sobre Venda (%)</label>
                    <input type="text" name="impostos_percentual" id="impostosPerc" class="form-control" value="<?= $fmtNum($produto->impostos_percentual ?? 0) ?>" oninput="calcularPrecos()" placeholder="Ex: 12,5">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Markup (%) <span class="text-danger">*</span></label>
                    <input type="text" name="markup_percentual" id="markupPerc" class="form-control fw-bold" value="<?= $fmtNum($produto->markup_percentual ?? 0) ?>" oninput="calcularPrecos()" placeholder="Ex: 45,00">
                    <small class="text-muted">Sobre o custo total</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Preço de Venda (R$) <span class="text-danger">*</span></label>
                    <input type="text" name="preco_venda" id="precoVenda" class="form-control fw-bold text-primary" value="<?= $fmtNum($produto->preco_venda ?? 0) ?>" oninput="calcularPorVenda()" required>
                    <small class="text-muted">Editável — recalcula markup</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Preço Mínimo de Venda (R$)</label>
                    <input type="text" name="preco_minimo_venda" id="precoMinimo" class="form-control" value="<?= $fmtNum($produto->preco_minimo_venda ?? 0) ?>" placeholder="Piso — não vender abaixo">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Moeda</label>
                    <select name="moeda" class="form-select">
                        <?php foreach (['BRL'=>'BRL — Real','USD'=>'USD — Dólar','EUR'=>'EUR — Euro'] as $k=>$v): ?>
                        <option value="<?= $k ?>" <?= $selOpt('moeda',$k) ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Calculadora de preço -->
            <div class="calc-box mt-4">
                <div class="fw-semibold mb-3"><i class="fas fa-calculator me-2 text-primary"></i>Calculadora de Preço</div>
                <div class="calc-row"><span>Preço de Custo</span><span class="calc-val" id="calcCusto">R$ 0,00</span></div>
                <div class="calc-row"><span>+ Despesas Acessórias</span><span class="calc-val" id="calcDesp">R$ 0,00</span></div>
                <div class="calc-row"><span>= Custo Total</span><span class="calc-val" id="calcCustoTotal">R$ 0,00</span></div>
                <div class="calc-row"><span>+ Markup aplicado</span><span class="calc-val" id="calcMarkupVal">R$ 0,00</span></div>
                <div class="calc-row total"><span>Preço Sugerido</span><span class="calc-val" id="calcSugerido">R$ 0,00</span></div>
                <div class="calc-row"><span>Margem Bruta</span><span class="calc-val" id="calcMargem">0,00%</span></div>
                <div class="calc-row"><span>Margem Líquida (est.)</span><span class="calc-val" id="calcMargemLiq">0,00%</span></div>
            </div>

            <!-- Preço sugerido -->
            <div class="preco-sugerido-box">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold text-warning-emphasis"><i class="fas fa-lightbulb me-2"></i>Preço Sugerido ao Cliente</div>
                        <small class="text-muted">Calculado com base no custo + markup. Serve como referência para o vendedor.</small>
                    </div>
                    <div class="preco-sugerido-val" id="precoSugeridoDisplay">R$ 0,00</div>
                </div>
                <input type="hidden" name="preco_sugerido" id="precoSugeridoInput" value="<?= $fmtNum($produto->preco_sugerido ?? 0) ?>">
            </div>

            <!-- Estoque -->
            <div class="section-title mt-4"><i class="fas fa-warehouse"></i> Controle de Estoque</div>
            <div class="row g-3">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="controla_estoque" id="controlaEstoque" value="1" <?= $checked('controla_estoque',1) ?> onchange="toggleEstoque()">
                        <label class="form-check-label fw-semibold" for="controlaEstoque">Controlar Estoque</label>
                    </div>
                </div>
                <div id="estoqueCampos">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Estoque Atual</label>
                            <input type="text" name="estoque_atual" class="form-control" value="<?= $fmtNum($produto->estoque_atual ?? 0) ?>">
                            <?php if ($isEdit): ?><small class="text-muted">Ajuste manual permitido. Para rastreabilidade, prefira usar movimentações.</small><?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estoque Mínimo</label>
                            <input type="text" name="estoque_minimo" class="form-control" value="<?= $fmtNum($produto->estoque_minimo ?? 0) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estoque Máximo</label>
                            <input type="text" name="estoque_maximo" class="form-control" value="<?= $fmtNum($produto->estoque_maximo ?? 0) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ponto de Reposição</label>
                            <input type="text" name="ponto_reposicao" class="form-control" value="<?= $fmtNum($produto->ponto_reposicao ?? 0) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Lead Time (dias)</label>
                            <input type="number" name="lead_time_dias" class="form-control" min="0" value="<?= $val('lead_time_dias') ?>" placeholder="Prazo do fornecedor">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Localização no Estoque</label>
                            <input type="text" name="localizacao_estoque" class="form-control" value="<?= $val('localizacao_estoque') ?>" placeholder="Ex: Prateleira A3">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unidade de Compra</label>
                            <input type="text" name="unidade_compra" class="form-control" value="<?= $val('unidade_compra') ?>" placeholder="Ex: CX c/ 10">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Validade -->
            <div class="section-title mt-4"><i class="fas fa-calendar-alt"></i> Controle de Validade</div>
            <div class="row g-3">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="controla_validade" id="controlaValidade" value="1" <?= $checked('controla_validade') ?>>
                        <label class="form-check-label" for="controlaValidade">Este produto tem validade</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Alertar com antecedência (dias)</label>
                    <input type="number" name="alerta_validade_dias" class="form-control" min="0" value="<?= $val('alerta_validade_dias') ?: 90 ?>">
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="lote_obrigatorio" id="loteObrig" value="1" <?= $checked('lote_obrigatorio') ?>>
                        <label class="form-check-label" for="loteObrig">Lote obrigatório na entrada</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna: Depreciação -->
        <div class="col-md-5">
            <div class="section-title"><i class="fas fa-chart-line"></i> Depreciação</div>
            <div class="depreciacao-box">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="controla_depreciacao" id="controlaDeprec" value="1" <?= $checked('controla_depreciacao') ?> onchange="toggleDeprec()">
                    <label class="form-check-label fw-semibold" for="controlaDeprec">Controlar Depreciação</label>
                    <div><small class="text-muted">Obrigatório para equipamentos — permite sugestão automática de troca</small></div>
                </div>
                <div id="deprecCampos">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Vida Útil (meses)</label>
                            <input type="number" name="vida_util_meses" class="form-control" min="1" value="<?= $val('vida_util_meses') ?>" placeholder="Ex: 60 = 5 anos" oninput="calcularDepreciacao()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valor Residual (R$)</label>
                            <input type="text" name="valor_residual" class="form-control money-mask" value="<?= $fmtNum($produto->valor_residual ?? 0) ?>" oninput="calcularDepreciacao()">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Método</label>
                            <select name="metodo_depreciacao" class="form-select">
                                <option value="linear"            <?= $selOpt('metodo_depreciacao','linear') ?>>Linear (Quotas Constantes)</option>
                                <option value="soma_digitos"      <?= $selOpt('metodo_depreciacao','soma_digitos') ?>>Soma dos Dígitos</option>
                                <option value="unidades_produzidas" <?= $selOpt('metodo_depreciacao','unidades_produzidas') ?>>Unidades Produzidas</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Depreciação Mensal (R$)</label>
                            <input type="text" name="depreciacao_mensal" id="deprecMensal" class="form-control" value="<?= $fmtNum($produto->depreciacao_mensal ?? 0) ?>" readonly>
                            <small class="text-muted">Calculado automaticamente</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alertar substituição (meses antes)</label>
                            <input type="number" name="alerta_substituicao_meses" class="form-control" min="1" value="<?= $val('alerta_substituicao_meses') ?>" placeholder="Ex: 6">
                            <small class="text-muted">Para sugestão automática de troca</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Histórico de preços -->
            <?php if ($isEdit && !empty($historico_precos)): ?>
            <div class="section-title mt-4"><i class="fas fa-history"></i> Histórico de Preços</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead><tr><th>Data</th><th>Custo</th><th>Venda</th><th>Markup</th></tr></thead>
                    <tbody>
                    <?php foreach ($historico_precos as $h): ?>
                    <tr>
                        <td><small><?= date('d/m/Y', strtotime($h->created_at)) ?></small></td>
                        <td><small>R$ <?= number_format($h->preco_custo, 2, ',', '.') ?></small></td>
                        <td><small>R$ <?= number_format($h->preco_venda, 2, ',', '.') ?></small></td>
                        <td><small><?= number_format($h->markup_percentual, 1, ',', '.') ?>%</small></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     ABA 3 — COMPONENTES (apenas edição)
════════════════════════════════════════════════════════════════════════════ -->
<?php if ($isEdit): ?>
<div class="prod-tab-pane <?= $activeTab==='componentes' ? 'active':'' ?>" id="tab-componentes">
    <div class="row g-4">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-plus-circle me-2 text-primary"></i>Adicionar Componente</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Buscar Produto/Componente</label>
                        <input type="text" id="buscaComponente" class="form-control" placeholder="Digite o nome ou código…" autocomplete="off">
                        <div id="resultadosBusca" class="list-group mt-1" style="position:absolute;z-index:999;width:calc(100% - 2rem);display:none;"></div>
                    </div>
                    <input type="hidden" id="compId" value="">
                    <div id="compSelecionado" class="alert alert-info py-2 px-3 d-none small"></div>
                    <div class="row g-2 mt-1">
                        <div class="col-6">
                            <label class="form-label small">Quantidade</label>
                            <input type="number" id="compQtd" class="form-control form-control-sm" value="1" min="0.001" step="0.001">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Preço de Venda Próprio (R$)</label>
                            <input type="text" id="compPrecoVenda" class="form-control form-control-sm" placeholder="Deixar vazio = usa preço do produto">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Desconto na Composição (%)</label>
                            <input type="number" id="compDesconto" class="form-control form-control-sm" value="0" min="0" max="100">
                        </div>
                        <div class="col-6 d-flex align-items-end gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="compObrig" checked>
                                <label class="form-check-label small" for="compObrig">Obrigatório</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="compSeparado" checked>
                                <label class="form-check-label small" for="compSeparado">Vende separado</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Observações</label>
                            <input type="text" id="compObs" class="form-control form-control-sm" placeholder="Ex: Incluído no kit padrão">
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm mt-3 w-100" onclick="adicionarComponente()">
                        <i class="fas fa-plus me-1"></i> Adicionar Componente
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <h6 class="fw-semibold mb-3">Componentes Cadastrados</h6>
            <div id="listaComponentes">
                <?php if (empty($componentes)): ?>
                <div class="text-center py-4 text-muted" id="semComponentes">
                    <i class="fas fa-puzzle-piece fa-2x mb-2"></i><br>Nenhum componente cadastrado.
                </div>
                <?php else: ?>
                <?php foreach ($componentes as $c): ?>
                <div class="comp-card mb-2" id="comp-<?= $c->id ?>">
                    <div class="comp-card-img">
                        <?php if ($c->comp_imagem): ?>
                        <img src="<?= $esc($c->comp_imagem) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:8px;" alt="">
                        <?php else: ?>
                        <i class="fas fa-box"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small"><?= $esc($c->comp_nome) ?></div>
                        <div class="text-muted" style="font-size:11px;"><?= $esc($c->comp_codigo) ?> · <?= $esc($c->comp_unidade) ?></div>
                        <div class="d-flex gap-2 mt-1 flex-wrap">
                            <span class="<?= $c->obrigatorio ? 'badge-obrig' : 'badge-opc' ?> badge"><?= $c->obrigatorio ? 'Obrigatório' : 'Opcional' ?></span>
                            <?php if ($c->vendido_separado): ?><span class="badge badge-info">Vende separado</span><?php endif; ?>
                            <span class="text-muted" style="font-size:11px;">Qtd: <?= number_format($c->quantidade,2,',','.') ?></span>
                            <?php if ($c->preco_venda_proprio): ?>
                            <span class="text-success fw-semibold" style="font-size:11px;">R$ <?= number_format($c->preco_venda_proprio,2,',','.') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerComponente(<?= $c->id ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     ABA 4 — COMISSÃO (apenas edição)
════════════════════════════════════════════════════════════════════════════ -->
<div class="prod-tab-pane <?= $activeTab==='comissao' ? 'active':'' ?>" id="tab-comissao">
    <div class="row g-4">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-plus-circle me-2 text-success"></i>Nova Regra de Comissão</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small">Descrição da Regra</label>
                        <input type="text" id="comDesc" class="form-control form-control-sm" placeholder="Ex: Comissão padrão vendedor">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Colaborador (vazio = todos)</label>
                        <select id="comColaborador" class="form-select form-select-sm">
                            <option value="">— Todos os colaboradores —</option>
                            <?php foreach ($colaboradores as $col): ?>
                            <option value="<?= $col->id ?>"><?= $esc($col->nome) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">Tipo</label>
                            <select id="comTipo" class="form-select form-select-sm">
                                <option value="percentual_venda">% sobre Venda</option>
                                <option value="valor_fixo">Valor Fixo (R$)</option>
                                <option value="percentual_margem">% sobre Margem</option>
                                <option value="percentual_lucro">% sobre Lucro</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Valor</label>
                            <input type="text" id="comValor" class="form-control form-control-sm" placeholder="Ex: 5,00">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Meta Mínima (R$)</label>
                            <input type="text" id="comMetaMin" class="form-control form-control-sm" placeholder="Opcional">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Meta Máxima (R$)</label>
                            <input type="text" id="comMetaMax" class="form-control form-control-sm" placeholder="Opcional">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Vigência Início</label>
                            <input type="date" id="comVigIni" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Vigência Fim</label>
                            <input type="date" id="comVigFim" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="comEscalonado">
                                <label class="form-check-label small" for="comEscalonado">Comissão escalonada (faixas progressivas)</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Observações</label>
                            <textarea id="comObs" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>
                    <button type="button" class="btn btn-success btn-sm mt-3 w-100" onclick="adicionarComissao()">
                        <i class="fas fa-plus me-1"></i> Adicionar Regra
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <h6 class="fw-semibold mb-3">Regras de Comissão</h6>
            <div id="listaComissoes">
                <?php if (empty($comissoes)): ?>
                <div class="text-center py-4 text-muted" id="semComissoes">
                    <i class="fas fa-hand-holding-usd fa-2x mb-2"></i><br>Nenhuma regra de comissão cadastrada.
                </div>
                <?php else: ?>
                <?php
                $tipoLabels = [
                    'percentual_venda'   => '% Venda',
                    'valor_fixo'         => 'Valor Fixo',
                    'percentual_margem'  => '% Margem',
                    'percentual_lucro'   => '% Lucro',
                ];
                foreach ($comissoes as $c): ?>
                <div class="com-rule-card mb-2" id="com-<?= $c->id ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold small"><?= $esc($c->descricao) ?></div>
                            <div class="text-muted" style="font-size:12px;">
                                <?= $esc($tipoLabels[$c->tipo] ?? $c->tipo) ?> ·
                                <strong><?= number_format($c->valor, 2, ',', '.') ?><?= $c->tipo==='valor_fixo' ? '' : '%' ?></strong>
                                <?= $c->colaborador_nome ? ' · ' . $esc($c->colaborador_nome) : ' · Todos' ?>
                                <?php if (!$c->ativo): ?><span class="badge badge-secondary ms-1">Inativa</span><?php endif; ?>
                            </div>
                            <?php if ($c->vigencia_inicio || $c->vigencia_fim): ?>
                            <small class="text-muted">
                                <?= $c->vigencia_inicio ? date('d/m/Y', strtotime($c->vigencia_inicio)) : '…' ?>
                                até <?= $c->vigencia_fim ? date('d/m/Y', strtotime($c->vigencia_fim)) : '…' ?>
                            </small>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerComissao(<?= $c->id ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     ABA 5 — TÉCNICO
════════════════════════════════════════════════════════════════════════════ -->
<div class="prod-tab-pane <?= $activeTab==='tecnico' ? 'active':'' ?>" id="tab-tecnico">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="section-title"><i class="fas fa-ruler-combined"></i> Dimensões e Peso</div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small">Peso (kg)</label>
                    <input type="text" name="peso_kg" class="form-control" value="<?= $fmtNum($produto->peso_kg ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Altura (cm)</label>
                    <input type="text" name="altura_cm" class="form-control" value="<?= $fmtNum($produto->altura_cm ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Largura (cm)</label>
                    <input type="text" name="largura_cm" class="form-control" value="<?= $fmtNum($produto->largura_cm ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Profundidade (cm)</label>
                    <input type="text" name="profundidade_cm" class="form-control" value="<?= $fmtNum($produto->profundidade_cm ?? '') ?>">
                </div>
            </div>

            <div class="section-title mt-4"><i class="fas fa-bolt"></i> Elétrico</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small">Voltagem</label>
                    <select name="voltagem" class="form-select">
                        <option value="">—</option>
                        <?php foreach (['110V','220V','bivolt','DC','N/A'] as $v): ?>
                        <option value="<?= $v ?>" <?= $selOpt('voltagem',$v) ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Potência (W)</label>
                    <input type="text" name="potencia_w" class="form-control" value="<?= $fmtNum($produto->potencia_w ?? '') ?>">
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="section-title"><i class="fas fa-users"></i> Público-Alvo e Indicações</div>
            <div class="mb-3">
                <label class="form-label small">Público-Alvo</label>
                <input type="text" name="publico_alvo" class="form-control" value="<?= $val('publico_alvo') ?>" placeholder="Ex: UTI, Laboratório, Clínica Geral, Centro Cirúrgico">
            </div>
            <div class="mb-3">
                <label class="form-label small">Indicações de Uso</label>
                <textarea name="indicacoes_uso" class="form-control" rows="3" placeholder="Indicações clínicas e aplicações"><?= $val('indicacoes_uso') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label small">Contraindicações</label>
                <textarea name="contraindicacoes" class="form-control" rows="2" placeholder="Contraindicações e restrições"><?= $val('contraindicacoes') ?></textarea>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     ABA 6 — INTELIGÊNCIA / IA
════════════════════════════════════════════════════════════════════════════ -->
<div class="prod-tab-pane <?= $activeTab==='ia' ? 'active':'' ?>" id="tab-ia">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="ia-box mb-4">
                <h6><i class="fas fa-brain me-2"></i>Dados para Inteligência de Vendas</h6>
                <p class="small text-muted mb-0">Estes campos alimentam o motor de sugestão de vendas e a futura IA de precificação e substituição de equipamentos.</p>
            </div>
            <div class="mb-3">
                <label class="form-label small">Palavras-chave / Tags</label>
                <input type="text" name="palavras_chave" class="form-control" value="<?= $val('palavras_chave') ?>" placeholder="Ex: monitor, sinais vitais, UTI, oximetria">
                <small class="text-muted">Separadas por vírgula</small>
            </div>
            <div class="mb-3">
                <label class="form-label small">Diferenciais Competitivos</label>
                <textarea name="diferenciais" class="form-control" rows="3" placeholder="O que torna este produto melhor que os concorrentes?"><?= $val('diferenciais') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label small">Produtos Concorrentes</label>
                <input type="text" name="concorrentes" class="form-control" value="<?= $val('concorrentes') ?>" placeholder="Ex: Philips IntelliVue, Mindray VS-900">
                <small class="text-muted">Para análise comparativa de precificação</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label small">Ciclo Médio de Venda (dias)</label>
                <input type="number" name="ciclo_venda_dias" class="form-control" min="0" value="<?= $val('ciclo_venda_dias') ?>" placeholder="Ex: 30">
                <small class="text-muted">Tempo médio entre o primeiro contato e o fechamento</small>
            </div>
            <?php if ($isEdit): ?>
            <div class="mb-3">
                <label class="form-label small">Score de Facilidade de Venda (0-100)</label>
                <div class="score-bar mb-1">
                    <div class="score-fill" style="width:<?= (int)($produto->score_venda ?? 0) ?>%"></div>
                </div>
                <small class="text-muted"><?= (int)($produto->score_venda ?? 0) ?>/100 — calculado automaticamente pelo sistema</small>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label small">Total Vendido</label>
                    <input type="text" class="form-control form-control-sm" value="<?= number_format($produto->total_vendido ?? 0, 2, ',', '.') ?> <?= $esc($produto->unidade_medida ?? '') ?>" readonly>
                </div>
                <div class="col-6">
                    <label class="form-label small">Receita Total Gerada</label>
                    <input type="text" class="form-control form-control-sm" value="R$ <?= number_format($produto->receita_total ?? 0, 2, ',', '.') ?>" readonly>
                </div>
                <div class="col-6">
                    <label class="form-label small">Taxa de Conversão</label>
                    <input type="text" class="form-control form-control-sm" value="<?= number_format($produto->taxa_conversao ?? 0, 1, ',', '.') ?>%" readonly>
                </div>
                <div class="col-6">
                    <label class="form-label small">Última Venda</label>
                    <input type="text" class="form-control form-control-sm" value="<?= $produto->ultima_venda_em ? date('d/m/Y', strtotime($produto->ultima_venda_em)) : '—' ?>" readonly>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Botões de ação -->
<div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
    <a href="/estoque/produtos" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>
    <div class="d-flex gap-2">
        <?php if ($isEdit): ?>
        <a href="/estoque/produtos/<?= $produto->id ?>" class="btn btn-outline-primary">
            <i class="fas fa-eye me-1"></i> Visualizar
        </a>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary px-4">
            <i class="fas fa-save me-1"></i> <?= $isEdit ? 'Salvar Alterações' : 'Cadastrar Produto' ?>
        </button>
    </div>
</div>

</form>

<script>
const PRODUTO_ID = <?= $isEdit ? $produto->id : 'null' ?>;

// ─── Abas ────────────────────────────────────────────────────────────────────
function switchTab(id) {
    document.querySelectorAll('.prod-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.prod-tab-pane').forEach(p => p.classList.remove('active'));
    document.getElementById('tab-' + id)?.classList.add('active');
    document.querySelectorAll('.prod-tab').forEach(t => {
        if (t.getAttribute('onclick') === "switchTab('" + id + "')") t.classList.add('active');
    });
}

// ─── Preview de imagem ───────────────────────────────────────────────────────
function previewImagem(input) {
    if (!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('imgPreview');
        const ph  = document.getElementById('imgPlaceholder');
        img.src = e.target.result;
        img.style.display = 'block';
        if (ph) ph.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
}

// ─── Máscara monetária simples ───────────────────────────────────────────────
function parseMoney(v) {
    if (!v) return 0;
    return parseFloat(String(v).replace(/\./g,'').replace(',','.')) || 0;
}
function formatMoney(v) {
    return v.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
}

// ─── Calculadora de preços ───────────────────────────────────────────────────
function calcularPrecos() {
    const custo   = parseMoney(document.getElementById('precoCusto').value);
    const desp    = parseMoney(document.getElementById('despesasAcess').value);
    const markup  = parseMoney(document.getElementById('markupPerc').value);
    const impostos= parseMoney(document.getElementById('impostosPerc').value);
    const custoTotal = custo + desp;
    const sugerido   = custoTotal * (1 + markup / 100);
    const margemBruta = sugerido > 0 ? ((sugerido - custo) / sugerido * 100) : 0;
    const margemLiq   = margemBruta - impostos - (markup > 0 ? 0 : 0);

    document.getElementById('calcCusto').textContent      = 'R$ ' + formatMoney(custo);
    document.getElementById('calcDesp').textContent       = 'R$ ' + formatMoney(desp);
    document.getElementById('calcCustoTotal').textContent = 'R$ ' + formatMoney(custoTotal);
    document.getElementById('calcMarkupVal').textContent  = 'R$ ' + formatMoney(sugerido - custoTotal);
    document.getElementById('calcSugerido').textContent   = 'R$ ' + formatMoney(sugerido);
    document.getElementById('calcMargem').textContent     = formatMoney(margemBruta) + '%';
    document.getElementById('calcMargemLiq').textContent  = formatMoney(Math.max(0, margemLiq)) + '%';
    document.getElementById('precoSugeridoDisplay').textContent = 'R$ ' + formatMoney(sugerido);
    document.getElementById('precoSugeridoInput').value   = formatMoney(sugerido);

    // Atualiza preço de venda se estiver vazio
    const vendaInput = document.getElementById('precoVenda');
    if (parseMoney(vendaInput.value) === 0 && sugerido > 0) {
        vendaInput.value = formatMoney(sugerido);
    }
}

function calcularPorVenda() {
    const custo  = parseMoney(document.getElementById('precoCusto').value);
    const desp   = parseMoney(document.getElementById('despesasAcess').value);
    const venda  = parseMoney(document.getElementById('precoVenda').value);
    const custoTotal = custo + desp;
    if (custoTotal > 0 && venda > 0) {
        const markup = ((venda / custoTotal) - 1) * 100;
        document.getElementById('markupPerc').value = formatMoney(markup);
    }
    calcularPrecos();
}

// ─── Depreciação ─────────────────────────────────────────────────────────────
function calcularDepreciacao() {
    const custo = parseMoney(document.getElementById('precoCusto')?.value || '0');
    const vidaUtil = parseInt(document.querySelector('[name=vida_util_meses]')?.value || '0');
    const residual = parseMoney(document.querySelector('[name=valor_residual]')?.value || '0');
    if (vidaUtil > 0 && custo > 0) {
        const mensal = (custo - residual) / vidaUtil;
        const el = document.getElementById('deprecMensal');
        if (el) el.value = formatMoney(Math.max(0, mensal));
    }
}

function toggleDeprec() {
    const campos = document.getElementById('deprecCampos');
    if (campos) campos.style.display = document.getElementById('controlaDeprec').checked ? 'block' : 'none';
}
function toggleEstoque() {
    const campos = document.getElementById('estoqueCampos');
    if (campos) campos.style.display = document.getElementById('controlaEstoque').checked ? 'block' : 'none';
}

// ─── Fabricante ──────────────────────────────────────────────────────────────
function preencherFabricante(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('fabricante_nome').value = opt.text;
    }
}

// ─── Busca de componentes ────────────────────────────────────────────────────
let buscaTimer;
document.getElementById('buscaComponente')?.addEventListener('input', function() {
    clearTimeout(buscaTimer);
    const q = this.value.trim();
    if (q.length < 2) { document.getElementById('resultadosBusca').style.display='none'; return; }
    buscaTimer = setTimeout(() => {
        fetch('/estoque/produtos/buscar?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                const box = document.getElementById('resultadosBusca');
                box.innerHTML = '';
                if (!data.length) { box.style.display='none'; return; }
                data.forEach(p => {
                    const a = document.createElement('a');
                    a.href = '#';
                    a.className = 'list-group-item list-group-item-action py-2';
                    a.innerHTML = `<strong>${p.codigo}</strong> — ${p.nome} <small class="text-muted">(R$ ${parseFloat(p.preco_venda).toLocaleString('pt-BR',{minimumFractionDigits:2})})</small>`;
                    a.onclick = e => {
                        e.preventDefault();
                        document.getElementById('compId').value = p.id;
                        document.getElementById('buscaComponente').value = p.codigo + ' — ' + p.nome;
                        document.getElementById('compSelecionado').textContent = '✓ ' + p.nome + ' selecionado';
                        document.getElementById('compSelecionado').classList.remove('d-none');
                        box.style.display = 'none';
                    };
                    box.appendChild(a);
                });
                box.style.display = 'block';
            });
    }, 300);
});

function adicionarComponente() {
    const compId = document.getElementById('compId').value;
    if (!compId) { alert('Selecione um componente na busca.'); return; }
    const data = new FormData();
    data.append('componente_id',       compId);
    data.append('quantidade',          document.getElementById('compQtd').value);
    data.append('preco_venda_proprio', document.getElementById('compPrecoVenda').value);
    data.append('desconto_composicao', document.getElementById('compDesconto').value);
    data.append('obrigatorio',         document.getElementById('compObrig').checked ? 1 : 0);
    data.append('vendido_separado',    document.getElementById('compSeparado').checked ? 1 : 0);
    data.append('observacoes',         document.getElementById('compObs').value);

    fetch('/estoque/produtos/' + PRODUTO_ID + '/componente/add', { method:'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                location.reload();
            } else {
                alert(res.message || 'Erro ao adicionar componente.');
            }
        });
}

function removerComponente(id) {
    if (!confirm('Remover este componente?')) return;
    fetch('/estoque/produtos/componente/' + id + '/delete', { method:'POST' })
        .then(r => r.json())
        .then(res => {
            if (res.success) document.getElementById('comp-' + id)?.remove();
        });
}

function adicionarComissao() {
    const desc = document.getElementById('comDesc').value.trim();
    if (!desc) { alert('Informe a descrição da regra.'); return; }
    const data = new FormData();
    data.append('descricao',      desc);
    data.append('colaborador_id', document.getElementById('comColaborador').value);
    data.append('tipo',           document.getElementById('comTipo').value);
    data.append('valor',          document.getElementById('comValor').value);
    data.append('meta_minima',    document.getElementById('comMetaMin').value);
    data.append('meta_maxima',    document.getElementById('comMetaMax').value);
    data.append('escalonado',     document.getElementById('comEscalonado').checked ? 1 : 0);
    data.append('vigencia_inicio',document.getElementById('comVigIni').value);
    data.append('vigencia_fim',   document.getElementById('comVigFim').value);
    data.append('observacoes',    document.getElementById('comObs').value);
    data.append('ativo',          1);

    fetch('/estoque/produtos/' + PRODUTO_ID + '/comissao/add', { method:'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                location.reload();
            } else {
                alert(res.message || 'Erro ao adicionar regra.');
            }
        });
}

function removerComissao(id) {
    if (!confirm('Remover esta regra de comissão?')) return;
    fetch('/estoque/produtos/comissao/' + id + '/delete', { method:'POST' })
        .then(r => r.json())
        .then(res => {
            if (res.success) document.getElementById('com-' + id)?.remove();
        });
}

// ─── Inicialização ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    calcularPrecos();
    calcularDepreciacao();
    toggleDeprec();
    toggleEstoque();

    // ─── Normaliza campos monetários para formato numérico antes do submit ────
    // Converte "1.650,00" (pt-BR) → "1650.0000" (float puro) para o PHP processar corretamente.
    document.getElementById('formProduto').addEventListener('submit', function(e) {
        this.querySelectorAll('.money-mask').forEach(function(input) {
            const raw = (input.value || '').trim();
            if (raw === '') return;
            // Remove pontos de milhar e troca vírgula decimal por ponto
            const numeric = raw.replace(/\./g, '').replace(',', '.');
            const parsed  = parseFloat(numeric);
            input.value   = isNaN(parsed) ? '0.0000' : parsed.toFixed(4);
        });
    });
});
</script>
