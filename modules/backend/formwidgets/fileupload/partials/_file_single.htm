<div
    id="<?= $this->getId() ?>"
    class="field-fileupload is-file is-single <?= $singleFile ? 'is-populated' : '' ?> <?= $this->previewMode ? 'is-preview' : '' ?>"
    data-control="fileupload"
    data-upload-handler="<?= $this->getEventHandler('onUpload') ?>"
    data-template="#<?= $this->getId('template') ?>"
    data-error-template="#<?= $this->getId('errorTemplate') ?>"
    data-unique-id="<?= $this->getId() ?>"
    data-max-filesize="<?= $maxFilesize ?>"
    <?php if ($externalToolbarAppState): ?>data-external-toolbar-app-state="<?= e($externalToolbarAppState)?>"<?php endif ?>
    <?php if ($externalToolbarEventBus): ?>data-external-toolbar-event-bus="<?= e($externalToolbarEventBus)?>"<?php endif ?>
    <?php if ($useCaption): ?>data-config-handler="<?= $this->getEventHandler('onLoadAttachmentConfig') ?>"<?php endif ?>
    <?php if ($acceptedFileTypes): ?>data-file-types="<?= $acceptedFileTypes ?>"<?php endif ?>
>
    <div class="empty-state">
        <img src="<?= Url::asset('/modules/backend/assets/images/no-files.svg') ?>"/>
    </div>

    <div class="uploader-control-container <?= $externalToolbarAppState ? 'external-toolbar' : null ?>">
        <div class="uploader-control-toolbar">
            <a href="javascript:;" class="backend-toolbar-button control-button toolbar-upload-button">
                <i class="octo-icon-common-file-upload"></i>
                <span
                    class="button-label"
                    data-upload-label="<?= e(trans('backend::lang.fileupload.upload')) ?>"
                    data-replace-label="<?= e(trans('backend::lang.fileupload.replace')) ?>"
                ><?= $singleFile
                    ? e(trans('backend::lang.fileupload.replace'))
                    : e(trans('backend::lang.fileupload.upload'))
                ?></span>
            </a>

            <button
                class="backend-toolbar-button control-button toolbar-clear-file populated-only"
                data-request="<?= $this->getEventHandler('onRemoveAttachment') ?>"
                data-request-confirm="<?= e(trans('backend::lang.fileupload.remove_confirm')) ?>"
            >
                <i class="octo-icon-common-file-remove"></i>
                <span class="button-label"><?= e(trans('backend::lang.fileupload.clear')) ?></span>
            </button>
        </div>

        <!-- Existing file -->
        <div class="upload-files-container">
            <?php if ($singleFile): ?>
                <div class="server-file"
                    data-id="<?= $singleFile->id ?>"
                    data-path="<?= $singleFile->pathUrl ?>"
                    data-thumb="<?= $singleFile->thumbUrl ?>"
                    data-name="<?= e($singleFile->title ?: $singleFile->file_name) ?>"
                    data-description="<?= e($singleFile->description) ?>"
                    data-size="<?= e($singleFile->file_size) ?>"
                    data-accepted="true"
                ></div>
            <?php endif ?>
        </div>
    </div>
</div>

<?= $this->makePartial('file_item_template') ?>
