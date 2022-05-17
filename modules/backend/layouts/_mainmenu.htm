<?php
    use Backend\Models\BrandSetting;
    $activeItem = BackendMenu::getActiveMainMenuItem();
    $navbarMode = BrandSetting::get('menu_mode', BrandSetting::MENU_INLINE);
    $context = BackendMenu::getContext();
?>
<div class="main-menu-container">
    <nav class="navbar control-toolbar navbar-mode-<?= $navbarMode ?> flex" role="navigation">
        <div class="toolbar-item">
            <div data-control="toolbar" <?php if (isset($isVerticalMenu)): ?>data-vertical="true"<?php endif ?>>
                <ul class="mainmenu-items mainmenu-general" data-main-menu>
                    <?= $this->makeLayoutPartial('mainmenu_items', [
                        'context' => $context,
                        'showTooltip' => $navbarMode === BrandSetting::MENU_ICONS
                    ]) ?>
                </ul>
            </div>
        </div>
        <div class="toolbar-item fix-width">
            <ul class="mainmenu-items mainmenu-extras" data-main-menu>
                <li class="mainmenu-item mainmenu-preview">
                    <a
                        href="<?= Url::to('/') ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        <?php if (!isset($isVerticalMenu)): ?>
                            data-tooltip-text="<?= e(trans('backend::lang.tooltips.preview_website')) ?>"
                        <?php endif ?>
                    >
                        <span class="nav-icon">
                            <i class="octo-icon-location-target"></i>
                        </span>

                        <?php if (isset($isVerticalMenu)): ?>
                            <span class="nav-label">
                                <?= e(trans('backend::lang.tooltips.preview_website')) ?>
                            </span>
                        <?php endif ?>
                    </a>
                </li>
                <li class="mainmenu-item mainmenu-account has-subitems <?php if (!isset($isVerticalMenu)): ?>hidden-xs<?php endif?>" data-submenu-index="account">
                    <a href="javascript:;">
                        <span class="nav-icon">
                            <div class="mainmenu-account-avatar">
                                <img
                                    src="<?= $this->user->getAvatarThumb(120, ['mode' => 'crop', 'extension' => 'png']) ?>"
                                    loading="lazy" width="80" height="80" />
                            </div>
                        </span>
                        <?php if (isset($isVerticalMenu)): ?>
                            <span class="nav-label">
                                <?= e($this->user->full_name) ?>
                            </span>
                        <?php endif ?>
                    </a>
                </li>
                <?php if (!isset($isVerticalMenu)): ?>
                    <li class="mainmenu-item mainmenu-toggle">
                        <a href="javascript:;">☰</a>
                    </li>
                <?php endif ?>
            </ul>
        </div>
    </nav>
</div>

<?php if (!isset($isVerticalMenu)): ?>
    <?php foreach (BackendMenu::listMainMenuItemsWithSubitems() as $itemIndex => $itemInfo): ?>
        <?php if ($itemInfo->hasHttpSubItems): ?>
        <ul class="mainmenu-items mainmenu-submenu-dropdown hover-effects" data-submenu-index="<?= $itemIndex ?>">
            <?= $this->makeLayoutPartial('submenu_items', [
                'sideMenuItems' => $itemInfo->subMenuItems,
                'mainMenuItemActive' => BackendMenu::isMainMenuItemActive($itemInfo->mainMenuItem),
                'mainMenuItemCode' => $itemInfo->mainMenuItem->code,
                'hideNonHttpItems' => true,
                'context' => $context
            ]) ?>
        </ul>
        <?php endif ?>
    <?php endforeach ?>

    <ul class="mainmenu-items mainmenu-submenu-dropdown hover-effects" data-submenu-index="account">
        <li class="mainmenu-item section-title">
            <span class="nav-label"><?= e($this->user->full_name) ?></span>
        </li>

        <?= $this->makeLayoutPartial('my_settings_menu_items', ['context' => $context]) ?>
    </ul>
<?php endif ?>