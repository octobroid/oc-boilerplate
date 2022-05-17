<?php if ($this->previewMode && !$fileList): ?>

    <span class="form-control" disabled="disabled"><?= e(trans('backend::lang.form.preview_no_media_message')) ?></span>

<?php else: ?>

    <?php switch ($displayMode):

        case 'image-single': ?>
            <?= $this->makePartial('image_single') ?>
        <?php break ?>

        <?php case 'image-multi': ?>
            <?= $this->makePartial('image_multi') ?>
        <?php break ?>

        <?php case 'file-single': ?>
            <?= $this->makePartial('file_single') ?>
        <?php break ?>

        <?php case 'file-multi': ?>
            <?= $this->makePartial('file_multi') ?>
        <?php break ?>

    <?php endswitch ?>

<?php endif ?>
