<?php
$proposta  = $proposta  ?? null;
$itens     = $itens     ?? [];
$clientes  = $clientes  ?? [];
$isEdit    = $isEdit    ?? false;

$titulo    = $isEdit ? 'Editar Proposta' : 'Nova Proposta';
$action    = $isEdit ? "/crm/propostas/{$proposta->id}/update" : "/crm/propostas";
$btnLabel  = $isEdit ? 'Salvar Alterações' : 'Criar Proposta';

// Helpers
function fv($proposta, string $campo, string $default = ''): string {
    if (!$proposta) return $default;
    return htmlspecialchars($proposta->{$campo} ?? $default);
}
?>
<style>
/* ── Wizard Steps ─────────────────────────────────────────────────────── */
.wizard-steps{display:flex;gap:0;margin-bottom:2rem;background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.wizard-step{flex:1;display:flex;align-items:center;gap:.6rem;padding:.875rem 1.1rem;cursor:pointer;border-right:1px solid #e2e8f0;transition:background .2s;position:relative;font-size:.85rem;color:#6b7280}
.wizard-step:last-child{border-right:none}
.wizard-step.active{background:#eff6ff;color:#1a56db;font-weight:600}
.wizard-step.done{background:#f0fdf4;color:#059669}
.wizard-step .step-num{width:26px;height:26px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0}
.wizard-step.active .step-num{background:#1a56db;color:#fff}
.wizard-step.done .step-num{background:#059669;color:#fff}
.wizard-step .step-label{line-height:1.2}
.wizard-step .step-sub{font-size:.7rem;opacity:.7}

/* ── Panels ───────────────────────────────────────────────────────────── */
.wizard-panel{display:none}
.wizard-panel.active{display:block}
.panel-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:1rem}
.panel-title{font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:1.25rem;padding-bottom:.75rem;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:.5rem}
.panel-title i{color:#1a56db}

/* ── Itens de produto ─────────────────────────────────────────────────── */
.item-row{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.5rem;padding:1rem;margin-bottom:.75rem;position:relative}
.item-row .remove-item{position:absolute;top:.5rem;right:.5rem;background:none;border:none;color:#dc2626;cursor:pointer;font-size:.9rem;padding:.2rem .4rem;border-radius:.3rem}
.item-row .remove-item:hover{background:#fef2f2}
.margem-badge{display:inline-block;padding:.2em .6em;border-radius:20px;font-size:.7rem;font-weight:600}
.margem-pos{background:#dcfce7;color:#166534}
.margem-neg{background:#fee2e2;color:#991b1b}
.margem-zero{background:#f3f4f6;color:#6b7280}

/* ── Resumo final ─────────────────────────────────────────────────────── */
.resumo-section{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.5rem;padding:1rem;margin-bottom:.75rem}
.resumo-title{font-size:.75rem;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;font-weight:700;margin-bottom:.5rem}
.resumo-row{display:flex;justify-content:space-between;font-size:.875rem;padding:.2rem 0;border-bottom:1px solid #e2e8f0}
.resumo-row:last-child{border-bottom:none}
.resumo-total{font-size:1.1rem;font-weight:700;color:#1a56db}

/* ── Busca de cliente ─────────────────────────────────────────────────── */
.busca-resultado{position:absolute;z-index:1000;background:#fff;border:1px solid #e2e8f0;border-radius:.5rem;box-shadow:0 4px 12px rgba(0,0,0,.1);max-height:250px;overflow-y:auto;width:100%}
.busca-item{padding:.6rem 1rem;cursor:pointer;font-size:.875rem;border-bottom:1px solid #f1f5f9}
.busca-item:last-child{border-bottom:none}
.busca-item:hover{background:#f0f7ff}
.busca-item .bi-nome{font-weight:600;color:#1e293b}
.busca-item .bi-sub{font-size:.75rem;color:#6b7280}

/* ── Wizard footer ────────────────────────────────────────────────────── */
.wizard-footer{display:flex;justify-content:space-between;align-items:center;background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1rem 1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.06)}
</style>

<div class="container-fluid">
  <div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?php echo $isEdit ? "/crm/propostas/{$proposta->id}" : '/crm/propostas'; ?>" class="btn btn-sm btn-outline-secondary">
      <i class="fas fa-arrow-left"></i>
    </a>
    <h5 class="mb-0"><i class="fas fa-file-contract text-primary me-2"></i><?php echo $titulo; ?></h5>
  </div>

  <!-- Wizard Steps -->
  <div class="wizard-steps" id="wizardSteps">
    <div class="wizard-step active" id="step-btn-1" onclick="goToStep(1)">
      <div class="step-num">1</div>
      <div class="step-label">Dados do Cliente<div class="step-sub">Identificação</div></div>
    </div>
    <div class="wizard-step" id="step-btn-2" onclick="goToStep(2)">
      <div class="step-num">2</div>
      <div class="step-label">Produtos<div class="step-sub">Itens e valores</div></div>
    </div>
    <div class="wizard-step" id="step-btn-3" onclick="goToStep(3)">
      <div class="step-num">3</div>
      <div class="step-label">Entrega e Pagamento<div class="step-sub">Condições</div></div>
    </div>
    <div class="wizard-step" id="step-btn-4" onclick="goToStep(4)">
      <div class="step-num">4</div>
      <div class="step-label">Revisão<div class="step-sub">Confirmar</div></div>
    </div>
  </div>

  <form id="formProposta" method="POST" action="<?php echo $action; ?>">

    <!-- ═══════════════════════════════════════════════════════════════════
         ETAPA 1 — Dados do Cliente
    ═══════════════════════════════════════════════════════════════════ -->
    <div class="wizard-panel active" id="panel-1">
      <div class="panel-card">
        <div class="panel-title"><i class="fas fa-user-tie"></i>Identificação do Cliente</div>

        <!-- Importar de Oportunidade -->
        <div class="alert alert-info d-flex align-items-start gap-2 mb-3" style="font-size:.875rem">
          <i class="fas fa-lightbulb mt-1"></i>
          <div>
            <strong>Dica:</strong> Você pode importar os dados automaticamente a partir de uma
            <strong>Oportunidade</strong> ou buscar um <strong>Cliente</strong> cadastrado.
          </div>
        </div>

        <div class="row g-3 mb-3">
          <!-- Importar por Oportunidade -->
          <div class="col-md-6">
            <label class="form-label fw-semibold">Importar de Oportunidade</label>
            <div class="input-group">
              <input type="number" id="inputOpId" class="form-control" placeholder="Número da oportunidade..." min="1">
              <button type="button" class="btn btn-outline-primary" onclick="importarOportunidade()">
                <i class="fas fa-download me-1"></i>Importar
              </button>
            </div>
            <div id="opImportMsg" class="form-text"></div>
            <input type="hidden" name="oportunidade_id" id="oportunidade_id" value="<?php echo fv($proposta, 'oportunidade_id'); ?>">
            <input type="hidden" name="lead_id"         id="lead_id"         value="<?php echo fv($proposta, 'lead_id'); ?>">
          </div>
          <!-- Buscar Cliente -->
          <div class="col-md-6">
            <label class="form-label fw-semibold">Buscar Cliente Cadastrado</label>
            <div class="position-relative">
              <input type="text" id="buscaCliente" class="form-control" placeholder="Digite nome, CNPJ ou e-mail..."
                autocomplete="off" oninput="buscarCliente(this.value)">
              <div id="buscaClienteResultados" class="busca-resultado" style="display:none"></div>
            </div>
            <input type="hidden" name="cliente_id" id="cliente_id" value="<?php echo fv($proposta, 'cliente_id'); ?>">
          </div>
        </div>

        <hr class="my-3">
        <p class="text-muted small mb-3">Ou preencha os dados manualmente:</p>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nome / Razão Social <span class="text-danger">*</span></label>
            <input type="text" name="cliente_nome" id="cliente_nome" class="form-control" required
              value="<?php echo fv($proposta, 'cliente_nome'); ?>" placeholder="Nome do cliente...">
          </div>
          <div class="col-md-6">
            <label class="form-label">Nome Fantasia / Razão Social</label>
            <input type="text" name="cliente_razao_social" id="cliente_razao_social" class="form-control"
              value="<?php echo fv($proposta, 'cliente_razao_social'); ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">CPF / CNPJ</label>
            <input type="text" name="cliente_cnpj_cpf" id="cliente_cnpj_cpf" class="form-control"
              value="<?php echo fv($proposta, 'cliente_cnpj_cpf'); ?>" placeholder="00.000.000/0000-00">
          </div>
          <div class="col-md-4">
            <label class="form-label">E-mail</label>
            <input type="email" name="cliente_email" id="cliente_email" class="form-control"
              value="<?php echo fv($proposta, 'cliente_email'); ?>" placeholder="email@empresa.com">
          </div>
          <div class="col-md-4">
            <label class="form-label">Telefone</label>
            <input type="text" name="cliente_telefone" id="cliente_telefone" class="form-control"
              value="<?php echo fv($proposta, 'cliente_telefone'); ?>" placeholder="(00) 00000-0000">
          </div>
          <div class="col-md-6">
            <label class="form-label">Endereço</label>
            <input type="text" name="cliente_endereco" id="cliente_endereco" class="form-control"
              value="<?php echo fv($proposta, 'cliente_endereco'); ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Cidade</label>
            <input type="text" name="cliente_cidade" id="cliente_cidade" class="form-control"
              value="<?php echo fv($proposta, 'cliente_cidade'); ?>">
          </div>
          <div class="col-md-1">
            <label class="form-label">UF</label>
            <input type="text" name="cliente_estado" id="cliente_estado" class="form-control" maxlength="2"
              value="<?php echo fv($proposta, 'cliente_estado'); ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">CEP</label>
            <input type="text" name="cliente_cep" id="cliente_cep" class="form-control"
              value="<?php echo fv($proposta, 'cliente_cep'); ?>" placeholder="00000-000">
          </div>
          <div class="col-md-6">
            <label class="form-label">Responsável / Contato</label>
            <input type="text" name="cliente_responsavel" id="cliente_responsavel" class="form-control"
              value="<?php echo fv($proposta, 'cliente_responsavel'); ?>" placeholder="Nome do responsável...">
          </div>
          <div class="col-md-6">
            <label class="form-label">Título da Proposta <span class="text-danger">*</span></label>
            <input type="text" name="titulo" id="titulo" class="form-control" required
              value="<?php echo fv($proposta, 'titulo'); ?>" placeholder="Ex: Proposta de Fornecimento de Materiais...">
          </div>
          <div class="col-12">
            <label class="form-label">Descrição / Objeto</label>
            <textarea name="descricao" class="form-control" rows="2"
              placeholder="Breve descrição do objeto desta proposta..."><?php echo fv($proposta, 'descricao'); ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         ETAPA 2 — Produtos / Itens
    ═══════════════════════════════════════════════════════════════════ -->
    <div class="wizard-panel" id="panel-2">
      <div class="panel-card">
        <div class="panel-title"><i class="fas fa-boxes"></i>Itens da Proposta</div>

        <!-- Aviso de módulo de estoque -->
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" style="font-size:.875rem">
          <i class="fas fa-info-circle mt-1"></i>
          <div>
            O módulo de <strong>Estoque/Produtos</strong> será integrado futuramente.
            Por enquanto, preencha os itens manualmente. Use o campo <strong>Código</strong>
            para referência interna.
          </div>
        </div>

        <!-- Busca de produto (placeholder) -->
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="input-group" style="max-width:400px">
            <input type="text" id="buscaProduto" class="form-control form-control-sm"
              placeholder="Buscar produto por código ou nome (em breve)..." disabled>
            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
              <i class="fas fa-search"></i>
            </button>
          </div>
          <button type="button" class="btn btn-sm btn-primary" onclick="adicionarItem()">
            <i class="fas fa-plus me-1"></i>Adicionar Item
          </button>
        </div>

        <!-- Lista de itens -->
        <div id="listaItens">
          <?php if (!empty($itens)): ?>
            <?php foreach ($itens as $idx => $item): ?>
            <div class="item-row" id="item-row-<?php echo $idx; ?>">
              <button type="button" class="remove-item" onclick="removerItem(<?php echo $idx; ?>)" title="Remover item">
                <i class="fas fa-times"></i>
              </button>
              <div class="row g-2">
                <div class="col-md-2">
                  <label class="form-label form-label-sm">Código</label>
                  <input type="text" name="item_codigo[]" class="form-control form-control-sm"
                    value="<?php echo htmlspecialchars($item->codigo ?? ''); ?>" placeholder="SKU...">
                  <input type="hidden" name="item_produto_id[]" value="<?php echo $item->produto_id ?? ''; ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label form-label-sm">Descrição <span class="text-danger">*</span></label>
                  <input type="text" name="item_descricao[]" class="form-control form-control-sm" required
                    value="<?php echo htmlspecialchars($item->descricao ?? ''); ?>" placeholder="Descrição do item...">
                </div>
                <div class="col-md-1">
                  <label class="form-label form-label-sm">Un.</label>
                  <input type="text" name="item_unidade[]" class="form-control form-control-sm"
                    value="<?php echo htmlspecialchars($item->unidade ?? 'un'); ?>" placeholder="un">
                </div>
                <div class="col-md-1">
                  <label class="form-label form-label-sm">Qtd.</label>
                  <input type="number" name="item_quantidade[]" class="form-control form-control-sm item-qtd"
                    value="<?php echo (float)($item->quantidade ?? 1); ?>" min="0.001" step="0.001"
                    oninput="recalcularItem(this)">
                </div>
                <div class="col-md-2">
                  <label class="form-label form-label-sm">Preço Unit. (R$)</label>
                  <input type="number" name="item_preco_unitario[]" class="form-control form-control-sm item-preco"
                    value="<?php echo (float)($item->preco_unitario ?? 0); ?>" min="0" step="0.01"
                    oninput="recalcularItem(this)">
                </div>
                <div class="col-md-1">
                  <label class="form-label form-label-sm">Desc. %</label>
                  <input type="number" name="item_desconto[]" class="form-control form-control-sm item-desc"
                    value="<?php echo (float)($item->desconto_item ?? 0); ?>" min="0" max="100" step="0.01"
                    oninput="recalcularItem(this)">
                </div>
                <div class="col-md-1">
                  <label class="form-label form-label-sm">Custo (R$)</label>
                  <input type="number" name="item_preco_custo[]" class="form-control form-control-sm item-custo"
                    value="<?php echo (float)($item->preco_custo ?? 0); ?>" min="0" step="0.01"
                    oninput="recalcularItem(this)">
                  <input type="hidden" name="item_margem[]" class="item-margem"
                    value="<?php echo (float)($item->margem_lucro ?? 0); ?>">
                </div>
              </div>
              <div class="row g-2 mt-1 align-items-center">
                <div class="col-md-2 offset-md-7">
                  <div class="item-margem-display small"></div>
                </div>
                <div class="col-md-2 text-end">
                  <span class="text-muted small">Total: </span>
                  <strong class="item-total text-success">R$ <?php echo number_format((float)($item->total_item ?? 0), 2, ',', '.'); ?></strong>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div id="semItens" class="text-center py-4 text-muted <?php echo !empty($itens) ? 'd-none' : ''; ?>">
          <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
          Nenhum item adicionado. Clique em <strong>Adicionar Item</strong> para começar.
        </div>

        <!-- Totais parciais -->
        <div class="d-flex justify-content-end mt-3">
          <div style="min-width:280px">
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted small">Subtotal:</span>
              <strong id="subtotalDisplay">R$ 0,00</strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Validade e desconto -->
      <div class="panel-card">
        <div class="panel-title"><i class="fas fa-calendar-alt"></i>Validade e Desconto</div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Validade da Proposta <span class="text-danger">*</span></label>
            <input type="date" name="validade_proposta" id="validade_proposta" class="form-control" required
              value="<?php echo fv($proposta, 'validade_proposta'); ?>">
            <div class="form-text">Data até a qual esta proposta é válida.</div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tipo de Desconto Global</label>
            <select name="desconto_tipo" id="desconto_tipo" class="form-select" onchange="recalcularTotais()">
              <option value="">Sem desconto</option>
              <option value="percentual" <?php echo fv($proposta, 'desconto_tipo') === 'percentual' ? 'selected' : ''; ?>>Percentual (%)</option>
              <option value="fixo"       <?php echo fv($proposta, 'desconto_tipo') === 'fixo'       ? 'selected' : ''; ?>>Valor Fixo (R$)</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Valor do Desconto</label>
            <input type="number" name="desconto_valor" id="desconto_valor" class="form-control"
              value="<?php echo fv($proposta, 'desconto_valor', '0'); ?>" min="0" step="0.01"
              oninput="recalcularTotais()">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <div class="text-muted small">
              Desconto: <strong id="descontoDisplay" class="text-danger">R$ 0,00</strong>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         ETAPA 3 — Entrega e Pagamento
    ═══════════════════════════════════════════════════════════════════ -->
    <div class="wizard-panel" id="panel-3">
      <div class="panel-card">
        <div class="panel-title"><i class="fas fa-truck"></i>Dados de Entrega</div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Prazo de Entrega</label>
            <input type="text" name="prazo_entrega" class="form-control"
              value="<?php echo fv($proposta, 'prazo_entrega'); ?>" placeholder="Ex: 15 dias úteis após aprovação">
          </div>
          <div class="col-md-4">
            <label class="form-label">Tipo de Frete</label>
            <select name="frete_tipo" id="frete_tipo" class="form-select" onchange="toggleFrete()">
              <option value="a_calcular" <?php echo fv($proposta, 'frete_tipo', 'a_calcular') === 'a_calcular' ? 'selected' : ''; ?>>A calcular</option>
              <option value="cif"        <?php echo fv($proposta, 'frete_tipo') === 'cif'        ? 'selected' : ''; ?>>CIF (por conta do fornecedor)</option>
              <option value="fob"        <?php echo fv($proposta, 'frete_tipo') === 'fob'        ? 'selected' : ''; ?>>FOB (por conta do cliente)</option>
              <option value="gratis"     <?php echo fv($proposta, 'frete_tipo') === 'gratis'     ? 'selected' : ''; ?>>Frete Grátis</option>
              <option value="valor"      <?php echo fv($proposta, 'frete_tipo') === 'valor'      ? 'selected' : ''; ?>>Valor Fixo</option>
            </select>
          </div>
          <div class="col-md-4" id="freteValorDiv" style="display:none">
            <label class="form-label">Valor do Frete (R$)</label>
            <input type="number" name="frete_valor" id="frete_valor" class="form-control"
              value="<?php echo fv($proposta, 'frete_valor', '0'); ?>" min="0" step="0.01"
              oninput="recalcularTotais()">
          </div>
          <div class="col-12">
            <label class="form-label">Local / Endereço de Entrega</label>
            <input type="text" name="local_entrega" class="form-control"
              value="<?php echo fv($proposta, 'local_entrega'); ?>" placeholder="Endereço completo de entrega...">
          </div>
        </div>
      </div>

      <div class="panel-card">
        <div class="panel-title"><i class="fas fa-credit-card"></i>Condições de Pagamento</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Condição de Pagamento</label>
            <input type="text" name="condicao_pagamento" class="form-control"
              value="<?php echo fv($proposta, 'condicao_pagamento'); ?>"
              placeholder="Ex: 30/60/90 dias, à vista com 5% de desconto...">
          </div>
          <div class="col-12">
            <label class="form-label">Observações Gerais</label>
            <textarea name="observacoes" class="form-control" rows="3"
              placeholder="Condições especiais, garantias, exclusões, etc..."><?php echo fv($proposta, 'observacoes'); ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Notas Internas <span class="text-muted small">(não aparece no PDF)</span></label>
            <textarea name="notas_internas" class="form-control" rows="2"
              placeholder="Anotações internas sobre esta proposta..."><?php echo fv($proposta, 'notas_internas'); ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         ETAPA 4 — Revisão
    ═══════════════════════════════════════════════════════════════════ -->
    <div class="wizard-panel" id="panel-4">
      <div class="panel-card">
        <div class="panel-title"><i class="fas fa-clipboard-check"></i>Revisão da Proposta</div>
        <p class="text-muted small mb-3">Confira os dados antes de <?php echo $isEdit ? 'salvar' : 'criar'; ?> a proposta.</p>

        <div class="row g-3">
          <div class="col-md-6">
            <div class="resumo-section">
              <div class="resumo-title">Cliente</div>
              <div id="resumo-cliente" class="text-muted small">—</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="resumo-section">
              <div class="resumo-title">Proposta</div>
              <div id="resumo-proposta" class="text-muted small">—</div>
            </div>
          </div>
          <div class="col-12">
            <div class="resumo-section">
              <div class="resumo-title">Itens</div>
              <div id="resumo-itens" class="text-muted small">—</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="resumo-section">
              <div class="resumo-title">Entrega e Pagamento</div>
              <div id="resumo-entrega" class="text-muted small">—</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="resumo-section">
              <div class="resumo-title">Valores</div>
              <div id="resumo-valores">
                <div class="resumo-row"><span>Subtotal:</span><span id="rv-subtotal">R$ 0,00</span></div>
                <div class="resumo-row"><span>Desconto:</span><span id="rv-desconto" class="text-danger">R$ 0,00</span></div>
                <div class="resumo-row"><span>Frete:</span><span id="rv-frete">R$ 0,00</span></div>
                <div class="resumo-row resumo-total"><span>TOTAL:</span><span id="rv-total">R$ 0,00</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Wizard Footer ──────────────────────────────────────────────── -->
    <div class="wizard-footer">
      <button type="button" id="btnAnterior" class="btn btn-outline-secondary" onclick="stepAnterior()" style="display:none">
        <i class="fas fa-arrow-left me-1"></i>Anterior
      </button>
      <div class="d-flex align-items-center gap-2 ms-auto">
        <span id="stepIndicator" class="text-muted small">Etapa 1 de 4</span>
        <button type="button" id="btnProximo" class="btn btn-primary" onclick="stepProximo()">
          Próximo <i class="fas fa-arrow-right ms-1"></i>
        </button>
        <button type="submit" id="btnSalvar" class="btn btn-success" style="display:none">
          <i class="fas fa-check me-1"></i><?php echo $btnLabel; ?>
        </button>
      </div>
    </div>

  </form>
</div>

<!-- Toast de feedback -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
  <div id="toastMsg" class="toast align-items-center text-white border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toastBody"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script>
// ─── Estado ──────────────────────────────────────────────────────────────────
let currentStep = 1;
const totalSteps = 4;
let itemCount = <?php echo max(count($itens), 0); ?>;

// ─── Navegação ───────────────────────────────────────────────────────────────
function goToStep(step) {
  if (step < 1 || step > totalSteps) return;
  if (step > currentStep && !validarEtapa(currentStep)) return;

  document.querySelector('#panel-' + currentStep).classList.remove('active');
  document.querySelector('#step-btn-' + currentStep).classList.remove('active');
  if (step > currentStep) {
    document.querySelector('#step-btn-' + currentStep).classList.add('done');
  } else {
    document.querySelector('#step-btn-' + currentStep).classList.remove('done');
  }

  currentStep = step;
  document.querySelector('#panel-' + currentStep).classList.add('active');
  document.querySelector('#step-btn-' + currentStep).classList.add('active');

  document.querySelector('#stepIndicator').textContent = 'Etapa ' + currentStep + ' de ' + totalSteps;
  document.querySelector('#btnAnterior').style.display = currentStep > 1 ? '' : 'none';
  document.querySelector('#btnProximo').style.display  = currentStep < totalSteps ? '' : 'none';
  document.querySelector('#btnSalvar').style.display   = currentStep === totalSteps ? '' : 'none';

  if (currentStep === totalSteps) montarResumo();
}

function stepProximo() { goToStep(currentStep + 1); }
function stepAnterior() { goToStep(currentStep - 1); }

// ─── Validação por etapa ──────────────────────────────────────────────────────
function validarEtapa(step) {
  if (step === 1) {
    const nome   = document.querySelector('#cliente_nome').value.trim();
    const titulo = document.querySelector('#titulo').value.trim();
    if (!nome)   { showToast('Informe o nome do cliente.', 'danger'); return false; }
    if (!titulo) { showToast('Informe o título da proposta.', 'danger'); return false; }
  }
  if (step === 2) {
    const validade = document.querySelector('#validade_proposta').value;
    if (!validade) { showToast('Informe a validade da proposta.', 'danger'); return false; }
    const itens = document.querySelectorAll('[name="item_descricao[]"]');
    if (itens.length === 0) { showToast('Adicione pelo menos um item à proposta.', 'danger'); return false; }
    for (let i = 0; i < itens.length; i++) {
      if (!itens[i].value.trim()) { showToast('Preencha a descrição de todos os itens.', 'danger'); return false; }
    }
  }
  return true;
}

// ─── Importar Oportunidade ────────────────────────────────────────────────────
async function importarOportunidade() {
  const id = document.querySelector('#inputOpId').value.trim();
  if (!id) { showToast('Informe o número da oportunidade.', 'warning'); return; }

  const msg = document.querySelector('#opImportMsg');
  msg.textContent = 'Buscando...';
  msg.className   = 'form-text text-muted';

  try {
    const r = await fetch('/crm/propostas/buscar-oportunidade?id=' + id);
    const j = await r.json();
    if (!j.success) { msg.textContent = j.error || 'Não encontrada.'; msg.className = 'form-text text-danger'; return; }

    const d = j.data;
    preencherCliente({
      id:           d.cliente_id_ref || '',
      nome:         d.cliente_nome_display || '',
      razao_social: d.razao_social || '',
      cnpj_cpf:     d.cliente_doc || '',
      email:        d.cliente_email_display || '',
      telefone:     d.cliente_tel_display || '',
      endereco:     d.endereco ? (d.endereco + (d.c_numero ? ', ' + d.c_numero : '')) : '',
      cidade:       d.cidade || '',
      estado:       d.estado || '',
      cep:          d.cep || '',
      responsavel:  d.responsavel_nome || '',
    });

    document.querySelector('#oportunidade_id').value = d.id || '';
    document.querySelector('#lead_id').value         = d.lead_id_ref || '';

    if (!document.querySelector('#titulo').value) {
      document.querySelector('#titulo').value = 'Proposta — ' + (d.titulo || '');
    }

    msg.textContent = '✓ Dados importados da oportunidade #' + d.id;
    msg.className   = 'form-text text-success';
  } catch(e) {
    msg.textContent = 'Erro ao buscar oportunidade.';
    msg.className   = 'form-text text-danger';
  }
}

// ─── Buscar Cliente ───────────────────────────────────────────────────────────
let buscaTimer = null;
async function buscarCliente(q) {
  clearTimeout(buscaTimer);
  const res = document.querySelector('#buscaClienteResultados');
  if (q.length < 2) { res.style.display = 'none'; return; }

  buscaTimer = setTimeout(async () => {
    try {
      const r = await fetch('/crm/propostas/buscar-cliente?q=' + encodeURIComponent(q));
      const j = await r.json();
      if (!j.success || !j.data.length) { res.style.display = 'none'; return; }

      res.innerHTML = j.data.map(c => `
        <div class="busca-item" onclick="selecionarCliente(${JSON.stringify(c).replace(/"/g,'&quot;')})">
          <div class="bi-nome">${c.nome || c.razao_social || ''}</div>
          <div class="bi-sub">${c.cpf_cnpj || ''} · ${c.email || ''}</div>
        </div>`).join('');
      res.style.display = 'block';
    } catch(e) { res.style.display = 'none'; }
  }, 300);
}

function selecionarCliente(c) {
  preencherCliente({
    id:           c.id || '',
    nome:         c.nome || c.razao_social || '',
    razao_social: c.razao_social || '',
    cnpj_cpf:     c.cpf_cnpj || '',
    email:        c.email || '',
    telefone:     c.telefone || '',
    endereco:     c.endereco || '',
    cidade:       c.cidade || '',
    estado:       c.estado || '',
    cep:          c.cep || '',
    responsavel:  c.responsavel_nome || '',
  });
  document.querySelector('#buscaClienteResultados').style.display = 'none';
  document.querySelector('#buscaCliente').value = '';
}

function preencherCliente(d) {
  const set = (id, val) => { const el = document.querySelector('#' + id); if (el) el.value = val || ''; };
  set('cliente_id',           d.id);
  set('cliente_nome',         d.nome);
  set('cliente_razao_social', d.razao_social);
  set('cliente_cnpj_cpf',     d.cnpj_cpf);
  set('cliente_email',        d.email);
  set('cliente_telefone',     d.telefone);
  set('cliente_endereco',     d.endereco);
  set('cliente_cidade',       d.cidade);
  set('cliente_estado',       d.estado);
  set('cliente_cep',          d.cep);
  set('cliente_responsavel',  d.responsavel);
}

// Fechar dropdown ao clicar fora
document.addEventListener('click', e => {
  if (!e.target.closest('#buscaCliente') && !e.target.closest('#buscaClienteResultados')) {
    const r = document.querySelector('#buscaClienteResultados');
    if (r) r.style.display = 'none';
  }
});

// ─── Itens ────────────────────────────────────────────────────────────────────
function adicionarItem() {
  const idx = itemCount++;
  const div = document.createElement('div');
  div.className = 'item-row';
  div.id = 'item-row-' + idx;
  div.innerHTML = `
    <button type="button" class="remove-item" onclick="removerItem(${idx})" title="Remover item">
      <i class="fas fa-times"></i>
    </button>
    <div class="row g-2">
      <div class="col-md-2">
        <label class="form-label form-label-sm">Código</label>
        <input type="text" name="item_codigo[]" class="form-control form-control-sm" placeholder="SKU...">
        <input type="hidden" name="item_produto_id[]" value="">
      </div>
      <div class="col-md-4">
        <label class="form-label form-label-sm">Descrição <span class="text-danger">*</span></label>
        <input type="text" name="item_descricao[]" class="form-control form-control-sm" required placeholder="Descrição do item...">
      </div>
      <div class="col-md-1">
        <label class="form-label form-label-sm">Un.</label>
        <input type="text" name="item_unidade[]" class="form-control form-control-sm" value="un" placeholder="un">
      </div>
      <div class="col-md-1">
        <label class="form-label form-label-sm">Qtd.</label>
        <input type="number" name="item_quantidade[]" class="form-control form-control-sm item-qtd"
          value="1" min="0.001" step="0.001" oninput="recalcularItem(this)">
      </div>
      <div class="col-md-2">
        <label class="form-label form-label-sm">Preço Unit. (R$)</label>
        <input type="number" name="item_preco_unitario[]" class="form-control form-control-sm item-preco"
          value="0" min="0" step="0.01" oninput="recalcularItem(this)">
      </div>
      <div class="col-md-1">
        <label class="form-label form-label-sm">Desc. %</label>
        <input type="number" name="item_desconto[]" class="form-control form-control-sm item-desc"
          value="0" min="0" max="100" step="0.01" oninput="recalcularItem(this)">
      </div>
      <div class="col-md-1">
        <label class="form-label form-label-sm">Custo (R$)</label>
        <input type="number" name="item_preco_custo[]" class="form-control form-control-sm item-custo"
          value="0" min="0" step="0.01" oninput="recalcularItem(this)">
        <input type="hidden" name="item_margem[]" class="item-margem" value="0">
      </div>
    </div>
    <div class="row g-2 mt-1 align-items-center">
      <div class="col-md-2 offset-md-7">
        <div class="item-margem-display small"></div>
      </div>
      <div class="col-md-2 text-end">
        <span class="text-muted small">Total: </span>
        <strong class="item-total text-success">R$ 0,00</strong>
      </div>
    </div>`;

  document.querySelector('#listaItens').appendChild(div);
  document.querySelector('#semItens').classList.add('d-none');
  recalcularTotais();
}

function removerItem(idx) {
  const row = document.querySelector('#item-row-' + idx);
  if (row) row.remove();
  if (!document.querySelectorAll('.item-row').length) {
    document.querySelector('#semItens').classList.remove('d-none');
  }
  recalcularTotais();
}

function recalcularItem(input) {
  const row   = input.closest('.item-row');
  if (!row) return;
  const qtd   = parseFloat(row.querySelector('.item-qtd')?.value   || 0);
  const preco = parseFloat(row.querySelector('.item-preco')?.value  || 0);
  const desc  = parseFloat(row.querySelector('.item-desc')?.value   || 0);
  const custo = parseFloat(row.querySelector('.item-custo')?.value  || 0);
  const total = qtd * preco * (1 - desc / 100);

  const totalEl  = row.querySelector('.item-total');
  const margemEl = row.querySelector('.item-margem-display');
  const margemIn = row.querySelector('.item-margem');

  if (totalEl) totalEl.textContent = 'R$ ' + formatNum(total);

  // Calcular margem de lucro
  if (custo > 0 && preco > 0) {
    const margem = ((preco - custo) / preco) * 100;
    if (margemIn) margemIn.value = margem.toFixed(2);
    if (margemEl) {
      const cls = margem >= 0 ? 'margem-pos' : 'margem-neg';
      margemEl.innerHTML = `<span class="margem-badge ${cls}">Margem: ${margem.toFixed(1)}%</span>`;
    }
  } else {
    if (margemIn) margemIn.value = '0';
    if (margemEl) margemEl.innerHTML = '';
  }

  recalcularTotais();
}

function recalcularTotais() {
  let subtotal = 0;
  document.querySelectorAll('.item-row').forEach(row => {
    const qtd   = parseFloat(row.querySelector('.item-qtd')?.value   || 0);
    const preco = parseFloat(row.querySelector('.item-preco')?.value  || 0);
    const desc  = parseFloat(row.querySelector('.item-desc')?.value   || 0);
    subtotal += qtd * preco * (1 - desc / 100);
  });

  const tipoDesc = document.querySelector('#desconto_tipo')?.value || '';
  const valDesc  = parseFloat(document.querySelector('#desconto_valor')?.value || 0);
  const freteTipo = document.querySelector('#frete_tipo')?.value || '';
  const freteVal = freteTipo === 'valor' ? parseFloat(document.querySelector('#frete_valor')?.value || 0) : 0;

  let desconto = 0;
  if (tipoDesc === 'percentual') desconto = subtotal * (valDesc / 100);
  else if (tipoDesc === 'fixo')  desconto = valDesc;

  const total = subtotal - desconto + freteVal;

  const set = (id, val) => { const el = document.querySelector(id); if (el) el.textContent = 'R$ ' + formatNum(val); };
  set('#subtotalDisplay', subtotal);
  set('#descontoDisplay', desconto);
  set('#rv-subtotal', subtotal);
  set('#rv-desconto', desconto);
  set('#rv-frete',    freteVal);
  set('#rv-total',    total);
}

function toggleFrete() {
  const tipo = document.querySelector('#frete_tipo').value;
  const div  = document.querySelector('#freteValorDiv');
  if (div) div.style.display = tipo === 'valor' ? '' : 'none';
  recalcularTotais();
}

// ─── Resumo ───────────────────────────────────────────────────────────────────
function montarResumo() {
  const g = id => document.querySelector('#' + id)?.value || '—';

  document.querySelector('#resumo-cliente').innerHTML =
    `<strong>${g('cliente_nome')}</strong><br>
     ${g('cliente_cnpj_cpf') !== '—' ? g('cliente_cnpj_cpf') + '<br>' : ''}
     ${g('cliente_email') !== '—' ? g('cliente_email') + '<br>' : ''}
     ${g('cliente_telefone') !== '—' ? g('cliente_telefone') : ''}`;

  const validade = g('validade_proposta');
  const valFmt   = validade !== '—' ? new Date(validade + 'T00:00:00').toLocaleDateString('pt-BR') : '—';
  document.querySelector('#resumo-proposta').innerHTML =
    `<strong>${g('titulo')}</strong><br>Validade: ${valFmt}`;

  const itens = document.querySelectorAll('.item-row');
  let itensHtml = '';
  itens.forEach((row, i) => {
    const desc  = row.querySelector('[name="item_descricao[]"]')?.value || '';
    const qtd   = row.querySelector('.item-qtd')?.value || '1';
    const preco = row.querySelector('.item-preco')?.value || '0';
    const total = parseFloat(qtd) * parseFloat(preco);
    itensHtml += `<div class="resumo-row"><span>${i+1}. ${desc} (${qtd} un)</span><span>R$ ${formatNum(total)}</span></div>`;
  });
  document.querySelector('#resumo-itens').innerHTML = itensHtml || 'Nenhum item.';

  const freteTipo = document.querySelector('#frete_tipo')?.value || '';
  const freteLabel = {a_calcular:'A calcular', cif:'CIF', fob:'FOB', gratis:'Grátis', valor:'Valor fixo'}[freteTipo] || freteTipo;
  document.querySelector('#resumo-entrega').innerHTML =
    `Prazo: ${g('prazo_entrega')}<br>Frete: ${freteLabel}<br>Pagamento: ${g('condicao_pagamento') !== '—' ? g('condicao_pagamento') : '—'}`;

  recalcularTotais();
}

// ─── Submit ───────────────────────────────────────────────────────────────────
document.querySelector('#formProposta').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.querySelector('#btnSalvar');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';

  try {
    const fd = new FormData(this);
    const r  = await fetch(this.action, { method: 'POST', body: fd });
    const j  = await r.json();
    if (j.success) {
      window.location.href = j.redirect;
    } else {
      showToast(j.error || 'Erro ao salvar proposta.', 'danger');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-check me-1"></i><?php echo $btnLabel; ?>';
    }
  } catch(err) {
    showToast('Erro de comunicação com o servidor.', 'danger');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check me-1"></i><?php echo $btnLabel; ?>';
  }
});

// ─── Toast ────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
  const el = document.querySelector('#toastMsg');
  el.className = 'toast align-items-center text-white border-0 bg-' + type;
  document.querySelector('#toastBody').textContent = msg;
  new bootstrap.Toast(el, { delay: 4000 }).show();
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function formatNum(n) {
  return parseFloat(n || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Init
document.addEventListener('DOMContentLoaded', () => {
  toggleFrete();
  recalcularTotais();
  // Recalcular itens existentes (edição)
  document.querySelectorAll('.item-row').forEach(row => {
    const qtdEl = row.querySelector('.item-qtd');
    if (qtdEl) recalcularItem(qtdEl);
  });
  if (document.querySelectorAll('.item-row').length > 0) {
    document.querySelector('#semItens')?.classList.add('d-none');
  }
});
</script>
