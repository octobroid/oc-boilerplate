<?php
    $useRowLink = $this->hasRecordAction();

    $classes = [
        'list-scrollable',
    ];

    if ($useRowLink) $classes[] = 'list-rowlink';
    if (!$showPagination) $classes[] = 'no-pagination';
?>
<div
    class="control-list <?= implode(' ', $classes) ?>"
    data-control="listwidget">
    <table class="table data" <?= $useRowLink ? 'data-control="rowlink"' : '' ?>>
        <thead>
            <?= $this->makePartial('list_head_row') ?>
        </thead>
        <tbody>
            <?php if (count($records)): ?>
                <?= $this->makePartial('list_body_rows') ?>
            <?php else: ?>
                <tr class="no-data">
                    <td colspan="<?= $columnTotal ?>" class="nolink">
                        <p class="no-data"><?= $noRecordsMessage ?></p>
                    </td>
                </tr>
            <?php endif ?>
        </tbody>
    </table>
    <?php if ($showPagination): ?>
        <div class="list-footer">
            <div class="list-pagination">
                <?php if ($showPageNumbers): ?>
                    <?= $this->makePartial('list_pagination') ?>
                <?php else: ?>
                    <?= $this->makePartial('list_pagination_simple') ?>
                <?php endif ?>
            </div>
        </div>
    <?php endif ?>

    <?= $this->makePartial('list_datalocker') ?>
</div>
