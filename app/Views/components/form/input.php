<div class="form-group mb-3">
    <?php if ($label): ?>
        <label for="<?php echo $name; ?>" class="form-label">
            <?php echo $label; ?>
            <?php if (isset($required) && $required): ?>
                <span class="text-danger">*</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>

    <div class="input-group">
        <?php if (isset($prepend)): ?>
            <span class="input-group-text"><?php echo $prepend; ?></span>
        <?php endif; ?>

        <input type="<?php echo $type; ?>" 
               class="form-control <?php echo isset($class) ? $class : ''; ?>" 
               id="<?php echo $name; ?>" 
               name="<?php echo $name; ?>" 
               value="<?php echo htmlspecialchars($value ?? ''); ?>" 
               placeholder="<?php echo $placeholder ?? ''; ?>"
               <?php echo (isset($required) && $required) ? 'required' : ''; ?>
               <?php echo (isset($readonly) && $readonly) ? 'readonly' : ''; ?>
               <?php echo (isset($disabled) && $disabled) ? 'disabled' : ''; ?>
               <?php if (isset($maxlength)) echo 'maxlength="' . $maxlength . '"'; ?>
               <?php if (isset($step)) echo 'step="' . $step . '"'; ?>
               <?php if (isset($min)) echo 'min="' . $min . '"'; ?>
               <?php if (isset($max)) echo 'max="' . $max . '"'; ?>>

        <?php if (isset($append)): ?>
            <?php echo $append; ?>
        <?php endif; ?>
    </div>
    
    <div id="erros<?php echo str_replace(' ', '', ucwords(str_replace('_', ' ', $name))); ?>" class="invalid-feedback d-block"></div>
</div>
