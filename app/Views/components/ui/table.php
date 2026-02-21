<div class="card shadow">
    <?php if (isset($title) || isset($actions)): ?>
        <div class="card-header border-0">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="mb-0">
                        <?php echo $title ?? ''; ?>
                    </h3>
                </div>
                <?php if (isset($actions)): ?>
                    <div class="col text-right">
                        <?php echo $actions; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    <div class="table-responsive">
        <?php if (!empty($items ?? [])): ?>
            <table class="table align-items-center table-flush">
                <thead class="thead-light">
                    <tr>
                        <?php foreach ($headers as $header): ?>
                            <th scope="col">
                                <?php echo $header; ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php echo $rowRenderer($item); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (!empty($rows ?? [])): ?>
            <table class="table align-items-center table-flush">
                <thead class="thead-light">
                    <tr>
                        <?php foreach ($headers as $header): ?>
                            <th scope="col">
                                <?php echo $header; ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td>
                                    <?php if (is_array($cell)): ?>
                                        <?php foreach ($cell as $action): ?>
                                            <a href="<?php echo $action['url'] ?? '#'; ?>"
                                               class="<?php echo $action['class'] ?? ''; ?> me-2"
                                               <?php echo $action['attr'] ?? ''; ?>>
                                                <i class="<?php echo $action['icon'] ?? 'fas fa-link'; ?>"></i>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php echo $cell; ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="card-body text-center py-5">
                <i class="<?php echo $emptyIcon ?? 'fas fa-folder-open'; ?> fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">
                    <?php echo $emptyMessage ?? 'Nenhum registro encontrado'; ?>
                </h5>
                <?php if (isset($emptySubmessage)): ?>
                    <p class="text-muted">
                        <?php echo $emptySubmessage; ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php if (isset($footer)): ?>
        <div class="card-footer py-4">
            <?php echo $footer; ?>
        </div>
    <?php endif; ?>
</div>