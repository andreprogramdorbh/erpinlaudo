<div class="card card-stats">
    <div class="card-body">
        <div class="row">
            <div class="col">
                <h5 class="card-title text-uppercase text-muted mb-0">
                    <?php echo $title; ?>
                </h5>
                <span class="h2 font-weight-bold mb-0">
                    <?php echo $value; ?>
                </span>
            </div>
            <div class="col-auto">
                <div class="icon icon-shape bg-gradient-<?php echo $gradient; ?> text-white rounded-circle shadow">
                    <i class="<?php echo $icon; ?>"></i>
                </div>
            </div>
        </div>
        <?php if ($footer): ?>
            <p class="mt-3 mb-0 text-sm">
                <?php echo $footer; ?>
            </p>
        <?php endif; ?>
    </div>
</div>