<div class="card <?php echo $class ?? 'shadow-sm'; ?> mb-4">
    <?php if (isset($title) || isset($actions)): ?>
        <div class="card-header bg-white border-bottom py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0 font-weight-bold text-dark">
                        <?php echo $title ?? ''; ?>
                    </h5>
                    <?php if (isset($subtitle)): ?>
                        <small class="text-muted">
                            <?php echo $subtitle; ?>
                        </small>
                    <?php endif; ?>
                </div>
                <?php if (isset($actions)): ?>
                    <div class="col text-end">
                        <?php echo $actions; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    <div class="card-body <?php echo $bodyClass ?? ''; ?>">
        <?php echo $content ?? ''; ?>
    </div>
    <?php if (isset($footer)): ?>
        <div class="card-footer bg-light border-top py-3">
            <?php echo $footer; ?>
        </div>
    <?php endif; ?>
</div>