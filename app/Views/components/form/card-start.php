<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            <?php if ($icon): ?><i class="<?php echo $icon; ?> mr-1"></i>
            <?php endif; ?>
            <?php echo $title; ?>
        </h6>
        <?php if (isset($headerContent))
            echo $headerContent; ?>
    </div>
    <div class="card-body">