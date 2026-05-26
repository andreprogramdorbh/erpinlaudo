<?php
/**
 * View pública de aceite e assinatura de proposta
 * Acessível via /proposta/aceite/{token}
 * Layout: public (sem autenticação)
 */
$p      = $proposta;
$total  = number_format((float) ($p->total ?? 0), 2, ',', '.');
$valida = !empty($p->validade_proposta) ? date('d/m/Y', strtotime($p->validade_proposta)) : '—';
$jaAceita  = ($p->status ?? '') === 'aceita';
$jaRecusada = ($p->status ?? '') === 'recusada';
$vencida   = !empty($p->validade_proposta) && $p->validade_proposta < date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Proposta <?php echo htmlspecialchars($p->numero ?? ''); ?> | INLAUDO</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #f1f5f9; min-height: 100vh; }
    .prop-header { background: linear-gradient(135deg, #1a56db, #0e3a8c); color: #fff; padding: 28px 0; }
    .prop-logo { height: 40px; filter: brightness(0) invert(1); }
    .prop-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
    .prop-section { border-bottom: 1px solid #e5e7eb; padding: 24px 32px; }
    .prop-section:last-child { border-bottom: none; }
    .badge-status { font-size: 13px; padding: 6px 14px; border-radius: 20px; font-weight: 600; }
    .badge-aceita   { background: #d1fae5; color: #065f46; }
    .badge-recusada { background: #fee2e2; color: #991b1b; }
    .badge-enviada  { background: #dbeafe; color: #1e40af; }
    .badge-vencida  { background: #fef3c7; color: #92400e; }
    .item-table th { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; background: #f9fafb; }
    .item-table td { vertical-align: middle; font-size: 14px; }
    .sign-area { border: 2px dashed #d1d5db; border-radius: 8px; background: #fafafa; cursor: crosshair; display: block; width: 100%; }
    .sign-area:hover { border-color: #1a56db; }
    .btn-aceitar { background: #16a34a; color: #fff; font-weight: 700; font-size: 16px; padding: 14px 36px; border-radius: 8px; border: none; }
    .btn-aceitar:hover { background: #15803d; color: #fff; }
    .btn-recusar { background: #fff; color: #dc2626; font-weight: 600; border: 2px solid #dc2626; padding: 12px 28px; border-radius: 8px; }
    .btn-recusar:hover { background: #fee2e2; }
    .cert-box { background: linear-gradient(135deg, #f0f7ff, #e8f4fd); border: 1px solid #bfdbfe; border-radius: 10px; padding: 20px 24px; }
    .cert-seal { width: 60px; height: 60px; background: #1a56db; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 24px; flex-shrink: 0; }
    .tab-btn { border: none; background: none; padding: 10px 20px; font-weight: 600; color: #6b7280; border-bottom: 3px solid transparent; }
    .tab-btn.active { color: #1a56db; border-bottom-color: #1a56db; }
    #canvasRubrica { touch-action: none; }
    .success-overlay { display: none; text-align: center; padding: 40px 20px; }
    .success-overlay .check-icon { width: 80px; height: 80px; background: #d1fae5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 36px; color: #16a34a; }
  </style>
</head>
<body>

<!-- Header -->
<div class="prop-header">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-3">
        <i class="fas fa-file-contract fa-2x opacity-75"></i>
        <div>
          <div class="fw-bold fs-5">Proposta Comercial</div>
          <div class="opacity-75 small"><?php echo htmlspecialchars($p->numero ?? ''); ?> — <?php echo htmlspecialchars($p->titulo ?? ''); ?></div>
        </div>
      </div>
      <?php if ($jaAceita): ?>
        <span class="badge-status badge-aceita"><i class="fas fa-check-circle me-1"></i>Aceita</span>
      <?php elseif ($jaRecusada): ?>
        <span class="badge-status badge-recusada"><i class="fas fa-times-circle me-1"></i>Recusada</span>
      <?php elseif ($vencida): ?>
        <span class="badge-status badge-vencida"><i class="fas fa-clock me-1"></i>Vencida</span>
      <?php else: ?>
        <span class="badge-status badge-enviada"><i class="fas fa-paper-plane me-1"></i>Aguardando Aceite</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-9">

      <!-- Proposta já aceita / recusada -->
      <?php if ($jaAceita): ?>
      <div class="prop-card mb-4 p-4 text-center">
        <div class="mb-3" style="font-size:56px">✅</div>
        <h4 class="fw-bold text-success">Proposta já aceita!</h4>
        <p class="text-muted">Esta proposta foi aceita por <strong><?php echo htmlspecialchars($p->aceito_por_nome ?? 'você'); ?></strong>
          em <?php echo !empty($p->aceito_em) ? date('d/m/Y \à\s H:i', strtotime($p->aceito_em)) : '—'; ?>.
        </p>
        <?php if (!empty($p->pdf_path)): ?>
        <a href="/crm/propostas/<?php echo $p->id; ?>/pdf" target="_blank" class="btn btn-outline-primary">
          <i class="fas fa-file-pdf me-2"></i>Baixar PDF da Proposta
        </a>
        <?php endif; ?>
      </div>
      <?php elseif ($jaRecusada): ?>
      <div class="prop-card mb-4 p-4 text-center">
        <div class="mb-3" style="font-size:56px">❌</div>
        <h4 class="fw-bold text-danger">Proposta recusada</h4>
        <p class="text-muted">Esta proposta foi recusada em <?php echo !empty($p->recusado_em) ? date('d/m/Y', strtotime($p->recusado_em)) : '—'; ?>.</p>
      </div>
      <?php else: ?>

      <!-- Resumo da proposta -->
      <div class="prop-card mb-4">
        <div class="prop-section">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="text-muted small mb-1">Cliente</div>
              <div class="fw-semibold"><?php echo htmlspecialchars($p->cliente_nome ?? '—'); ?></div>
              <?php if (!empty($p->cliente_cnpj_cpf)): ?>
              <div class="text-muted small"><?php echo htmlspecialchars($p->cliente_cnpj_cpf); ?></div>
              <?php endif; ?>
            </div>
            <div class="col-md-3">
              <div class="text-muted small mb-1">Valor Total</div>
              <div class="fw-bold text-primary fs-5">R$ <?php echo $total; ?></div>
            </div>
            <div class="col-md-3">
              <div class="text-muted small mb-1">Válida até</div>
              <div class="fw-semibold <?php echo $vencida ? 'text-danger' : ''; ?>"><?php echo $valida; ?></div>
            </div>
          </div>
        </div>
        <?php if (!empty($p->descricao)): ?>
        <div class="prop-section">
          <div class="text-muted small mb-1">Descrição</div>
          <p class="mb-0"><?php echo nl2br(htmlspecialchars($p->descricao)); ?></p>
        </div>
        <?php endif; ?>
      </div>

      <!-- Itens -->
      <?php if (!empty($itens)): ?>
      <div class="prop-card mb-4">
        <div class="prop-section">
          <h6 class="fw-bold mb-3"><i class="fas fa-boxes me-2 text-primary"></i>Itens da Proposta</h6>
          <div class="table-responsive">
            <table class="table item-table mb-0">
              <thead>
                <tr>
                  <th>Cód.</th><th>Descrição</th><th>Un.</th>
                  <th class="text-end">Qtd.</th><th class="text-end">Unit. (R$)</th>
                  <th class="text-end">Desc.%</th><th class="text-end">Total (R$)</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($itens as $item): ?>
                <tr>
                  <td class="text-muted small"><?php echo htmlspecialchars($item->codigo ?? '—'); ?></td>
                  <td><?php echo htmlspecialchars($item->descricao ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($item->unidade ?? 'un'); ?></td>
                  <td class="text-end"><?php echo number_format((float)($item->quantidade ?? 1), 3, ',', '.'); ?></td>
                  <td class="text-end"><?php echo number_format((float)($item->preco_unitario ?? 0), 2, ',', '.'); ?></td>
                  <td class="text-end"><?php echo number_format((float)($item->desconto ?? 0), 2, ',', '.'); ?>%</td>
                  <td class="text-end fw-semibold"><?php echo number_format((float)($item->total ?? 0), 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="6" class="text-end fw-bold">Total da Proposta:</td>
                  <td class="text-end fw-bold text-primary fs-6">R$ <?php echo $total; ?></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Condições -->
      <?php if (!empty($p->condicoes_pagamento) || !empty($p->prazo_entrega)): ?>
      <div class="prop-card mb-4">
        <div class="prop-section">
          <h6 class="fw-bold mb-3"><i class="fas fa-handshake me-2 text-primary"></i>Condições</h6>
          <div class="row g-3">
            <?php if (!empty($p->prazo_entrega)): ?>
            <div class="col-md-6">
              <div class="text-muted small mb-1">Prazo de Entrega</div>
              <div><?php echo htmlspecialchars($p->prazo_entrega); ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($p->condicoes_pagamento)): ?>
            <div class="col-md-6">
              <div class="text-muted small mb-1">Condições de Pagamento</div>
              <div><?php echo htmlspecialchars($p->condicoes_pagamento); ?></div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Área de Assinatura -->
      <?php if (!$vencida): ?>
      <div class="prop-card mb-4" id="blocoAssinatura">
        <div class="prop-section">
          <h6 class="fw-bold mb-1"><i class="fas fa-signature me-2 text-primary"></i>Aceite e Assinatura Digital</h6>
          <p class="text-muted small mb-4">Ao aceitar, você concorda com os termos desta proposta. Sua assinatura terá validade como termo de aceite.</p>

          <!-- Tabs: Rubrica / Nome digitado -->
          <div class="d-flex border-bottom mb-4">
            <button class="tab-btn active" id="tab-rubrica" onclick="mudarTab('rubrica')">
              <i class="fas fa-pen-nib me-1"></i>Rubrica (desenhar)
            </button>
            <button class="tab-btn" id="tab-nome" onclick="mudarTab('nome')">
              <i class="fas fa-keyboard me-1"></i>Nome digitado
            </button>
          </div>

          <!-- Painel Rubrica -->
          <div id="painel-rubrica">
            <p class="text-muted small mb-2">Desenhe sua rubrica ou assinatura abaixo:</p>
            <canvas id="canvasRubrica" class="sign-area" width="600" height="160"></canvas>
            <div class="d-flex gap-2 mt-2">
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limparCanvas()">
                <i class="fas fa-eraser me-1"></i>Limpar
              </button>
            </div>
          </div>

          <!-- Painel Nome digitado -->
          <div id="painel-nome" style="display:none">
            <p class="text-muted small mb-2">Digite seu nome completo para usar como assinatura:</p>
            <input type="text" id="nomeDigitado" class="form-control form-control-lg"
              placeholder="Seu nome completo..." style="font-family:'Dancing Script',cursive;font-size:1.4rem">
            <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600&display=swap" rel="stylesheet">
          </div>

          <!-- Nome do assinante -->
          <div class="mt-4">
            <label class="form-label fw-semibold">Nome do Assinante <span class="text-danger">*</span></label>
            <input type="text" id="nomeAssinante" class="form-control" placeholder="Nome completo do responsável pelo aceite...">
          </div>

          <!-- Certificado visual -->
          <div class="cert-box mt-4">
            <div class="d-flex align-items-center gap-3">
              <div class="cert-seal"><i class="fas fa-shield-check"></i></div>
              <div>
                <div class="fw-bold">Termo de Aceite Digital</div>
                <div class="text-muted small">Ao confirmar, serão registrados: seu nome, endereço IP, data/hora e assinatura. Este registro tem validade jurídica como aceite eletrônico conforme a Lei 14.063/2020.</div>
              </div>
            </div>
          </div>

          <!-- Botões -->
          <div class="d-flex gap-3 mt-4 flex-wrap">
            <button type="button" class="btn-aceitar btn" onclick="confirmarAceite()">
              <i class="fas fa-check-circle me-2"></i>Aceitar e Assinar Proposta
            </button>
            <button type="button" class="btn-recusar btn" onclick="mostrarRecusa()">
              <i class="fas fa-times me-2"></i>Recusar Proposta
            </button>
          </div>
        </div>
      </div>

      <!-- Bloco de recusa (oculto) -->
      <div class="prop-card mb-4" id="blocoRecusa" style="display:none">
        <div class="prop-section">
          <h6 class="fw-bold text-danger mb-3"><i class="fas fa-times-circle me-2"></i>Recusar Proposta</h6>
          <div class="mb-3">
            <label class="form-label">Motivo da recusa (opcional)</label>
            <textarea id="motivoRecusa" class="form-control" rows="3" placeholder="Informe o motivo..."></textarea>
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-danger" onclick="confirmarRecusa()">
              <i class="fas fa-times me-1"></i>Confirmar Recusa
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="cancelarRecusa()">Cancelar</button>
          </div>
        </div>
      </div>
      <?php else: ?>
      <div class="alert alert-warning">
        <i class="fas fa-clock me-2"></i>Esta proposta está vencida (válida até <?php echo $valida; ?>) e não pode mais ser aceita. Entre em contato com o fornecedor para solicitar uma nova proposta.
      </div>
      <?php endif; ?>

      <!-- Overlay de sucesso -->
      <div class="prop-card mb-4 success-overlay" id="overlaySuccesso">
        <div class="check-icon"><i class="fas fa-check"></i></div>
        <h4 class="fw-bold text-success">Proposta Aceita com Sucesso!</h4>
        <p class="text-muted" id="msgSuccesso">Sua assinatura foi registrada. Em breve você receberá a confirmação por e-mail.</p>
        <div class="mt-3">
          <span class="badge bg-light text-dark border p-2">
            <i class="fas fa-shield-check text-success me-1"></i>
            Assinatura registrada em <?php echo date('d/m/Y \à\s H:i'); ?>
          </span>
        </div>
      </div>

      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ─── Canvas de Rubrica ────────────────────────────────────────────────────────
const canvas  = document.getElementById('canvasRubrica');
const ctx     = canvas ? canvas.getContext('2d') : null;
let drawing   = false;
let tabAtual  = 'rubrica';

function initCanvas() {
  if (!canvas) return;
  ctx.strokeStyle = '#1a1a2e';
  ctx.lineWidth   = 2.5;
  ctx.lineCap     = 'round';
  ctx.lineJoin    = 'round';
  // Mouse
  canvas.addEventListener('mousedown',  e => { drawing = true; ctx.beginPath(); ctx.moveTo(pos(e).x, pos(e).y); });
  canvas.addEventListener('mousemove',  e => { if (!drawing) return; ctx.lineTo(pos(e).x, pos(e).y); ctx.stroke(); });
  canvas.addEventListener('mouseup',    () => drawing = false);
  canvas.addEventListener('mouseleave', () => drawing = false);
  // Touch
  canvas.addEventListener('touchstart', e => { e.preventDefault(); drawing = true; ctx.beginPath(); ctx.moveTo(posT(e).x, posT(e).y); }, {passive:false});
  canvas.addEventListener('touchmove',  e => { e.preventDefault(); if (!drawing) return; ctx.lineTo(posT(e).x, posT(e).y); ctx.stroke(); }, {passive:false});
  canvas.addEventListener('touchend',   () => drawing = false);
}
function pos(e) {
  const r = canvas.getBoundingClientRect();
  return { x: (e.clientX - r.left) * (canvas.width / r.width), y: (e.clientY - r.top) * (canvas.height / r.height) };
}
function posT(e) {
  const t = e.touches[0];
  return pos(t);
}
function limparCanvas() {
  if (ctx) ctx.clearRect(0, 0, canvas.width, canvas.height);
}
function canvasVazio() {
  if (!canvas) return true;
  const d = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
  return !d.some(v => v !== 0);
}

// ─── Tabs ─────────────────────────────────────────────────────────────────────
function mudarTab(tab) {
  tabAtual = tab;
  document.getElementById('painel-rubrica').style.display = tab === 'rubrica' ? '' : 'none';
  document.getElementById('painel-nome').style.display    = tab === 'nome'    ? '' : 'none';
  document.getElementById('tab-rubrica').classList.toggle('active', tab === 'rubrica');
  document.getElementById('tab-nome').classList.toggle('active', tab === 'nome');
}

// ─── Aceite ───────────────────────────────────────────────────────────────────
async function confirmarAceite() {
  const nome = (document.getElementById('nomeAssinante')?.value ?? '').trim();
  if (!nome) { alert('Por favor, informe o nome do assinante.'); return; }

  let assinaturaImg = '';
  let tipo = tabAtual;

  if (tabAtual === 'rubrica') {
    if (canvasVazio()) { alert('Por favor, desenhe sua rubrica antes de confirmar.'); return; }
    assinaturaImg = canvas.toDataURL('image/png');
  } else {
    const nd = (document.getElementById('nomeDigitado')?.value ?? '').trim();
    if (!nd) { alert('Por favor, digite seu nome para usar como assinatura.'); return; }
    // Renderizar nome digitado no canvas para salvar como imagem
    if (ctx) {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.font = '48px Dancing Script, cursive';
      ctx.fillStyle = '#1a1a2e';
      ctx.textAlign = 'center';
      ctx.fillText(nd, canvas.width / 2, canvas.height / 2 + 16);
      assinaturaImg = canvas.toDataURL('image/png');
    }
    tipo = 'nome_digitado';
  }

  if (!confirm('Confirmar o aceite desta proposta com a assinatura de "' + nome + '"?')) return;

  const fd = new FormData();
  fd.append('acao', 'aceitar');
  fd.append('nome_assinante', nome);
  fd.append('assinatura_tipo', tipo);
  fd.append('assinatura_imagem', assinaturaImg);

  try {
    const r = await fetch(window.location.href, { method: 'POST', body: fd });
    const j = await r.json();
    if (j.success) {
      document.getElementById('blocoAssinatura').style.display = 'none';
      const ov = document.getElementById('overlaySuccesso');
      ov.style.display = 'block';
      document.getElementById('msgSuccesso').textContent =
        'Proposta aceita e assinada por ' + (j.nome || nome) + '. Em breve você receberá a confirmação por e-mail.';
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
      alert('Erro: ' + (j.error || 'Não foi possível registrar o aceite.'));
    }
  } catch (e) {
    alert('Erro de conexão. Tente novamente.');
  }
}

// ─── Recusa ───────────────────────────────────────────────────────────────────
function mostrarRecusa() {
  document.getElementById('blocoRecusa').style.display = '';
  document.getElementById('blocoRecusa').scrollIntoView({ behavior: 'smooth' });
}
function cancelarRecusa() {
  document.getElementById('blocoRecusa').style.display = 'none';
}
async function confirmarRecusa() {
  const motivo = (document.getElementById('motivoRecusa')?.value ?? '').trim();
  if (!confirm('Tem certeza que deseja recusar esta proposta?')) return;
  const fd = new FormData();
  fd.append('acao', 'recusar');
  fd.append('motivo_recusa', motivo);
  try {
    const r = await fetch(window.location.href, { method: 'POST', body: fd });
    const j = await r.json();
    if (j.success) {
      location.reload();
    } else {
      alert('Erro: ' + (j.error || 'Não foi possível registrar a recusa.'));
    }
  } catch (e) {
    alert('Erro de conexão. Tente novamente.');
  }
}

document.addEventListener('DOMContentLoaded', initCanvas);
</script>
</body>
</html>
