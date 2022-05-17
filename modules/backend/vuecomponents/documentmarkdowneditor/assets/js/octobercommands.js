$.oc.module.register('backend.vuecomponents.documentmarkdowneditor.octobercommands', function () {
    'use strict';

    function getUrlPopupConfig(component) {
        return [{
            property: 'url',
            title: 'URL',
            type: 'string',
            defaultFocus: true,
            placeholder: 'https://...',
            validation: {
                required: {
                    message: component.trans('url_required')
                },
                regex: {
                    message: component.trans('url_validation'),
                    pattern: '^https?:\\/\\/'
                }
            }
        }];
    }

    var OctoberCommands = function () {
        function OctoberCommands() {
            babelHelpers.classCallCheck(this, OctoberCommands);
        }

        babelHelpers.createClass(OctoberCommands, [{
            key: 'invoke',
            value: function invoke(command, editor, component) {
                switch (command) {
                    case 'oc-upload-image':
                        return this.uploadImage(editor);

                    case 'oc-browse-image':
                        return this.browseImage(editor);

                    case 'oc-enter-image-url':
                        return this.addImageByUrl(editor, component);

                    case 'oc-browse-file':
                        return this.browseFile(editor);

                    case 'oc-upload-file':
                        return this.uploadFile(editor);

                    case 'oc-enter-file-url':
                        return this.addFileByUrl(editor, component);
                }
            }
        }, {
            key: 'uploadMedia',
            value: function uploadMedia(callback, accept) {
                var uploaderUtils = $.oc.module.import('backend.vuecomponents.uploader.utils');
                uploaderUtils.selectAndUploadMediaManagerFiles(callback, true, accept);
            }
        }, {
            key: 'titleFromUrl',
            value: function titleFromUrl(url) {
                var parts = url.split('/');
                for (var index = parts.length - 1; index >= 0; index--) {
                    var part = parts[index];
                    if (part.length === 0) {
                        continue;
                    }

                    return decodeURIComponent(part);
                }

                return decodeURIComponent(url);
            }
        }, {
            key: 'insertFileLink',
            value: function insertFileLink(link, editor, addNewline) {
                var str = '[' + this.titleFromUrl(link) + '](' + link + ')';
                if (addNewline) {
                    str += '\n';
                }

                editor.codemirror.replaceSelection(str);
            }
        }, {
            key: 'uploadImage',
            value: function uploadImage(editor) {
                var _this = this;

                this.uploadMedia(function (link, isMultiple, isLast) {
                    var str = '![' + _this.titleFromUrl(link) + '](' + link + ')';
                    if (isMultiple && !isLast) {
                        str += '\n';
                    }
                    editor.codemirror.replaceSelection(str);
                }, '.png, .jpg, .jpeg, .gif, .svg');
            }
        }, {
            key: 'browseImage',
            value: function browseImage(editor) {
                var that = this;

                new $.oc.mediaManager.popup({
                    alias: 'ocmediamanager',
                    cropAndInsertButton: true,
                    onInsert: function onInsert(items) {
                        if (!items.length) {
                            $.oc.alert($.oc.lang.get('mediamanager.invalid_image_empty_insert'));
                            return;
                        }

                        var imagesInserted = 0;

                        for (var i = 0, len = items.length; i < len; i++) {
                            if (items[i].documentType !== 'image') {
                                $.oc.alert($.oc.lang.get('mediamanager.invalid_image_invalid_insert', 'The file "' + items[i].title + '" is not an image.'));
                                continue;
                            }

                            var str = '![' + that.titleFromUrl(items[i].publicUrl) + '](' + items[i].publicUrl + ')';
                            if (i < items.length - 1) {
                                str += '\n';
                            }

                            editor.codemirror.replaceSelection(str);

                            imagesInserted++;
                        }

                        if (imagesInserted !== 0) {
                            this.hide();
                        }
                    }
                });
            }
        }, {
            key: 'addImageByUrl',
            value: function addImageByUrl(editor, component) {
                var _this2 = this;

                var config = getUrlPopupConfig(component);
                var data = {
                    url: ''
                };

                $.oc.vueComponentHelpers.inspector.host.showModal(component.trans('add_image_title'), data, config, 'file-url', {
                    beforeApplyCallback: function beforeApplyCallback(updatedData) {
                        editor.codemirror.replaceSelection('![' + _this2.titleFromUrl(updatedData.url) + '](' + updatedData.url + ')');
                        return Promise.resolve();
                    }
                }).then($.noop, $.noop);
            }
        }, {
            key: 'browseFile',
            value: function browseFile(editor) {
                var that = this;

                new $.oc.mediaManager.popup({
                    alias: 'ocmediamanager',
                    cropAndInsertButton: false,
                    onInsert: function onInsert(items) {
                        if (!items.length) {
                            $.oc.alert($.oc.lang.get('mediamanager.invalid_file_empty_insert'));
                            return;
                        }

                        for (var i = 0, len = items.length; i < len; i++) {
                            var str = '[' + that.titleFromUrl(items[i].publicUrl) + '](' + items[i].publicUrl + ')';

                            if (i < items.length - 1) {
                                str += '\n';
                            }

                            editor.codemirror.replaceSelection(str);
                        }

                        this.hide();
                    }
                });
            }
        }, {
            key: 'uploadFile',
            value: function uploadFile(editor) {
                var _this3 = this;

                this.uploadMedia(function (link, isMultiple, isLast) {
                    _this3.insertFileLink(link, editor, isMultiple && !isLast);
                });
            }
        }, {
            key: 'addFileByUrl',
            value: function addFileByUrl(editor, component) {
                var _this4 = this;

                var config = getUrlPopupConfig(component);
                var data = {
                    url: ''
                };

                $.oc.vueComponentHelpers.inspector.host.showModal(component.trans('add_file_title'), data, config, 'file-url', {
                    beforeApplyCallback: function beforeApplyCallback(updatedData) {
                        _this4.insertFileLink(updatedData.url, editor);
                        return Promise.resolve();
                    }
                }).then($.noop, $.noop);
            }
        }]);
        return OctoberCommands;
    }();

    return new OctoberCommands();
});
