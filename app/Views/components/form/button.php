<button type="<?php echo $type; ?>" class="btn <?php echo $class; ?>" <?php echo isset($id) ? 'id="' . $id . '"' : ''; ?>
    <?php if (isset($disabled) && $disabled)
        echo 'disabled'; ?>>
    <?php if ($icon): ?><i class="<?php echo $icon; ?> mr-1"></i>
    <?php endif; ?>
    <?php echo $text; ?>
</button>