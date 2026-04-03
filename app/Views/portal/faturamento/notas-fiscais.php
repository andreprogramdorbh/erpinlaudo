<?php
$statusMap = [
    'emitida'       => ['label' => 'Emitida',            'class' => 'portal-badge-success',   'icon' => 'fa-check-circle'],
    'emitida_asaas' => ['label' => 'Emitida',            'class' => 'portal-badge-success',   'icon' => 'fa-check-circle'],
    'importada'     => ['label' => 'Emitida Manualmente','class' => 'portal-badge-info',      'icon' => 'fa-user-check'],
    'pendente'      => ['label' => 'Aguardando Emiss&atilde;o', 'class' => 'portal-badge-warning', 'icon' => 'fa-hourglass-half'],
    'cancelada'     => ['label' => 'Cancelada',          'class' => 'portal-badge-danger',    'icon' => 'fa-ban'],
    'agendada'      => ['label' => 'Agendada',           'class' => 'portal-badge-warning',   'icon' => 'fa-clock'],
    'erro_emissao'  => ['label' => 'Erro na emiss&atilde;o', 'class' => 'portal-badge-error', 'icon' => 'fa-exclamation-triangle'],
];
$origemMap = [
    'asaas'  => ['label' => 'Asaas',  'class' => 'portal-badge-primary',   'icon' => 'fa-bolt'],
    'manual' => ['label' => 'Manual', 'class' => 'portal-badge-secondary', 'icon' => 'fa-user'],
    ''       => ['label' => 'Manual', 'class' => 'portal-badge-secondary', 'icon' => 'fa-user'],
];

/**
 * Retorna true se a NF está em estado "emitida" (tem PDF/XML disponível).
 */
function nfEmitida(object $nota): bool {
    return in_array($nota->status ?? '', ['emitida', 'emitida_asaas', 'importada'], true);
}

/**
 * Exibe o número da NF ou um traço (—) se não houver número.
 * Usa echo direto (sem htmlspecialchars) para permitir entidades HTML.
 */
function exibirNumeroNf(object $nota): string {
    $num = trim($nota->numero_nf ?? '');
    return $num !== '' ? htmlspecialchars($num) : '&mdash;';
}
?>
<div class="portal-page-header">
    <div>
        <h1 class="portal-page-title"><i class="fa fa-file-invoice me-2"></i>Minhas Notas Fiscais</h1>
        <p class="portal-page-subtitle">Visualize, filtre e baixe suas notas fiscais de servi&ccedil;o</p>
    </div>
</div>

<?php if (!empty($_GET['success'])): ?>
    <?php $msgs = [
        'nf_emitida'    => 'NF-s emitida com sucesso via Asaas! Ela aparecer&aacute; na lista abaixo.',
        'nf_ja_emitida' => 'J&aacute; existe uma NF-s emitida para esta conta.',
    ]; ?>
    <div class="portal-alert portal-alert-success mb-3">
        <i class="fa fa-check-circle me-2"></i>
        <?php echo $msgs[$_GET['success']] ?? 'Opera&ccedil;&atilde;o realizada com sucesso.'; ?>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
    <?php $erros = [
        'xml_indisponivel'       => 'O arquivo XML desta nota n&atilde;o est&aacute; dispon&iacute;vel.',
        'pdf_indisponivel'       => 'O PDF desta nota n&atilde;o est&aacute; dispon&iacute;vel no momento.',
        'arquivo_nao_encontrado' => 'Arquivo n&atilde;o encontrado no servidor.',
        'acesso_negado'          => 'Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar este arquivo.',
        'erro_download_pdf'      => 'Ocorreu um erro ao baixar o PDF. Tente novamente.',
    ]; ?>
    <div class="portal-alert portal-alert-danger mb-3">
        <i class="fa fa-exclamation-circle me-2"></i>
        <?php echo $erros[$_GET['error']] ?? 'Ocorreu um erro.'; ?>
    </div>
<?php endif; ?>

