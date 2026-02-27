<div class="portal-page-header">
    <div>
        <h1 class="portal-page-title"><i class="fa fa-file-alt me-2"></i>Minhas Notas Fiscais</h1>
        <p class="portal-page-subtitle">Visualize e baixe suas notas fiscais e anexos</p>
    </div>
</div>

<?php if (!empty($_GET['error'])): ?>
    <?php $erros = [
        'xml_indisponivel'       => 'O arquivo XML desta nota não está disponível.',
        'arquivo_nao_encontrado' => 'Arquivo não encontrado no servidor.',
        'acesso_negado'          => 'Você não tem permissão para acessar este arquivo.',
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

    <!-- ============================================================
         TABELA — Desktop (md+)
         ============================================================ -->
    <div class="portal-table-wrapper d-none d-md-block">
        <table class="portal-table">
            <thead>
                <tr>
                    <th>Nº NF</th>
                    <th>Série</th>
                    <th>Data Emissão</th>
                    <th>Valor Total</th>
                    <th>Status</th>
                    <th>Ações / Downloads</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notas as $nota): ?>
                <?php
                    $temXml    = !empty($nota->xml_path);
                    $temAnexos = !empty($nota->anexos);
                    $temAlgo   = $temXml || $temAnexos;
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($nota->numero_nf); ?></strong></td>
                    <td><?php echo htmlspecialchars($nota->serie); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($nota->data_emissao)); ?></td>
                    <td>R$ <?php echo number_format((float) $nota->valor_total, 2, ',', '.'); ?></td>
                    <td>
                        <?php
                        $statusClass = match($nota->status) {
                            'emitida'   => 'portal-badge-success',
                            'importada' => 'portal-badge-info',
                            default     => 'portal-badge-secondary',
                        };
                        ?>
                        <span class="portal-badge <?php echo $statusClass; ?>">
                            <?php echo ucfirst($nota->status); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($temAlgo): ?>
                            <div class="d-flex flex-wrap gap-1 align-items-center">

                                <?php if ($temXml): ?>
                                    <a href="/portal/faturamento/nota-fiscal/xml/<?php echo (int) $nota->id; ?>"
                                       class="portal-btn portal-btn-outline portal-btn-sm"
                                       title="Baixar XML da NF-e">
                                        <i class="fa fa-file-code me-1"></i> XML
                                    </a>
                                <?php endif; ?>

                                <?php if ($temAnexos): ?>
                                    <?php foreach ($nota->anexos as $anexo): ?>
                                        <?php
                                        $mime = $anexo->mime_type ?? '';
                                        if (str_contains($mime, 'pdf'))        $iconClass = 'fa-file-pdf';
                                        elseif (str_contains($mime, 'xml'))    $iconClass = 'fa-file-code';
                                        elseif (str_contains($mime, 'image'))  $iconClass = 'fa-file-image';
                                        else                                   $iconClass = 'fa-file-download';
                                        $nomeExibicao = htmlspecialchars($anexo->original_name ?? 'Anexo');
                                        ?>
                                        <a href="/portal/faturamento/nota-fiscal/anexo/<?php echo (int) $anexo->id; ?>"
                                           class="portal-btn portal-btn-outline portal-btn-sm"
                                           title="Baixar: <?php echo $nomeExibicao; ?>">
                                            <i class="fa <?php echo $iconClass; ?> me-1"></i>
                                            <?php echo $nomeExibicao; ?>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                            </div>
                        <?php else: ?>
                            <span class="text-muted small"><i class="fa fa-minus me-1"></i>Nenhum arquivo</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ============================================================
         CARDS — Mobile (< md)
         ============================================================ -->
    <div class="portal-contas-list d-md-none">
        <?php foreach ($notas as $nota): ?>
        <?php
            $temXml    = !empty($nota->xml_path);
            $temAnexos = !empty($nota->anexos);
            $temAlgo   = $temXml || $temAnexos;
            $statusClass = match($nota->status) {
                'emitida'   => 'portal-badge-success',
                'importada' => 'portal-badge-info',
                default     => 'portal-badge-secondary',
            };
        ?>
        <div class="portal-conta-card">

            <!-- Cabeçalho do card -->
            <div class="portal-conta-header">
                <div class="portal-conta-desc">
                    <strong>NF <?php echo htmlspecialchars($nota->numero_nf); ?></strong>
                    <span class="text-muted ms-2">Série <?php echo htmlspecialchars($nota->serie); ?></span>
                </div>
                <span class="portal-badge <?php echo $statusClass; ?>">
                    <?php echo ucfirst($nota->status); ?>
                </span>
            </div>

            <!-- Detalhes -->
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

            <!-- Arquivos / Ações -->
            <?php if ($temAlgo): ?>
            <div class="portal-conta-attachments mt-3 p-2 rounded bg-light border">
                <span class="d-block small fw-bold text-muted mb-2">
                    <i class="fa fa-paperclip me-1"></i>Arquivos disponíveis:
                </span>
                <div class="d-flex flex-wrap gap-2">

                    <?php if ($temXml): ?>
                        <a href="/portal/faturamento/nota-fiscal/xml/<?php echo (int) $nota->id; ?>"
                           class="portal-btn portal-btn-outline portal-btn-sm"
                           title="Baixar XML da NF-e">
                            <i class="fa fa-file-code me-1"></i> XML
                        </a>
                    <?php endif; ?>

                    <?php if ($temAnexos): ?>
                        <?php foreach ($nota->anexos as $anexo): ?>
                            <?php
                            $mime = $anexo->mime_type ?? '';
                            if (str_contains($mime, 'pdf'))        $iconClass = 'fa-file-pdf';
                            elseif (str_contains($mime, 'xml'))    $iconClass = 'fa-file-code';
                            elseif (str_contains($mime, 'image'))  $iconClass = 'fa-file-image';
                            else                                   $iconClass = 'fa-file-download';
                            ?>
                            <a href="/portal/faturamento/nota-fiscal/anexo/<?php echo (int) $anexo->id; ?>"
                               class="portal-btn portal-btn-outline portal-btn-sm"
                               title="Baixar: <?php echo htmlspecialchars($anexo->original_name ?? 'Anexo'); ?>">
                                <i class="fa <?php echo $iconClass; ?> me-1"></i>
                                <?php echo htmlspecialchars($anexo->original_name ?? 'Anexo'); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </div>
            <?php else: ?>
            <div class="portal-conta-actions mt-3">
                <span class="text-muted small"><i class="fa fa-minus me-1"></i>Nenhum arquivo disponível</span>
            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>
