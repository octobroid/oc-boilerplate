<?php
    $fieldOptions = $field->options();
    $checkedValues = (array) $field->value;
    $isScrollable = count($fieldOptions) > 10;
    $isQuickselect = $field->getConfig('quickselect', $isScrollable);
    $readOnly = $this->previewMode || $field->readOnly || $field->disabled;
?>
<!-- Checkbox List -->
<?php if ($readOnly && $field->value): ?>

    <div class="field-checkboxlist control-disabled">
        <?php $index = 0; foreach ($fieldOptions as $value => $option): ?>
            <?php
                $index++;
                $checkboxId = 'checkbox_'.$field->getId().'_'.$index;
                if (!in_array($value, $checkedValues)) continue;
                if (!is_array($option)) $option = [$option];
            ?>
            <div class="checkbox custom-checkbox">
                <input
                    type="checkbox"
                    id="<?= $checkboxId ?>"
                    name="<?= $field->getName() ?>[]"
                    value="<?= e($value) ?>"
                    disabled="disabled"
                    checked="checked" />

                <label class="storm-icon-pseudo" for="<?= $checkboxId ?>">
                    <?= e(trans($option[0])) ?>
                </label>
                <?php if (isset($option[1])): ?>
                    <p class="help-block"><?= e(trans($option[1])) ?></p>
                <?php endif ?>
            </div>
        <?php endforeach ?>
    </div>

<?php elseif (!$readOnly && count($fieldOptions)): ?>

    <div class="field-checkboxlist <?= $isScrollable ? 'is-scrollable' : '' ?>" <?= $field->getAttributes() ?>>
        <?php if ($isQuickselect): ?>
            <!-- Quick selection -->
            <div class="checkboxlist-controls">
                <a href="javascript:;" class="backend-toolbar-button control-button" data-field-checkboxlist-all>
                    <i class="octo-icon-check-multi"></i>
                    <span class="button-label"><?= e(trans('backend::lang.form.select_all')) ?></span>
                </a>

                <a href="javascript:;" class="backend-toolbar-button control-button" data-field-checkboxlist-none>
                    <i class="octo-icon-eraser"></i>
                    <span class="button-label"><?= e(trans('backend::lang.form.select_none')) ?></span>
                </a>
            </div>
        <?php endif ?>

        <div class="field-checkboxlist-inner">

            <?php if ($isScrollable): ?>
                <!-- Scrollable Checkbox list -->
                <div class="field-checkboxlist-scrollable">
                    <div class="control-scrollbar" data-control="scrollbar">
            <?php endif ?>

            <input
                type="hidden"
                name="<?= $field->getName() ?>"
                value="" />

            <?php $index = 0; foreach ($fieldOptions as $value => $option): ?>
                <?php
                    $index++;
                    $checkboxId = 'checkbox_'.$field->getId().'_'.$index;
                    if (!is_array($option)) $option = [$option];
                ?>
                <div class="checkbox custom-checkbox">
                    <input
                        type="checkbox"
                        id="<?= $checkboxId ?>"
                        name="<?= $field->getName() ?>[]"
                        value="<?= e($value) ?>"
                        <?= in_array($value, $checkedValues) ? 'checked="checked"' : '' ?>>

                    <label class="storm-icon-pseudo" for="<?= $checkboxId ?>">
                        <?= e(trans($option[0])) ?>
                    </label>
                    <?php if (isset($option[1]) && strlen($option[1])): ?>
                        <p class="help-block"><?= e(trans($option[1])) ?></p>
                    <?php endif ?>
                </div>
            <?php endforeach ?>

            <?php if ($isScrollable): ?>
                    </div>
                </div>
            <?php endif ?>

        </div>

    </div>

<?php else: ?>

    <!-- No options specified -->
    <?php if ($field->placeholder): ?>
        <p><?= e(trans($field->placeholder)) ?></p>
    <?php endif ?>

<?php endif ?>
