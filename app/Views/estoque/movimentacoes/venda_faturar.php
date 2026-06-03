<?php
$pedido           = $pedido           ?? null;
$itens            = $itens            ?? [];
$historico        = $historico        ?? [];
$clienteExistente = $clienteExistente ?? null;

if (!$pedido) { header('Location: /estoque/vendas'); exit(); }

$statusConfig = [
    'rascunho'   => ['label' => 'Rascunho',   'color' => 'secondary', 'bg' => '#f3f4f6', 'text' => '#374151'],
    'confirmado' => ['label' => 'Confirmado',  'color' => 'primary',   'bg' => '#eff6ff', 'text' => '#1d4ed8'],
    'aberto'     => ['label' => 'Aberto',      'color' => 'info',      'bg' => '#ecfeff', 'text' => '#0e7490'],
    'expedido'   => ['label' => 'Expedido',    'color' => 'warning',   'bg' => '#fffbeb', 'text' => '#92400e'],
    'faturado'   => ['label' => 'Faturado',    'color' => 'success',   'bg' => '#f0fdf4', 'text' => '#166534'],
    'cancelado'  => ['label' => 'Cancelado',   'color' => 'danger',    'bg' => '#fef2f2', 'text' => '#991b1b'],
];
$sc = $statusConfig[$pedido->status] ?? $statusConfig['rascunho'];

