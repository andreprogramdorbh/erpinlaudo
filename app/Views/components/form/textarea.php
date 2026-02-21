<div class="form-group mb-3">
    <?php if ($label): ?>
        <label for="<?php echo $name; ?>" class="form-label">
            <?php echo $label; ?>
            <?php if (isset($required) && $required): ?>
                <span class="text-danger">*</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>

    <textarea class="form-control <?php echo isset($class) ? $class : ''; ?>" id="<?php echo $name; ?>"
        name="<?php echo $name; ?>" rows="<?php echo $rows ?? 3; ?>" placeholder="<?php echo $placeholder ?? ''; ?>"
        <?php echo (isset($required) && $required) ? 'required' : ''; ?>
              <?php echo (isset($readonly) && $readonly) ? 'readonly' : ''; ?>
              <?php echo (isset($disabled) && $disabled) ? 'disabled' : ''; ?>><?php echo htmlspecialchars($value ?? ''); ?></textarea>
</div>