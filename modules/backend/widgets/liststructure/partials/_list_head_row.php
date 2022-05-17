<tr>
    <?php if ($showCheckboxes): ?>
        <th class="list-checkbox">
            <div class="checkbox custom-checkbox nolabel">
                <input type="checkbox" id="<?= $this->getId('checkboxAll') ?>" />
                <label class="storm-icon-pseudo" for="<?= $this->getId('checkboxAll') ?>"></label>
            </div>
        </th>
    <?php endif ?>
    <?php if ($showReorder): ?>
        <th class="list-reorder"></th>
    <?php endif ?>
    <?php $index = 0; foreach ($columns as $key => $column): ?>
        <?php
            $index++;
            $styles = [];
            if ($column->width) {
                $styles[] = 'width: '.$column->width;
            }

            $classes = [
                'list-cell-name-'.$column->getName(),
                'list-cell-type-'.$column->type,
                $column->getAlignClass(),
                $column->headCssClass
            ];

            if ($index === 1) {
                $styles[] = 'padding-left: '.$this->getIndentStartSize(0).'px';
                $classes[] = 'explicit-left-padding';
            }

        ?>
        <?php if ($showSorting && $column->sortable): ?>
            <?php
                if ($this->sortColumn == $column->columnName) {
                    $classes[] = 'sort-'.$this->sortDirection.' active';
                }
                else {
                    $classes[] = 'sort-desc';
                }
            ?>
            <th style="<?= implode(';', $styles) ?>" class="<?= implode(' ', $classes) ?>">
                <a
                    href="javascript:;"
                    data-request="<?= $this->getEventHandler('onSort') ?>"
                    data-stripe-load-indicator
                    data-request-data="sortColumn: '<?= $column->columnName ?>', page: <?= $pageCurrent ?>">
                    <?= $this->getHeaderValue($column) ?>
                </a>
            </th>
        <?php else: ?>
            <th style="<?= implode(';', $styles) ?>" class="<?= implode(' ', $classes) ?>">
                <span><?= $this->getHeaderValue($column) ?></span>
            </th>
        <?php endif ?>
    <?php endforeach ?>

    <?php if ($showSetup): ?>
        <th class="list-setup">
            <a href="javascript:;"
                title="<?= e(trans('backend::lang.list.setup_title')) ?>"
                data-control="popup"
                data-handler="<?= $this->getEventHandler('onLoadSetup') ?>"><span></span></a>
        </th>
    <?php endif ?>
</tr>
