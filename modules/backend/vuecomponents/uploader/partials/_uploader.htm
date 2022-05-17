<div
    class="component-backend-uploader"
    v-show="!hidden"
    :class="{'uploader-collapsed': collapsed}"
    data-lang-uploading="<?= e(trans('backend::lang.uploader.uploading')) ?>"
    data-lang-complete="<?= e(trans('backend::lang.uploader.complete')) ?>"
>
    <div class="uploader-header">
        <h3 v-text="titleText"></h3>

        <backend-component-loading-indicator
            indicator-style="bar"
            :progress="totalProgress"
        ></backend-component-loading-indicator>

        <button
            @click.prevent="onHeaderButtonClick"
            type="button"
            class="close backend-icon-background-pseudo"
            :class="{'collapse': !collapsed && hasFilesInProgress, 'expand': collapsed && hasFilesInProgress}"
            tabindex="0"
            ><span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="uploader-body-container">
        <div class="uploader-body">
            <backend-component-scrollable-panel
                :relative-layout="true" 
                :relative-layout-max-height="200"
            >
                <div>
                    <ul>
                        <backend-component-uploader-item
                            v-for="(file, index) in files"
                            :key="file.key"
                            :fileName="file.name"
                            :status="file.status"
                            :progress="file.progress"
                            :errorMessage="file.errorMessage"
                            @removeclick="onRemoveClick(index)"
                        ></backend-component-uploader-item>
                    </ul>
                </div>
            </backend-component-scrollable-panel>
        </div>
    </div>

</div>