<?php
$valor      = number_format((float)($conta->valor ?? 0), 2, ',', '.');
$vencimento = !empty($conta->data_vencimento)
    ? date('d/m/Y', strtotime($conta->data_vencimento))
    : '—';

$meioLabels = [
    'dinheiro'     => ['icon' => 'fas fa-money-bill-wave', 'label' => 'Dinheiro'],
    'transferencia'=> ['icon' => 'fas fa-exchange-alt',    'label' => 'Transferência Bancária'],
    'cartao'       => ['icon' => 'fas fa-credit-card',     'label' => 'Cartão'],
    'outro'        => ['icon' => 'fas fa-hand-holding-usd','label' => 'Outro'],
    ''             => ['icon' => 'fas fa-hand-holding-usd','label' => 'A definir'],
];
$meio = $conta->meio_pagamento ?? '';
$meioInfo = $meioLabels[$meio] ?? $meioLabels[''];
?>

<div class="portal-page-header">
    <a href="/portal/contas-a-pagar" class="portal-back-btn">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
    <h1 class="portal-page-title">
        <i class="<?php echo $meioInfo['icon']; ?>"></i>
        Pagamento — <?php echo htmlspecialchars($meioInfo['label']); ?>
    </h1>
</div>

<div class="portal-card">
    <div class="portal-card-header">
        <i class="fas fa-file-invoice-dollar"></i> Detalhes da Cobrança
    </div>
    <div class="portal-card-body">
        <div class="portal-info-grid">
            <div class="portal-info-item">
                <span class="portal-info-label">Descrição</span>
                <span class="portal-info-value"><?php echo htmlspecialchars($conta->descricao ?? ''); ?></span>
            </div>
            <div class="portal-info-item">
                <span class="portal-info-label">Valor</span>
                <span class="portal-info-value portal-valor-destaque">R$ <?php echo $valor; ?></span>
            </div>
            <div class="portal-info-item">
                <span class="portal-info-label">Vencimento</span>
                <span class="portal-info-value"><?php echo $vencimento; ?></span>
            </div>
            <div class="portal-info-item">
                <span class="portal-info-label">Forma de Pagamento</span>
                <span class="portal-info-value">
                    <i class="<?php echo $meioInfo['icon']; ?>"></i>
                    <?php echo htmlspecialchars($meioInfo['label']); ?>
                </span>
            </div>
        </div>

        <div class="alert alert-warning mt-4">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Pagamento manual:</strong> Esta cobrança deve ser paga diretamente
            com a nossa equipe. Entre em contato para obter as instruções de pagamento.
        </div>

        <div class="mt-3">
            <a href="/portal/contas-a-pagar" class="portal-btn portal-btn-outline">
                <i class="fas fa-arrow-left"></i> Voltar às Contas
            </a>
        </div>
    </div>
</div>