<!-- FILTROS -->
<div class="portal-filter-card mb-4">
    <form method="GET" action="/portal/faturamento/notas-fiscais" class="portal-filter-form">
        <div class="portal-filter-row">
            <div class="portal-filter-field">
                <label class="portal-filter-label"><i class="fa fa-search me-1"></i>Pesquisar</label>
                <input type="text" name="pesquisa" class="portal-filter-input"
                       placeholder="N&uacute;mero da NF, s&eacute;rie..."
                       value="<?php echo htmlspecialchars($filtros['pesquisa'] ?? ''); ?>">
            </div>
            <div class="portal-filter-field">
                <label class="portal-filter-label"><i class="fa fa-calendar me-1"></i>Data In&iacute;cio</label>
                <input type="date" name="data_inicio" class="portal-filter-input"
                       value="<?php echo htmlspecialchars($filtros['data_inicio'] ?? ''); ?>">
            </div>
            <div class="portal-filter-field">
                <label class="portal-filter-label"><i class="fa fa-calendar me-1"></i>Data Fim</label>
                <input type="date" name="data_fim" class="portal-filter-input"
                       value="<?php echo htmlspecialchars($filtros['data_fim'] ?? ''); ?>">
            </div>
            <div class="portal-filter-field">
                <label class="portal-filter-label"><i class="fa fa-tag me-1"></i>Status</label>
                <select name="status" class="portal-filter-input">
                    <option value="">Todos</option>
                    <option value="emitida"       <?php echo ($filtros['status'] ?? '') === 'emitida'       ? 'selected' : ''; ?>>Emitida</option>
                    <option value="emitida_asaas" <?php echo ($filtros['status'] ?? '') === 'emitida_asaas' ? 'selected' : ''; ?>>Emitida (Asaas)</option>
                    <option value="importada"     <?php echo ($filtros['status'] ?? '') === 'importada'     ? 'selected' : ''; ?>>Emitida Manualmente</option>
                    <option value="agendada"      <?php echo ($filtros['status'] ?? '') === 'agendada'      ? 'selected' : ''; ?>>Agendada</option>
                    <option value="cancelada"     <?php echo ($filtros['status'] ?? '') === 'cancelada'     ? 'selected' : ''; ?>>Cancelada</option>
                    <option value="erro_emissao"  <?php echo ($filtros['status'] ?? '') === 'erro_emissao'  ? 'selected' : ''; ?>>Erro na emiss&atilde;o</option>
                </select>
            </div>
            <div class="portal-filter-actions">
                <button type="submit" class="portal-btn portal-btn-primary portal-btn-sm">
                    <i class="fa fa-search me-1"></i> Filtrar
                </button>
                <a href="/portal/faturamento/notas-fiscais" class="portal-btn portal-btn-outline portal-btn-sm">
                    <i class="fa fa-times me-1"></i> Limpar
                </a>
            </div>
        </div>
    </form>
</div>

<?php if (empty($notas)): ?>
    <div class="portal-empty-state">
        <i class="fa fa-file-invoice fa-3x text-muted mb-3 d-block"></i>
        <h3 class="h5 text-muted">Nenhuma nota fiscal encontrada</h3>
        <p class="text-muted small">
            <?php if (!empty(array_filter($filtros ?? []))): ?>
                Nenhuma NF corresponde aos filtros aplicados.
                <a href="/portal/faturamento/notas-fiscais">Limpar filtros</a>
            <?php else: ?>
                N&atilde;o h&aacute; notas fiscais emitidas para sua conta ainda.
            <?php endif; ?>
        </p>
    </div>
