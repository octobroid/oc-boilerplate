<div class="field-repeater-toolbar">
    <?php if ($useGroups): ?>
        <a
            href="javascript:;"
            data-repeater-cmd="add-group"
            data-attach-loading>
            <i class="octo-icon-add-bold"></i>
            <?= e(trans($prompt)) ?>
        </a>
    <?php else: ?>
        <a
            href="javascript:;"
            data-repeater-cmd="add"
            data-request="<?= $this->getEventHandler('onAddItem') ?>"
            data-attach-loading>
            <i class="octo-icon-add-bold"></i>
            <?= e(trans($prompt)) ?>
        </a>
    <?php endif ?>
</div>
