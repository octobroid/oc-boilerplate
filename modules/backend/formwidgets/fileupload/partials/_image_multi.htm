<div
    id="<?= $this->getId() ?>"
    class="field-fileupload size-<?= $size ?> is-image is-multi is-grid is-sortable <?= count($fileList) ? 'is-populated' : '' ?> <?= $this->previewMode ? 'is-preview' : '' ?>"
    data-control="fileupload"
    data-upload-handler="<?= $this->getEventHandler('onUpload') ?>"
    data-template="#<?= $this->getId('template') ?>"
    data-error-template="#<?= $this->getId('errorTemplate') ?>"
    data-sort-handler="<?= $this->getEventHandler('onSortAttachments') ?>"
    data-unique-id="<?= $this->getId() ?>"
    data-max-filesize="<?= $maxFilesize ?>"
    <?php if ($externalToolbarAppState): ?>data-external-toolbar-app-state="<?= e($externalToolbarAppState)?>"<?php endif ?>
    <?php if ($externalToolbarEventBus): ?>data-external-toolbar-event-bus="<?= e($externalToolbarEventBus)?>"<?php endif ?>
    <?php if ($maxFiles): ?>data-max-files="<?= $maxFiles ?>"<?php endif ?>
    <?php if ($useCaption): ?>data-config-handler="<?= $this->getEventHandler('onLoadAttachmentConfig') ?>"<?php endif ?>
    <?php if ($acceptedFileTypes): ?>data-file-types="<?= $acceptedFileTypes ?>"<?php endif ?>
    <?= $this->formField->getAttributes() ?>
>
    <div class="empty-state">
        <img src="<?= Url::asset('/modules/backend/assets/images/no-files.svg') ?>"/>
    </div>

    <div class="uploader-control-container <?= $externalToolbarAppState ? 'external-toolbar' : null ?>">
        <div class="uploader-control-toolbar">
            <a href="javascript:;" class="backend-toolbar-button control-button toolbar-upload-button">
                <i class="octo-icon-common-file-upload"></i>
                <span class="button-label"><?= e(trans('backend::lang.fileupload.upload')) ?></span>
            </a>

            <button
                class="backend-toolbar-button control-button toolbar-delete-selected populated-only"
                data-request-confirm="<?= e(trans('backend::lang.fileupload.remove_confirm')) ?>"
                data-request="<?= $this->getEventHandler('onRemoveAttachment') ?>"
                disabled
            >
                <i class="octo-icon-common-file-remove"></i>
                <span class="button-label"><?= e(trans('backend::lang.fileupload.delete_selected')) ?> <span></span></span>
            </button>
        </div>

        <!-- Existing files -->
        <div class="upload-files-container">
            <?php foreach ($fileList as $file): ?>
                <div class="server-file"
                    data-id="<?= $file->id ?>"
                    data-path="<?= $file->pathUrl ?>"
                    data-thumb="<?= $file->thumbUrl ?>"
                    data-name="<?= e($file->title ?: $file->file_name) ?>"
                    data-description="<?= e($file->description) ?>"
                    data-size="<?= e($file->file_size) ?>"
                    data-accepted="true"
                ></div>
            <?php endforeach ?>
        </div>
    </div>
</div>

<!-- Template for new files -->
<?= $this->makePartial('image_item_template', ['modeMulti' => true]) ?>
