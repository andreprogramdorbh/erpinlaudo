<?php
$statusMap = [
    'aberta'    => ['label' => 'Em Aberto', 'class' => 'portal-badge-warning'],
    'recebida'  => ['label' => 'Pago',      'class' => 'portal-badge-success'],
    'cancelada' => ['label' => 'Cancelada', 'class' => 'portal-badge-danger'],
    'vencida'   => ['label' => 'Vencida',   'class' => 'portal-badge-danger'],
];
$hoje = date('Y-m-d');

// Ícone por meio de pagamento
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

<!-- Alertas de feedback -->
<?php if (!empty($_GET['error'])): ?>
    <?php $erros = [
        'nao_autorizado'         => 'Acesso não autorizado.',
        'sem_link_pagamento'     => 'Esta conta ainda não possui link de pagamento. Entre em contato conosco.',
        'pagamento_indisponivel' => 'O sistema de pagamento está temporariamente indisponível. Tente novamente mais tarde.',
        'link_indisponivel'      => 'Não foi possível gerar o link de pagamento. Tente novamente.',
        'pix_indisponivel'       => 'O QR Code PIX não está disponível no momento. Tente novamente.',
        'boleto_indisponivel'    => 'O boleto não está disponível no momento. Tente novamente.',
        'erro_pagamento'         => 'Ocorreu um erro ao processar o pagamento. Tente novamente.',
        'cancelada'              => 'Esta conta está cancelada e não pode ser paga.',
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

<!-- Cards de resumo financeiro -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="portal-summary-card">
            <div class="portal-summary-icon text-warning">
                <i class="fa fa-clock"></i>
            </div>
            <div class="portal-summary-info">
                <div class="portal-summary-value"><?php echo $totalAbertas; ?></div>
                <div class="portal-summary-label">Em Aberto</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="portal-summary-card">
            <div class="portal-summary-icon text-success">
                <i class="fa fa-check-circle"></i>
            </div>
            <div class="portal-summary-info">
                <div class="portal-summary-value"><?php echo $totalRecebidas; ?></div>
                <div class="portal-summary-label">Pagas</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="portal-summary-card">
            <div class="portal-summary-icon text-primary">
                <i class="fa fa-dollar-sign"></i>
            </div>
            <div class="portal-summary-info">
                <div class="portal-summary-value">R$ <?php echo number_format($totalValorAberto, 2, ',', '.'); ?></div>
                <div class="portal-summary-label">Total em Aberto</div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros de status -->
<div class="portal-filter-tabs mb-4">
    <a href="/portal/contas-a-pagar"
       class="portal-filter-tab <?php echo $statusFiltro === '' ? 'active' : ''; ?>">
        Todas
    </a>
    <a href="/portal/contas-a-pagar?status=aberta"
       class="portal-filter-tab <?php echo $statusFiltro === 'aberta' ? 'active' : ''; ?>">
        Em Aberto
        <?php if ($totalAbertas > 0): ?>
            <span class="portal-filter-badge"><?php echo $totalAbertas; ?></span>
        <?php endif; ?>
    </a>
    <a href="/portal/contas-a-pagar?status=recebida"
       class="portal-filter-tab <?php echo $statusFiltro === 'recebida' ? 'active' : ''; ?>">
        Pagas
    </a>
</div>

<?php if (empty($contas)): ?>
    <div class="portal-empty-state">
        <i class="fa fa-check-circle portal-empty-icon text-success"></i>
        <h3>Nenhuma conta encontrada</h3>
        <p>
            <?php if ($statusFiltro === 'aberta'): ?>
                Você não possui contas em aberto no momento.
            <?php elseif ($statusFiltro === 'recebida'): ?>
                Nenhuma conta paga encontrada.
            <?php else: ?>
                Não há contas registradas para exibir.
            <?php endif; ?>
        </p>
    </div>
<?php else: ?>

    <!-- Listagem de contas — cards responsivos -->
    <div class="portal-contas-list">
        <?php foreach ($contas as $conta):
            $vencida   = ($conta->status === 'aberta' && $conta->data_vencimento < $hoje);
            $statusKey = $vencida ? 'vencida' : $conta->status;
            $badge     = $statusMap[$statusKey] ?? ['label' => ucfirst($conta->status), 'class' => 'portal-badge-secondary'];
            $dataVenc  = date('d/m/Y', strtotime($conta->data_vencimento));
            $dataRec   = !empty($conta->data_recebimento) ? date('d/m/Y', strtotime($conta->data_recebimento)) : null;
            $meioPag   = $conta->meio_pagamento ?? '';
            $meioPagLabel = match($meioPag) {
                'pix'          => 'PIX',
                'boleto'       => 'Boleto',
                'cartao'       => 'Cartão',
                'checkout'     => 'Checkout',
                'dinheiro'     => 'Dinheiro',
                'transferencia'=> 'Transferência',
                'outro'        => 'Outro',
                default        => ''
            };
            $meioPagIconClass = $meioPagIcon[$meioPag] ?? 'fa-money-bill';

            // Determina se o botão Pagar deve aparecer:
            // - Conta aberta
            // - Asaas configurado OU já tem payment_id
            $podeUsarAsaas = ($asaasEnabled ?? false) || !empty($conta->asaas_payment_id);
            $meiosManuais  = ['dinheiro', 'transferencia', 'cartao', 'outro', ''];
            $ehMeioManual  = in_array($meioPag, $meiosManuais, true) && empty($conta->asaas_payment_id);
        ?>
        <div class="portal-conta-card <?php echo $vencida ? 'portal-conta-vencida' : ''; ?>">

            <!-- Cabeçalho do card -->
            <div class="portal-conta-header">
                <div class="portal-conta-desc">
                    <?php echo htmlspecialchars($conta->descricao); ?>
                </div>
                <span class="portal-badge <?php echo $badge['class']; ?>">
                    <?php echo $badge['label']; ?>
                </span>
            </div>

            <!-- Detalhes -->
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
                        R$ <?php echo number_format((float)$conta->valor, 2, ',', '.'); ?>
                    </span>
                </div>

                <?php if ($meioPagLabel !== ''): ?>
                <div class="portal-conta-detail">
                    <span class="portal-detail-label"><i class="fa <?php echo $meioPagIconClass; ?> me-1"></i>Forma de Pagamento</span>
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
                    <span class="portal-detail-value">
                        <?php echo (int)$conta->numero_parcela; ?> / <?php echo (int)$conta->total_parcelas; ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Anexos -->
            <?php if (!empty($conta->anexos)): ?>
            <div class="portal-conta-attachments mt-3 p-2 rounded bg-light border">
                <span class="d-block small fw-bold text-muted mb-2">
                    <i class="fa fa-paperclip me-1"></i>Documentos:
                </span>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($conta->anexos as $anexo):
                        $ext = strtolower(pathinfo($anexo->original_name, PATHINFO_EXTENSION));
                        $iconAnexo = match($ext) {
                            'pdf'  => 'fa-file-pdf text-danger',
                            'xml'  => 'fa-file-code text-info',
                            'jpg', 'jpeg', 'png' => 'fa-file-image text-warning',
                            default => 'fa-file text-secondary'
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

            <!-- Ações -->
            <div class="portal-conta-actions">
                <?php if ($conta->status === 'aberta'): ?>
                    <?php if ($podeUsarAsaas && !$ehMeioManual): ?>
                        <!-- Botão Pagar via Asaas -->
                        <a href="/portal/contas-a-pagar/pagar/<?php echo (int)$conta->id; ?>"
                           class="portal-btn portal-btn-primary portal-btn-sm"
                           onclick="return confirm('Você será redirecionado para a página de pagamento. Deseja continuar?')">
                            <i class="fa fa-credit-card me-1"></i>
                            <?php echo $vencida ? 'Pagar Agora' : 'Pagar'; ?>
                        </a>
                    <?php else: ?>
                        <!-- Pagamento manual — sem integração Asaas -->
                        <a href="/portal/contas-a-pagar/pagar/<?php echo (int)$conta->id; ?>"
                           class="portal-btn portal-btn-outline portal-btn-sm">
                            <i class="fa fa-info-circle me-1"></i> Ver Instruções
                        </a>
                    <?php endif; ?>

                <?php elseif ($conta->status === 'recebida'): ?>
                    <span class="portal-btn portal-btn-success portal-btn-sm" style="cursor:default;">
                        <i class="fa fa-check-circle me-1"></i> Pago
                    </span>

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
