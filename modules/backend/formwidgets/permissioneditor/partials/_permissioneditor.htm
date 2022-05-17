<div class="permissioneditor <?= $this->previewMode ? 'control-disabled' : '' ?>" <?= $field->getAttributes() ?>>
    <table>
        <?php
            $globalIndex = 0;
            $checkboxMode = !($this->mode === 'radio');
        ?>
        <?php foreach ($permissions as $tab => $tabPermissions): ?>
            <tr class="section">
                <th class="tab" colspan="100">
                    <div class="tab-inner">
                        <div class="tab-title">
                            <?= e(trans($tab)) ?>
                        </div>

                        <div class="tab-controls">
                            <?php if ($this->mode === 'radio'): ?>
                                <a href="javascript:;" class="backend-toolbar-button control-button" data-field-permission-toggle>
                                    <i class="octo-icon-check-multi"></i>
                                    <span class="button-label"><?= e(trans('backend::lang.form.select_all')) ?></span>
                                </a>
                            <?php else: ?>
                                <a href="javascript:;" class="backend-toolbar-button control-button" data-field-permission-all>
                                    <i class="octo-icon-check-multi"></i>
                                    <span class="button-label"><?= e(trans('backend::lang.form.select_all')) ?></span>
                                </a>

                                <a href="javascript:;" class="backend-toolbar-button control-button" style="display: none" data-field-permission-none>
                                    <i class="octo-icon-eraser"></i>
                                    <span class="button-label"><?= e(trans('backend::lang.form.select_none')) ?></span>
                                </a>
                            <?php endif ?>
                        </div>
                    </div>
                </th>
            </tr>

            <?php
                $lastIndex = count($tabPermissions) - 1;
            ?>
            <?php foreach ($tabPermissions as $index => $permission): ?>
                <?php
                    $globalIndex++;

                    switch ($this->mode) {
                        case 'radio':
                            $permissionValue = array_key_exists($permission->code, $permissionsData)
                                ? $permissionsData[$permission->code]
                                : 0;
                            break;
                        case 'switch':
                            $isChecked = !((int) @$permissionsData[$permission->code] === -1);
                            break;
                        case 'checkbox':
                        default:
                            $isChecked = array_key_exists($permission->code, $permissionsData);
                            break;
                    }

                    $allowId = $this->getId('permission-' . $globalIndex . '-allow');
                    $inheritId = $this->getId('permission-' . $globalIndex . '-inherit');
                    $denyId = $this->getId('permission-' . $globalIndex . '-deny');
                ?>
                <tr class="<?= $lastIndex == $index ? 'last-section-row' : '' ?>
                    <?= $checkboxMode ? 'mode-checkbox' : 'mode-radio' ?>
                    <?= $checkboxMode && !$isChecked ? 'disabled' : '' ?>
                    <?= !$checkboxMode && $permissionValue == -1 ? 'disabled' : '' ?>
                ">

                    <?php if ($this->mode === 'radio'): ?>
                        <td class="permission-value">
                            <div class="radio custom-radio">
                                 <input
                                    id="<?= $allowId ?>"
                                    name="<?= e($baseFieldName) ?>[<?= e($permission->code) ?>]"
                                    value="1"
                                    type="radio"
                                    <?= $permissionValue == 1 ? 'checked="checked"' : '' ?>
                                    data-radio-color="green"
                                >
                                <label title="<?= e(trans('backend::lang.user.allow')) ?>" class="storm-icon-pseudo" for="<?= $allowId ?>"><span><?= e(trans('backend::lang.user.allow')) ?></span></label>
                            </div>
                        </td>
                        <td class="permission-value">
                            <div class="radio custom-radio">
                                 <input
                                    id="<?= $inheritId ?>"
                                    name="<?= e($baseFieldName) ?>[<?= e($permission->code) ?>]"
                                    value="0"
                                    <?= $permissionValue == 0 ? 'checked="checked"' : '' ?>
                                    type="radio"
                                >
                                <label title="<?= e(trans('backend::lang.user.inherit')) ?>" class="storm-icon-pseudo" for="<?= $inheritId ?>"><span><?= e(trans('backend::lang.user.inherit')) ?></span></label>
                            </div>
                        </td>
                        <td class="permission-value">
                            <div class="radio custom-radio">
                                 <input
                                    id="<?= $denyId ?>"
                                    name="<?= e($baseFieldName) ?>[<?= e($permission->code) ?>]"
                                    value="-1"
                                    <?= $permissionValue == -1 ? 'checked="checked"' : '' ?>
                                    type="radio"
                                    data-radio-color="red"
                                >
                                <label title="<?= e(trans('backend::lang.user.deny')) ?>" class="storm-icon-pseudo" for="<?= $denyId ?>"><span><?= e(trans('backend::lang.user.deny')) ?></span></label>
                            </div>
                        </td>
                    <?php elseif ($this->mode === 'switch'): ?>
                        <td class="permission-value">
                            <input
                                type="hidden"
                                name="<?= e($baseFieldName) ?>[<?= e($permission->code) ?>]"
                                value="-1"
                            >

                            <label class="custom-switch">
                                <input
                                    id="<?= $allowId ?>"
                                    name="<?= e($baseFieldName) ?>[<?= e($permission->code) ?>]"
                                    value="1"
                                    type="checkbox"
                                    <?= $isChecked ? 'checked="checked"' : '' ?>
                                >
                                <span><span><?= e(trans('backend::lang.list.column_switch_true')) ?></span><span><?= e(trans('backend::lang.list.column_switch_false')) ?></span></span>
                                <a class="slide-button"></a>
                            </label>
                        </td>
                    <?php else: ?>
                        <td class="permission-value">
                            <div class="checkbox custom-checkbox">
                                 <input
                                    id="<?= $allowId ?>"
                                    name="<?= e($baseFieldName) ?>[<?= e($permission->code) ?>]"
                                    value="1"
                                    type="checkbox"
                                    <?= $isChecked ? 'checked="checked"' : '' ?>
                                >

                                <label title="<?= e(trans('backend::lang.user.allow')) ?>" class="storm-icon-pseudo" for="<?= $allowId ?>"><span><?= e(trans('backend::lang.user.allow')) ?></span></label>
                            </div>
                        </td>
                    <?php endif ?>

                    <td class="permission-name">
                        <?= e(trans($permission->label)) ?>
                        <p class="comment"><?= e(trans($permission->comment)) ?></p>
                    </td>
                </tr>
            <?php endforeach ?>
        <?php endforeach ?>
    </table>
    <div class="permissions-overlay"></div>
</div>
