<?php
    $type = $tabs->section;

    $navCss = '';
    $contentCss = '';
    $paneCss = '';

    if ($tabs->stretch) {
        $navCss = 'layout-row min-size';
        $contentCss = 'layout-row';
        $paneCss = 'layout-cell';
    }
?>
<div class="<?= $navCss ?>">
    <ul class="nav nav-tabs">
        <?php $index = 0; foreach ($tabs as $name => $fields): $index++ ?>
        <?php
            $isActive = $tabs->isPaneActive($index, $name);
            $isLazy = !$isActive && $tabs->isLazy($name);
            $tName = trans($name);
        ?>
            <li class="<?= $isActive ? 'active' : '' ?> <?= $isLazy ? 'tab-lazy' : '' ?>">
                <a
                    href="#<?= $type . 'tab-' . ($tabs->linkable ? str_slug($tName) : $index) ?>"
                    <?php if ($isLazy): ?>
                        data-tab-name="<?= e($name) ?>"
                        data-tab-section="<?= $type ?>"
                        data-tab-lazy-handler="<?= $this->getEventHandler('onLazyLoadTab') ?>"
                    <?php endif ?>
                >
                    <span class="title">
                        <span>
                            <?php if ($tabs->getIcon($name)): ?>
                                <span class="<?= $tabs->getIcon($name) ?>"></span>
                            <?php endif ?>
                            <?= e($tName) ?>
                        </span>
                    </span>
                </a>
            </li>
        <?php endforeach ?>
    </ul>
</div>

<div class="tab-content <?= $contentCss ?>">
    <?php $index = 0; foreach ($tabs as $name => $fields): $index++ ?>
        <?php
            $isActive = $tabs->isPaneActive($index, $name);
            $isLazy = !$isActive && $tabs->isLazy($name);
            $isAdaptive = $tabs->isAdaptive($name);
        ?>
            <div
                class="tab-pane <?= $isLazy ? 'is-lazy' : '' ?> <?= $isAdaptive ? 'is-adaptive' : '' ?> <?= e($tabs->getPaneCssClass($index, $name)) ?> <?= $isActive ? 'active' : '' ?> <?= $paneCss ?>"
                id="<?= $type . 'tab-' . $index ?>">
                <?php if ($isLazy): ?>
                    <?= $this->makePartial('form_fields_lazy', ['fields' => $fields]) ?>
                <?php else: ?>
                    <?= $this->makePartial('form_fields', ['fields' => $fields]) ?>
                <?php endif ?>
            </div>
    <?php endforeach ?>
</div>