<?php else: ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted small">
            <i class="fa fa-list me-1"></i>
            <?php echo count($notas); ?> nota<?php echo count($notas) !== 1 ? 's' : ''; ?> encontrada<?php echo count($notas) !== 1 ? 's' : ''; ?>
        </span>
        <?php if (!empty(array_filter($filtros ?? []))): ?>
            <a href="/portal/faturamento/notas-fiscais" class="portal-btn portal-btn-outline portal-btn-sm">
                <i class="fa fa-times me-1"></i> Limpar filtros
            </a>
        <?php endif; ?>
    </div>

    <!-- TABELA Desktop -->
    <div class="portal-table-wrapper d-none d-md-block">
        <table class="portal-table">
            <thead>
                <tr>
                    <th>N&ordm; NF</th>
                    <th>S&eacute;rie</th>
                    <th>Data Emiss&atilde;o</th>
                    <th>Valor Total</th>
                    <th>Origem</th>
                    <th>Status</th>
                    <th>Downloads</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notas as $nota):
                    $statusInfo  = $statusMap[$nota->status] ?? ['label' => ucfirst($nota->status ?? ''), 'class' => 'portal-badge-secondary', 'icon' => 'fa-file'];
                    $origem      = strtolower(trim($nota->origem_emissao ?? ''));
                    if ($nota->status === 'importada') { $origem = 'manual'; }
                    $origemInfo  = $origemMap[$origem] ?? $origemMap['manual'];
                    $emitida     = nfEmitida($nota);
                    $temXml      = $emitida && !empty($nota->xml_path);
                    $temPdfAsaas = $emitida && !empty($nota->asaas_pdf_url);
                    $temAnexos   = $emitida && !empty($nota->anexos);
                ?>
                <tr>
                    <td><strong><?php echo exibirNumeroNf($nota); ?></strong></td>
                    <td><?php echo !empty($nota->serie) ? htmlspecialchars($nota->serie) : '&mdash;'; ?></td>
                    <td><?php echo !empty($nota->data_emissao) ? date('d/m/Y', strtotime($nota->data_emissao)) : '&mdash;'; ?></td>
                    <td class="fw-semibold">R$ <?php echo number_format((float) $nota->valor_total, 2, ',', '.'); ?></td>
                    <td>
                        <span class="portal-badge <?php echo $origemInfo['class']; ?>">
                            <i class="fa <?php echo $origemInfo['icon']; ?> me-1"></i>
                            <?php echo $origemInfo['label']; ?>
                        </span>
                    </td>
                    <td>
                        <span class="portal-badge <?php echo $statusInfo['class']; ?>">
                            <i class="fa <?php echo $statusInfo['icon']; ?> me-1"></i>
                            <?php echo $statusInfo['label']; ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-1 align-items-center">
                            <?php if ($temPdfAsaas): ?>
                                <a href="/portal/faturamento/nota-fiscal/pdf/<?php echo (int) $nota->id; ?>"
                                   class="portal-btn portal-btn-danger portal-btn-sm"
                                   title="Baixar PDF da NF-s" target="_blank">
                                    <i class="fa fa-file-pdf me-1"></i> PDF
                                </a>
                            <?php endif; ?>
                            <?php if ($temXml): ?>
                                <a href="/portal/faturamento/nota-fiscal/xml/<?php echo (int) $nota->id; ?>"
                                   class="portal-btn portal-btn-info portal-btn-sm"
                                   title="Baixar XML da NF-e">
                                    <i class="fa fa-file-code me-1"></i> XML
                                </a>
                            <?php endif; ?>
                            <?php if ($temAnexos): ?>
                                <?php foreach ($nota->anexos as $anexo):
                                    $ext = strtolower(pathinfo($anexo->original_name ?? '', PATHINFO_EXTENSION));
                                    $iconAnexo = match($ext) {
                                        'pdf' => 'fa-file-pdf text-danger',
                                        'xml' => 'fa-file-code text-info',
                                        'jpg','jpeg','png' => 'fa-file-image text-warning',
                                        default => 'fa-file text-secondary'
                                    };
                                ?>
                                    <a href="/portal/faturamento/nota-fiscal/anexo/<?php echo (int) $anexo->id; ?>"
                                       class="portal-btn portal-btn-outline portal-btn-sm"
                                       title="Baixar <?php echo htmlspecialchars($anexo->original_name ?? 'Anexo'); ?>">
                                        <i class="fa <?php echo $iconAnexo; ?> me-1"></i>
                                        <?php echo htmlspecialchars(mb_strimwidth($anexo->original_name ?? 'Anexo', 0, 18, '...')); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!$emitida): ?>
                                <?php if (($nota->status ?? '') === 'pendente' && !empty($nota->conta_receber_id)): ?>
                                    <button type="button"
                                            class="portal-btn portal-btn-info portal-btn-sm btn-emitir-nfs-nf"
                                            data-conta-id="<?php echo (int)$nota->conta_receber_id; ?>"
                                            data-descricao="<?php echo htmlspecialchars($nota->servico_descricao ?? 'Servi&ccedil;os Prestados'); ?>"
                                            data-valor="R$ <?php echo number_format((float)$nota->valor_total, 2, ',', '.'); ?>">
                                        <i class="fa fa-file-invoice me-1"></i> Emitir NF-s
                                    </button>
                                <?php elseif (($nota->status ?? '') === 'pendente'): ?>
                                    <span class="nf-status-msg nf-status-agendada">
                                        <i class="fa fa-hourglass-half me-1"></i>Aguardando emiss&atilde;o
                                    </span>
                                <?php elseif (($nota->status ?? '') === 'erro_emissao'): ?>
                                    <span class="nf-status-msg nf-status-error">
                                        <i class="fa fa-exclamation-triangle me-1"></i>Erro na emiss&atilde;o
                                    </span>
                                <?php elseif (($nota->status ?? '') === 'cancelada'): ?>
                                    <span class="nf-status-msg nf-status-cancelada">
                                        <i class="fa fa-ban me-1"></i>Cancelada
                                    </span>
                                <?php elseif (($nota->status ?? '') === 'agendada'): ?>
                                    <span class="nf-status-msg nf-status-agendada">
                                        <i class="fa fa-clock me-1"></i>Agendada
                                    </span>
                                <?php else: ?>
                                    <span class="nf-status-msg nf-status-processando">
                                        <i class="fa fa-spinner fa-spin me-1"></i>Processando
                                    </span>
                                <?php endif; ?>
                            <?php elseif (!$temPdfAsaas && !$temXml && !$temAnexos): ?>
                                <span class="nf-status-msg nf-status-processando">
                                    <i class="fa fa-spinner fa-spin me-1"></i>Processando
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- CARDS Mobile -->
    <div class="d-md-none">
        <?php foreach ($notas as $nota):
            $statusInfo  = $statusMap[$nota->status] ?? ['label' => ucfirst($nota->status ?? ''), 'class' => 'portal-badge-secondary', 'icon' => 'fa-file'];
            $origem      = strtolower(trim($nota->origem_emissao ?? ''));
            if ($nota->status === 'importada') { $origem = 'manual'; }
            $origemInfo  = $origemMap[$origem] ?? $origemMap['manual'];
            $emitida     = nfEmitida($nota);
            $temXml      = $emitida && !empty($nota->xml_path);
            $temPdfAsaas = $emitida && !empty($nota->asaas_pdf_url);
            $temAnexos   = $emitida && !empty($nota->anexos);
        ?>
        <div class="portal-conta-card mb-3">
            <div class="portal-conta-header">
                <div class="d-flex gap-1 flex-wrap">
                    <span class="portal-badge <?php echo $statusInfo['class']; ?>">
                        <i class="fa <?php echo $statusInfo['icon']; ?> me-1"></i><?php echo $statusInfo['label']; ?>
                    </span>
                    <span class="portal-badge <?php echo $origemInfo['class']; ?>">
                        <i class="fa <?php echo $origemInfo['icon']; ?> me-1"></i><?php echo $origemInfo['label']; ?>
                    </span>
                </div>
                <span class="text-muted small">
                    <?php echo !empty($nota->data_emissao) ? date('d/m/Y', strtotime($nota->data_emissao)) : '&mdash;'; ?>
                </span>
            </div>
            <div class="portal-conta-body">
                <div class="portal-conta-details">
                    <div class="portal-conta-detail">
                        <span class="portal-detail-label">N&ordm; NF</span>
                        <span class="portal-detail-value fw-semibold"><?php echo exibirNumeroNf($nota); ?></span>
                    </div>
                    <div class="portal-conta-detail">
                        <span class="portal-detail-label">S&eacute;rie</span>
                        <span class="portal-detail-value"><?php echo !empty($nota->serie) ? htmlspecialchars($nota->serie) : '&mdash;'; ?></span>
                    </div>
                    <div class="portal-conta-detail">
                        <span class="portal-detail-label">Valor</span>
                        <span class="portal-detail-value fw-bold text-success">R$ <?php echo number_format((float) $nota->valor_total, 2, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
            <div class="portal-conta-actions mt-2">
                <?php if ($temPdfAsaas): ?>
                    <a href="/portal/faturamento/nota-fiscal/pdf/<?php echo (int) $nota->id; ?>"
                       class="portal-btn portal-btn-danger portal-btn-sm" target="_blank">
                        <i class="fa fa-file-pdf me-1"></i> PDF
                    </a>
                <?php endif; ?>
                <?php if ($temXml): ?>
                    <a href="/portal/faturamento/nota-fiscal/xml/<?php echo (int) $nota->id; ?>"
                       class="portal-btn portal-btn-info portal-btn-sm">
                        <i class="fa fa-file-code me-1"></i> XML
                    </a>
                <?php endif; ?>
                <?php if ($temAnexos): ?>
                    <?php foreach ($nota->anexos as $anexo): ?>
                        <a href="/portal/faturamento/nota-fiscal/anexo/<?php echo (int) $anexo->id; ?>"
                           class="portal-btn portal-btn-outline portal-btn-sm">
                            <i class="fa fa-paperclip me-1"></i>
                            <?php echo htmlspecialchars(mb_strimwidth($anexo->original_name ?? 'Anexo', 0, 16, '...')); ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!$emitida): ?>
                    <?php if (($nota->status ?? '') === 'pendente' && !empty($nota->conta_receber_id)): ?>
                        <button type="button"
                                class="portal-btn portal-btn-info portal-btn-sm btn-emitir-nfs-nf"
                                data-conta-id="<?php echo (int)$nota->conta_receber_id; ?>"
                                data-descricao="<?php echo htmlspecialchars($nota->servico_descricao ?? 'Servi&ccedil;os Prestados'); ?>"
                                data-valor="R$ <?php echo number_format((float)$nota->valor_total, 2, ',', '.'); ?>">
                            <i class="fa fa-file-invoice me-1"></i> Emitir NF-s
                        </button>
                    <?php elseif (($nota->status ?? '') === 'pendente'): ?>
                        <span class="nf-status-msg nf-status-agendada">
                            <i class="fa fa-hourglass-half me-1"></i>Aguardando emiss&atilde;o
                        </span>
                    <?php elseif (($nota->status ?? '') === 'erro_emissao'): ?>
                        <span class="nf-status-msg nf-status-error">
                            <i class="fa fa-exclamation-triangle me-1"></i>Erro na emiss&atilde;o
                        </span>
                    <?php elseif (($nota->status ?? '') === 'cancelada'): ?>
                        <span class="nf-status-msg nf-status-cancelada">
                            <i class="fa fa-ban me-1"></i>Cancelada
                        </span>
                    <?php elseif (($nota->status ?? '') === 'agendada'): ?>
                        <span class="nf-status-msg nf-status-agendada">
                            <i class="fa fa-clock me-1"></i>Agendada
                        </span>
                    <?php else: ?>
                        <span class="nf-status-msg nf-status-processando">
                            <i class="fa fa-spinner fa-spin me-1"></i>Processando
                        </span>
                    <?php endif; ?>
                <?php elseif (!$temPdfAsaas && !$temXml && !$temAnexos): ?>
                    <span class="nf-status-msg nf-status-processando">
                        <i class="fa fa-spinner fa-spin me-1"></i>Processando
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<!-- Modal Emitir NF-s (para NFs pendentes de recebimento manual) -->
<div id="modalEmitirNfsNf" class="portal-modal-overlay" style="display:none;">
    <div class="portal-modal-box" style="max-width:440px;">
        <div class="p-4">
            <div class="d-flex align-items-center mb-3">
                <i class="fa fa-file-invoice text-primary fa-lg me-2"></i>
                <h5 class="mb-0 fw-bold">Emitir Nota Fiscal de Servi&ccedil;o</h5>
            </div>
            <p class="text-muted small mb-3">Confirme os dados antes de emitir</p>
            <div class="portal-conta-card p-3 mb-3" style="background:#f8fafc;">
                <div class="fw-semibold mb-1" id="nfsNfDescricao">—</div>
                <div class="text-success fw-bold" id="nfsNfValor">—</div>
            </div>
            <div id="nfsNfLoadingMsg" style="display:none;" class="text-center text-muted small py-2">
                <i class="fa fa-spinner fa-spin me-1"></i> Emitindo nota fiscal...
            </div>
            <div id="nfsNfErroMsg" class="portal-alert portal-alert-danger mt-2" style="display:none;"></div>
        </div>
        <div class="d-flex gap-2 px-4 pb-4">
            <button type="button" id="btnCancelarNfsNf"
                    class="portal-btn portal-btn-outline flex-fill">
                <i class="fa fa-times me-1"></i> Cancelar
            </button>
            <button type="button" id="btnConfirmarNfsNf"
                    class="portal-btn portal-btn-primary flex-fill">
                <i class="fa fa-file-invoice me-1"></i> Emitir NF-s
            </button>
        </div>
    </div>
