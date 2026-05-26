<?php
/**
 * Portal do Cliente — Negociações > Propostas
 */
$statusLabels = [
    'rascunho'  => ['label' => 'Rascunho',          'class' => 'bg-secondary'],
    'enviada'   => ['label' => 'Aguardando Aceite',  'class' => 'bg-primary'],
    'aceita'    => ['label' => 'Aceita',             'class' => 'bg-success'],
    'recusada'  => ['label' => 'Recusada',           'class' => 'bg-danger'],
    'vencida'   => ['label' => 'Vencida',            'class' => 'bg-warning text-dark'],
    'cancelada' => ['label' => 'Cancelada',          'class' => 'bg-dark'],
];
?>
<div class="portal-page-header">
    <div>
        <h1 class="portal-page-title"><i class="fa fa-file-contract me-2 text-primary"></i>Minhas Propostas</h1>
        <p class="portal-page-subtitle">Visualize e aceite as propostas comerciais enviadas para você</p>
    </div>
</div>

<!-- Filtro de status -->
<div class="portal-info-card mb-4 p-3">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="text-muted small fw-semibold me-2">Filtrar por status:</span>
        <a href="/portal/negociacoes/propostas"
           class="btn btn-sm <?php echo empty($statusFiltro) ? 'btn-primary' : 'btn-outline-secondary'; ?>">
            Todas
        </a>
        <?php foreach ($statusLabels as $key => $info): ?>
        <a href="/portal/negociacoes/propostas?status=<?php echo $key; ?>"
           class="btn btn-sm <?php echo $statusFiltro === $key ? 'btn-primary' : 'btn-outline-secondary'; ?>">
            <?php echo $info['label']; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (empty($propostas)): ?>
<div class="portal-info-card text-center py-5">
    <div style="font-size:48px;margin-bottom:12px">📄</div>
    <h5 class="fw-semibold text-muted">Nenhuma proposta encontrada</h5>
    <p class="text-muted small">Quando uma proposta for enviada para você, ela aparecerá aqui.</p>
</div>
<?php else: ?>
<div class="portal-info-card p-0" style="overflow:hidden">
    <div class="table-responsive">
        <table style="width:100%;border-collapse:collapse;font-size:.9rem">
            <thead style="background:#f8fafc">
                <tr>
                    <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Proposta</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Título</th>
                    <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Data</th>
                    <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Validade</th>
                    <th style="padding:12px 16px;text-align:right;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Valor Total</th>
                    <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Status</th>
                    <th style="padding:12px 16px;text-align:center;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($propostas as $p):
                $st = $p->status ?? 'enviada';
                // Verificar se vencida
                if ($st === 'enviada' && !empty($p->validade_proposta) && $p->validade_proposta < date('Y-m-d')) {
                    $st = 'vencida';
                }
                $badge = $statusLabels[$st] ?? ['label' => ucfirst($st), 'class' => 'bg-secondary'];
                $token = $p->token_acesso ?? '';
                $podeAceitar = $st === 'enviada' && !empty($token);
            ?>
            <tr style="border-bottom:1px solid #f3f4f6">
                <td style="padding:12px 16px;font-weight:600;color:#1a56db"><?php echo htmlspecialchars($p->numero ?? '—'); ?></td>
                <td style="padding:12px 16px"><?php echo htmlspecialchars($p->titulo ?? '—'); ?></td>
                <td style="padding:12px 16px;text-align:center;color:#6b7280">
                    <?php echo !empty($p->data_proposta) ? date('d/m/Y', strtotime($p->data_proposta)) : '—'; ?>
                </td>
                <td style="padding:12px 16px;text-align:center;color:<?php echo $st === 'vencida' ? '#dc2626' : '#6b7280'; ?>">
                    <?php echo !empty($p->validade_proposta) ? date('d/m/Y', strtotime($p->validade_proposta)) : '—'; ?>
                </td>
                <td style="padding:12px 16px;text-align:right;font-weight:600">
                    R$ <?php echo number_format((float)($p->total ?? 0), 2, ',', '.'); ?>
                </td>
                <td style="padding:12px 16px;text-align:center">
                    <span class="badge <?php echo $badge['class']; ?>"><?php echo $badge['label']; ?></span>
                </td>
                <td style="padding:12px 16px;text-align:center">
                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                        <?php if ($podeAceitar): ?>
                        <a href="/proposta/aceite/<?php echo htmlspecialchars($token); ?>" target="_blank"
                           class="btn btn-sm btn-success" title="Aceitar / Assinar">
                            <i class="fa fa-signature me-1"></i>Aceitar
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($token)): ?>
                        <a href="/proposta/aceite/<?php echo htmlspecialchars($token); ?>" target="_blank"
                           class="btn btn-sm btn-outline-primary" title="Ver Proposta">
                            <i class="fa fa-eye"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($st === 'aceita' && !empty($p->aceito_em)): ?>
                        <span class="text-success small" title="Aceita em <?php echo date('d/m/Y H:i', strtotime($p->aceito_em)); ?>">
                            <i class="fa fa-check-circle"></i>
                            <?php echo date('d/m/Y', strtotime($p->aceito_em)); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
