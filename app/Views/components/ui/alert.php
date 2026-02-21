<div class="alert alert-<?php echo $type ?? 'info'; ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
    <div class="d-flex align-items-center">
        <div class="alert-icon me-3">
            <?php
            $type = $type ?? 'info';
            $icons = [
                'success' => 'fas fa-check-circle',
                'danger' => 'fas fa-exclamation-circle',
                'warning' => 'fas fa-exclamation-triangle',
                'info' => 'fas fa-info-circle'
            ];
            echo '<i class="' . ($icons[$type] ?? $icons['info']) . '"></i>';
            ?>
        </div>
        <div>
            <?php if (isset($title)): ?><strong>
                    <?php echo $title; ?>
                </strong><br>
            <?php endif; ?>
            <?php echo $message; ?>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>