</div>

<script>
(function(){
    var nfsNfContaIdAtual = null;
    document.querySelectorAll('.btn-emitir-nfs-nf').forEach(function(btn){
        btn.addEventListener('click',function(){
            nfsNfContaIdAtual = btn.dataset.contaId;
            document.getElementById('nfsNfDescricao').textContent = btn.dataset.descricao || 'Servi\u00e7os Prestados';
            document.getElementById('nfsNfValor').textContent = btn.dataset.valor || '';
            document.getElementById('nfsNfErroMsg').style.display = 'none';
            document.getElementById('nfsNfLoadingMsg').style.display = 'none';
            document.getElementById('btnConfirmarNfsNf').disabled = false;
            document.getElementById('modalEmitirNfsNf').style.display = 'flex';
        });
    });
    document.getElementById('btnCancelarNfsNf').addEventListener('click',function(){
        document.getElementById('modalEmitirNfsNf').style.display = 'none';
        nfsNfContaIdAtual = null;
    });
    document.getElementById('btnConfirmarNfsNf').addEventListener('click',function(){
        if(!nfsNfContaIdAtual) return;
        var btn = document.getElementById('btnConfirmarNfsNf');
        var loading = document.getElementById('nfsNfLoadingMsg');
        var erroEl = document.getElementById('nfsNfErroMsg');
        btn.disabled = true;
        loading.style.display = 'block';
        erroEl.style.display = 'none';
        fetch('/portal/faturamento/emitir-nfs/'+nfsNfContaIdAtual,{
            method:'POST',
            credentials:'same-origin',
            headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'}
        })
        .then(function(r){return r.json();})
        .then(function(d){
            loading.style.display = 'none';
            if(d.success){
                document.getElementById('modalEmitirNfsNf').style.display = 'none';
                setTimeout(function(){
                    window.location.href = d.redirect || '/portal/faturamento/notas-fiscais?success=nf_emitida';
                }, 800);
            } else {
                btn.disabled = false;
                erroEl.textContent = d.error || 'Erro ao emitir NF-s. Tente novamente.';
                erroEl.style.display = 'block';
            }
        })
        .catch(function(){
            loading.style.display = 'none';
            btn.disabled = false;
            erroEl.textContent = 'Erro de conex\u00e3o. Tente novamente.';
            erroEl.style.display = 'block';
        });
    });
})();
</script>

