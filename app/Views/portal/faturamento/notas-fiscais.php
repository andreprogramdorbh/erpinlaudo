<div class="portal-page-header">
    <div>
        <h1 class="portal-page-title"><i class="fa fa-file-alt me-2"></i>Minhas Notas Fiscais</h1>
        <p class="portal-page-subtitle">Visualize e baixe suas notas fiscais</p>
    </div>
</div>

<?php if (!empty($_GET['error'])): ?>
    <?php $erros = [
        'xml_indisponivel'      => 'O arquivo XML desta nota não está disponível.',
        'arquivo_nao_encontrado'=> 'Arquivo XML não encontrado no servidor.',
    ]; ?>
    <div class="portal-alert portal-alert-danger mb-3">
        <i class="fa fa-exclamation-circle me-2"></i>
        <?php echo htmlspecialchars($erros[$_GET['error']] ?? 'Ocorreu um erro.'); ?>
    </div>
<?php endif; ?>

<?php if (empty($notas)): ?>
    <div class="portal-empty-state">
        <i class="fa fa-file-alt portal-empty-icon"></i>
        <h3>Nenhuma nota fiscal encontrada</h3>
        <p>Não há notas fiscais emitidas para sua conta ainda.</p>
    </div>
<?php else: ?>

    <!-- Tabela para desktop / Cards para mobile -->
    <div class="portal-table-wrapper d-none d-md-block">
        <table class="portal-table">
            <thead>
                <tr>
                    <th>Nº NF</th>
                    <th>Série</th>
                    <th>Data Emissão</th>
                    <th>Valor Total</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notas as $nota): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($nota->numero_nf); ?></strong></td>
                    <td><?php echo htmlspecialchars($nota->serie); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($nota->data_emissao)); ?></td>
                    <td>R$ <?php echo number_format((float) $nota->valor_total, 2, ',', '.'); ?></td>
                    <td>
                        <span class="portal-badge <?php echo $nota->status === 'emitida' ? 'portal-badge-success' : 'portal-badge-info'; ?>">
                            <?php echo ucfirst($nota->status); ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($nota->xml_path)): ?>
                            <a href="/portal/faturamento/nota-fiscal/xml/<?php echo (int) $nota->id; ?>"
                               class="portal-btn portal-btn-outline portal-btn-sm"
                               title="Baixar XML">
                                <i class="fa fa-download me-1"></i> XML
                            </a>
                        <?php else: ?>
                            <span class="text-muted small">Sem XML</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Cards para mobile -->
    <div class="portal-contas-list d-md-none">
        <?php foreach ($notas as $nota): ?>
        <div class="portal-conta-card">
            <div class="portal-conta-header">
                <div class="portal-conta-desc">
                    <strong>NF <?php echo htmlspecialchars($nota->numero_nf); ?></strong>
                    <span class="text-muted ms-2">Série <?php echo htmlspecialchars($nota->serie); ?></span>
                </div>
                <span class="portal-badge <?php echo $nota->status === 'emitida' ? 'portal-badge-success' : 'portal-badge-info'; ?>">
                    <?php echo ucfirst($nota->status); ?>
                </span>
            </div>
            <div class="portal-conta-details">
                <div class="portal-conta-detail">
                    <span class="portal-detail-label"><i class="fa fa-calendar me-1"></i>Emissão</span>
                    <span class="portal-detail-value"><?php echo date('d/m/Y', strtotime($nota->data_emissao)); ?></span>
                </div>
                <div class="portal-conta-detail">
                    <span class="portal-detail-label"><i class="fa fa-dollar-sign me-1"></i>Valor</span>
                    <span class="portal-detail-value fw-semibold">R$ <?php echo number_format((float) $nota->valor_total, 2, ',', '.'); ?></span>
                </div>
            </div>
            <div class="portal-conta-actions">
                <?php if (!empty($nota->xml_path)): ?>
                    <a href="/portal/faturamento/nota-fiscal/xml/<?php echo (int) $nota->id; ?>"
                       class="portal-btn portal-btn-outline portal-btn-sm">
                        <i class="fa fa-download me-1"></i> Baixar XML
                    </a>
                <?php else: ?>
                    <span class="text-muted small">XML não disponível</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>
