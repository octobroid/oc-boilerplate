<?php if (!$this->fatalError): ?>

    <div id="executePopup">

        <div id="executeActivity">
            <div class="modal-body modal-no-header">
                <div class="progress bar-loading-indicator" id="executeLoadingBar">
                    <div class="progress-bar"></div>
                </div>

                <div class="loading-indicator-container">
                    <p>&nbsp;</p>
                    <div class="loading-indicator is-transparent">
                        <div id="executeMessage" class="text-ellipsis" style="width:85%"></div>
                        <span></span>
                    </div>
                </div>
                <p>&nbsp;</p>
            </div>
        </div>

        <div id="executeStatus"></div>

        <div class="control-executeoutput" id="executeOutput">
            <div class="control-scrollbar" style="height:300px" data-control="scrollbar">
                <div data-output-items>
                    <div class="update-item">
                        <dl>
                            <dt><!-- Line number --></dt>
                            <dd><!-- Message --></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <iframe id="executeFrame" src="about:blank" style="display: none"></iframe>
    </div>

    <script type="text/template" id="executeFailed">
        <div class="modal-body modal-no-header">
            <div class="callout callout-danger no-icon">
                <div class="header">
                    <h3><?= e(trans('system::lang.updates.update_failed_label')) ?></h3>
                    <p>{{ reason }}</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button
                type="button"
                class="btn btn-primary"
                onclick="$.oc.updater.retryUpdate()">
                <?= e(trans('system::lang.updates.retry_label')) ?>
            </button>
            <button
                type="button"
                class="btn btn-default"
                data-dismiss="popup">
                <?= e(trans('backend::lang.form.cancel')) ?>
            </button>
        </div>
    </script>

    <script>
        $('#executePopup').on('popupComplete', function() {
            $.oc.updater.execute(
                <?= json_encode($updateSteps) ?>,
                '<?= $this->composerActionUrl() ?>'
            );
        });
    </script>

<?php else: ?>

    <div class="modal-body modal-no-header">
        <p class="flash-message static error"><?= e(trans($this->fatalError)) ?></p>
    </div>
    <div class="modal-footer">
        <button
            type="button"
            class="btn btn-default"
            data-dismiss="popup">
            <?= e(trans('backend::lang.form.close')) ?>
        </button>
    </div>

<?php endif ?>
