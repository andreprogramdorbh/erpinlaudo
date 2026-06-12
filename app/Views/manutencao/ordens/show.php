<?php
$os        = $os        ?? null;
$historico = $historico ?? [];
$trocas    = $trocas    ?? [];
$produtos  = $produtos  ?? [];
$proposta  = $proposta  ?? null;
$csrfToken = \App\Core\View::csrfToken();

$statusConfig = [
    'aberta'          => ['label' => 'Aberta',           'color' => 'primary',   'icon' => 'fa-folder-open'],
    'em_andamento'    => ['label' => 'Em Andamento',     'color' => 'info',      'icon' => 'fa-tools'],
    'aguardando_peca' => ['label' => 'Aguard. Peça',     'color' => 'warning',   'icon' => 'fa-clock'],
    'concluida'       => ['label' => 'Concluída',        'color' => 'success',   'icon' => 'fa-check-circle'],
    'faturada'        => ['label' => 'Faturada',         'color' => 'dark',      'icon' => 'fa-receipt'],
    'cancelada'       => ['label' => 'Cancelada',        'color' => 'danger',    'icon' => 'fa-times-circle'],
];
$tipoConfig = [
    'preventiva' => ['label' => 'Preventiva', 'color' => 'success'],
    'corretiva'  => ['label' => 'Corretiva',  'color' => 'danger'],
];
$st = $statusConfig[$os->status]  ?? ['label' => ucfirst($os->status), 'color' => 'secondary', 'icon' => 'fa-circle'];
$tp = $tipoConfig[$os->tipo]      ?? ['label' => ucfirst($os->tipo),   'color' => 'secondary'];
$isFaturada = $os->status === 'faturada';
$isCancelada = $os->status === 'cancelada';
?>
<style>
.os-show-card{background:#fff;border:1px solid #e2e8f0;border-radius:.75rem;padding:1.25rem;margin-bottom:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.os-show-card h5{font-size:.875rem;font-weight:700;color:#1e293b;margin-bottom:.875rem;padding-bottom:.5rem;border-bottom:1px solid #f1f5f9}
.os-show-card h5 i{color:#1a56db;margin-right:.4rem}
.info-row{display:flex;gap:.5rem;margin-bottom:.5rem;font-size:.84rem}
.info-label{color:#6b7280;min-width:140px;flex-shrink:0}
.info-val{color:#1e293b;font-weight:500}
.timeline-item{display:flex;gap:.75rem;margin-bottom:1rem}
.timeline-dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.7rem;margin-top:.1rem}
.timeline-content{flex:1;background:#f8fafc;border:1px solid #e2e8f0;border-radius:.5rem;padding:.6rem .875rem}
.timeline-meta{font-size:.72rem;color:#94a3b8;margin-top:.25rem}
.troca-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.5rem;padding:.875rem;margin-bottom:.75rem}
.troca-card .troca-title{font-weight:600;font-size:.84rem;color:#1e293b}
.troca-card .troca-sub{font-size:.75rem;color:#64748b;margin-top:.2rem}
.prox-troca-badge{background:#dbeafe;color:#1d4ed8;padding:.2em .6em;border-radius:20px;font-size:.72rem;font-weight:600}
.os-header{background:linear-gradient(135deg,#1a56db 0%,#1e40af 100%);color:#fff;border-radius:.75rem;padding:1.5rem;margin-bottom:1.25rem}
</style>

<div class="container-fluid">
  <!-- Header -->
  <div class="os-header">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <a href="/manutencao/ordens" class="btn btn-sm btn-light btn-sm" style="opacity:.85"><i class="fas fa-arrow-left"></i></a>
          <h3 class="mb-0 fw-bold"><?= htmlspecialchars($os->numero) ?></h3>
          <span class="badge bg-<?= $st['color'] ?> bg-opacity-25 border border-<?= $st['color'] ?> text-white">
            <i class="fas <?= $st['icon'] ?> me-1"></i><?= $st['label'] ?>
          </span>
          <span class="badge bg-<?= $tp['color'] ?> bg-opacity-25 border border-<?= $tp['color'] ?> text-white">
            <?= $tp['label'] ?>
          </span>
        </div>
        <p class="mb-0 opacity-85" style="font-size:.9rem">
          <?= htmlspecialchars($os->cliente_nome) ?>
          <?php if (!empty($os->produto_nome)): ?> — <?= htmlspecialchars($os->produto_nome) ?><?php endif; ?>
        </p>
        <p class="mb-0 opacity-75" style="font-size:.8rem">Aberta em <?= date('d/m/Y', strtotime($os->data_abertura)) ?></p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <?php if (!$isFaturada && !$isCancelada): ?>
        <a href="/manutencao/ordens/<?= $os->id ?>/edit" class="btn btn-sm btn-light"><i class="fas fa-edit me-1"></i>Editar</a>
        <?php endif; ?>
        <a href="/manutencao/ordens/<?= $os->id ?>/imprimir" target="_blank" class="btn btn-sm btn-light"><i class="fas fa-print me-1"></i>Imprimir</a>
        <?php if (!empty($os->cliente_email) && !$isCancelada): ?>
        <button class="btn btn-sm btn-light" id="btnEnviarEmail"><i class="fas fa-envelope me-1"></i>Enviar por E-mail</button>
        <?php endif; ?>
        <?php if (!$isFaturada && !$isCancelada): ?>
        <button class="btn btn-sm btn-danger" id="btnCancelarOS"><i class="fas fa-times me-1"></i>Cancelar O.S</button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <!-- Coluna principal -->
    <div class="col-lg-8">

      <!-- Dados do Cliente -->
      <div class="os-show-card">
        <h5><i class="fas fa-user"></i> Dados do Cliente</h5>
        <div class="row">
          <div class="col-md-6">
            <div class="info-row"><span class="info-label">Nome:</span><span class="info-val"><?= htmlspecialchars($os->cliente_nome) ?></span></div>
            <div class="info-row"><span class="info-label">CPF / CNPJ:</span><span class="info-val"><?= htmlspecialchars($os->cliente_cpf_cnpj ?? '-') ?></span></div>
            <div class="info-row"><span class="info-label">E-mail:</span><span class="info-val"><?= htmlspecialchars($os->cliente_email ?? '-') ?></span></div>
            <div class="info-row"><span class="info-label">Telefone:</span><span class="info-val"><?= htmlspecialchars($os->cliente_telefone ?? '-') ?></span></div>
          </div>
          <div class="col-md-6">
            <div class="info-row"><span class="info-label">Endereço:</span><span class="info-val"><?= htmlspecialchars($os->cliente_endereco ?? '-') ?></span></div>
            <div class="info-row"><span class="info-label">Cidade / UF:</span><span class="info-val"><?= htmlspecialchars(($os->cliente_cidade ?? '') . ($os->cliente_estado ? ' - ' . $os->cliente_estado : '')) ?: '-' ?></span></div>
          </div>
        </div>
      </div>

      <!-- Equipamento -->
      <div class="os-show-card">
        <h5><i class="fas fa-cog"></i> Equipamento</h5>
        <div class="row">
          <div class="col-md-6">
            <div class="info-row"><span class="info-label">Produto / Equipamento:</span><span class="info-val"><?= htmlspecialchars($os->produto_nome ?? '-') ?></span></div>
            <div class="info-row"><span class="info-label">Código:</span><span class="info-val"><?= htmlspecialchars($os->produto_codigo ?? '-') ?></span></div>
            <div class="info-row"><span class="info-label">Número de Série:</span><span class="info-val fw-bold"><?= htmlspecialchars($os->numero_serie ?? '-') ?></span></div>
          </div>
          <div class="col-md-6">
            <div class="info-row"><span class="info-label">Marca / Modelo:</span><span class="info-val"><?= htmlspecialchars(($os->marca ?? '') . (!empty($os->modelo ?? '') ? ' / ' . ($os->modelo ?? '') : '')) ?: '-' ?></span></div>
            <div class="info-row"><span class="info-label">Vida Útil:</span><span class="info-val"><?= !empty($os->vida_util_meses ?? null) ? ($os->vida_util_meses) . ' meses' : '-' ?></span></div>
          </div>
        </div>
      </div>

      <!-- Dados da O.S -->
      <div class="os-show-card">
        <h5><i class="fas fa-clipboard-list"></i> Dados da Ordem de Serviço</h5>
        <div class="row">
          <div class="col-md-6">
            <div class="info-row"><span class="info-label">Tipo:</span><span class="info-val"><span class="badge bg-<?= $tp['color'] ?>-subtle text-<?= $tp['color'] ?>"><?= $tp['label'] ?></span></span></div>
            <div class="info-row"><span class="info-label">Técnico:</span><span class="info-val"><?= htmlspecialchars($os->tecnico_responsavel ?? '-') ?></span></div>
            <div class="info-row"><span class="info-label">Data Abertura:</span><span class="info-val"><?= date('d/m/Y', strtotime($os->data_abertura)) ?></span></div>
            <div class="info-row"><span class="info-label">Previsão:</span><span class="info-val"><?= !empty($os->data_previsao) ? date('d/m/Y', strtotime($os->data_previsao)) : '-' ?></span></div>
            <?php if (!empty($os->data_conclusao)): ?>
            <div class="info-row"><span class="info-label">Conclusão:</span><span class="info-val"><?= date('d/m/Y', strtotime($os->data_conclusao)) ?></span></div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <div class="info-row"><span class="info-label">Motivo do Chamado:</span></div>
            <div class="p-2 bg-light rounded mb-2" style="font-size:.84rem"><?= nl2br(htmlspecialchars($os->motivo_chamado)) ?></div>
            <?php if (!empty($os->descricao_servico)): ?>
            <div class="info-row"><span class="info-label">Descrição:</span></div>
            <div class="p-2 bg-light rounded" style="font-size:.84rem"><?= nl2br(htmlspecialchars($os->descricao_servico)) ?></div>
            <?php endif; ?>
          </div>
        </div>
        <?php if (!empty($os->evolucao)): ?>
        <div class="mt-3">
          <div class="info-label mb-1"><i class="fas fa-chart-line text-info me-1"></i>Evolução da Manutenção:</div>
          <div class="p-3 border rounded" style="font-size:.84rem;background:#f0f9ff;border-color:#bae6fd!important"><?= nl2br(htmlspecialchars($os->evolucao)) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($os->observacoes)): ?>
        <div class="mt-2">
          <div class="info-label mb-1">Observações:</div>
          <div class="p-2 bg-light rounded" style="font-size:.84rem"><?= nl2br(htmlspecialchars($os->observacoes)) ?></div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Itens de Troca -->
      <div class="os-show-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Itens Trocados / Serviços Realizados</h5>
          <?php if (!$isFaturada && !$isCancelada): ?>
          <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAddTroca">
            <i class="fas fa-plus me-1"></i> Adicionar Item
          </button>
          <?php endif; ?>
        </div>

        <?php if (empty($trocas)): ?>
        <p class="text-muted small">Nenhum item de troca registrado ainda.</p>
        <?php else: ?>
        <?php foreach ($trocas as $t): ?>
        <div class="troca-card" id="troca-<?= $t->id ?>">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <div class="troca-title">
                <?php if (!empty($t->produto_codigo)): ?><span class="text-muted me-1">[<?= htmlspecialchars($t->produto_codigo) ?>]</span><?php endif; ?>
                <?= htmlspecialchars($t->descricao) ?>
              </div>
              <div class="troca-sub">
                Qtd: <?= number_format((float)$t->quantidade, 3, ',', '.') ?> <?= htmlspecialchars($t->unidade ?? 'UN') ?>
                — Unit.: R$ <?= number_format((float)$t->preco_unitario, 2, ',', '.') ?>
                — <strong>Total: R$ <?= number_format((float)$t->preco_total, 2, ',', '.') ?></strong>
              </div>
              <?php if (!empty($t->vida_util_meses)): ?>
              <div class="troca-sub mt-1">
                <i class="fas fa-clock text-warning me-1"></i>Vida útil: <?= $t->vida_util_meses ?> meses
                <?php if (!empty($t->data_proxima_troca)): ?>
                — <span class="prox-troca-badge"><i class="fas fa-calendar-alt me-1"></i>Próx. troca: <?= date('d/m/Y', strtotime($t->data_proxima_troca)) ?></span>
                <?php endif; ?>
              </div>
              <?php endif; ?>
              <?php if (!empty($t->observacoes)): ?>
              <div class="troca-sub mt-1 text-muted"><?= htmlspecialchars($t->observacoes) ?></div>
              <?php endif; ?>
            </div>
            <?php if (!$isFaturada && !$isCancelada): ?>
            <button class="btn btn-xs btn-outline-danger ms-2 btn-del-troca" data-id="<?= $t->id ?>" title="Remover">
              <i class="fas fa-trash"></i>
            </button>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <div class="text-end mt-2">
          <span class="text-muted small me-2">Valor Peças:</span>
          <strong id="valorPecas">R$ <?= number_format((float)$os->valor_pecas, 2, ',', '.') ?></strong>
          <span class="text-muted small ms-3 me-2">Valor Total:</span>
          <strong class="text-success fs-6" id="valorTotal">R$ <?= number_format((float)$os->valor_total, 2, ',', '.') ?></strong>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /col-lg-8 -->

    <!-- Sidebar -->
    <div class="col-lg-4">

      <!-- Status -->
      <?php if (!$isFaturada && !$isCancelada): ?>
      <div class="os-show-card">
        <h5><i class="fas fa-exchange-alt"></i> Alterar Status</h5>
        <div class="d-grid gap-2">
          <?php
          $proxStatus = [
            'aberta'          => ['em_andamento'    => 'Iniciar Atendimento'],
            'em_andamento'    => ['aguardando_peca' => 'Aguardando Peça', 'concluida' => 'Marcar como Concluída'],
            'aguardando_peca' => ['em_andamento'    => 'Retomar Atendimento', 'concluida' => 'Marcar como Concluída'],
            'concluida'       => [],
          ];
          foreach ($proxStatus[$os->status] ?? [] as $st_key => $st_label):
          ?>
          <button class="btn btn-sm btn-outline-primary btn-status" data-status="<?= $st_key ?>">
            <i class="fas fa-arrow-right me-1"></i><?= $st_label ?>
          </button>
          <?php endforeach; ?>
        </div>
        <div class="mt-2">
          <textarea id="obsStatus" class="form-control form-control-sm" rows="2" placeholder="Observação (opcional)..."></textarea>
        </div>
      </div>
      <?php endif; ?>

      <!-- Proposta CRM vinculada -->
      <div class="os-show-card">
        <h5><i class="fas fa-file-contract"></i> Proposta CRM</h5>
        <?php if ($proposta): ?>
        <div class="info-row"><span class="info-label">Número:</span>
          <a href="/crm/propostas/<?= $proposta->id ?>" class="fw-semibold text-primary"><?= htmlspecialchars($proposta->numero) ?></a>
        </div>
        <div class="info-row"><span class="info-label">Status:</span>
          <span class="badge bg-secondary-subtle text-secondary"><?= ucfirst($proposta->status) ?></span>
        </div>
        <div class="info-row"><span class="info-label">Total:</span>
          <span class="fw-semibold">R$ <?= number_format((float)$proposta->total, 2, ',', '.') ?></span>
        </div>
        <a href="/crm/propostas/<?= $proposta->id ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">
          <i class="fas fa-external-link-alt me-1"></i>Ver Proposta
        </a>
        <?php else: ?>
        <p class="text-muted small">Nenhuma proposta vinculada.</p>
        <?php endif; ?>
      </div>

      <!-- Resumo Financeiro -->
      <div class="os-show-card">
        <h5><i class="fas fa-dollar-sign"></i> Resumo Financeiro</h5>
        <table class="table table-sm table-borderless mb-0" style="font-size:.84rem">
          <tr><td class="text-muted">Valor do Serviço:</td><td class="text-end">R$ <?= number_format((float)$os->valor_servico, 2, ',', '.') ?></td></tr>
          <tr><td class="text-muted">Valor de Peças:</td><td class="text-end" id="valorPecasSidebar">R$ <?= number_format((float)$os->valor_pecas, 2, ',', '.') ?></td></tr>
          <tr style="border-top:2px solid #e2e8f0"><td class="fw-bold">Total:</td><td class="text-end fw-bold text-success fs-6" id="valorTotalSidebar">R$ <?= number_format((float)$os->valor_total, 2, ',', '.') ?></td></tr>
        </table>
      </div>

      <!-- Histórico de Evolução -->
      <div class="os-show-card">
        <h5><i class="fas fa-history"></i> Histórico de Evolução</h5>
        <?php if (empty($historico)): ?>
        <p class="text-muted small">Nenhum registro no histórico.</p>
        <?php else: ?>
        <div class="timeline">
          <?php foreach ($historico as $h): ?>
          <?php
          $dotColor = match($h->status_novo ?? '') {
            'aberta'          => 'primary',
            'em_andamento'    => 'info',
            'aguardando_peca' => 'warning',
            'concluida'       => 'success',
            'faturada'        => 'dark',
            'cancelada'       => 'danger',
            default           => 'secondary',
          };
          ?>
          <div class="timeline-item">
            <div class="timeline-dot bg-<?= $dotColor ?>-subtle text-<?= $dotColor ?>">
              <i class="fas fa-circle" style="font-size:.5rem"></i>
            </div>
            <div class="timeline-content">
              <div style="font-size:.82rem"><?= htmlspecialchars($h->descricao ?? '') ?></div>
              <div class="timeline-meta">
                <?= htmlspecialchars($h->usuario_nome ?? 'Sistema') ?>
                — <?= date('d/m/Y H:i', strtotime($h->created_at)) ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /col-lg-4 -->
  </div><!-- /row -->
</div>

<!-- Modal: Adicionar Troca/Peça -->
<div class="modal fade" id="modalAddTroca" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i>Adicionar Item / Troca</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label form-label-sm">Produto (do Estoque)</label>
            <select id="trocaProdutoId" class="form-select form-select-sm">
              <option value="">— Selecionar produto —</option>
              <?php foreach ($produtos as $p): ?>
              <option value="<?= $p->id ?>"
                data-nome="<?= htmlspecialchars($p->nome) ?>"
                data-codigo="<?= htmlspecialchars($p->codigo ?? '') ?>"
                data-unidade="<?= htmlspecialchars($p->unidade_medida ?? 'UN') ?>"
                data-preco="<?= number_format((float)($p->preco_venda ?? 0), 2, '.', '') ?>"
                data-vida="<?= (int)($p->vida_util_meses ?? 0) ?>">
                <?= htmlspecialchars($p->codigo ? "[{$p->codigo}] " : '') . htmlspecialchars($p->nome) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label form-label-sm">Descrição <span class="text-danger">*</span></label>
            <input type="text" id="trocaDescricao" class="form-control form-control-sm" placeholder="Nome do item ou serviço">
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-sm">Código</label>
            <input type="text" id="trocaCodigo" class="form-control form-control-sm">
          </div>
          <div class="col-md-2">
            <label class="form-label form-label-sm">Unidade</label>
            <input type="text" id="trocaUnidade" class="form-control form-control-sm" value="UN">
          </div>
          <div class="col-md-2">
            <label class="form-label form-label-sm">Quantidade</label>
            <input type="number" id="trocaQtd" class="form-control form-control-sm" value="1" min="0.001" step="0.001">
          </div>
          <div class="col-md-2">
            <label class="form-label form-label-sm">Preço Unit. (R$)</label>
            <input type="text" id="trocaPreco" class="form-control form-control-sm money-mask" value="0,00">
          </div>
          <div class="col-md-3">
            <label class="form-label form-label-sm">Total</label>
            <input type="text" id="trocaTotal" class="form-control form-control-sm" readonly value="R$ 0,00">
          </div>
          <div class="col-md-4">
            <label class="form-label form-label-sm">Vida Útil (meses)</label>
            <input type="number" id="trocaVida" class="form-control form-control-sm" min="0" value="0"
                   placeholder="0 = sem controle">
          </div>
          <div class="col-md-4">
            <label class="form-label form-label-sm">Próxima Troca (calculada)</label>
            <input type="text" id="trocaProxData" class="form-control form-control-sm" readonly placeholder="Calculada automaticamente">
          </div>
          <div class="col-12">
            <label class="form-label form-label-sm">Observações</label>
            <textarea id="trocaObs" class="form-control form-control-sm" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary btn-sm" id="btnSalvarTroca">
          <i class="fas fa-save me-1"></i>Salvar Item
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const osId     = <?= (int)$os->id ?>;
const csrfTok  = '<?= $csrfToken ?>';

// ── Alterar Status ─────────────────────────────────────────────────────────
document.querySelectorAll('.btn-status').forEach(function(btn) {
  btn.addEventListener('click', function() {
    const novoStatus = this.dataset.status;
    const obs        = document.getElementById('obsStatus').value;
    const fd = new FormData();
    fd.append('csrf_token', csrfTok);
    fd.append('status', novoStatus);
    fd.append('obs', obs);
    fetch('/manutencao/ordens/' + osId + '/status', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        if (d.success) location.reload();
        else alert(d.error || 'Erro ao alterar status.');
      });
  });
});

// ── Cancelar O.S ──────────────────────────────────────────────────────────
const btnCancelar = document.getElementById('btnCancelarOS');
if (btnCancelar) {
  btnCancelar.addEventListener('click', function() {
    if (!confirm('Deseja realmente cancelar esta Ordem de Serviço?')) return;
    const fd = new FormData();
    fd.append('csrf_token', csrfTok);
    fetch('/manutencao/ordens/' + osId + '/cancelar', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => { if (d.success) window.location.href = d.redirect; else alert(d.error); });
  });
}

// ── Enviar por E-mail ──────────────────────────────────────────────────────
const btnEmail = document.getElementById('btnEnviarEmail');
if (btnEmail) {
  btnEmail.addEventListener('click', function() {
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enviando...';
    const fd = new FormData();
    fd.append('csrf_token', csrfTok);
    fetch('/manutencao/ordens/' + osId + '/enviar', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          alert(d.message || 'E-mail enviado com sucesso!');
        } else {
          alert(d.error || 'Erro ao enviar e-mail.');
        }
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-envelope me-1"></i>Enviar por E-mail';
      });
  });
}

// ── Modal Troca: preencher ao selecionar produto ───────────────────────────
document.getElementById('trocaProdutoId').addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  if (!opt.value) return;
  document.getElementById('trocaDescricao').value = opt.dataset.nome   || '';
  document.getElementById('trocaCodigo').value    = opt.dataset.codigo || '';
  document.getElementById('trocaUnidade').value   = opt.dataset.unidade|| 'UN';
  const preco = parseFloat(opt.dataset.preco || 0);
  document.getElementById('trocaPreco').value = preco.toFixed(2).replace('.', ',');
  document.getElementById('trocaVida').value  = opt.dataset.vida || 0;
  calcTrocaTotal();
  calcProxTroca();
});

function calcTrocaTotal() {
  const qtd   = parseFloat(document.getElementById('trocaQtd').value.replace(',', '.'))  || 0;
  const preco = parseFloat(document.getElementById('trocaPreco').value.replace(/\./g,'').replace(',','.')) || 0;
  const total = qtd * preco;
  document.getElementById('trocaTotal').value = 'R$ ' + total.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
function calcProxTroca() {
  const vida = parseInt(document.getElementById('trocaVida').value) || 0;
  if (!vida) { document.getElementById('trocaProxData').value = ''; return; }
  const d = new Date();
  d.setMonth(d.getMonth() + vida);
  document.getElementById('trocaProxData').value = d.toLocaleDateString('pt-BR');
}
document.getElementById('trocaQtd').addEventListener('input', calcTrocaTotal);
document.getElementById('trocaPreco').addEventListener('input', calcTrocaTotal);
document.getElementById('trocaVida').addEventListener('input', calcProxTroca);

// Money mask no modal
document.getElementById('trocaPreco').addEventListener('input', function() {
  let v = this.value.replace(/\D/g, '');
  if (!v) { this.value = '0,00'; return; }
  v = (parseInt(v, 10) / 100).toFixed(2);
  this.value = v.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  calcTrocaTotal();
});

// ── Salvar Troca ──────────────────────────────────────────────────────────
document.getElementById('btnSalvarTroca').addEventListener('click', function() {
  const descricao = document.getElementById('trocaDescricao').value.trim();
  if (!descricao) { alert('A descrição é obrigatória.'); return; }

  const precoRaw = document.getElementById('trocaPreco').value.replace(/\./g,'').replace(',','.');

  const fd = new FormData();
  fd.append('csrf_token',      csrfTok);
  fd.append('produto_id',      document.getElementById('trocaProdutoId').value);
  fd.append('produto_codigo',  document.getElementById('trocaCodigo').value);
  fd.append('descricao',       descricao);
  fd.append('unidade',         document.getElementById('trocaUnidade').value);
  fd.append('quantidade',      document.getElementById('trocaQtd').value);
  fd.append('preco_unitario',  precoRaw);
  fd.append('vida_util_meses', document.getElementById('trocaVida').value);
  fd.append('observacoes',     document.getElementById('trocaObs').value);

  this.disabled = true;
  this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';

  fetch('/manutencao/ordens/' + osId + '/troca/add', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        location.reload();
      } else {
        alert(d.error || 'Erro ao salvar item.');
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-save me-1"></i>Salvar Item';
      }
    });
});

// ── Deletar Troca ─────────────────────────────────────────────────────────
document.querySelectorAll('.btn-del-troca').forEach(function(btn) {
  btn.addEventListener('click', function() {
    if (!confirm('Remover este item?')) return;
    const trocaId = this.dataset.id;
    const fd = new FormData();
    fd.append('csrf_token', csrfTok);
    fetch('/manutencao/ordens/' + osId + '/troca/' + trocaId + '/delete', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          document.getElementById('troca-' + trocaId).remove();
          document.getElementById('valorPecas').textContent       = 'R$ ' + d.valor_pecas;
          document.getElementById('valorTotal').textContent       = 'R$ ' + d.valor_total;
          document.getElementById('valorPecasSidebar').textContent= 'R$ ' + d.valor_pecas;
          document.getElementById('valorTotalSidebar').textContent= 'R$ ' + d.valor_total;
        } else {
          alert(d.error || 'Erro ao remover item.');
        }
      });
  });
});
</script>
