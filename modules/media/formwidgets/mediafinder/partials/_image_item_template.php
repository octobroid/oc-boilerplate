<!-- Template for new file -->
<script type="text/template" id="<?= $this->getId('template') ?>">
    <div class="item-object item-object-image <?= isset($modeMulti) ? 'mode-multi' : '' ?>">
        <?php if (isset($modeMulti)): ?>
            <div class="custom-checkbox-v2">
                <label>
                    <input
                        data-record-selector
                        type="checkbox"
                        value=""
                    />
                    <span class="storm-icon-pseudo"></span>
                </label>
            </div>

            <a href="javascript:;" class="drag-handle"><i class="octo-icon-list-reorder"></i></a>
        <?php endif ?>

        <div class="file-data-container">
            <div class="file-data-container-inner">
                <div class="icon-container image">
                    <img data-public-url alt="" />
                </div>
                <div class="info">
                    <h4 class="filename">
                        <span data-title></span>
                    </h4>
                </div>
            </div>
        </div>
    </div>
</script>
