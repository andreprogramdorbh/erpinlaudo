<div class="form-group mb-3">
    <?php if ($label): ?>
        <label for="<?php echo $name; ?>" class="form-label">
            <?php echo $label; ?>
            <?php if (isset($required) && $required): ?>
                <span class="text-danger">*</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>

    <select class="form-control <?php echo isset($class) ? $class : ''; ?>" id="<?php echo $name; ?>"
        name="<?php echo $name; ?>" <?php echo (isset($required) && $required) ? 'required' : ''; ?>
        <?php echo (isset($disabled) && $disabled) ? 'disabled' : ''; ?>>

        <?php if (isset($placeholder)): ?>
            <option value="">
                <?php echo $placeholder; ?>
            </option>
        <?php endif; ?>

        <?php foreach ($options as $key => $labelOption): ?>
            <?php
            $val = is_numeric($key) && !isset($options[$key]) ? $labelOption : $key;
            $selected = ($value == $val) ? 'selected' : '';
            ?>
            <option value="<?php echo $val; ?>" <?php echo $selected; ?>>
                <?php echo $labelOption; ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>