<style>
.portal-filter-card{background:var(--portal-surface);border-radius:var(--portal-radius);border:1px solid var(--portal-border);padding:1rem 1.25rem;box-shadow:var(--portal-shadow)}
.portal-filter-row{display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end}
.portal-filter-field{display:flex;flex-direction:column;gap:.25rem;flex:1;min-width:140px}
.portal-filter-label{font-size:.75rem;font-weight:600;color:var(--portal-muted);text-transform:uppercase;letter-spacing:.03em}
.portal-filter-input{padding:.4rem .65rem;border:1px solid var(--portal-border);border-radius:6px;font-size:.875rem;color:var(--portal-text);background:var(--portal-bg);transition:border-color .2s}
.portal-filter-input:focus{outline:none;border-color:var(--portal-primary);box-shadow:0 0 0 3px rgba(37,99,235,.12)}
.portal-filter-actions{display:flex;gap:.5rem;align-items:flex-end;padding-bottom:1px}
.portal-table-wrapper{overflow-x:auto;border-radius:var(--portal-radius);border:1px solid var(--portal-border);background:var(--portal-surface)}
.portal-table{width:100%;border-collapse:collapse;font-size:.875rem}
.portal-table thead th{background:#f8fafc;padding:.75rem 1rem;font-weight:600;color:var(--portal-muted);text-transform:uppercase;font-size:.7rem;letter-spacing:.05em;border-bottom:2px solid var(--portal-border);white-space:nowrap}
.portal-table tbody td{padding:.75rem 1rem;border-bottom:1px solid var(--portal-border);vertical-align:middle}
.portal-table tbody tr:last-child td{border-bottom:none}
.portal-table tbody tr:hover{background:#f8fafc}
.portal-badge-info{background:#dbeafe;color:#1e40af}
.portal-badge-primary{background:#ede9fe;color:#5b21b6}
.portal-badge-secondary{background:#f1f5f9;color:#475569}
.portal-badge-error{background:#fee2e2;color:#991b1b}
/* Mensagens de status na coluna Downloads */
.nf-status-msg{display:inline-flex;align-items:center;font-size:.78rem;padding:.2rem .5rem;border-radius:4px;font-weight:500}
.nf-status-error{background:#fee2e2;color:#991b1b}
.nf-status-cancelada{background:#f1f5f9;color:#64748b}
.nf-status-agendada{background:#fef9c3;color:#92400e}
.nf-status-processando{background:#f0f9ff;color:#0369a1}
</style>
