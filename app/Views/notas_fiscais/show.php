<?php

use App\Core\Auth;

/** @var object $nota */
/** @var array  $anexos */

$status        = $nota->status        ?? 'rascunho';
$origemEmissao = $nota->origem_emissao ?? 'manual';
$asaasStatus   = $nota->asaas_status   ?? null;
$pdfUrl        = $nota->asaas_pdf_url  ?? null;
$xmlUrl        = $nota->asaas_xml_url  ?? null;
$errorDesc     = $nota->asaas_error_desc ?? null;
$invoiceId     = $nota->asaas_invoice_id ?? null;
$id            = (int)$nota->id;

// ── Helpers de badge ─────────────────────────────────────────────────────────
$localBadgeHtml = match ($status) {
    'emitida'      => '<span class="badge bg-success fs-6 px-3 py-2">Emitida</span>',
    'importada'    => '<span class="badge bg-info text-dark fs-6 px-3 py-2">Importada</span>',
    'cancelada'    => '<span class="badge bg-secondary fs-6 px-3 py-2">Cancelada</span>',
    'agendada'     => '<span class="badge bg-primary fs-6 px-3 py-2">Agendada</span>',
    'erro_emissao' => '<span class="badge bg-danger fs-6 px-3 py-2">Erro de Emissão</span>',
    'pendente'     => '<span class="badge bg-warning text-dark fs-6 px-3 py-2">Pendente</span>',
    default        => '<span class="badge bg-warning text-dark fs-6 px-3 py-2">Rascunho</span>',
};