$meioPagamentoOpcoes = [
    ''            => '— Selecione —',
    'boleto'      => 'Boleto Bancário',
    'pix'         => 'PIX',
    'cartao'      => 'Cartão de Crédito',
    'transferencia'=> 'Transferência Bancária',
    'dinheiro'    => 'Dinheiro',
    'cheque'      => 'Cheque',
    'a_prazo'     => 'A Prazo',
    'outros'      => 'Outros',
];
?>
<style>
.fat-header{background:linear-gradient(135deg,#166534 0%,#14532d 100%);color:#fff;border-radius:.75rem;padding:1.5rem 2rem;margin-bottom:1.5rem;box-shadow:0 4px 12px rgba(22,101,52,.2)}
.fat-header .numero{font-size:1.4rem;font-weight:700}
.info-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.06);margin-bottom:1rem}
.info-card-title{font-size:.7rem;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;font-weight:700;margin-bottom:.75rem;padding-bottom:.5rem;border-bottom:1px solid #e2e8f0}
.info-row{display:flex;justify-content:space-between;align-items:flex-start;padding:.3rem 0;border-bottom:1px solid #f1f5f9;font-size:.875rem}
.info-row:last-child{border-bottom:none}
.info-label{color:#6b7280;flex-shrink:0;margin-right:.5rem}
.info-value{font-weight:500;color:#1e293b;text-align:right}
.itens-table th{background:#f8fafc;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;font-weight:600;padding:.6rem .75rem;border-bottom:2px solid #e2e8f0}
.itens-table td{padding:.65rem .75rem;border-bottom:1px solid #f1f5f9;font-size:.875rem;vertical-align:middle}
.itens-table tr:last-child td{border-bottom:none}
.timeline-item{display:flex;gap:.75rem;padding:.6rem 0;border-bottom:1px solid #f1f5f9}
.timeline-item:last-child{border-bottom:none}
.timeline-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;margin-top:.35rem}
.step-badge{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;font-size:.75rem;font-weight:700;flex-shrink:0}
.step-active{background:#166534;color:#fff}
.step-pending{background:#e2e8f0;color:#6b7280}
.total-box{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:.5rem;padding:1rem 1.25rem}
.total-row{display:flex;justify-content:space-between;padding:.3rem 0;font-size:.875rem}
.total-final{font-size:1.2rem;font-weight:700;color:#166534;border-top:2px solid #86efac;padding-top:.5rem;margin-top:.25rem}
</style>

<div class="container-fluid">

  <!-- Cabeçalho -->
  <div class="fat-header">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <a href="/estoque/vendas/<?php echo $pedido->id; ?>" class="btn btn-sm btn-light opacity-75">
            <i class="fas fa-arrow-left"></i>
          </a>
          <span class="numero"><i class="fas fa-file-invoice-dollar me-2"></i>Faturar <?php echo htmlspecialchars($pedido->numero); ?></span>
        </div>
        <div style="font-size:.9rem;opacity:.85"><?php echo htmlspecialchars($pedido->cliente_nome ?? '—'); ?></div>
        <div style="font-size:.75rem;opacity:.7;margin-top:.25rem">
          Pedido criado em <?php echo date('d/m/Y', strtotime($pedido->created_at ?? 'now')); ?>
        </div>
      </div>
      <div class="text-end">
        <div style="font-size:.8rem;opacity:.8;margin-bottom:.25rem">Valor Total</div>
        <div style="font-size:1.8rem;font-weight:700">R$ <?php echo number_format((float)$pedido->valor_total, 2, ',', '.'); ?></div>
        <span class="badge" style="background:<?php echo $sc['bg']; ?>;color:<?php echo $sc['text']; ?>;font-size:.75rem">
          <?php echo $sc['label']; ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Passos do faturamento -->
  <div class="info-card mb-3">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <div class="d-flex align-items-center gap-2">
        <span class="step-badge step-active">1</span>
        <span style="font-size:.85rem;font-weight:600;color:#166534">Dados Financeiros</span>
      </div>
      <div style="flex:1;height:2px;background:#e2e8f0;min-width:20px"></div>
      <div class="d-flex align-items-center gap-2">
        <span class="step-badge step-active">2</span>
        <span style="font-size:.85rem;font-weight:600;color:#166534">Conta a Receber</span>
      </div>
      <div style="flex:1;height:2px;background:#e2e8f0;min-width:20px"></div>
      <div class="d-flex align-items-center gap-2">
        <span class="step-badge step-active">3</span>
        <span style="font-size:.85rem;font-weight:600;color:#166534">NF (opcional)</span>
      </div>
      <div style="flex:1;height:2px;background:#e2e8f0;min-width:20px"></div>
      <div class="d-flex align-items-center gap-2">
        <span class="step-badge step-active">4</span>
        <span style="font-size:.85rem;font-weight:600;color:#166534">Confirmar</span>
      </div>
    </div>
  </div>

  <div class="row g-3">

    <!-- Coluna principal: formulário -->
    <div class="col-lg-8">

      <!-- Aviso sobre cliente -->
      <?php if ($clienteExistente): ?>
      <div class="alert alert-info py-2 px-3 mb-3" style="font-size:.85rem">
        <i class="fas fa-user-check me-1"></i>
        Cliente <strong><?php echo htmlspecialchars($clienteExistente->razao_social); ?></strong> já cadastrado no sistema.
        <input type="hidden" id="clienteIdPreenchido" value="<?php echo $clienteExistente->id; ?>">
      </div>
      <?php else: ?>
      <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:.85rem">
        <i class="fas fa-user-plus me-1"></i>
        Cliente <strong><?php echo htmlspecialchars($pedido->cliente_nome ?? '—'); ?></strong> será criado automaticamente no módulo de Clientes ao faturar.
      </div>
      <?php endif; ?>

      <!-- Formulário de faturamento -->
      <form id="formFaturar">
        <input type="hidden" name="cliente_id" value="<?php echo $clienteExistente ? $clienteExistente->id : ''; ?>">

        <!-- Dados financeiros -->
        <div class="info-card">
          <div class="info-card-title"><i class="fas fa-money-bill-wave me-1"></i>Dados Financeiros — Conta a Receber</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Data de Vencimento <span class="text-danger">*</span></label>
              <input type="date" name="data_vencimento" class="form-control"
                value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
              <div class="form-text">Prazo para pagamento da conta a receber.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Meio de Pagamento</label>
              <select name="meio_pagamento" class="form-select">
                <?php foreach ($meioPagamentoOpcoes as $val => $label): ?>
                <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Observações Financeiras</label>
              <textarea name="obs_financeiro" class="form-control form-control-sm" rows="2"
                placeholder="Ex: Pagamento em 3x, referência do pedido, etc."></textarea>
            </div>
          </div>
        </div>

        <!-- Emissão de NF -->
        <div class="info-card">
          <div class="info-card-title"><i class="fas fa-file-invoice me-1"></i>Nota Fiscal (opcional)</div>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="emitir_nf" id="emitirNf" value="1"
              onchange="toggleNfFields(this.checked)">
            <label class="form-check-label fw-semibold" for="emitirNf">
              Criar rascunho de NF para emissão
            </label>
          </div>
          <div id="nfFields" style="display:none">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Série NF</label>
                <input type="text" name="serie_nf" class="form-control form-control-sm" value="1" maxlength="5">
              </div>
              <div class="col-md-8">
                <label class="form-label">Código do Serviço (CNAE/LC116)</label>
                <input type="text" name="servico_codigo" class="form-control form-control-sm"
                  placeholder="Ex: 14.01, 7.01, etc.">
              </div>
              <div class="col-12">
                <label class="form-label">Descrição do Serviço</label>
                <textarea name="servico_descricao" class="form-control form-control-sm" rows="2"
                  placeholder="Descrição que constará na NF..."><?php echo htmlspecialchars('Pedido de Venda ' . $pedido->numero . ' — ' . $pedido->cliente_nome); ?></textarea>
              </div>
              <div class="col-12">
                <label class="form-label">Observações da NF</label>
                <textarea name="obs_nf" class="form-control form-control-sm" rows="2"
                  placeholder="Informações adicionais para a nota fiscal..."></textarea>
              </div>
            </div>
          </div>
          <div class="text-muted" style="font-size:.8rem">
            <i class="fas fa-info-circle me-1"></i>
            O rascunho de NF será criado em <strong>Faturamento &rsaquo; Notas Fiscais</strong> para emissão posterior.
          </div>
        </div>

        <!-- Resumo e confirmação -->
        <div class="info-card">
          <div class="info-card-title"><i class="fas fa-check-double me-1"></i>Confirmar Faturamento</div>
          <div class="total-box mb-3">
            <div class="total-row"><span class="text-muted">Pedido:</span><span class="fw-bold"><?php echo htmlspecialchars($pedido->numero); ?></span></div>
            <div class="total-row"><span class="text-muted">Cliente:</span><span><?php echo htmlspecialchars($pedido->cliente_nome ?? '—'); ?></span></div>
            <div class="total-row total-final">
              <span>Total a Receber:</span>
              <span>R$ <?php echo number_format((float)$pedido->valor_total, 2, ',', '.'); ?></span>
            </div>
          </div>
          <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:.82rem">
            <i class="fas fa-exclamation-triangle me-1"></i>
            Ao confirmar, as seguintes ações serão executadas <strong>automaticamente</strong>:
            <ul class="mb-0 mt-1 ps-3">
              <li>Pedido de venda marcado como <strong>Faturado</strong></li>
              <li>Conta a Receber criada em <strong>Financeiro &rsaquo; Contas a Receber</strong></li>
              <?php if ($clienteExistente): ?>
              <li>Cliente <strong><?php echo htmlspecialchars($clienteExistente->razao_social); ?></strong> vinculado</li>
              <?php else: ?>
              <li>Cliente <strong><?php echo htmlspecialchars($pedido->cliente_nome ?? '—'); ?></strong> criado em <strong>Clientes</strong></li>
              <?php endif; ?>
              <li id="nfAcaoTexto" style="display:none">Rascunho de NF criado em <strong>Faturamento</strong></li>
            </ul>
          </div>
          <div class="d-flex gap-2">
            <a href="/estoque/vendas/<?php echo $pedido->id; ?>" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left me-1"></i>Cancelar
            </a>
            <button type="button" class="btn btn-success flex-fill" onclick="confirmarFaturamento()">
              <i class="fas fa-check-circle me-1"></i>Confirmar Faturamento
            </button>
          </div>
        </div>
      </form>

    </div>

    <!-- Coluna lateral: resumo do pedido -->
    <div class="col-lg-4">

      <!-- Dados do cliente -->
      <div class="info-card">
        <div class="info-card-title"><i class="fas fa-user-tie me-1"></i>Dados do Cliente</div>
        <div class="info-row"><span class="info-label">Nome:</span><span class="info-value"><?php echo htmlspecialchars($pedido->cliente_nome ?? '—'); ?></span></div>
        <?php if (!empty($pedido->cliente_cpf_cnpj)): ?>
        <div class="info-row"><span class="info-label">CPF/CNPJ:</span><span class="info-value"><?php echo htmlspecialchars($pedido->cliente_cpf_cnpj); ?></span></div>
        <?php endif; ?>
        <?php if (!empty($pedido->cliente_email)): ?>
        <div class="info-row"><span class="info-label">E-mail:</span><span class="info-value"><?php echo htmlspecialchars($pedido->cliente_email); ?></span></div>
        <?php endif; ?>
        <?php if (!empty($pedido->cliente_telefone)): ?>
        <div class="info-row"><span class="info-label">Telefone:</span><span class="info-value"><?php echo htmlspecialchars($pedido->cliente_telefone); ?></span></div>
        <?php endif; ?>
        <?php if (!empty($pedido->cliente_endereco)): ?>
        <div class="info-row"><span class="info-label">Endereço:</span><span class="info-value"><?php echo htmlspecialchars($pedido->cliente_endereco); ?></span></div>
        <?php endif; ?>
      </div>

      <!-- Itens do pedido -->
      <div class="info-card">
        <div class="info-card-title"><i class="fas fa-boxes me-1"></i>Itens do Pedido</div>
        <?php if (empty($itens)): ?>
        <p class="text-muted small text-center py-2">Nenhum item.</p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table itens-table mb-0">
            <thead>
              <tr>
                <th>Produto</th>
                <th class="text-end">Qtd</th>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($itens as $item): ?>
              <tr>
                <td style="font-size:.8rem"><?php echo htmlspecialchars($item->produto_nome ?? $item->descricao ?? '—'); ?></td>
                <td class="text-end" style="font-size:.8rem"><?php echo number_format((float)$item->quantidade, 2, ',', '.'); ?></td>
                <td class="text-end fw-bold" style="font-size:.8rem">R$ <?php echo number_format((float)$item->total_item, 2, ',', '.'); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="total-box mt-2">
          <div class="total-row total-final">
            <span>Total:</span>
            <span>R$ <?php echo number_format((float)$pedido->valor_total, 2, ',', '.'); ?></span>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Histórico de evolução -->
      <?php if (!empty($historico)): ?>
      <div class="info-card">
        <div class="info-card-title"><i class="fas fa-history me-1"></i>Histórico</div>
        <div style="max-height:250px;overflow-y:auto">
          <?php
          $histStatusConfig = [
              'rascunho'   => ['label' => 'Rascunho',   'color' => '#374151'],
              'confirmado' => ['label' => 'Confirmado',  'color' => '#1d4ed8'],
              'aberto'     => ['label' => 'Aberto',      'color' => '#0e7490'],
              'expedido'   => ['label' => 'Expedido',    'color' => '#92400e'],
              'faturado'   => ['label' => 'Faturado',    'color' => '#166534'],
              'cancelado'  => ['label' => 'Cancelado',   'color' => '#991b1b'],
          ];
          foreach ($historico as $h):
            $hc = $histStatusConfig[$h->status_para] ?? ['label' => $h->status_para, 'color' => '#6b7280'];
          ?>
          <div class="timeline-item">
            <div class="timeline-dot" style="background:<?php echo $hc['color']; ?>"></div>
            <div style="flex:1">
              <div style="font-size:.8rem;font-weight:600;color:<?php echo $hc['color']; ?>"><?php echo $hc['label']; ?></div>
              <?php if (!empty($h->observacao)): ?>
              <div style="font-size:.75rem;color:#374151"><?php echo htmlspecialchars($h->observacao); ?></div>
              <?php endif; ?>
              <div style="font-size:.7rem;color:#6b7280">
                <?php echo htmlspecialchars($h->usuario_nome ?? 'Sistema'); ?> ·
                <?php echo date('d/m/Y H:i', strtotime($h->created_at)); ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script>
function toggleNfFields(show) {
    document.getElementById('nfFields').style.display = show ? 'block' : 'none';
    document.getElementById('nfAcaoTexto').style.display = show ? 'list-item' : 'none';
}

function confirmarFaturamento() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processando...';

    const form   = document.getElementById('formFaturar');
    const data   = new FormData(form);

    fetch('/estoque/vendas/<?php echo $pedido->id; ?>/faturar', {
        method: 'POST',
        body: data,
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.success) {
            let msg = 'Pedido faturado com sucesso!\n\n';
            msg += '✅ Conta a Receber criada (ID: ' + resp.conta_receber_id + ')\n';
            if (resp.nota_fiscal_id) msg += '✅ Rascunho de NF criado (ID: ' + resp.nota_fiscal_id + ')\n';
            if (resp.cliente_id) msg += '✅ Cliente vinculado (ID: ' + resp.cliente_id + ')';
            alert(msg);
            window.location.href = resp.redirect || '/estoque/vendas/<?php echo $pedido->id; ?>';
        } else {
            alert('Erro: ' + (resp.error || 'Falha ao faturar o pedido.'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle me-1"></i>Confirmar Faturamento';
        }
    })
    .catch(err => {
        alert('Erro de comunicação: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle me-1"></i>Confirmar Faturamento';
    });
}
</script>
