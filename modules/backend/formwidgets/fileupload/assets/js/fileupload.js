/*
 * File upload form field control
 *
 * Data attributes:
 * - data-control="fileupload" - enables the file upload plugin
 * - data-unique-id="XXX" - an optional identifier for multiple uploaders on the same page, this value will
 *   appear in the postback variable called X_OCTOBER_FILEUPLOAD
 * - data-template - a Dropzone.js template to use for each item
 * - data-error-template - a popover template used to show an error
 * - data-sort-handler - AJAX handler for sorting postbacks
 * - data-config-handler - AJAX handler for configuration popup
 *
 * JavaScript API:
 * $('div').fileUploader()
 *
 * Dependancies:
 * - Dropzone.js
 */
+function ($) { "use strict";

    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype;

    // FILEUPLOAD CLASS DEFINITION
    // ============================

    var FileUpload = function (element, options) {
        this.$el = $(element);
        this.options = options || {};

        $.oc.foundation.controlUtils.markDisposable(element);
        Base.call(this);
        this.init();
    }

    FileUpload.prototype = Object.create(BaseProto);
    FileUpload.prototype.constructor = FileUpload;

    FileUpload.prototype.init = function() {
        if (this.options.isMulti === null) {
            this.options.isMulti = this.$el.hasClass('is-multi');
        }

        if (this.options.isPreview === null) {
            this.options.isPreview = this.$el.hasClass('is-preview');
        }

        if (this.options.isSortable === null) {
            this.options.isSortable = this.$el.hasClass('is-sortable');
        }

        this.$el.one('dispose-control', this.proxy(this.dispose));
        this.$uploadButton = $('.toolbar-upload-button', this.$el);
        this.$filesContainer = $('.upload-files-container', this.$el);
        this.uploaderOptions = {};

        this.$el.on('click', '.upload-object.is-success .file-data-container-inner', this.proxy(this.onClickSuccessObject));
        this.$el.on('click', '.upload-object.is-error', this.proxy(this.onClickErrorObject));
        this.$el.on('click', '.toolbar-clear-file', this.proxy(this.onClearFileClick));
        this.$el.on('click', '.toolbar-delete-selected', this.proxy(this.onDeleteSelectedClick));

        this.$el.on('change', 'input[data-record-selector]', this.proxy(this.onSelectionChanged));

        this.initToolbarExtensionPoint();
        this.initExternalToolbarEventBus();
        this.mountExternalToolbarEventBusEvents();

        this.bindUploader();

        if (this.options.isSortable) {
            this.bindSortable();
        }

        this.extendExternalToolbar();
    }

    FileUpload.prototype.dispose = function() {
        this.$el.off('click', '.upload-object.is-success .file-data-container-inner', this.proxy(this.onClickSuccessObject));
        this.$el.off('click', '.upload-object.is-error', this.proxy(this.onClickErrorObject));
        this.$el.off('click', '.toolbar-clear-file', this.proxy(this.onClearFileClick));
        this.$el.off('click', '.toolbar-delete-selected', this.proxy(this.onDeleteSelectedClick));

        this.$el.off('change', 'input[data-record-selector]', this.proxy(this.onSelectionChanged));

        this.$el.off('dispose-control', this.proxy(this.dispose));
        this.$el.removeData('oc.fileUpload');
        this.unmountExternalToolbarEventBusEvents();

        this.sortable = null;
        this.dropzone = null;

        this.$el = null;
        this.$uploadButton = null;
        this.$filesContainer = null;
        this.uploaderOptions = null;
        this.toolbarExtensionPoint = null;
        this.externalToolbarEventBusObj = null;

        // In some cases options could contain callbacks,
        // so it's better to clean them up too.
        this.options = null;

        BaseProto.dispose.call(this);
    }

    //
    // External toolbar
    //

    FileUpload.prototype.initToolbarExtensionPoint = function () {
        if (!this.options.externalToolbarAppState) {
            return;
        }

        // Expected format: tailor.app::toolbarExtensionPoint
        const parts = this.options.externalToolbarAppState.split('::');
        if (parts.length !== 2) {
            throw new Error('Invalid externalToolbarAppState format. Expected format: module.name::stateElementName');
        }

        const app = $.oc.module.import(parts[0]);
        this.toolbarExtensionPoint = app.state[parts[1]];
    }

    FileUpload.prototype.initExternalToolbarEventBus = function() {
        if (!this.options.externalToolbarEventBus) {
            return;
        }

        // Expected format: tailor.app::eventBus
        const parts = this.options.externalToolbarEventBus.split('::');
        if (parts.length !== 2) {
            throw new Error('Invalid externalToolbarEventBus format. Expected format: module.name::stateElementName');
        }

        const module = $.oc.module.import(parts[0]);
        this.externalToolbarEventBusObj = module.state[parts[1]];
    }

    FileUpload.prototype.mountExternalToolbarEventBusEvents = function() {
        if (!this.externalToolbarEventBusObj) {
            return;
        }

        this.externalToolbarEventBusObj.$on('toolbarcmd', this.proxy(this.onToolbarExternalCommand));
        this.externalToolbarEventBusObj.$on('extendapptoolbar', this.proxy(this.extendExternalToolbar));
    }

    FileUpload.prototype.unmountExternalToolbarEventBusEvents = function() {
        if (!this.externalToolbarEventBusObj) {
            return;
        }

        this.externalToolbarEventBusObj.$off('toolbarcmd', this.proxy(this.onToolbarExternalCommand));
        this.externalToolbarEventBusObj.$off('extendapptoolbar', this.proxy(this.extendExternalToolbar));
    }

    FileUpload.prototype.onToolbarExternalCommand = function (ev) {
        var cmdPrefix = 'fileupload-toolbar-';

        if (ev.command.substring(0, cmdPrefix.length) != cmdPrefix) {
            return;
        }

        var buttonClassName = ev.command.substring(cmdPrefix.length),
            $toolbar = this.$el.find('.uploader-control-toolbar'),
            $button = $toolbar.find('[class="'+buttonClassName+'"]');

        $button.get(0).click(ev.ev);
    }

    FileUpload.prototype.extendExternalToolbar = function () {
        if (!this.$el.is(":visible") || !this.toolbarExtensionPoint) {
            return;
        }

        this.toolbarExtensionPoint.splice(0, this.toolbarExtensionPoint.length);

        this.toolbarExtensionPoint.push({
            type: 'separator'
        });

        var that = this,
            $buttons = this.$el.find('.uploader-control-toolbar .backend-toolbar-button');

        $buttons.each(function () {
            var $button = $(this),
                $icon = $button.find('i[class^=octo-icon]');

            that.toolbarExtensionPoint.push(
                {
                    type: 'button',
                    icon: $icon.attr('class'),
                    label: $button.find('.button-label').text(),
                    command: 'fileupload-toolbar-' + $button.attr('class'),
                    disabled: $button.attr('disabled') !== undefined
                }
            );
        });
    }

    //
    // Uploading
    //

    FileUpload.prototype.bindUploader = function() {
        this.uploaderOptions = {
            url: this.options.url,
            paramName: this.options.paramName,
            clickable: this.$uploadButton.get(0),
            previewsContainer: this.$filesContainer.get(0),
            maxFilesize: this.options.maxFilesize,
            headers: {},
            timeout: 0
        }

        if (!this.options.isMulti) {
            this.uploaderOptions.maxFiles = 1;
        }
        else {
            this.uploaderOptions.maxFiles = this.options.maxFiles;
        }

        if (this.options.fileTypes) {
            this.uploaderOptions.acceptedFiles = this.options.fileTypes;
        }

        if (this.options.template) {
            this.uploaderOptions.previewTemplate = $(this.options.template).html();
        }

        this.uploaderOptions.thumbnailWidth = this.options.thumbnailWidth
            ? this.options.thumbnailWidth : null;

        this.uploaderOptions.thumbnailHeight = this.options.thumbnailHeight
            ? this.options.thumbnailHeight : null;

        this.uploaderOptions.resize = this.onResizeFileInfo;

        /*
         * Locale
         */
        this.uploaderOptions.dictMaxFilesExceeded = $.oc.lang.get('upload.max_files');
        this.uploaderOptions.dictInvalidFileType = $.oc.lang.get('upload.invalid_file_type');
        this.uploaderOptions.dictFileTooBig = $.oc.lang.get('upload.file_too_big');
        this.uploaderOptions.dictResponseError = $.oc.lang.get('upload.response_error');
        this.uploaderOptions.dictRemoveFile = $.oc.lang.get('upload.remove_file');

        /*
         * Add CSRF token to headers
         */
        var token = $('meta[name="csrf-token"]').attr('content');
        if (token) {
            this.uploaderOptions.headers['X-CSRF-TOKEN'] = token;
        }

        this.dropzone = new Dropzone(this.$el.get(0), this.uploaderOptions);

        this.dropzone.on('addedfile', this.proxy(this.onUploadAddedFile));
        this.dropzone.on('sending', this.proxy(this.onUploadSending));
        this.dropzone.on('success', this.proxy(this.onUploadSuccess));
        this.dropzone.on('error', this.proxy(this.onUploadError));

        this.dropzone.on('dragenter', this.proxy(this.onDragEnter));
        this.dropzone.on('dragover', this.proxy(this.onDragEnter));
        this.dropzone.on('dragleave', this.proxy(this.onDragEnd));
        this.dropzone.on('dragend', this.proxy(this.onDragEnd));
        this.dropzone.on('drop', this.proxy(this.onDragEnd));

        // this.dropzone.on('maxfilesreached', this.proxy(this.removeEventListeners));
        // this.dropzone.on('removedfile', this.proxy(this.setupEventListeners));

        this.loadExistingFiles();
    }

    // FileUpload.prototype.removeEventListeners = function () {
    //     this.dropzone.removeEventListeners();
    // }

    // FileUpload.prototype.setupEventListeners = function () {
    //     if (this.dropzone.files.length < this.dropzone.options.maxFiles) {
    //         this.dropzone.setupEventListeners();
    //     }
    // }

    FileUpload.prototype.loadExistingFiles = function () {
        var self = this;

        $('.server-file', this.$el).each(function () {
            var file = $(this).data();

            self.dropzone.files.push(file);
            self.dropzone.emit('addedfile', file);
            self.dropzone.emit('success', file, file);

            $('[data-description]', file.previewElement).text(file.description);

            $(this).remove();
        });

        this.dropzone._updateMaxFilesReachedClass();
    }

    FileUpload.prototype.onResizeFileInfo = function(file) {
        var info,
            targetWidth,
            targetHeight;

        if (!this.options.thumbnailWidth && !this.options.thumbnailWidth) {
            targetWidth = targetHeight = 100;
        }
        else if (this.options.thumbnailWidth) {
            targetWidth = this.options.thumbnailWidth;
            targetHeight = this.options.thumbnailWidth * file.height / file.width;
        }
        else if (this.options.thumbnailHeight) {
            targetWidth = this.options.thumbnailHeight * file.height / file.width;
            targetHeight = this.options.thumbnailHeight;
        }

        // drawImage(image, srcX, srcY, srcWidth, srcHeight, trgX, trgY, trgWidth, trgHeight) takes an image, clips it to
        // the rectangle (srcX, srcY, srcWidth, srcHeight), scales it to dimensions (trgWidth, trgHeight), and draws it
        // on the canvas at coordinates (trgX, trgY).
        info = {
            srcX: 0,
            srcY: 0,
            srcWidth: file.width,
            srcHeight: file.height,
            trgX: 0,
            trgY: 0,
            trgWidth: targetWidth,
            trgHeight: targetHeight
        }

        return info;
    }

    FileUpload.prototype.onUploadAddedFile = function (file) {
        this.$uploadButton.blur();

        var $object = $(file.previewElement).data('dzFileObject', file),
            filesize = this.getFilesize(file);

        // Change filesize format to match October\Rain\Filesystem\Filesystem::sizeToString() format
        $(file.previewElement).find('[data-dz-size]').html(filesize.size + ' ' + filesize.units);

        // Remove any exisiting objects for single variety
        if (!this.options.isMulti) {
            this.removeFileFromElement($object.siblings());
        }

        if (this.options.isMulti) {
            file.previewElement.scrollIntoView();
        }

        this.evalIsPopulated();
        this.updateDeleteSelectedState();
        this.extendExternalToolbar();
    }

    FileUpload.prototype.onUploadSending = function(file, xhr, formData) {
        this.addExtraFormData(formData);
        xhr.setRequestHeader('X-OCTOBER-REQUEST-HANDLER', this.options.uploadHandler);
    }

    FileUpload.prototype.onUploadSuccess = function(file, response) {
        var $preview = $(file.previewElement),
            $img = $('.image img', $preview);

        $preview.addClass('is-success');

        if (response.id) {
            $preview.data('id', response.id);
            $preview.data('path', response.path);
            $img.attr('src', response.thumb);
        }

        this.triggerChange();
    }

    FileUpload.prototype.onUploadError = function(file, error) {
        var $preview = $(file.previewElement);
        $preview.addClass('is-error');
    }

    FileUpload.prototype.onSelectionChanged = function (ev) {
        var $object = $(ev.target).closest('.upload-object');

        $object.toggleClass('selected', ev.target.checked);

        this.updateDeleteSelectedState();
        this.extendExternalToolbar();
    }

    /*
     * Trigger change event (Compatibility with october.form.js)
     */
    FileUpload.prototype.triggerChange = function() {
        this.$el.closest('[data-field-name]').trigger('change.oc.formwidget');
    }

    FileUpload.prototype.addExtraFormData = function(formData) {
        if (this.options.extraData) {
            $.each(this.options.extraData, function (name, value) {
                formData.append(name, value)
            });
        }

        var $form = this.$el.closest('form');
        if ($form.length > 0) {
            $.each($form.serializeArray(), function (index, field) {
                formData.append(field.name, field.value)
            });
        }
    }

    FileUpload.prototype.removeFileFromElement = function($element) {
        var self = this;

        $element.each(function() {
            var $el = $(this),
                obj = $el.data('dzFileObject');

            if (obj) {
                self.dropzone.removeFile(obj);
            }
            else {
                $el.remove();
            }
        })
    }

    //
    // Sorting
    //

    FileUpload.prototype.bindSortable = function() {
        this.dragging = false;

        this.sortable = Sortable.create(this.$filesContainer.get(0), {
            // forceFallback: true,
            animation: 150,
            draggable: 'div.upload-object.is-success',
            handle: '.drag-handle',
            onStart: $.proxy(this.onDragStart, this),
            onChange: this.proxy(this.onSortAttachments),
            onEnd: $.proxy(this.onDragStop, this)
        });
    }

    FileUpload.prototype.onDragStart = function(evt) {
        this.dragging = true;
    }

    FileUpload.prototype.onDragStop = function(evt) {
        this.dragging = false;
    }

    FileUpload.prototype.onSortAttachments = function() {
        if (this.options.sortHandler) {

            // Build an object of ID:ORDER
            var orderData = {}

            this.$el.find('.upload-object.is-success')
                .each(function(index){
                    var id = $(this).data('id')
                    orderData[id] = index + 1
                });

            this.$el.request(this.options.sortHandler, {
                data: { sortOrder: orderData }
            });
        }
    }

    //
    // User interaction
    //

    FileUpload.prototype.onClearFileClick = function (ev) {
        var self = this,
            $form = $(ev.target).closest('form'),
            $button = $(ev.target).closest('.toolbar-clear-file'),
            $currentObjects = $('.upload-object', this.$filesContainer);

        $.oc.confirm($button.attr('data-request-confirm'), function() {
            $currentObjects.addClass('is-loading');

            $form.request($button.attr('data-request'), {
                data: {
                    file_id: $currentObjects.data('id')
                }
            }).done(function() {
                    self.removeFileFromElement($currentObjects);
                    self.evalIsPopulated();
                    self.updateDeleteSelectedState();
                    self.extendExternalToolbar();
                    self.triggerChange();
            }).always(function() {
                $currentObjects.removeClass('is-loading');
            });
        });

        ev.stopPropagation();
        ev.preventDefault();
    }

    FileUpload.prototype.onDeleteSelectedClick = function (ev) {
        var self = this,
            $form = $(ev.target).closest('form'),
            $button = $(ev.target).closest('.toolbar-delete-selected'),
            $currentObjects = $('.upload-object:has(input[data-record-selector]:checked)', this.$filesContainer);

        $.oc.confirm($button.attr('data-request-confirm'), function () {
            $currentObjects.addClass('is-loading');

            $currentObjects.each(function () {
                var $currentObject = $(this)

                $form.request($button.attr('data-request'), {
                    data: {
                        file_id: $currentObject.data('id')
                    }
                }).done(function() {
                    self.removeFileFromElement($currentObject);
                    self.evalIsPopulated();
                    self.updateDeleteSelectedState();
                    self.extendExternalToolbar();
                    self.triggerChange();
                }).always(function () {
                    $currentObject.removeClass('is-loading');
                });
            });
        });

        ev.stopPropagation();
        ev.preventDefault();
    }

    FileUpload.prototype.onClickSuccessObject = function(ev) {
        if ($(ev.target).closest('.meta').length) return;
        if ($(ev.target).closest('.custom-checkbox-v2').length) return;

        var $target = $(ev.target).closest('.upload-object');

        if (!this.options.configHandler) {
            window.open($target.data('path'));
            return;
        }

        $target.popup({
            handler: this.options.configHandler,
            extraData: { file_id: $target.data('id') }
        });

        $target.one('popupComplete', function(event, element, modal){
            modal.one('ajaxDone', 'button[type=submit]', function(e, context, data) {
                if (data.displayName) {
                    $('[data-dz-name]', $target).text(data.displayName)
                    $('[data-description]', $target).text(data.description)
                }
            });
        });
    }

    FileUpload.prototype.onClickErrorObject = function(ev) {
        var
            self = this,
            $target = $(ev.target).closest('.upload-object'),
            errorMsg = $('[data-dz-errormessage]', $target).text(),
            $template = $(this.options.errorTemplate);

        // Remove any exisiting objects for single variety
        if (!this.options.isMulti) {
            this.removeFileFromElement($target.siblings());
        }

        $target.ocPopover({
            content: Mustache.render($template.html(), { errorMsg: errorMsg }),
            modal: true,
            highlightModalTarget: true,
            placement: 'top',
            fallbackPlacement: 'left',
            containerClass: 'popover-danger'
        });

        var $container = $target.data('oc.popover').$container;
        $container.one('click', '[data-remove-file]', function() {
            $target.data('oc.popover').hide()
            self.removeFileFromElement($target)
            self.evalIsPopulated()
            self.updateDeleteSelectedState()
            self.extendExternalToolbar();
        });
    }

    //
    // Helpers
    //

    FileUpload.prototype.evalIsPopulated = function() {
        var isPopulated = !!$('.upload-object', this.$filesContainer).length;
        this.$el.toggleClass('is-populated', isPopulated);

        // Reset maxFiles counter
        if (!isPopulated) {
            this.dropzone.removeAllFiles();
        }

        var $uploadLabelSpan = this.$uploadButton.find('span.button-label');
        if ($uploadLabelSpan.attr('data-upload-label')) {
            if (isPopulated) {
                $uploadLabelSpan.text($uploadLabelSpan.attr('data-replace-label'))
            }
            else {
                $uploadLabelSpan.text($uploadLabelSpan.attr('data-upload-label'));
            }
        }
    }

    FileUpload.prototype.updateDeleteSelectedState = function () {
        var enabled = false,
            selectedCount = this.$el.find('input[data-record-selector]:checked').length;

        if (this.$el.hasClass('is-populated')) {
            enabled = selectedCount > 0;
        }

        var $button = this.$el.find('.toolbar-delete-selected'),
            $counter = $button.find('.button-label > span');

        $button.prop('disabled', !enabled);

        if (enabled) {
            $counter.text('(' + selectedCount + ')');
        }
        else {
            $counter.text('');
        }
    }

    FileUpload.prototype.onDragEnter = function() {
        if (this.dragging) {
            return;
        }

        this.$el.addClass('file-drag-over');
    }

    FileUpload.prototype.onDragEnd = function () {
        if (this.dragging) {
            return;
        }

        this.$el.removeClass('file-drag-over');
    }

    /*
     * Replicates the formatting of October\Rain\Filesystem\Filesystem::sizeToString(). This method will return
     * an object with the file size amount and the unit used as `size` and `units` respectively.
     */
    FileUpload.prototype.getFilesize = function (file) {
        var formatter = new Intl.NumberFormat('en', {
                style: 'decimal',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }),
            size = 0,
            units = 'bytes';

        if (file.size >= 1073741824) {
            size = formatter.format(file.size / 1073741824);
            units = 'GB';
        }
        else if (file.size >= 1048576) {
            size = formatter.format(file.size / 1048576);
            units = 'MB';
        }
        else if (file.size >= 1024) {
            size = formatter.format(file.size / 1024);
            units = 'KB';
        }
        else if (file.size > 1) {
            size = file.size;
            units = 'bytes';
        }
        else if (file.size == 1) {
            size = 1;
            units = 'byte';
        }

        return {
            size: size,
            units: units
        };
    }

    FileUpload.DEFAULTS = {
        url: window.location,
        uploadHandler: null,
        configHandler: null,
        sortHandler: null,
        uniqueId: null,
        extraData: {},
        paramName: 'file_data',
        fileTypes: null,
        maxFilesize: 256,
        maxFiles: null,
        template: null,
        errorTemplate: null,
        isMulti: null,
        isPreview: null,
        isSortable: null,
        thumbnailWidth: 120,
        thumbnailHeight: 120
    }

    // FILEUPLOAD PLUGIN DEFINITION
    // ============================

    var old = $.fn.fileUploader;

    $.fn.fileUploader = function (option) {
        return this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.fileUpload')
            var options = $.extend({}, FileUpload.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.fileUpload', (data = new FileUpload(this, options)))
            if (typeof option == 'string') data[option].call($this)
        })
    }

    $.fn.fileUploader.Constructor = FileUpload;

    // FILEUPLOAD NO CONFLICT
    // =================

    $.fn.fileUploader.noConflict = function () {
        $.fn.fileUpload = old
        return this
    }

    // FILEUPLOAD DATA-API
    // ===============
    $(document).render(function () {
        $('[data-control="fileupload"]').fileUploader();
    });

}(window.jQuery);
