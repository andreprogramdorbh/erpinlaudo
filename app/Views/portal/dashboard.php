<?php
$nomeExibido = $portal->nome_fantasia ?: ($portal->razao_social ?? 'Cliente');
?>

<?php if (!empty($welcome)): ?>
<div class="portal-welcome-banner">
    <i class="fa fa-party-horn me-2"></i>
    Bem-vindo(a) à Área do Cliente! Sua senha foi criada com sucesso.
    <button type="button" class="portal-welcome-close" onclick="this.parentElement.remove()">
        <i class="fa fa-times"></i>
    </button>
</div>
<?php endif; ?>

<div class="portal-page-header">
    <div>
        <h1 class="portal-page-title">Olá, <?php echo htmlspecialchars(mb_substr($nomeExibido, 0, 30)); ?>!</h1>
        <p class="portal-page-subtitle">Aqui está o resumo da sua conta</p>
    </div>
</div>

<!-- Cards de resumo -->
<div class="portal-cards-grid">
    <div class="portal-card portal-card-info">
        <div class="portal-card-icon">
            <i class="fa fa-file-invoice-dollar"></i>
        </div>
        <div class="portal-card-content">
            <div class="portal-card-value"><?php echo (int) $contasAbertas; ?></div>
            <div class="portal-card-label">Conta(s) em Aberto</div>
        </div>
        <a href="/portal/contas-a-pagar" class="portal-card-link">Ver todas <i class="fa fa-arrow-right"></i></a>
    </div>

    <div class="portal-card <?php echo $contasVencidas > 0 ? 'portal-card-danger' : 'portal-card-success'; ?>">
        <div class="portal-card-icon">
            <i class="fa fa-<?php echo $contasVencidas > 0 ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
        </div>
        <div class="portal-card-content">
            <div class="portal-card-value"><?php echo (int) $contasVencidas; ?></div>
            <div class="portal-card-label">Conta(s) Vencida(s)</div>
        </div>
        <a href="/portal/contas-a-pagar?status=aberta" class="portal-card-link">Ver detalhes <i class="fa fa-arrow-right"></i></a>
    </div>

    <div class="portal-card portal-card-warning">
        <div class="portal-card-icon">
            <i class="fa fa-dollar-sign"></i>
        </div>
        <div class="portal-card-content">
            <div class="portal-card-value">R$ <?php echo number_format((float) $totalAberto, 2, ',', '.'); ?></div>
            <div class="portal-card-label">Total em Aberto</div>
        </div>
        <a href="/portal/contas-a-pagar" class="portal-card-link">Pagar agora <i class="fa fa-arrow-right"></i></a>
    </div>
</div>

<?php
$gruposParcelas = $gruposParcelas ?? [];
$proximasVencer = $proximasVencer ?? [];
$hoje = date('Y-m-d');
?>

<?php if (!empty($proximasVencer)): ?>
<!-- Próximas parcelas a vencer -->
<div class="mt-4">
    <h2 class="portal-section-title"><i class="fa fa-calendar-alt me-2 text-warning"></i>Próximas a Vencer (30 dias)</h2>
    <div class="portal-info-card p-0" style="overflow:hidden">
        <table style="width:100%;border-collapse:collapse;font-size:.9rem">
            <thead style="background:#f8fafc">
                <tr>
                    <th style="padding:10px 14px;text-align:left;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Descrição</th>
                    <th style="padding:10px 14px;text-align:center;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Vencimento</th>
                    <th style="padding:10px 14px;text-align:right;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Valor</th>
                    <th style="padding:10px 14px;text-align:center;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Ação</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (array_slice($proximasVencer, 0, 8) as $pv):
                $diasRestantes = (int)round((strtotime($pv->data_vencimento) - strtotime($hoje)) / 86400);
                $urgente = $diasRestantes <= 5;
            ?>
                <tr style="border-bottom:1px solid #f3f4f6;<?php echo $urgente ? 'background:#fffbeb' : ''; ?>">
                    <td style="padding:10px 14px">
                        <?php echo htmlspecialchars($pv->descricao ?? ''); ?>
                        <?php if (!empty($pv->numero_parcela) && !empty($pv->total_parcelas)): ?>
                            <span style="color:#9ca3af;font-size:.8rem">(<?php echo $pv->numero_parcela; ?>/<?php echo $pv->total_parcelas; ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px 14px;text-align:center">
                        <span style="<?php echo $urgente ? 'color:#d97706;font-weight:600' : ''; ?>">
                            <?php echo date('d/m/Y', strtotime($pv->data_vencimento)); ?>
                        </span>
                        <?php if ($diasRestantes === 0): ?>
                            <span style="background:#fef3c7;color:#92400e;padding:1px 6px;border-radius:10px;font-size:.75rem;margin-left:4px">Hoje</span>
                        <?php elseif ($diasRestantes <= 5): ?>
                            <span style="background:#fef3c7;color:#92400e;padding:1px 6px;border-radius:10px;font-size:.75rem;margin-left:4px"><?php echo $diasRestantes; ?>d</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px 14px;text-align:right;font-weight:600">
                        R$ <?php echo number_format((float)($pv->valor ?? 0), 2, ',', '.'); ?>
                    </td>
                    <td style="padding:10px 14px;text-align:center">
                        <a href="/portal/contas-a-pagar/pagar/<?php echo (int)$pv->id; ?>"
                           style="background:#3b82f6;color:#fff;padding:4px 12px;border-radius:6px;text-decoration:none;font-size:.8rem">
                            Pagar
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (count($proximasVencer) > 8): ?>
        <div style="padding:10px 14px;text-align:center;font-size:.85rem;color:#6b7280">
            + <?php echo count($proximasVencer) - 8; ?> parcela(s) adicionais &mdash;
            <a href="/portal/contas-a-pagar" style="color:#3b82f6">Ver todas</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($gruposParcelas)): ?>
