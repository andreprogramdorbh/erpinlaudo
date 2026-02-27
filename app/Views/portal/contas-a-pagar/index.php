<?php
$hoje = date('Y-m-d');
$statusMap = [
    'aberta'    => ['label' => 'Em Aberto', 'class' => 'portal-badge-warning'],
    'recebida'  => ['label' => 'Pago',      'class' => 'portal-badge-success'],
    'cancelada' => ['label' => 'Cancelada', 'class' => 'portal-badge-danger'],
];
$meioPagIcon = [
    'pix'          => 'fa-qrcode',
    'boleto'       => 'fa-barcode',
    'cartao'       => 'fa-credit-card',
    'checkout'     => 'fa-shopping-cart',
    'dinheiro'     => 'fa-money-bill-wave',
    'transferencia'=> 'fa-exchange-alt',
    'outro'        => 'fa-ellipsis-h',
];
?>
<div class="portal-page-header">
    <div>
        <h1 class="portal-page-title"><i class="fa fa-file-invoice-dollar me-2"></i>Minhas Contas</h1>
        <p class="portal-page-subtitle">Visualize e pague suas faturas</p>
    </div>
</div>
<?php if (!empty($_GET['error'])): ?>
    <?php $erros = [
        'nao_autorizado'         => 'Acesso não autorizado.',
        'pagamento_indisponivel' => 'O sistema de pagamento está temporariamente indisponível.',
        'link_indisponivel'      => 'Não foi possível gerar o link de pagamento. Tente novamente.',
        'pix_indisponivel'       => 'O QR Code PIX não está disponível no momento.',
        'boleto_indisponivel'    => 'O boleto não está disponível no momento.',
        'erro_pagamento'         => 'Ocorreu um erro ao processar o pagamento.',
        'cancelada'              => 'Esta conta está cancelada e não pode ser paga.',
        'valor_minimo'           => null,
    ]; ?>
    <div class="portal-alert portal-alert-danger mb-3">
        <i class="fa fa-exclamation-circle me-2"></i>
        <?php
            $errorKey = $_GET['error'] ?? '';
            if ($errorKey === 'valor_minimo' && !empty($_GET['msg'])) {
                echo '<strong>Pagamento não gerado:</strong> ' . htmlspecialchars(urldecode($_GET['msg']));
            } else {
                echo htmlspecialchars($erros[$errorKey] ?? 'Ocorreu um erro. Tente novamente.');
            }
        ?>
    </div>
<?php endif; ?>
<?php if (!empty($_GET['info']) && $_GET['info'] === 'ja_pago'): ?>
    <div class="portal-alert portal-alert-success mb-3">
        <i class="fa fa-check-circle me-2"></i> Esta conta já foi paga. Obrigado!
    </div>
<?php endif; ?>
<div id="alertaPagamentoRetorno" class="portal-alert portal-alert-info mb-3" style="display:none;">
    <i class="fa fa-sync fa-spin me-2" id="alertaIcon"></i>
    <span id="alertaPagamentoTexto">Verificando status do pagamento...</span>
</div>
<!-- Cards de resumo -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="portal-summary-card">
            <div class="portal-summary-icon text-warning"><i class="fa fa-clock"></i></div>
            <div class="portal-summary-info">
                <div class="portal-summary-value"><?php echo $totalAbertas; ?></div>
                <div class="portal-summary-label">Em Aberto</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="portal-summary-card">
            <div class="portal-summary-icon text-success"><i class="fa fa-check-circle"></i></div>
            <div class="portal-summary-info">
                <div class="portal-summary-value"><?php echo $totalRecebidas; ?></div>
                <div class="portal-summary-label">Pagas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="portal-summary-card">
            <div class="portal-summary-icon text-danger"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="portal-summary-info">
                <div class="portal-summary-value"><?php echo $totalVencidas; ?></div>
                <div class="portal-summary-label">Vencidas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="portal-summary-card">
            <div class="portal-summary-icon text-primary"><i class="fa fa-dollar-sign"></i></div>
            <div class="portal-summary-info">
                <div class="portal-summary-value">R$ <?php echo number_format($totalValorAberto, 2, ',', '.'); ?></div>
                <div class="portal-summary-label">Total em Aberto</div>
            </div>
        </div>
    </div>
