<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="h4 mb-1 font-weight-bold text-dark">
            <?php echo $title; ?>
        </h2>
        <?php if (isset($subtitle)): ?>
            <p class="text-muted small mb-0">
                <?php echo $subtitle; ?>
            </p>
        <?php endif; ?>
    </div>
    <?php if (isset($actions)): ?>
        <div class="section-actions">
            <?php foreach ($actions as $action): ?>
                <?php
                $actionLink = $action['link'] ?? ($action['url'] ?? '#');
                $actionText = $action['text'] ?? ($action['label'] ?? '');
                ?>
                <a href="<?php echo $actionLink; ?>"
                    class="btn <?php echo $action['class'] ?? 'btn-primary'; ?> btn-sm ms-2">
                    <?php if (isset($action['icon'])): ?><i class="<?php echo $action['icon']; ?> me-1"></i>
                    <?php endif; ?>
                    <?php echo $actionText; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>