<div
    data-default-button-text="<?= e(trans('backend::lang.form.ok')) ?>"
>
    <backend-component-modal
        ref="modal"
        :aria-labeled-by="modalTitleId"
        :unique-key="uniqueId"
        :size="size"
        :store-position="true"
        :resizable="resizableWidth ? 'horizontal' : false"
        :resize-default-width="600"
        :close-by-esc="!readOnly"
        :modal-temporary-hidden="layoutUpdateData.modalTemporaryHidden"
        @hidden="onHidden"
        @resized="onResized"
        @shown="onShown"
        @enterkey="onEnterKey"
    >
        <template v-slot:content>
            <div class="modal-header">
                <button
                    @click.prevent="onCloseClick"
                    type="button"
                    class="close backend-icon-background-pseudo"
                    v-bind:disabled="readOnly"
                    aria-label="<?= e(trans('backend::lang.form.close')) ?>"
                    tabindex="0"
                    ><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" v-bind:id="modalTitleId" v-text="title"></h4>
            </div>
            <div class="modal-body inspector-modal-host">
                <backend-component-inspector 
                    :data-schema="dataSchema"
                    :data="data"
                    :live-mode="false"
                    :unique-id="uniqueId"
                    :layout-update-data="layoutUpdateData"
                    :read-only="readOnly"
                    ref="inspector"
                >
                </backend-component-inspector>
            </div>
            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-primary btn-default-action"
                    data-default-focus
                    @click="onApplyClick"
                    v-text="primaryButtonText"
                    v-bind:disabled="readOnly"
                ></button>
                <span class="button-separator"><?= e(trans('backend::lang.form.or')) ?></span>
                <button
                    class="btn btn-link text-muted"
                    :class="{disabled: readOnly}"
                    @click.prevent="onCloseClick"
                ><?= e(trans('backend::lang.form.cancel')) ?></button>
            </div>
        </template>
    </backend-component-modal>
</div>