</div>
<!-- Abas -->
<div class="portal-tabs-container mb-3">
    <div class="portal-tabs-nav">
        <a href="?aba=abertas" class="portal-tab-btn <?php echo $abaAtiva === 'abertas' ? 'active' : ''; ?>">
            <i class="fa fa-clock me-1"></i>Em Aberto
            <?php if ($totalAbertas > 0): ?><span class="portal-tab-badge portal-tab-badge-warning"><?php echo $totalAbertas; ?></span><?php endif; ?>
        </a>
        <a href="?aba=pagas" class="portal-tab-btn <?php echo $abaAtiva === 'pagas' ? 'active' : ''; ?>">
            <i class="fa fa-check-circle me-1"></i>Pagas
            <?php if ($totalRecebidas > 0): ?><span class="portal-tab-badge portal-tab-badge-success"><?php echo $totalRecebidas; ?></span><?php endif; ?>
        </a>
        <?php if ($totalCanceladas > 0): ?>
        <a href="?aba=canceladas" class="portal-tab-btn <?php echo $abaAtiva === 'canceladas' ? 'active' : ''; ?>">
            <i class="fa fa-ban me-1"></i>Canceladas
            <span class="portal-tab-badge portal-tab-badge-danger"><?php echo $totalCanceladas; ?></span>
        </a>
        <?php endif; ?>
    </div>
</div>
<!-- Lista de Contas -->
<?php if (empty($contas)): ?>
    <div class="portal-empty-state">
        <i class="fa fa-<?php echo $abaAtiva === 'pagas' ? 'check-circle text-success' : 'file-invoice text-muted'; ?> fa-3x mb-3 d-block"></i>
        <p class="text-muted">
            <?php if ($abaAtiva === 'pagas'): ?>Nenhuma conta paga ainda.
            <?php elseif ($abaAtiva === 'canceladas'): ?>Nenhuma conta cancelada.
            <?php else: ?>Nenhuma conta em aberto. Tudo em dia! <?php endif; ?>
        </p>
    </div>