$asaasStatusCfg = null;
if ($asaasStatus !== null && $asaasStatus !== '') {
    $asaasStatusCfg = match (strtoupper($asaasStatus)) {
        'SCHEDULED'               => ['cls' => 'secondary', 'icon' => 'fa-clock',              'label' => 'Agendada'],
        'SYNCHRONIZED'            => ['cls' => 'primary',   'icon' => 'fa-paper-plane',        'label' => 'Enviada à Prefeitura'],
        'AUTHORIZED'              => ['cls' => 'success',   'icon' => 'fa-check-circle',       'label' => 'Emitida / Autorizada'],
        'PROCESSING_CANCELLATION' => ['cls' => 'warning',   'icon' => 'fa-hourglass-half',     'label' => 'Cancelamento em Andamento'],
        'CANCELED'                => ['cls' => 'secondary', 'icon' => 'fa-ban',                'label' => 'Cancelada'],
        'CANCELLATION_DENIED'     => ['cls' => 'info',      'icon' => 'fa-times-circle',       'label' => 'Cancelamento Negado'],
        'ERROR'                   => ['cls' => 'danger',    'icon' => 'fa-exclamation-triangle','label' => 'Erro na Emissão'],
        default                   => ['cls' => 'light',     'icon' => 'fa-circle',             'label' => htmlspecialchars($asaasStatus)],
    };
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="fas fa-file-invoice me-2 text-primary"></i>
            Nota Fiscal <?php echo $nota->numero_nf ? '#' . htmlspecialchars($nota->numero_nf) : '(sem número)'; ?>
        </h4>
        <small class="text-muted">
            <?php echo $origemEmissao === 'asaas' ? '<i class="fas fa-bolt text-success me-1"></i>Emitida via Asaas' : '<i class="fas fa-user text-muted me-1"></i>Emissão Manual'; ?>
            <?php if ($invoiceId): ?>
                &nbsp;&bull;&nbsp; ID Asaas: <code><?php echo htmlspecialchars($invoiceId); ?></code>
            <?php endif; ?>
        </small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?php echo $localBadgeHtml; ?>
        <a href="/faturamento/notas-fiscais" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<div class="row g-4">

    <!-- ── Coluna principal ─────────────────────────────────────────────── -->
    <div class="col-lg-7">

        <!-- Dados da NF -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2 text-primary"></i>Dados da Nota Fiscal</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Número</small>
                        <span class="fw-bold"><?php echo htmlspecialchars($nota->numero_nf ?: '—'); ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Série</small>
                        <span><?php echo htmlspecialchars($nota->serie ?: '—'); ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Data de Emissão</small>
                        <span><?php
                            $de = $nota->data_emissao ?? '';
                            echo $de ? date('d/m/Y', strtotime($de)) : '—';
                        ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Valor Total</small>
                        <span class="fw-bold text-success fs-5">
                            R$ <?php echo number_format((float)($nota->valor_total ?? 0), 2, ',', '.'); ?>
                        </span>
                    </div>
                    <div class="col-sm-12">
                        <small class="text-muted d-block">Cliente</small>
                        <span class="fw-bold"><?php echo htmlspecialchars($nota->cliente_nome ?? '—'); ?></span>
                    </div>
                    <?php if (!empty($nota->servico_descricao)): ?>
                    <div class="col-sm-12">
                        <small class="text-muted d-block">Serviço</small>
                        <span><?php echo htmlspecialchars($nota->servico_descricao); ?>
                            <?php if (!empty($nota->servico_codigo)): ?>
                                <span class="badge bg-light text-muted border ms-1"><?php echo htmlspecialchars($nota->servico_codigo); ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($nota->observacoes_nf)): ?>
                    <div class="col-sm-12">
                        <small class="text-muted d-block">Observações</small>
                        <span><?php echo nl2br(htmlspecialchars($nota->observacoes_nf)); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (Auth::can('edit_notas_fiscais') && $origemEmissao !== 'asaas'): ?>
            <div class="card-footer bg-white border-top-0 pt-0 pb-3 px-3">
                <a href="/faturamento/notas-fiscais/edit/<?php echo $id; ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-edit me-1"></i>Editar
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Anexos locais -->
        <?php if (!empty($anexos)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-paperclip me-2 text-muted"></i>Anexos</h6>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($anexos as $anx): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-file me-2 text-muted"></i><?php echo htmlspecialchars($anx->original_name ?? 'arquivo'); ?></span>
                    <a href="/faturamento/notas-fiscais/anexos/download/<?php echo (int)$anx->id; ?>"
                       class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="fas fa-download me-1"></i>Baixar
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

    </div>

    <!-- ── Coluna Asaas ─────────────────────────────────────────────────── -->
    <div class="col-lg-5">

        <?php if ($invoiceId): ?>
        <!-- Status Asaas -->
        <div class="card border-0 shadow-sm mb-4 <?php echo $asaasStatusCfg ? 'border-' . $asaasStatusCfg['cls'] . ' border-start border-4' : ''; ?>">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-bolt me-2" style="color:#00b37e"></i>Status Asaas</h6>
                <?php if (Auth::can('view_notas_fiscais')): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnConsultarAsaas" data-id="<?php echo $id; ?>">
                    <i class="fas fa-sync-alt me-1"></i>Atualizar
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body" id="asaasStatusCard">
                <?php if ($asaasStatusCfg): ?>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-<?php echo $asaasStatusCfg['cls']; ?> bg-opacity-15"
                         style="width:48px;height:48px;flex-shrink:0">
                        <i class="fas <?php echo $asaasStatusCfg['icon']; ?> fa-lg text-<?php echo $asaasStatusCfg['cls']; ?>"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-<?php echo $asaasStatusCfg['cls']; ?>"><?php echo htmlspecialchars($asaasStatusCfg['label']); ?></div>
                        <small class="text-muted">Código: <code><?php echo htmlspecialchars($asaasStatus); ?></code></small>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-muted small"><i class="fas fa-circle-notch fa-spin me-1"></i>Status não disponível</div>
                <?php endif; ?>

                <?php if ($asaasStatus === 'ERROR' && $errorDesc): ?>
                <div class="alert alert-danger mb-3 py-2 px-3">
                    <strong><i class="fas fa-exclamation-triangle me-1"></i>Descrição do Erro:</strong>
                    <div class="mt-1 small"><?php echo nl2br(htmlspecialchars($errorDesc)); ?></div>
                </div>
                <?php elseif ($asaasStatus === 'ERROR' && !$errorDesc): ?>
                <div class="alert alert-warning mb-3 py-2 px-3 small">
                    <i class="fas fa-info-circle me-1"></i>Descrição do erro não disponível. Clique em <strong>Atualizar</strong> para consultar o Asaas.
                </div>
                <?php endif; ?>

                <?php if ($asaasStatus === 'ERROR' && $origemEmissao === 'asaas' && Auth::can('edit_notas_fiscais')): ?>
                <div class="border border-warning rounded p-3 mb-3" style="background:#fffbeb">
                    <p class="mb-2 small fw-bold text-warning-emphasis">
                        <i class="fas fa-redo-alt me-1"></i>Reemitir esta Nota Fiscal
                    </p>
                    <p class="mb-3 small text-muted">
                        Uma nova NF-s será criada no Asaas com os mesmos dados de serviço.
                        O registro será atualizado com o novo ID gerado.
                    </p>
                    <button type="button" class="btn btn-warning fw-bold w-100" id="btnReemitirShow" data-id="<?php echo $id; ?>">
                        <i class="fas fa-redo-alt me-2"></i>Reemitir NF no Asaas
                    </button>
                </div>
                <?php endif; ?>

                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">ID Asaas</dt>
                    <dd class="col-7"><code><?php echo htmlspecialchars($invoiceId); ?></code></dd>
                    <?php if (!empty($nota->numero_nf)): ?>
                    <dt class="col-5 text-muted">Número NF</dt>
                    <dd class="col-7"><?php echo htmlspecialchars($nota->numero_nf); ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- PDF da NF -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-file-pdf me-2 text-danger"></i>PDF da NF-e</h6>
            </div>
            <div class="card-body">
                <?php if ($pdfUrl): ?>
                <div class="d-grid gap-2">
                    <a href="<?php echo htmlspecialchars($pdfUrl); ?>" target="_blank" class="btn btn-danger fw-bold">
                        <i class="fas fa-external-link-alt me-2"></i>Abrir PDF da Nota Fiscal
                    </a>
                    <a href="<?php echo htmlspecialchars($pdfUrl); ?>" download class="btn btn-outline-danger">
                        <i class="fas fa-download me-2"></i>Baixar PDF
                    </a>
                </div>
                <?php elseif ($asaasStatus === 'AUTHORIZED'): ?>
                <div class="alert alert-warning py-2 small mb-0">
                    <i class="fas fa-info-circle me-1"></i>NF autorizada mas PDF ainda não disponível. Clique em <strong>Atualizar</strong>.
                </div>
                <?php else: ?>
                <div class="text-muted small">
                    <i class="fas fa-hourglass-half me-1"></i>
                    PDF disponível após a autorização da prefeitura.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- XML da NF -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-code me-2 text-success"></i>XML da NF-e</h6>
            </div>
            <div class="card-body" id="xmlCard">
                <?php if ($xmlUrl): ?>
                <div class="d-grid">
                    <a href="<?php echo htmlspecialchars($xmlUrl); ?>" target="_blank" class="btn btn-success fw-bold">
                        <i class="fas fa-download me-2"></i>Baixar XML
                    </a>
                </div>
                <?php elseif ($asaasStatus === 'AUTHORIZED'): ?>
                <div class="alert alert-warning py-2 small mb-2">
                    <i class="fas fa-info-circle me-1"></i>XML não recuperado ainda. Clique em <strong>Atualizar</strong>.
                </div>
                <div class="d-grid">
                    <button type="button" class="btn btn-outline-success btn-sm" id="btnBuscarXml" data-id="<?php echo $id; ?>">
                        <i class="fas fa-sync-alt me-1"></i>Buscar XML no Asaas
                    </button>
                </div>
                <?php else: ?>
                <div class="text-muted small">
                    <i class="fas fa-hourglass-half me-1"></i>
                    XML disponível após a autorização da prefeitura.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php else: /* NF sem ID Asaas — emissão manual */ ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-muted"><i class="fas fa-info-circle me-2"></i>Asaas</h6>
            </div>
            <div class="card-body text-muted small">
                Esta nota fiscal não está vinculada ao Asaas.
                <?php if ($status === 'importada'): ?>
                <br>Foi importada via XML manualmente.
                <?php elseif ($status === 'rascunho'): ?>
                <br>Status: rascunho — ainda não emitida.
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /col-lg-5 -->
</div><!-- /row -->

<!-- Toast feedback -->
<div id="toastShow" class="position-fixed top-0 end-0 m-3" style="z-index:9999;min-width:320px;display:none">
    <div class="toast show shadow">
        <div class="toast-header" id="toastShowHeader">
            <i class="fas fa-sync-alt me-2"></i>
            <strong class="me-auto">Asaas</strong>
            <button type="button" class="btn-close" onclick="document.getElementById('toastShow').style.display='none'"></button>
        </div>
        <div class="toast-body" id="toastShowBody"></div>
    </div>
</div>

<script>
function showToast(message, type) {
    const toast  = document.getElementById('toastShow');
    const header = document.getElementById('toastShowHeader');
    const body   = document.getElementById('toastShowBody');
    const cls    = { success: 'bg-success text-white', danger: 'bg-danger text-white', warning: 'bg-warning text-dark', info: 'bg-info text-dark' };
    header.className = 'toast-header ' + (cls[type] || '');
    body.innerHTML   = message;
    toast.style.display = 'block';
    setTimeout(function() { toast.style.display = 'none'; }, type === 'danger' ? 8000 : 4000);
}

function consultarAsaas(id, reloadOnSuccess) {
    return fetch('/faturamento/notas-fiscais/consultar-asaas/' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    .then(function(r) {
        const ct = r.headers.get('Content-Type') || '';
        if (ct.includes('application/json')) return r.json();
        if (r.status === 401) return { success: false, _sessionExpired: true };
        throw new Error('HTTP ' + r.status);
    });
}

// ── Botão "Reemitir NF" ──────────────────────────────────────────────────────
const btnReemitirShow = document.getElementById('btnReemitirShow');
if (btnReemitirShow) {
    btnReemitirShow.addEventListener('click', function () {
        if (!confirm('Confirmar reemissão desta NF no Asaas?\n\nUma nova NF-s será criada com os mesmos dados de serviço.')) return;

        const id   = this.dataset.id;
        const orig = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Reemitindo...';

        fetch('/faturamento/notas-fiscais/reemitir-asaas/' + id, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(function (r) {
            const ct = r.headers.get('Content-Type') || '';
            if (ct.includes('application/json')) return r.json();
            if (r.status === 401) return { success: false, _sessionExpired: true };
            throw new Error('HTTP ' + r.status);
        })
        .then(function (data) {
            if (data._sessionExpired) {
                if (confirm('Sessão expirada. Ir para login?')) window.location.href = '/login';
                return;
            }
            if (data.success) {
                showToast(
                    '<i class="fas fa-redo-alt me-1"></i> NF reemitida! Status: <strong>'
                    + (data.asaas_status_label || data.asaas_status || '—') + '</strong>',
                    'success'
                );
                setTimeout(function () { location.reload(); }, 2500);
            } else {
                showToast('<i class="fas fa-exclamation-triangle me-1"></i> ' + (data.message || 'Erro ao reemitir.'), 'danger');
                if (btnReemitirShow) {
                    btnReemitirShow.disabled = false;
                    btnReemitirShow.innerHTML = orig;
                }
            }
        })
        .catch(function (err) {
            showToast('<i class="fas fa-exclamation-circle me-1"></i> Erro de comunicação: ' + err.message, 'danger');
            if (btnReemitirShow) {
                btnReemitirShow.disabled = false;
                btnReemitirShow.innerHTML = orig;
            }
        });
    });
}

// ── Botão "Atualizar" no card de status ──────────────────────────────────────
const btnAtualizar = document.getElementById('btnConsultarAsaas');
if (btnAtualizar) {
    btnAtualizar.addEventListener('click', function() {
        const id   = this.dataset.id;
        const orig = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Consultando...';

        consultarAsaas(id, true)
        .then(function(data) {
            if (data._sessionExpired) {
                if (confirm('Sessão expirada. Ir para login?')) window.location.href = '/login';
                return;
            }
            if (data.success) {
                const isError = data.asaas_status === 'ERROR';
                const isOk    = data.asaas_status === 'AUTHORIZED';
                let msg = '<i class="fas fa-check me-1"></i>Status: <strong>' + (data.asaas_status_label || data.asaas_status) + '</strong>';
                if (isError && data.error_desc) {
                    msg += '<br><small>' + data.error_desc + '</small>';
                }
                if (data.pdf_url && !document.querySelector('a[href="' + data.pdf_url + '"]')) {
                    // PDF URL veio novo — recarrega para refletir
                    setTimeout(function() { location.reload(); }, 1500);
                }
                showToast(msg, isError ? 'danger' : (isOk ? 'success' : 'info'));
                setTimeout(function() { location.reload(); }, 3000);
            } else {
                showToast('<i class="fas fa-exclamation-circle me-1"></i>' + (data.message || 'Erro ao consultar'), 'warning');
            }
        })
        .catch(function(err) {
            showToast('<i class="fas fa-exclamation-circle me-1"></i>Erro: ' + err.message, 'danger');
        })
        .finally(function() {
            if (btnAtualizar) {
                btnAtualizar.disabled  = false;
                btnAtualizar.innerHTML = orig;
            }
        });
    });
}

// Botão "Buscar XML no Asaas"
const btnBuscarXml = document.getElementById('btnBuscarXml');
if (btnBuscarXml) {
    btnBuscarXml.addEventListener('click', function() {
        const id   = this.dataset.id;
        const orig = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Buscando...';

        consultarAsaas(id, false)
        .then(function(data) {
            if (data._sessionExpired) {
                if (confirm('Sessão expirada. Ir para login?')) window.location.href = '/login';
                return;
            }
            if (data.success && data.xml_url) {
                const xmlCard = document.getElementById('xmlCard');
                if (xmlCard) {
                    xmlCard.innerHTML = '<div class="d-grid"><a href="' + data.xml_url + '" target="_blank" class="btn btn-success fw-bold"><i class="fas fa-download me-2"></i>Baixar XML</a></div>';
                }
                showToast('<i class="fas fa-check me-1"></i>XML encontrado!', 'success');
            } else if (data.success && !data.xml_url) {
                showToast('<i class="fas fa-info-circle me-1"></i>XML ainda não disponível no Asaas. Tente novamente em alguns instantes.', 'warning');
            } else {
                showToast('<i class="fas fa-exclamation-circle me-1"></i>' + (data.message || 'Erro ao buscar XML'), 'danger');
            }
        })
        .catch(function(err) {
            showToast('<i class="fas fa-exclamation-circle me-1"></i>Erro: ' + err.message, 'danger');
        })
        .finally(function() {
            if (btnBuscarXml) {
                btnBuscarXml.disabled  = false;
                btnBuscarXml.innerHTML = orig;
            }
        });
    });
}
</script>
