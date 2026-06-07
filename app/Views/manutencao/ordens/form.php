<?php
$os       = $os       ?? null;
$clientes = $clientes ?? [];
$produtos = $produtos ?? [];
$trocas   = $trocas   ?? [];
$isEdit   = $os !== null;
$titulo   = $isEdit ? 'Editar O.S ' . $os->numero : 'Nova Ordem de Serviço';
$csrfToken = \App\Core\View::csrfToken();
?>
<style>
.os-form-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.5rem;margin-bottom:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.os-form-card h5{font-size:.875rem;font-weight:700;color:#1e293b;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid #f1f5f9}
.os-form-card h5 i{color:#1a56db;margin-right:.4rem}
.form-label{font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.3rem}
.required-star{color:#dc2626}
.troca-row{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.5rem;padding:.75rem;margin-bottom:.5rem;font-size:.82rem}
</style>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0"><i class="fas fa-wrench text-primary me-2"></i><?= htmlspecialchars($titulo) ?></h4>
    <a href="/manutencao/ordens" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
  </div>

  <form id="formOS" method="POST" action="<?= $isEdit ? "/manutencao/ordens/{$os->id}/update" : '/manutencao/ordens/store' ?>">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

    <div class="row g-3">
      <!-- Coluna principal -->
      <div class="col-lg-8">

        <!-- Dados do Cliente -->
        <div class="os-form-card">
          <h5><i class="fas fa-user"></i> Dados do Cliente</h5>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Cliente <span class="required-star">*</span></label>
              <select id="selectCliente" name="cliente_id" class="form-select form-select-sm">
                <option value="">— Selecionar cliente cadastrado —</option>
                <?php foreach ($clientes as $c): ?>
                <option value="<?= $c->id ?>"
                  data-nome="<?= htmlspecialchars($c->nome ?? $c->razao_social ?? '') ?>"
                  data-cpfcnpj="<?= htmlspecialchars($c->cpf_cnpj ?? '') ?>"
                  data-email="<?= htmlspecialchars($c->email ?? '') ?>"
                  data-tel="<?= htmlspecialchars($c->telefone ?? '') ?>"
                  data-end="<?= htmlspecialchars($c->logradouro ?? '') ?>"
                  data-cidade="<?= htmlspecialchars($c->cidade ?? '') ?>"
                  data-estado="<?= htmlspecialchars($c->estado ?? '') ?>"
                  <?= $isEdit && (int)($os->cliente_id ?? 0) === (int)$c->id ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c->nome ?? $c->razao_social ?? '') ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nome do Cliente <span class="required-star">*</span></label>
              <input type="text" name="cliente_nome" id="clienteNome" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($os->cliente_nome ?? '') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">CPF / CNPJ</label>
              <input type="text" name="cliente_cpf_cnpj" id="clienteCpfCnpj" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($os->cliente_cpf_cnpj ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">E-mail</label>
              <input type="email" name="cliente_email" id="clienteEmail" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($os->cliente_email ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Telefone</label>
              <input type="text" name="cliente_telefone" id="clienteTel" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($os->cliente_telefone ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Endereço</label>
              <input type="text" name="cliente_endereco" id="clienteEnd" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($os->cliente_endereco ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Cidade</label>
              <input type="text" name="cliente_cidade" id="clienteCidade" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($os->cliente_cidade ?? '') ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">UF</label>
              <input type="text" name="cliente_estado" id="clienteEstado" class="form-control form-control-sm" maxlength="2"
                     value="<?= htmlspecialchars($os->cliente_estado ?? '') ?>">
            </div>
          </div>
        </div>

        <!-- Equipamento -->
        <div class="os-form-card">
          <h5><i class="fas fa-cog"></i> Equipamento / Produto</h5>
          <div class="row g-3">
            <!-- Equipamento do cliente (carregado via AJAX ao selecionar cliente) -->
            <div class="col-12" id="blocoEquipCliente" style="display:none">
              <label class="form-label fw-semibold text-primary">
                <i class="fas fa-tools me-1"></i>Equipamento Cadastrado do Cliente
              </label>
              <select id="selectEquipCliente" class="form-select form-select-sm border-primary">
                <option value="">— Selecionar equipamento do cliente —</option>
              </select>
              <small class="text-muted">Ao selecionar, os campos abaixo serão preenchidos automaticamente.</small>
            </div>
            <div class="col-12"><hr class="my-1" id="hrEquipSep" style="display:none"></div>
            <div class="col-md-6">
              <label class="form-label">Produto / Serviço (do Estoque)</label>
              <select id="selectProduto" name="produto_id" class="form-select form-select-sm">
                <option value="">— Selecionar produto —</option>
                <?php foreach ($produtos as $p): ?>
                <option value="<?= $p->id ?>"
                  data-nome="<?= htmlspecialchars($p->nome) ?>"
                  data-codigo="<?= htmlspecialchars($p->codigo ?? '') ?>"
                  data-unidade="<?= htmlspecialchars($p->unidade_medida ?? 'UN') ?>"
                  data-preco="<?= number_format((float)($p->preco_venda ?? 0), 4, '.', '') ?>"
                  data-vida="<?= (int)($p->vida_util_meses ?? 0) ?>"
                  data-deprec="<?= number_format((float)($p->depreciacao_mensal ?? 0), 4, '.', '') ?>"
                  data-marca="<?= htmlspecialchars($p->marca ?? '') ?>"
                  data-modelo="<?= htmlspecialchars($p->modelo ?? '') ?>"
                  <?= $isEdit && (int)($os->produto_id ?? 0) === (int)$p->id ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p->codigo ? "[{$p->codigo}] " : '') . htmlspecialchars($p->nome) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nome do Produto / Equipamento</label>
              <input type="text" name="produto_nome" id="produtoNome" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($os->produto_nome ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Código</label>
              <input type="text" name="produto_codigo" id="produtoCodigo" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($os->produto_codigo ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Número de Série</label>
              <input type="text" name="numero_serie" id="numeroSerie" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($os->numero_serie ?? '') ?>"
                     placeholder="S/N do equipamento">
            </div>
            <div class="col-md-2">
              <label class="form-label">Marca</label>
              <input type="text" name="marca" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($os->marca ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Modelo</label>
              <input type="text" name="modelo" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($os->modelo ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Vida Útil (meses)</label>
              <input type="number" name="vida_util_meses" id="vidaUtilMeses" class="form-control form-control-sm" min="0"
                     value="<?= (int)($os->vida_util_meses ?? 0) ?>">
            </div>
          </div>
        </div>

        <!-- Dados da O.S -->
        <div class="os-form-card">
          <h5><i class="fas fa-clipboard-list"></i> Dados da Ordem de Serviço</h5>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Tipo de Manutenção <span class="required-star">*</span></label>
              <select name="tipo" class="form-select form-select-sm" required>
                <option value="">— Selecionar —</option>
                <option value="preventiva" <?= ($os->tipo ?? '') === 'preventiva' ? 'selected' : '' ?>>Preventiva</option>
                <option value="corretiva"  <?= ($os->tipo ?? '') === 'corretiva'  ? 'selected' : '' ?>>Corretiva</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Prioridade</label>
              <select name="prioridade" class="form-select form-select-sm">
                <option value="baixa"   <?= ($os->prioridade ?? '') === 'baixa'   ? 'selected' : '' ?>>Baixa</option>
                <option value="normal"  <?= ($os->prioridade ?? 'normal') === 'normal' ? 'selected' : '' ?>>Normal</option>
                <option value="alta"    <?= ($os->prioridade ?? '') === 'alta'    ? 'selected' : '' ?>>Alta</option>
                <option value="urgente" <?= ($os->prioridade ?? '') === 'urgente' ? 'selected' : '' ?>>Urgente</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Técnico Responsável</label>
              <input type="text" name="tecnico_responsavel" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($os->tecnico_responsavel ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Data de Abertura</label>
              <input type="date" name="data_abertura" class="form-control form-control-sm"
                     value="<?= $os->data_abertura ?? date('Y-m-d') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Previsão de Conclusão</label>
              <input type="date" name="data_previsao" class="form-control form-control-sm"
                     value="<?= $os->data_previsao ?? '' ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Valor do Serviço (R$)</label>
              <input type="text" name="valor_servico" id="valorServico" class="form-control form-control-sm money-mask"
                     value="<?= number_format((float)($os->valor_servico ?? 0), 2, ',', '.') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Motivo do Chamado <span class="required-star">*</span></label>
              <textarea name="motivo_chamado" class="form-control form-control-sm" rows="2" required><?= htmlspecialchars($os->motivo_chamado ?? '') ?></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Descrição do Serviço</label>
              <textarea name="descricao_servico" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($os->descricao_servico ?? '') ?></textarea>
            </div>
            <?php if ($isEdit): ?>
            <div class="col-12">
              <label class="form-label"><i class="fas fa-chart-line text-info me-1"></i>Evolução da Manutenção</label>
              <textarea name="evolucao" class="form-control form-control-sm" rows="4"
                        placeholder="Relate aqui o andamento, diagnósticos e procedimentos realizados..."><?= htmlspecialchars($os->evolucao ?? '') ?></textarea>
            </div>
            <?php endif; ?>
            <div class="col-12">
              <label class="form-label">Observações</label>
              <textarea name="observacoes" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($os->observacoes ?? '') ?></textarea>
            </div>
          </div>
        </div>

      </div><!-- /col-lg-8 -->

      <!-- Sidebar -->
      <div class="col-lg-4">
        <!-- Resumo -->
        <div class="os-form-card">
          <h5><i class="fas fa-info-circle"></i> Resumo</h5>
          <?php if ($isEdit): ?>
          <table class="table table-sm table-borderless mb-0" style="font-size:.82rem">
            <tr><td class="text-muted">Número</td><td class="fw-semibold"><?= htmlspecialchars($os->numero) ?></td></tr>
            <tr><td class="text-muted">Status</td><td>
              <span class="badge bg-primary-subtle text-primary"><?= ucfirst(str_replace('_', ' ', $os->status)) ?></span>
            </td></tr>
            <tr><td class="text-muted">Abertura</td><td><?= date('d/m/Y', strtotime($os->data_abertura)) ?></td></tr>
            <tr><td class="text-muted">Valor Peças</td><td>R$ <?= number_format((float)$os->valor_pecas, 2, ',', '.') ?></td></tr>
            <tr><td class="text-muted">Valor Total</td><td class="fw-bold text-success">R$ <?= number_format((float)$os->valor_total, 2, ',', '.') ?></td></tr>
          </table>
          <?php else: ?>
          <p class="text-muted small mb-0">Preencha os dados ao lado para criar a O.S. Uma proposta CRM será gerada automaticamente.</p>
          <?php endif; ?>
        </div>

        <!-- Botões -->
        <div class="os-form-card">
          <h5><i class="fas fa-save"></i> Ações</h5>
          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-sm" id="btnSalvar">
              <i class="fas fa-save me-1"></i> <?= $isEdit ? 'Salvar Alterações' : 'Criar Ordem de Serviço' ?>
            </button>
            <a href="/manutencao/ordens<?= $isEdit ? '/' . $os->id : '' ?>" class="btn btn-outline-secondary btn-sm">
              <i class="fas fa-times me-1"></i> Cancelar
            </a>
          </div>
        </div>

        <!-- Trocas existentes (edição) -->
        <?php if ($isEdit && !empty($trocas)): ?>
        <div class="os-form-card">
          <h5><i class="fas fa-exchange-alt"></i> Itens / Trocas Registrados</h5>
          <?php foreach ($trocas as $t): ?>
          <div class="troca-row">
            <div class="fw-semibold"><?= htmlspecialchars($t->descricao) ?></div>
            <div class="text-muted">Qtd: <?= number_format((float)$t->quantidade, 3, ',', '.') ?> — R$ <?= number_format((float)$t->preco_total, 2, ',', '.') ?></div>
            <?php if (!empty($t->data_proxima_troca)): ?>
            <div class="text-info"><i class="fas fa-calendar-alt me-1"></i>Próx. troca: <?= date('d/m/Y', strtotime($t->data_proxima_troca)) ?></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          <p class="text-muted small mt-2 mb-0">Para gerenciar trocas, acesse a visualização da O.S.</p>
        </div>
        <?php endif; ?>
      </div>
    </div><!-- /row -->
  </form>
</div>

<script>
// Preencher dados do cliente ao selecionar + carregar equipamentos do cliente
document.getElementById('selectCliente').addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  // Limpar bloco de equipamentos
  const blocoEquip = document.getElementById('blocoEquipCliente');
  const hrSep      = document.getElementById('hrEquipSep');
  const selEquip   = document.getElementById('selectEquipCliente');
  selEquip.innerHTML = '<option value="">— Selecionar equipamento do cliente —</option>';
  blocoEquip.style.display = 'none';
  hrSep.style.display      = 'none';

  if (!opt.value) return;
  document.getElementById('clienteNome').value    = opt.dataset.nome    || '';
  document.getElementById('clienteCpfCnpj').value = opt.dataset.cpfcnpj || '';
  document.getElementById('clienteEmail').value   = opt.dataset.email   || '';
  document.getElementById('clienteTel').value     = opt.dataset.tel     || '';
  document.getElementById('clienteEnd').value     = opt.dataset.end     || '';
  document.getElementById('clienteCidade').value  = opt.dataset.cidade  || '';
  document.getElementById('clienteEstado').value  = opt.dataset.estado  || '';

  // Buscar equipamentos cadastrados do cliente
  fetch('/clientes/' + opt.value + '/equipamentos')
    .then(r => r.json())
    .then(d => {
      if (!d.success || !d.equipamentos || d.equipamentos.length === 0) return;
      d.equipamentos.forEach(function(eq) {
        const o = document.createElement('option');
        o.value              = eq.id;
        o.dataset.nome       = eq.produto_nome   || '';
        o.dataset.codigo     = eq.produto_codigo || '';
        o.dataset.serie      = eq.numero_serie   || '';
        o.dataset.marca      = eq.marca          || '';
        o.dataset.modelo     = eq.modelo         || '';
        o.dataset.vida       = eq.vida_util_meses || 0;
        o.dataset.produtoId  = eq.produto_id     || '';
        o.textContent = (eq.produto_nome || 'Equipamento') +
          (eq.numero_serie ? ' — S/N: ' + eq.numero_serie : '') +
          (eq.marca ? ' (' + eq.marca + ')' : '');
        selEquip.appendChild(o);
      });
      blocoEquip.style.display = 'block';
      hrSep.style.display      = 'block';
    })
    .catch(() => { /* silencioso se não houver equipamentos */ });
});

// Preencher campos ao selecionar equipamento do cliente
document.getElementById('selectEquipCliente').addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  if (!opt.value) return;
  document.getElementById('produtoNome').value   = opt.dataset.nome   || '';
  document.getElementById('produtoCodigo').value = opt.dataset.codigo || '';
  document.getElementById('numeroSerie').value   = opt.dataset.serie  || '';
  const marcaEl  = document.querySelector('input[name="marca"]');
  const modeloEl = document.querySelector('input[name="modelo"]');
  if (marcaEl)  marcaEl.value  = opt.dataset.marca  || '';
  if (modeloEl) modeloEl.value = opt.dataset.modelo || '';
  if (opt.dataset.vida && parseInt(opt.dataset.vida) > 0) {
    document.getElementById('vidaUtilMeses').value = opt.dataset.vida;
  }
  // Se o equipamento tem produto_id, selecionar no select de produto
  if (opt.dataset.produtoId) {
    const selProd = document.getElementById('selectProduto');
    for (let i = 0; i < selProd.options.length; i++) {
      if (selProd.options[i].value == opt.dataset.produtoId) {
        selProd.selectedIndex = i;
        break;
      }
    }
  }
});

// Preencher dados do produto ao selecionar (marca, modelo e vida útil do cadastro)
document.getElementById('selectProduto').addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  if (!opt.value) return;
  document.getElementById('produtoNome').value   = opt.dataset.nome   || '';
  document.getElementById('produtoCodigo').value = opt.dataset.codigo || '';
  // Marca e Modelo
  const marcaEl  = document.querySelector('input[name="marca"]');
  const modeloEl = document.querySelector('input[name="modelo"]');
  if (marcaEl)  marcaEl.value  = opt.dataset.marca  || '';
  if (modeloEl) modeloEl.value = opt.dataset.modelo || '';
  // Vida útil
  if (opt.dataset.vida && parseInt(opt.dataset.vida) > 0) {
    document.getElementById('vidaUtilMeses').value = opt.dataset.vida;
  }
});

// Money mask
document.querySelectorAll('.money-mask').forEach(function(el) {
  el.addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '');
    if (!v) { this.value = '0,00'; return; }
    v = (parseInt(v, 10) / 100).toFixed(2);
    this.value = v.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  });
});

// Submit — converter money-mask para numérico
document.getElementById('formOS').addEventListener('submit', function(e) {
  e.preventDefault();
  this.querySelectorAll('.money-mask').forEach(function(inp) {
    const raw = inp.value.replace(/\./g, '').replace(',', '.');
    inp.value = isNaN(parseFloat(raw)) ? '0.0000' : parseFloat(raw).toFixed(4);
  });

  const btn = document.getElementById('btnSalvar');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Salvando...';

  const fd = new FormData(this);
  fetch(this.action, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        window.location.href = d.redirect;
      } else {
        alert(d.error || 'Erro ao salvar.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i> Salvar';
      }
    })
    .catch(() => {
      alert('Erro de comunicação com o servidor.');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-save me-1"></i> Salvar';
    });
});
</script>