<?php else: ?>
    <div class="portal-contas-list" id="listaContas">
        <?php foreach ($contas as $conta):
            $statusInfo  = $statusMap[$conta->status] ?? ['label' => ucfirst($conta->status), 'class' => 'portal-badge-secondary'];
            $vencida     = ($conta->status === 'aberta' && $conta->data_vencimento < $hoje);
            $dataVenc    = date('d/m/Y', strtotime($conta->data_vencimento));
            $dataRec     = !empty($conta->data_recebimento) ? date('d/m/Y', strtotime($conta->data_recebimento)) : null;
            $meioPag     = strtolower((string)($conta->meio_pagamento ?? ''));
            $meioPagIconClass = $meioPagIcon[$meioPag] ?? 'fa-ellipsis-h';
            $meioPagLabel = match($meioPag) {
                'pix'=>'PIX','boleto'=>'Boleto','cartao'=>'Cartão',
                'checkout'=>'Checkout','dinheiro'=>'Dinheiro',
                'transferencia'=>'Transferência','outro'=>'Outro', default=>''
            };
            $meiosManuais  = ['dinheiro', 'transferencia', 'outro', ''];
            $ehMeioManual  = in_array($meioPag, $meiosManuais, true);
            $podeUsarAsaas = $asaasEnabled && !$ehMeioManual;
            $jaPago        = ($conta->status === 'recebida');
        ?>
        <div class="portal-conta-card <?php echo $vencida ? 'portal-conta-vencida' : ''; ?> <?php echo $jaPago ? 'portal-conta-paga' : ''; ?>"
             id="conta-card-<?php echo (int)$conta->id; ?>"
             data-conta-id="<?php echo (int)$conta->id; ?>"
             data-status="<?php echo htmlspecialchars($conta->status); ?>">
            <div class="portal-conta-header">
                <div class="portal-conta-title">
                    <?php if ($vencida): ?>
                        <span class="portal-badge portal-badge-danger me-1"><i class="fa fa-exclamation-triangle me-1"></i>Vencida</span>
                    <?php endif; ?>
                    <span class="portal-badge <?php echo $statusInfo['class']; ?>">
                        <?php if ($jaPago): ?><i class="fa fa-check-circle me-1"></i><?php endif; ?>
                        <?php echo $statusInfo['label']; ?>
                    </span>
                </div>
                <div class="portal-conta-venc">
                    <i class="fa fa-calendar me-1 text-muted"></i>
                    <span class="<?php echo $vencida ? 'text-danger fw-semibold' : ''; ?>"><?php echo $dataVenc; ?></span>
                </div>
            </div>
            <div class="portal-conta-body">
                <div class="portal-conta-desc fw-semibold mb-2"><?php echo htmlspecialchars($conta->descricao ?? '—'); ?></div>
                <div class="portal-conta-details">
                    <div class="portal-conta-detail">
                        <span class="portal-detail-label"><i class="fa fa-dollar-sign me-1"></i>Valor</span>
                        <span class="portal-detail-value fw-semibold">R$ <?php echo number_format((float)$conta->valor, 2, ',', '.'); ?></span>
                    </div>
                    <?php if ($meioPagLabel !== ''): ?>
                    <div class="portal-conta-detail">
                        <span class="portal-detail-label"><i class="fa <?php echo $meioPagIconClass; ?> me-1"></i>Forma</span>
                        <span class="portal-detail-value"><?php echo $meioPagLabel; ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($dataRec): ?>
                    <div class="portal-conta-detail">
                        <span class="portal-detail-label"><i class="fa fa-check me-1"></i>Pago em</span>
                        <span class="portal-detail-value text-success"><?php echo $dataRec; ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($conta->numero_parcela) && !empty($conta->total_parcelas)): ?>
                    <div class="portal-conta-detail">
                        <span class="portal-detail-label"><i class="fa fa-list-ol me-1"></i>Parcela</span>
                        <span class="portal-detail-value"><?php echo (int)$conta->numero_parcela; ?>/<?php echo (int)$conta->total_parcelas; ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($conta->anexos)): ?>
            <div class="portal-conta-attachments mt-2 p-2 rounded bg-light border">
                <span class="d-block small fw-bold text-muted mb-2"><i class="fa fa-paperclip me-1"></i>Documentos:</span>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($conta->anexos as $anexo):
                        $ext = strtolower(pathinfo($anexo->original_name, PATHINFO_EXTENSION));
                        $iconAnexo = match($ext) {
                            'pdf'=>'fa-file-pdf text-danger','xml'=>'fa-file-code text-info',
                            'jpg','jpeg','png'=>'fa-file-image text-warning',default=>'fa-file text-secondary'
                        };
                    ?>
                        <a href="/portal/contas-a-pagar/anexos/download/<?php echo (int)$anexo->id; ?>"
                           class="portal-btn portal-btn-outline portal-btn-sm"
                           title="Baixar <?php echo htmlspecialchars($anexo->original_name); ?>">
                            <i class="fa <?php echo $iconAnexo; ?> me-1"></i>
                            <?php echo htmlspecialchars(mb_strimwidth($anexo->original_name, 0, 20, '...')); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="portal-conta-actions" id="acoes-<?php echo (int)$conta->id; ?>">
                <?php if ($conta->status === 'aberta'): ?>
                    <?php if ($podeUsarAsaas): ?>
                        <button type="button"
                                class="portal-btn portal-btn-primary portal-btn-sm btn-pagar-asaas"
                                data-conta-id="<?php echo (int)$conta->id; ?>">
                            <i class="fa fa-credit-card me-1"></i>
                            <?php echo $vencida ? 'Pagar Agora' : 'Pagar'; ?>
                            <i class="fa fa-external-link-alt ms-1 small"></i>
                        </button>
                    <?php else: ?>
                        <a href="/portal/contas-a-pagar/pagar/<?php echo (int)$conta->id; ?>"
                           class="portal-btn portal-btn-outline portal-btn-sm">
                            <i class="fa fa-info-circle me-1"></i> Ver Instruções
                        </a>
                    <?php endif; ?>
                <?php elseif ($conta->status === 'recebida'): ?>
                    <span class="portal-btn portal-btn-success portal-btn-sm" style="cursor:default;">
                        <i class="fa fa-check-circle me-1"></i> Pago
                    </span>
                    <?php
                    $nfJaEmitida = isset($nfsPorConta[(int)$conta->id]) ? $nfsPorConta[(int)$conta->id] : null;
                    ?>
                    <?php if ($nfJaEmitida): ?>
                        <a href="/portal/faturamento/notas-fiscais" class="portal-btn portal-btn-outline portal-btn-sm">
                            <i class="fa fa-file-invoice me-1"></i> Ver NF-s
                        </a>
                    <?php elseif ($asaasEnabled): ?>
                        <button type="button"
                                class="portal-btn portal-btn-info portal-btn-sm btn-emitir-nfs"
                                data-conta-id="<?php echo (int)$conta->id; ?>"
                                data-descricao="<?php echo htmlspecialchars($conta->descricao ?? 'Serviços Prestados'); ?>"
                                data-valor="R$ <?php echo number_format((float)$conta->valor, 2, ',', '.'); ?>">
                            <i class="fa fa-file-invoice me-1"></i> Emitir NF-s
                        </button>
                    <?php endif; ?>
                <?php elseif ($conta->status === 'cancelada'): ?>
                    <span class="portal-btn portal-btn-outline portal-btn-sm text-muted" style="cursor:default;">
                        <i class="fa fa-ban me-1"></i> Cancelada
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<!-- Modal Emitir NF-s -->
<div id="modalEmitirNfs" class="portal-modal-overlay" style="display:none;">
    <div class="portal-modal-box" style="max-width:440px;">
        <div class="p-4">
            <div class="d-flex align-items-center mb-3">
                <div style="background:#eff6ff;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;margin-right:12px;flex-shrink:0;">
                    <i class="fa fa-file-invoice fa-lg text-primary"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold">Emitir Nota Fiscal de Serviço</h5>
                    <p class="text-muted small mb-0">Confirme os dados antes de emitir</p>
                </div>
            </div>
            <div class="rounded p-3 mb-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Descrição:</span>
                    <span class="fw-semibold small" id="nfsDescricao" style="max-width:220px;text-align:right;"></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted small">Valor:</span>
                    <span class="fw-bold text-success" id="nfsValor"></span>
                </div>
            </div>
            <div class="portal-alert portal-alert-info mb-3" style="font-size:.8125rem;">
                <i class="fa fa-info-circle me-1"></i>
                A NF-s será emitida via Asaas e ficará disponível em <strong>Minhas Notas Fiscais</strong>. Esta ação não pode ser desfeita.
            </div>
            <div id="nfsLoadingMsg" style="display:none;" class="text-center py-2">
                <i class="fa fa-circle-notch fa-spin text-primary me-2"></i>
                <span>Emitindo NF-s, aguarde...</span>
            </div>
            <div id="nfsErroMsg" class="portal-alert portal-alert-danger mb-3" style="display:none;"></div>
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="portal-btn portal-btn-outline portal-btn-sm" id="btnCancelarNfs">
                    <i class="fa fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="portal-btn portal-btn-primary portal-btn-sm" id="btnConfirmarNfs">
                    <i class="fa fa-check me-1"></i> Confirmar Emissão
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Modal loading -->
<div id="modalPagando" class="portal-modal-overlay" style="display:none;">
    <div class="portal-modal-box">
        <div class="text-center p-4">
            <i class="fa fa-circle-notch fa-spin fa-2x text-primary mb-3"></i>
            <p class="fw-semibold mb-1">Gerando link de pagamento...</p>
            <p class="text-muted small">Aguarde um momento.</p>
        </div>
    </div>
