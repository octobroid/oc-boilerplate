$.oc.module.register('backend.vuecomponents.richeditordocumentconnector.octobercommands', function () {
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

    function getEmbeddingPopupConfig(component) {
        return [{
            property: 'code',
            title: component.trans('embedding_code'),
            type: 'text',
            size: 'medium',
            defaultFocus: true,
            placeholder: '<iframe...',
            validation: {
                required: {
                    message: component.trans('embedding_code_required')
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
            value: function invoke(command, $textarea, component) {
                switch (command) {
                    case 'oc-upload-image':
                        return this.uploadImage($textarea);

                    case 'oc-browse-image':
                        return this.browseImage($textarea);

                    case 'oc-enter-image-url':
                        return this.addImageByUrl($textarea, component);

                    case 'oc-browse-video':
                        return this.browseVideo($textarea);

                    case 'oc-enter-video-url':
                        return this.addVideoByUrl($textarea, component);

                    case 'oc-embed-video':
                        return this.addVideoByEmbedding($textarea, component);

                    case 'oc-embed-audio':
                        return this.addAudioByEmbedding($textarea, component);

                    case 'oc-browse-audio':
                        return this.browseAudio($textarea);

                    case 'oc-enter-audio-url':
                        return this.addAudioByUrl($textarea, component);

                    case 'oc-browse-file':
                        return this.browseFile($textarea);

                    case 'oc-upload-file':
                        return this.uploadFile($textarea);

                    case 'oc-enter-file-url':
                        return this.addFileByUrl($textarea, component);

                    case 'oc-toggle-code-editor':
                        return this.toggleCodeEditor(component);
                }
            }
        }, {
            key: 'uploadMedia',
            value: function uploadMedia(callback, $textarea, accept) {
                var uploaderUtils = $.oc.module.import('backend.vuecomponents.uploader.utils');
                uploaderUtils.selectAndUploadMediaManagerFiles(function (link, isMultipleFiles) {
                    callback(link);

                    if (isMultipleFiles) {
                        $textarea.froalaEditor('selection.clear');
                    }
                }, accept, true);
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
            value: function insertFileLink(link, $textarea) {
                var selectionText = $textarea.froalaEditor('selection.text');
                if (typeof selectionText !== 'string' || !selectionText.length) {
                    selectionText = this.titleFromUrl(link);
                }

                $textarea.froalaEditor('link.insert', link, selectionText, { class: 'fr-file' });
            }
        }, {
            key: 'uploadImage',
            value: function uploadImage($textarea) {
                this.uploadMedia(function (link) {
                    $textarea.froalaEditor('image.insert', link, true);
                }, $textarea, '.png, .jpg, .jpeg, .gif, .svg');
            }
        }, {
            key: 'browseImage',
            value: function browseImage($textarea) {
                $textarea.froalaEditor('mediaManager.insertImage');
            }
        }, {
            key: 'addImageByUrl',
            value: function addImageByUrl($textarea, component) {
                var config = getUrlPopupConfig(component);
                var data = {
                    url: ''
                };

                $.oc.vueComponentHelpers.inspector.host.showModal(component.trans('add_image_title'), data, config, 'file-url', {
                    beforeApplyCallback: function beforeApplyCallback(updatedData) {
                        $textarea.froalaEditor('image.insert', updatedData.url, true);
                        return Promise.resolve();
                    }
                }).then($.noop, $.noop);
            }
        }, {
            key: 'browseVideo',
            value: function browseVideo($textarea) {
                $textarea.froalaEditor('mediaManager.insertVideo', function (url, title) {
                    $textarea.froalaEditor('figures.insertVideo', url, title);
                });
            }
        }, {
            key: 'addVideoByUrl',
            value: function addVideoByUrl($textarea, component) {
                var _this = this;

                var config = getUrlPopupConfig(component);
                var data = {
                    url: ''
                };

                $.oc.vueComponentHelpers.inspector.host.showModal(component.trans('add_video_title'), data, config, 'file-url', {
                    beforeApplyCallback: function beforeApplyCallback(updatedData) {
                        var url = updatedData.url;
                        var title = _this.titleFromUrl(url);

                        $textarea.froalaEditor('figures.insertVideo', url, title);
                        return Promise.resolve();
                    }
                }).then($.noop, $.noop);
            }
        }, {
            key: 'embedMedia',
            value: function embedMedia(component, title, callback) {
                var config = getEmbeddingPopupConfig(component);
                var data = {
                    code: ''
                };

                $.oc.vueComponentHelpers.inspector.host.showModal(title, data, config, 'embed-media', {
                    beforeApplyCallback: function beforeApplyCallback(updatedData) {
                        var valid = false;
                        try {
                            valid = $(updatedData.code).length > 0;
                        } catch (error) {}

                        if (!valid) {
                            $.oc.vueComponentHelpers.modalUtils.showAlert(component.trans('invalid_embedding_code_title'), component.trans('invalid_embedding_code_message'));

                            return Promise.reject();
                        }

                        callback(updatedData.code);
                        return Promise.resolve();
                    }
                }).then($.noop, $.noop);
            }
        }, {
            key: 'addVideoByEmbedding',
            value: function addVideoByEmbedding($textarea, component) {
                this.embedMedia(component, component.trans('add_video_title'), function (code) {
                    $textarea.froalaEditor('video.insert', code);
                });
            }
        }, {
            key: 'addAudioByEmbedding',
            value: function addAudioByEmbedding($textarea, component) {
                this.embedMedia(component, component.trans('add_audio_title'), function (code) {
                    $textarea.froalaEditor('video.insert', code); // Audio embedding code can be inserted with video.insert
                });
            }
        }, {
            key: 'browseAudio',
            value: function browseAudio($textarea) {
                $textarea.froalaEditor('mediaManager.insertAudio', function (url, title) {
                    $textarea.froalaEditor('figures.insertAudio', url, title);
                });
            }
        }, {
            key: 'addAudioByUrl',
            value: function addAudioByUrl($textarea, component) {
                var _this2 = this;

                var config = getUrlPopupConfig(component);
                var data = {
                    url: ''
                };

                $.oc.vueComponentHelpers.inspector.host.showModal(component.trans('add_audio_title'), data, config, 'file-url', {
                    beforeApplyCallback: function beforeApplyCallback(updatedData) {
                        var url = updatedData.url;
                        var title = _this2.titleFromUrl(url);

                        $textarea.froalaEditor('figures.insertAudio', url, title);
                        return Promise.resolve();
                    }
                }).then($.noop, $.noop);
            }
        }, {
            key: 'browseFile',
            value: function browseFile($textarea) {
                $textarea.froalaEditor('mediaManager.insertFile');
            }
        }, {
            key: 'uploadFile',
            value: function uploadFile($textarea) {
                var _this3 = this;

                this.uploadMedia(function (link) {
                    _this3.insertFileLink(link, $textarea);
                }, $textarea);
            }
        }, {
            key: 'addFileByUrl',
            value: function addFileByUrl($textarea, component) {
                var _this4 = this;

                var config = getUrlPopupConfig(component);
                var data = {
                    url: ''
                };

                $.oc.vueComponentHelpers.inspector.host.showModal(component.trans('add_file_title'), data, config, 'file-url', {
                    beforeApplyCallback: function beforeApplyCallback(updatedData) {
                        _this4.insertFileLink(updatedData.url, $textarea);
                        return Promise.resolve();
                    }
                }).then($.noop, $.noop);
            }
        }, {
            key: 'toggleCodeEditor',
            value: function toggleCodeEditor(component) {
                component.toggleCodeEditing();
            }
        }]);
        return OctoberCommands;
    }();

    return new OctoberCommands();
});