<!-- Grupos de parcelas recorrentes -->
<div class="mt-4">
    <h2 class="portal-section-title"><i class="fa fa-list-ol me-2 text-primary"></i>Meus Planos de Pagamento</h2>
    <?php foreach ($gruposParcelas as $g):
        $total  = (int)($g->total_parcelas ?? 0);
        $pagas  = (int)($g->parcelas_pagas ?? 0);
        $venc   = (int)($g->parcelas_vencidas ?? 0);
        $pct    = $total > 0 ? round(($pagas / $total) * 100) : 0;
        $desc   = $g->descricao ?? 'Plano de pagamento';
        // Remove sufixo de parcela da descrição
        $desc   = preg_replace('/\s*\(\d+\/\d+\)\s*$/', '', $desc);
    ?>
    <div class="portal-info-card mb-3" style="padding:16px 20px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px">
            <div>
                <div style="font-weight:700;font-size:1rem"><?php echo htmlspecialchars($desc); ?></div>
                <div style="font-size:.82rem;color:#6b7280;margin-top:2px">
                    <?php echo date('d/m/Y', strtotime($g->primeira_parcela)); ?>
                    &rarr;
                    <?php echo date('d/m/Y', strtotime($g->ultima_parcela)); ?>
                </div>
            </div>
            <div style="text-align:right">
                <div style="font-weight:700;font-size:1.05rem;color:#1d4ed8">
                    R$ <?php echo number_format((float)($g->valor_total ?? 0), 2, ',', '.'); ?>
                </div>
                <div style="font-size:.8rem;color:#6b7280">
                    Pago: R$ <?php echo number_format((float)($g->valor_pago ?? 0), 2, ',', '.'); ?>
                </div>
            </div>
        </div>

        <div style="margin-top:12px">
            <div style="display:flex;justify-content:space-between;font-size:.82rem;color:#6b7280;margin-bottom:4px">
                <span><?php echo $pagas; ?>/<?php echo $total; ?> parcelas pagas</span>
                <span><?php echo $pct; ?>%</span>
            </div>
            <div style="height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden">
                <div style="height:100%;width:<?php echo $pct; ?>%;background:<?php echo $pct >= 100 ? '#22c55e' : '#3b82f6'; ?>;border-radius:4px;transition:width .4s"></div>
            </div>
        </div>

        <?php if ($venc > 0): ?>
        <div style="margin-top:10px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:8px 12px;font-size:.83rem;color:#dc2626">
            <i class="fa fa-exclamation-circle me-1"></i>
            <?php echo $venc; ?> parcela(s) vencida(s) em atraso
        </div>
        <?php endif; ?>

        <div style="margin-top:10px;text-align:right">
            <a href="/portal/contas-a-pagar?grupo=<?php echo urlencode($g->grupo_parcelas); ?>"
               style="color:#3b82f6;font-size:.85rem;text-decoration:none">
                Ver todas as parcelas <i class="fa fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Atalhos rápidos -->
<div class="portal-quick-actions mt-4">
    <h2 class="portal-section-title">Acesso Rápido</h2>
    <div class="portal-quick-grid">
        <a href="/portal/contas-a-pagar" class="portal-quick-item">
            <i class="fa fa-file-invoice-dollar"></i>
            <span>Minhas Contas</span>
        </a>
        <a href="/portal/faturamento/notas-fiscais" class="portal-quick-item">
            <i class="fa fa-file-alt"></i>
            <span>Notas Fiscais</span>
        </a>
        <a href="/portal/perfil" class="portal-quick-item">
            <i class="fa fa-user-cog"></i>
            <span>Meu Perfil</span>
        </a>
    </div>
</div>

<!-- Dados da empresa -->
<div class="portal-company-info mt-4">
    <h2 class="portal-section-title">Seus Dados</h2>
    <div class="portal-info-card">
        <div class="portal-info-row">
            <span class="portal-info-label"><i class="fa fa-building me-2"></i>Empresa</span>
            <span class="portal-info-value"><?php echo htmlspecialchars($portal->razao_social ?? '—'); ?></span>
        </div>
        <?php if (!empty($portal->nome_fantasia)): ?>
        <div class="portal-info-row">
            <span class="portal-info-label"><i class="fa fa-tag me-2"></i>Nome Fantasia</span>
            <span class="portal-info-value"><?php echo htmlspecialchars($portal->nome_fantasia); ?></span>
        </div>
        <?php endif; ?>
        <div class="portal-info-row">
            <span class="portal-info-label"><i class="fa fa-id-card me-2"></i>CNPJ/CPF</span>
            <span class="portal-info-value"><?php echo htmlspecialchars($portal->cpf_cnpj ?? '—'); ?></span>
        </div>
        <div class="portal-info-row">
            <span class="portal-info-label"><i class="fa fa-envelope me-2"></i>E-mail</span>
            <span class="portal-info-value"><?php echo htmlspecialchars($portal->email ?? '—'); ?></span>
        </div>
        <?php if (!empty($portal->cidade)): ?>
        <div class="portal-info-row">
            <span class="portal-info-label"><i class="fa fa-map-marker-alt me-2"></i>Cidade</span>
            <span class="portal-info-value"><?php echo htmlspecialchars($portal->cidade . '/' . $portal->estado); ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>