</div>
<style>
.portal-tabs-container{border-bottom:2px solid var(--portal-border)}
.portal-tabs-nav{display:flex;gap:4px;overflow-x:auto;padding-bottom:0}
.portal-tab-btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:8px 8px 0 0;font-size:.875rem;font-weight:500;color:var(--portal-muted);text-decoration:none;border:2px solid transparent;border-bottom:none;white-space:nowrap;transition:all .2s;background:transparent;cursor:pointer}
.portal-tab-btn:hover{color:var(--portal-primary);background:#eff6ff}
.portal-tab-btn.active{color:var(--portal-primary);background:var(--portal-surface);border-color:var(--portal-border);border-bottom-color:var(--portal-surface);margin-bottom:-2px}
.portal-tab-badge{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 6px;border-radius:10px;font-size:.7rem;font-weight:700}
.portal-tab-badge-warning{background:#fef3c7;color:#92400e}
.portal-tab-badge-success{background:#d1fae5;color:#065f46}
.portal-tab-badge-danger{background:#fee2e2;color:#991b1b}
.portal-conta-card{background:var(--portal-surface);border-radius:var(--portal-radius);border:1px solid var(--portal-border);padding:1rem 1.25rem;margin-bottom:.75rem;transition:box-shadow .2s,border-color .2s}
.portal-conta-card:hover{box-shadow:var(--portal-shadow-md)}
.portal-conta-vencida{border-left:4px solid var(--portal-danger)}
.portal-conta-paga{border-left:4px solid var(--portal-success);opacity:.9}
.portal-conta-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem}
.portal-conta-venc{font-size:.875rem}
.portal-conta-desc{font-size:.9375rem}
.portal-conta-details{display:flex;flex-wrap:wrap;gap:.5rem 1.5rem;margin-bottom:.75rem}
.portal-conta-detail{display:flex;flex-direction:column}
.portal-detail-label{font-size:.75rem;color:var(--portal-muted)}
.portal-detail-value{font-size:.875rem}
.portal-conta-actions{display:flex;gap:.5rem;flex-wrap:wrap}
.portal-conta-attachments{font-size:.8125rem}
.portal-summary-card{background:var(--portal-surface);border-radius:var(--portal-radius);border:1px solid var(--portal-border);padding:.875rem 1rem;display:flex;align-items:center;gap:.875rem;box-shadow:var(--portal-shadow)}
.portal-summary-icon{font-size:1.375rem}
.portal-summary-value{font-size:1.25rem;font-weight:700;line-height:1.2}
.portal-summary-label{font-size:.75rem;color:var(--portal-muted)}
.portal-empty-state{text-align:center;padding:3rem 1rem}
.portal-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;display:flex;align-items:center;justify-content:center}
.portal-modal-box{background:#fff;border-radius:12px;min-width:280px;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.2)}
.portal-alert-info{background:#dbeafe;color:#1e40af;border-color:#bfdbfe}
.portal-alert-warning{background:#fef3c7;color:#92400e;border-color:#fde68a}
</style>
<script>
(function(){
'use strict';
var POLL_INTERVAL=4000,POLL_MAX=15,pollTimer=null,pollCount=0;
function iniciarPolling(id){
    pollCount=0;clearInterval(pollTimer);
    mostrarAlerta('Verificando status do pagamento...','info');
    pollTimer=setInterval(function(){
        pollCount++;verificarStatus(id);
        if(pollCount>=POLL_MAX){pararPolling();mostrarAlerta('Não detectamos confirmação ainda. Aguarde e recarregue a página.','warning');}
    },POLL_INTERVAL);
    verificarStatus(id);
}
function pararPolling(){clearInterval(pollTimer);pollTimer=null;}
function verificarStatus(id){
    fetch('/portal/contas-a-pagar/sync/'+id,{credentials:'same-origin'})
    .then(function(r){return r.json();})
    .then(function(d){
        if(d.pago){
            pararPolling();
            marcarComoPago(id,d.data_recebimento);
            mostrarAlerta('Pagamento confirmado! Obrigado.','success');
            sessionStorage.removeItem('portalPagandoContaId');
            setTimeout(function(){var el=document.getElementById('alertaPagamentoRetorno');if(el)el.style.display='none';},6000);
        }
    }).catch(function(){});
}
function marcarComoPago(id,dataRec){
    var card=document.getElementById('conta-card-'+id);
    if(!card)return;
    var header=card.querySelector('.portal-conta-title');
    if(header)header.innerHTML='<span class="portal-badge portal-badge-success"><i class="fa fa-check-circle me-1"></i>Pago</span>';
    if(dataRec){
        var details=card.querySelector('.portal-conta-details');
        if(details){
            var div=document.createElement('div');div.className='portal-conta-detail';
            div.innerHTML='<span class="portal-detail-label"><i class="fa fa-check me-1"></i>Pago em</span><span class="portal-detail-value text-success">'+formatarData(dataRec)+'</span>';
            details.appendChild(div);
        }
    }
    var acoes=document.getElementById('acoes-'+id);
    if(acoes)acoes.innerHTML='<span class="portal-btn portal-btn-success portal-btn-sm" style="cursor:default;"><i class="fa fa-check-circle me-1"></i> Pago</span>';
    card.classList.remove('portal-conta-vencida');card.classList.add('portal-conta-paga');card.dataset.status='recebida';
}
function formatarData(iso){if(!iso)return'';var p=iso.split('-');return p.length===3?p[2]+'/'+p[1]+'/'+p[0]:iso;}
function mostrarAlerta(texto,tipo){
    var el=document.getElementById('alertaPagamentoRetorno');
    var txt=document.getElementById('alertaPagamentoTexto');
    var icon=document.getElementById('alertaIcon');
    if(!el||!txt)return;
    txt.textContent=texto;
    el.className='portal-alert portal-alert-'+(tipo||'info')+' mb-3';
    if(icon)icon.className=tipo==='success'?'fa fa-check-circle me-2':tipo==='warning'?'fa fa-exclamation-triangle me-2':'fa fa-sync fa-spin me-2';
    el.style.display='block';
}
document.querySelectorAll('.btn-pagar-asaas').forEach(function(btn){
    btn.addEventListener('click',function(){
        var id=btn.dataset.contaId;
        var modal=document.getElementById('modalPagando');
        if(modal)modal.style.display='flex';
        fetch('/portal/contas-a-pagar/link/'+id,{credentials:'same-origin'})
        .then(function(r){return r.json();})
        .then(function(d){
            if(modal)modal.style.display='none';
            if(!d.success){
                if(d.error==='ja_pago'){marcarComoPago(id,null);mostrarAlerta('Esta conta já foi paga!','success');}
                else if(d.error==='valor_minimo'){alert('Pagamento não gerado: '+(d.mensagem||'Valor abaixo do mínimo.'));}
                else{alert('Não foi possível gerar o link. Tente novamente.');}
                return;
            }
            if(d.tipo==='redirect'){window.location.href=d.url;return;}
            window.open(d.url,'_blank');
            sessionStorage.setItem('portalPagandoContaId',id);
            setTimeout(function(){iniciarPolling(parseInt(id,10));},5000);
        })
        .catch(function(){if(modal)modal.style.display='none';alert('Erro de conexão. Tente novamente.');});
    });
});
document.addEventListener('visibilitychange',function(){
    if(document.visibilityState==='visible'){
        var pendente=sessionStorage.getItem('portalPagandoContaId');
        if(pendente){
            var card=document.getElementById('conta-card-'+pendente);
            if(card&&card.dataset.status!=='recebida'){iniciarPolling(parseInt(pendente,10));}
            else{sessionStorage.removeItem('portalPagandoContaId');}
        }
    }
});
// --- Emissão de NF-s ---
var nfsContaIdAtual = null;
document.querySelectorAll('.btn-emitir-nfs').forEach(function(btn){
    btn.addEventListener('click',function(){
        nfsContaIdAtual = btn.dataset.contaId;
        document.getElementById('nfsDescricao').textContent = btn.dataset.descricao || 'Serviços Prestados';
        document.getElementById('nfsValor').textContent = btn.dataset.valor || '';
        document.getElementById('nfsErroMsg').style.display = 'none';
        document.getElementById('nfsLoadingMsg').style.display = 'none';
        document.getElementById('btnConfirmarNfs').disabled = false;
        document.getElementById('modalEmitirNfs').style.display = 'flex';
    });
});
document.getElementById('btnCancelarNfs').addEventListener('click',function(){
    document.getElementById('modalEmitirNfs').style.display = 'none';
    nfsContaIdAtual = null;
});
document.getElementById('btnConfirmarNfs').addEventListener('click',function(){
    if(!nfsContaIdAtual)return;
    var btn = document.getElementById('btnConfirmarNfs');
    var loading = document.getElementById('nfsLoadingMsg');
    var erroEl = document.getElementById('nfsErroMsg');
    btn.disabled = true;
    loading.style.display = 'block';
    erroEl.style.display = 'none';
    fetch('/portal/faturamento/emitir-nfs/'+nfsContaIdAtual,{
        method:'POST',
        credentials:'same-origin',
        headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'}
    })
    .then(function(r){return r.json();})
    .then(function(d){
        loading.style.display = 'none';
        if(d.success){
            document.getElementById('modalEmitirNfs').style.display = 'none';
            // Atualiza o botão no card para "Ver NF-s"
            var acoes = document.getElementById('acoes-'+nfsContaIdAtual);
            if(acoes){
                var btnEmitir = acoes.querySelector('.btn-emitir-nfs');
                if(btnEmitir){
                    var link = document.createElement('a');
                    link.href = '/portal/faturamento/notas-fiscais';
                    link.className = 'portal-btn portal-btn-outline portal-btn-sm';
                    link.innerHTML = '<i class="fa fa-file-invoice me-1"></i> Ver NF-s';
                    btnEmitir.replaceWith(link);
                }
            }
            mostrarAlerta(d.message || 'NF-s emitida com sucesso!','success');
            setTimeout(function(){window.location.href = d.redirect || '/portal/faturamento/notas-fiscais';},2500);
        } else {
            btn.disabled = false;
            erroEl.textContent = d.error || 'Erro ao emitir NF-s. Tente novamente.';
            erroEl.style.display = 'block';
        }
    })
    .catch(function(){
        loading.style.display = 'none';
        btn.disabled = false;
        erroEl.textContent = 'Erro de conexão. Tente novamente.';
        erroEl.style.display = 'block';
    });
});
})();
</script>
