<?php
    $context = System\Classes\SettingsManager::instance()->getContext();

    $collapsedGroups = explode('|',
        isset($_COOKIE['sidenav_treegroupStatus']) ? $_COOKIE['sidenav_treegroupStatus'] : null
    );

    function settingsMenuItemIsActive($item, $context)  {
        return strtolower($item->owner) == $context->owner && strtolower($item->code) == $context->itemCode;
    }

    function settingsMenuItemsIsActive($items, $context) {
        foreach ($items as $item) {
            if (settingsMenuItemIsActive($item, $context)) {
                return true;
            }
        }

        return false;
    }
?>
<ul class="top-level">
    <?php foreach ($items as $category => $items): ?>
        <?php
            $collapsed = in_array($category, $collapsedGroups);
        ?>
        <li class="<?= settingsMenuItemsIsActive($items, $context) ? 'is-active-group' : 'is-inactive-group' ?>" data-group-code="<?= e($category) ?>" <?= $collapsed ? 'data-status="collapsed"' : null ?>>
            <div class="group">
                <h3><?= e(trans($category)) ?></h3>
            </div>

            <ul <?= $collapsed ? 'style="overflow: visible; height: 0px; display: none;"' : null ?>>
                <?php foreach ($items as $item): ?>
                    <li
                        class="<?= strtolower($item->owner) == $context->owner && strtolower($item->code) == $context->itemCode ? 'active' : false ?>"
                        data-keywords="<?= e(trans($item->keywords)) ?>"
                    >
                        <a href="<?= $item->url ?>" ontouchstart="">
                            <i class="<?= $item->icon ?>"></i>
                            <span class="header"><?= e(trans($item->label)) ?></span>
                            <span class="description"><?= e(trans($item->description)) ?></span>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </li>
    <?php endforeach ?>
</ul>
