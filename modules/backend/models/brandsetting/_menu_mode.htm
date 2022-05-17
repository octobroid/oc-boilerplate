<?php
    use Backend\Models\BrandSetting;
?>
<div class="control-simplelist menu-mode-selector is-selectable-box is-flush">
    <ul>
        <li class="<?= $field->value === BrandSetting::MENU_INLINE ? 'active' : ''?>" data-menu-mode="<?= BrandSetting::MENU_INLINE ?>">
            <a href="javascript:;">
                <div class="menu-mode-box menu-mode-box-inline">
                    <span class="mode-image"></span>
                    <h5 class="heading"><?= e(trans('backend::lang.branding.menu_mode_inline')) ?></h5>
                </div>
            </a>
        </li>
        <li class="<?= $field->value === BrandSetting::MENU_TEXT ? 'active' : ''?>" data-menu-mode="<?= BrandSetting::MENU_TEXT ?>">
            <a href="javascript:;">
                <div class="menu-mode-box menu-mode-box-text">
                    <span class="mode-image"></span>
                    <h5 class="heading"><?= e(trans('backend::lang.branding.menu_mode_text')) ?></h5>
                </div>
            </a>
        </li>
        <li class="<?= $field->value === BrandSetting::MENU_TILE ? 'active' : ''?>" data-menu-mode="<?= BrandSetting::MENU_TILE ?>">
            <a href="javascript:;">
                <div class="menu-mode-box menu-mode-box-tiles">
                    <span class="mode-image"></span>
                    <h5 class="heading"><?= e(trans('backend::lang.branding.menu_mode_tile')) ?></h5>
                </div>
            </a>
        </li>
        <li class="<?= $field->value === BrandSetting::MENU_COLLAPSE ? 'active' : ''?>" data-menu-mode="<?= BrandSetting::MENU_COLLAPSE ?>">
            <a href="javascript:;">
                <div class="menu-mode-box menu-mode-box-collapsed">
                    <span class="mode-image"></span>
                    <h5 class="heading"><?= e(trans('backend::lang.branding.menu_mode_collapsed')) ?></h5>
                </div>
            </a>
        </li>
        <li class="<?= $field->value === BrandSetting::MENU_ICONS ? 'active' : ''?>" data-menu-mode="<?= BrandSetting::MENU_ICONS ?>">
            <a href="javascript:;">
                <div class="menu-mode-box menu-mode-box-icons">
                    <span class="mode-image"></span>
                    <h5 class="heading"><?= e(trans('backend::lang.branding.menu_mode_icon')) ?></h5>
                </div>
            </a>
        </li>
        <li class="<?= $field->value === BrandSetting::MENU_LEFT ? 'active' : ''?>" data-menu-mode="<?= BrandSetting::MENU_LEFT ?>">
            <a href="javascript:;">
                <div class="menu-mode-box menu-mode-box-left">
                    <span class="mode-image"></span>
                    <h5 class="heading"><?= e(trans('backend::lang.branding.menu_mode_left')) ?></h5>
                </div>
            </a>
        </li>
    </ul>
</div>

<input
    type="hidden"
    name="<?= $field->getName() ?>"
    value="<?= e($field->value) ?>"
    id="<?= $field->getId() ?>"
/>

<script>
    $(document).on('click', '[data-menu-mode]', function() {
        backendBrandSettingSetMenuMode($(this).data('menu-mode'))
    })

    function backendBrandSettingSetMenuMode(mode) {
        $('[data-menu-mode]').removeClass('active')
        $('[data-menu-mode="'+mode+'"]').addClass('active')
        $('#<?= $field->getId() ?>').val(mode)

        $('#layout-mainmenu .main-menu-container > nav.navbar')
            .removeClass('navbar-mode-icons navbar-mode-inline navbar-mode-text navbar-mode-tile navbar-mode-collapse')
            .addClass('navbar-mode-' + mode)

        $(document.body).toggleClass('main-menu-left', mode === 'left')
    }
</script>
