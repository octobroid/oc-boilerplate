<div
    id="<?= $this->getId() ?>"
    class="field-mediafinder size-<?= $size ?> is-file is-multi is-sortable <?= $fileList->count() ? 'is-populated' : '' ?> <?= $this->previewMode ? 'is-preview' : '' ?>"
    data-control="mediafinder"
    data-template="#<?= $this->getId('template') ?>"
    data-input-name="<?= $field->getName() ?>"
    <?php if ($maxItems): ?>data-max-items="<?= $maxItems ?>"<?php endif ?>
    <?php if ($externalToolbarAppState): ?>data-external-toolbar-app-state="<?= e($externalToolbarAppState)?>"<?php endif ?>
    <?php if ($externalToolbarEventBus): ?>data-external-toolbar-event-bus="<?= e($externalToolbarEventBus)?>"<?php endif ?>
    <?= $field->getAttributes() ?>
>
    <div class="empty-state">
        <img src="<?= Url::asset('/modules/backend/assets/images/no-files.svg') ?>"/>
    </div>

    <div class="mediafinder-control-container <?= $externalToolbarAppState ? 'external-toolbar' : null ?>">
        <div class="mediafinder-control-toolbar">
            <a href="javascript:;" class="backend-toolbar-button control-button toolbar-find-button">
                <i class="octo-icon-common-file-star"></i>
                <span class="button-label"><?= e(trans('backend::lang.mediafinder.select')) ?></span>
            </a>

            <button
                class="backend-toolbar-button control-button toolbar-delete-selected populated-only"
                disabled
            >
                <i class="octo-icon-common-file-remove"></i>
                <span class="button-label"><?= e(trans('backend::lang.fileupload.delete_selected')) ?> <span></span></span>
            </button>
        </div>

        <!-- Existing file -->
        <div class="mediafinder-files-container">
            <?php foreach ($fileList as $file): ?>
                <div class="server-file"
                    data-public-url="<?= e($file->publicUrl ?? '') ?>"
                    data-path="<?= e($file->spawnPath ?? '') ?>"
                    data-title="<?= e($file->title ?? '') ?>"
                ></div>
            <?php endforeach ?>
        </div>
    </div>

    <!-- Data locker -->
    <div id="<?= $field->getId() ?>" data-data-locker>
        <?php foreach ($fileList as $file): ?>
            <input
                type="hidden"
                name="<?= $field->getName() ?>[]"
                value="<?= e($file->spawnPath) ?>"
                />
        <?php endforeach ?>
    </div>
</div>

<?= $this->makePartial('file_item_template', ['modeMulti' => true]) ?>
