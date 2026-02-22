<?php
$statusMap = [
    'aberta'    => ['label' => 'Em Aberto',  'class' => 'portal-badge-warning'],
    'recebida'  => ['label' => 'Pago',        'class' => 'portal-badge-success'],
    'cancelada' => ['label' => 'Cancelada',   'class' => 'portal-badge-danger'],
    'vencida'   => ['label' => 'Vencida',     'class' => 'portal-badge-danger'],
];
$hoje = date('Y-m-d');
?>

<div class="portal-page-header">
    <div>
        <h1 class="portal-page-title"><i class="fa fa-file-invoice-dollar me-2"></i>Minhas Contas</h1>
        <p class="portal-page-subtitle">Visualize e pague suas faturas</p>
    </div>
</div>

<!-- Alertas de feedback -->
<?php if (!empty($_GET['error'])): ?>
    <?php $erros = [
        'nao_autorizado'       => 'Acesso não autorizado.',
        'sem_link_pagamento'   => 'Esta conta ainda não possui link de pagamento. Entre em contato conosco.',
        'pagamento_indisponivel' => 'O sistema de pagamento está temporariamente indisponível.',
        'link_indisponivel'    => 'Não foi possível gerar o link de pagamento. Tente novamente.',
        'erro_pagamento'       => 'Ocorreu um erro ao processar o pagamento. Tente novamente.',
        'cancelada'            => 'Esta conta está cancelada.',
    ]; ?>
    <div class="portal-alert portal-alert-danger mb-3">
        <i class="fa fa-exclamation-circle me-2"></i>
        <?php echo htmlspecialchars($erros[$_GET['error']] ?? 'Ocorreu um erro.'); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['info']) && $_GET['info'] === 'ja_pago'): ?>
    <div class="portal-alert portal-alert-success mb-3">
        <i class="fa fa-check-circle me-2"></i>
        Esta conta já foi paga. Obrigado!
    </div>
<?php endif; ?>

<!-- Filtros de status -->
<div class="portal-filter-tabs mb-4">
    <a href="/portal/contas-a-pagar" class="portal-filter-tab <?php echo $statusFiltro === '' ? 'active' : ''; ?>">
        Todas (<?php echo count($contas); ?>)
    </a>
    <a href="/portal/contas-a-pagar?status=aberta" class="portal-filter-tab <?php echo $statusFiltro === 'aberta' ? 'active' : ''; ?>">
        Em Aberto (<?php echo count($contasAbertas); ?>)
    </a>
    <a href="/portal/contas-a-pagar?status=recebida" class="portal-filter-tab <?php echo $statusFiltro === 'recebida' ? 'active' : ''; ?>">
        Pagas (<?php echo count($contasRecebidas); ?>)
    </a>
</div>

<?php if (empty($contas)): ?>
    <div class="portal-empty-state">
        <i class="fa fa-check-circle portal-empty-icon text-success"></i>
        <h3>Nenhuma conta encontrada</h3>
        <p>Não há contas <?php echo $statusFiltro === 'aberta' ? 'em aberto' : ''; ?> para exibir.</p>
    </div>
<?php else: ?>

    <!-- Listagem mobile-first: cards -->
    <div class="portal-contas-list">
        <?php foreach ($contas as $conta):
            $vencida  = ($conta->status === 'aberta' && $conta->data_vencimento < $hoje);
            $statusKey = $vencida ? 'vencida' : $conta->status;
            $badge    = $statusMap[$statusKey] ?? ['label' => $conta->status, 'class' => 'portal-badge-secondary'];
            $dataVenc = date('d/m/Y', strtotime($conta->data_vencimento));
            $dataRec  = !empty($conta->data_recebimento) ? date('d/m/Y', strtotime($conta->data_recebimento)) : null;
        ?>
        <div class="portal-conta-card <?php echo $vencida ? 'portal-conta-vencida' : ''; ?>">
            <div class="portal-conta-header">
                <div class="portal-conta-desc">
                    <?php echo htmlspecialchars($conta->descricao); ?>
                </div>
                <span class="portal-badge <?php echo $badge['class']; ?>">
                    <?php echo $badge['label']; ?>
                </span>
            </div>

            <div class="portal-conta-details">
                <div class="portal-conta-detail">
                    <span class="portal-detail-label"><i class="fa fa-calendar me-1"></i>Vencimento</span>
                    <span class="portal-detail-value <?php echo $vencida ? 'text-danger fw-semibold' : ''; ?>">
                        <?php echo $dataVenc; ?>
                        <?php if ($vencida): ?>
                            <span class="portal-overdue-tag">Vencida</span>
                        <?php endif; ?>
                    </span>
                </div>

                <div class="portal-conta-detail">
                    <span class="portal-detail-label"><i class="fa fa-dollar-sign me-1"></i>Valor</span>
                    <span class="portal-detail-value fw-semibold">
                        R$ <?php echo number_format((float) $conta->valor, 2, ',', '.'); ?>
                    </span>
                </div>

                <?php if (!empty($conta->meio_pagamento)): ?>
                <div class="portal-conta-detail">
                    <span class="portal-detail-label"><i class="fa fa-credit-card me-1"></i>Meio</span>
                    <span class="portal-detail-value"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $conta->meio_pagamento))); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($dataRec): ?>
                <div class="portal-conta-detail">
                    <span class="portal-detail-label"><i class="fa fa-check me-1"></i>Pago em</span>
                    <span class="portal-detail-value text-success"><?php echo $dataRec; ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Ações -->
            <div class="portal-conta-actions">
                <?php if ($conta->status === 'aberta' && !empty($conta->asaas_payment_id)): ?>
                    <a href="/portal/contas-a-pagar/pagar/<?php echo (int) $conta->id; ?>"
                       class="portal-btn portal-btn-primary portal-btn-sm"
                       onclick="return confirm('Você será redirecionado para a página de pagamento. Deseja continuar?')">
                        <i class="fa fa-credit-card me-1"></i>
                        <?php echo $vencida ? 'Pagar Agora' : 'Pagar'; ?>
                    </a>
                <?php elseif ($conta->status === 'aberta' && empty($conta->asaas_payment_id)): ?>
                    <span class="portal-btn portal-btn-outline portal-btn-sm" title="Entre em contato para efetuar o pagamento">
                        <i class="fa fa-info-circle me-1"></i> Aguardando cobrança
                    </span>
                <?php elseif ($conta->status === 'recebida'): ?>
                    <span class="portal-btn portal-btn-success portal-btn-sm">
                        <i class="fa fa-check-circle me-1"></i> Pago
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>
