$.oc.module.register('backend.component.richeditor.document.connector', function () {
    var utils = $.oc.module.import('backend.vuecomponents.richeditordocumentconnector.utils');
    var octoberCommands = $.oc.module.import('backend.vuecomponents.richeditordocumentconnector.octobercommands');

    Vue.component('backend-component-richeditor-document-connector', {
        props: {
            toolbarContainer: Array,
            allowResizing: {
                type: Boolean,
                default: true
            },
            uniqueKey: {
                type: String,
                required: true
            },
            containerCssClass: {
                type: String,
                default: ''
            },
            useMediaManager: {
                type: Boolean,
                default: false
            },
            builtInMode: {
                type: Boolean,
                default: false
            },
            externalToolbarEventBus: String
        },
        data: function data() {
            var imageDropdownItems = [{
                command: 'oc-upload-image',
                label: 'command_upload_from_computer'
            }];
            var fileDropdownItems = [{
                command: 'oc-upload-file',
                label: 'command_upload_from_computer'
            }];

            var videoDropdownOptions = [{
                command: 'oc-embed-video',
                label: 'embedding_code'
            }];

            var audioDropdownOptions = [{
                command: 'oc-embed-audio',
                label: 'embedding_code'
            }];

            if (this.useMediaManager) {
                imageDropdownItems.push({
                    command: 'oc-browse-image',
                    label: 'browse'
                });

                fileDropdownItems.push({
                    command: 'oc-browse-file',
                    label: 'browse'
                });

                videoDropdownOptions.push({
                    command: 'oc-browse-video',
                    label: 'browse'
                });

                audioDropdownOptions.push({
                    command: 'oc-browse-audio',
                    label: 'browse'
                });
            }

            imageDropdownItems.push({
                command: 'oc-enter-image-url',
                label: 'by_url'
            });

            fileDropdownItems.push({
                command: 'oc-enter-file-url',
                label: 'by_url'
            });

            videoDropdownOptions.push({
                command: 'oc-enter-video-url',
                label: 'by_url'
            });

            audioDropdownOptions.push({
                command: 'oc-enter-audio-url',
                label: 'by_url'
            });

            return {
                size: 800,
                minSize: 200,
                containerSize: 0,

                config: null,

                $textarea: null,
                $buttons: null,
                updateDebounceTimeoutId: null,

                codeEditingMode: false,
                loadingCodeEditingMode: false,
                htmlCode: '',
                codeEditorModelDefinitions: [],

                buttonConfig: {
                    fullscreen: {
                        ignore: true
                    },
                    quote: {
                        dropdownOnly: true
                    },
                    paragraphStyle: {
                        dropdownOnly: true,
                        checkboxDropdown: true,
                        noPressedState: true
                    },
                    paragraphFormat: {
                        dropdownOnly: true,
                        checkedToLabel: true,
                        checkboxDropdown: true,
                        noPressedState: true,
                        separatorAfter: true,
                        separatorBefore: true
                    },
                    fontFamily: {
                        dropdownOnly: true,
                        checkedToLabel: true,
                        checkboxDropdown: true,
                        noPressedState: true,
                        separatorAfter: true,
                        separatorBefore: true,
                        applyItemStyle: true
                    },
                    fontSize: {
                        dropdownOnly: true,
                        checkedToLabel: true,
                        checkboxDropdown: true,
                        noPressedState: true,
                        separatorAfter: true
                    },
                    inlineStyle: {
                        dropdownOnly: true
                    },
                    align: {
                        convertToButtonGroup: true
                    },
                    html: {
                        cmd: 'oc-toggle-code-editor'
                    },
                    insertLink: {},
                    insertImage: {
                        dropdown: imageDropdownItems,
                        dropdownOnly: true
                    },
                    insertVideo: {
                        dropdown: videoDropdownOptions,
                        dropdownOnly: true
                    },
                    insertAudio: {
                        dropdown: audioDropdownOptions,
                        dropdownOnly: true
                    },
                    insertFile: {
                        dropdown: fileDropdownItems,
                        dropdownOnly: true
                    }
                },
                nonModifyingCommands: ['insertLink'],
                iconMap: {
                    'align-left': 'text-left',
                    'align-right': 'text-right',
                    'align-center': 'text-center',
                    'align-justify': 'text-justify',
                    'icon-list-ol': 'text-format-ol',
                    'icon-list-ul': 'text-format-ul',
                    'icon-magic': 'magic-wand',
                    'icon-quote-left': 'quote',
                    'icon-paint-brush': 'text-inline-style',
                    color: 'text-colors',
                    emoticons: 'text-emoticons',
                    subscript: 'text-subscript',
                    superscript: 'text-superscript',
                    strikeThrough: 'text-strikethrough',
                    insertLink: 'link',
                    insertTable: 'text-insert-table',
                    outdent: 'text-decrease-indent',
                    indent: 'text-increase-indent',
                    'icon-paragraph': null,
                    insertHR: 'horizontal-line',
                    'icon-image': 'text-image',
                    'icon-video-camera': 'text-video',
                    'icon-volume-up': 'volume',
                    'icon-file-o': 'attachment',
                    clearFormatting: 'text-clear-formatting',
                    selectAll: 'cursor-arrow',
                    html: 'edit-code'
                }
            };
        },
        computed: {
            majorTicks: function computeMajorTicks() {
                return utils.makeTicks(this, 100);
            },

            minorTicks: function computeMinorTicks() {
                return utils.makeTicks(this, 25);
            },

            cssClass: function computeCssClass() {
                var result = '';

                if (this.allowResizing) {
                    result += ' resizing-ui';
                } else {
                    result += ' no-resizing-ui';
                }

                if (this.codeEditingMode) {
                    result += ' code-editing-mode';
                }

                result += ' ' + this.containerCssClass;

                return result;
            },

            configuration: function computeConfiguration() {
                if (this.config === null) {
                    this.config = JSON.parse(this.$el.getAttribute('data-configuration'));
                }

                return this.config;
            },

            rulerStyle: function computeRulerStyle() {
                var result = {
                    width: this.size + 'px'
                };

                result.margin = '0 auto';

                return result;
            },

            storageKey: function computeStorageKey() {
                return this.uniqueKey + '-splitter';
            },

            externalToolbarEventBusObj: function computeExternalToolbarEventBusObj() {
                if (!this.externalToolbarEventBus) {
                    return null;
                }

                // Expected format: tailor.app::eventBus
                var parts = this.externalToolbarEventBus.split('::');
                if (parts.length !== 2) {
                    throw new Error('Invalid externalToolbarEventBus format. Expected format: module.name::stateElementName');
                }

                var module = $.oc.module.import(parts[0]);
                return module.state[parts[1]];
            },

            hasExternalToolbar: function computeHasExternalToolbar() {
                return !!this.externalToolbarEventBusObj;
            }
        },
        methods: {
            extendToolbar: function extendToolbar(dropdownId) {
                if (!this.$buttons) {
                    this.$buttons = $(this.$el).find('.fr-toolbar .fr-btn, .fr-toolbar .fr-separator');
                }
                var that = this;

                if (!dropdownId) {
                    this.toolbarContainer.splice(0, this.toolbarContainer.length);

                    if (!this.builtInMode) {
                        this.toolbarContainer.push({ type: 'separator' });
                    }
                }

                if (!utils.hasActiveFroalaPopup()) {
                    this.openDropdown('fontFamily');
                    this.openDropdown('fontSize');
                    this.openDropdown('paragraphFormat');
                }

                this.$buttons.each(function () {
                    var $button = $(this);

                    if (!dropdownId && $button.hasClass('fr-separator')) {
                        utils.addSeparator(that);
                        return;
                    }

                    if (dropdownId && $button.attr('data-oc-button-id') !== dropdownId) {
                        return;
                    }

                    var cmd = $button.attr('data-cmd');
                    if (that.buttonConfig[cmd] && that.buttonConfig[cmd].ignore) {
                        return;
                    }

                    var hasCustomDropdown = that.buttonConfig[cmd] && that.buttonConfig[cmd].dropdown;

                    if (!$button.hasClass('fr-dropdown') && !hasCustomDropdown) {
                        utils.buttonFromButton(that, $button);
                    } else {
                        utils.dropdownFromButton(that, $button, dropdownId);
                    }
                });

                this.updateDebounceTimeoutId = null;
            },

            extendExternalToolbar: function extendExternalToolbar() {
                if ($(this.$el).is(":visible")) {
                    this.extendToolbar(null);
                }
            },

            trans: function trans(key) {
                if (this.configuration.lang[key] === undefined) {
                    return key;
                }

                return this.configuration.lang[key];
            },

            mountEventBus: function mountEventBus() {
                if (!this.externalToolbarEventBusObj) {
                    return;
                }

                this.externalToolbarEventBusObj.$on('toolbarcmd', this.onToolbarExternalCommand);
                this.externalToolbarEventBusObj.$on('extendapptoolbar', this.extendExternalToolbar);
            },

            unmountEventBus: function unmountEventBus() {
                if (!this.externalToolbarEventBusObj) {
                    return;
                }

                this.externalToolbarEventBusObj.$off('toolbarcmd', this.onToolbarExternalCommand);
                this.externalToolbarEventBusObj.$off('extendapptoolbar', this.extendExternalToolbar);
            },

            updateSize: function updateSize() {
                if (this.allowResizing) {
                    $(this.$el).find('.fr-element.fr-view').css('width', this.size + 'px');
                }
            },

            updateUi: function updateUi(dropdownId) {
                if (dropdownId) {
                    this.extendToolbar(dropdownId);
                    return;
                }

                if (this.updateDebounceTimeoutId !== null) {
                    clearTimeout(this.updateDebounceTimeoutId);
                }

                // Froala uses 50ms timeout
                //
                this.updateDebounceTimeoutId = setTimeout(this.extendToolbar, 80);
            },

            initListeners: function initListeners() {
                this.$textarea = $(this.$el).find('.editor-element');
                this.$textarea.on(['froalaEditor.click.connector', 'froalaEditor.input.connector', 'froalaEditor.keyup.connector'].join(' '), this.onEditorContextChanged);
            },

            openDropdown: function openDropdown(cmd) {
                var $button = $(this.$el).find('.fr-toolbar button[data-cmd="' + cmd + '"]');
                this.triggerEditorButtonClick($button);
            },

            triggerEditorButtonClick: function triggerEditorCommandClick($button) {
                var ev1 = jQuery.Event('mouseup');
                ev1.which = 1;
                $button.addClass('fr-selected').trigger(ev1).removeClass('fr-selected');
            },

            toggleCodeEditing: function toggleCodeEditing() {
                var _this = this;

                var EditorModelDefinition = $.oc.module.import('backend.vuecomponents.monacoeditor.modeldefinition');

                if (!this.codeEditingMode) {
                    var basePath = this.configuration.vendorPath;
                    this.loadingCodeEditingMode = true;

                    require.config({
                        paths: {
                            beautify: basePath + '/beautify@1.13.0/beautify.js',
                            beautifyHtml: basePath + '/beautify@1.13.0/beautify-html.js'
                        }
                    });

                    // require is provided by the Monaco editor loader
                    //
                    require(['beautify', 'beautifyHtml'], function (js, html) {
                        _this.htmlCode = html.html_beautify(_this.$textarea.froalaEditor('html.get'));
                        var defMarkup = new EditorModelDefinition('html', 'HTML', {}, 'htmlCode', 'backend-icon-background monaco-document html');
                        defMarkup.setHolderObject(_this);
                        _this.codeEditorModelDefinitions = [defMarkup];

                        _this.codeEditingMode = true;
                    });
                } else {
                    this.codeEditingMode = false;
                    this.loadingCodeEditingMode = false;
                    utils.updateEditorHtml(this, this.htmlCode);
                }

                this.updateUi();
            },

            saveSize: function saveSize() {
                if (isNaN(this.size)) {
                    return;
                }

                localStorage.setItem(this.storageKey + '-size', this.size);
            },

            alignEditorToolbars: function alignEditorToolbars($button) {
                var $popup = $(this.$el).find('.fr-toolbar .fr-popup.fr-desktop.fr-active');
                if (!$popup.length) {
                    return;
                }

                $popup.css('visibility', 'hidden');
                var buttonPosition = $button.position();

                $popup.css('left', buttonPosition.left + 'px');
                var popupOffset = $popup.offset();
                var popupRight = popupOffset.left + $popup.width();
                var documentWidth = $(document).width();
                var correction = popupRight - documentWidth;
                if (correction > 0) {
                    $popup.css('left', buttonPosition.left - correction - 15 + 'px');
                }

                if (popupOffset.left < 15) {
                    $popup.css('left', 15 + 'px');
                }

                $popup.css('visibility', 'visible');
            },

            onResizingHandleMouseDown: function onResizingHandleMouseDown() {
                this.containerSize = $(this.$el).width();
                $(document.body).addClass('richeditor-document-connector-resizing');

                document.addEventListener('mousemove', this.onMouseMove, { passive: true });
                document.addEventListener('mouseup', this.onMouseUp);

                this.startMargin = $(this.$refs.handle).position();
            },

            onMouseMove: function onMouseMove(ev) {
                if (ev.buttons != 1) {
                    // Handle the case when the button was released
                    // outside of the viewport. mouseup doesn't fire
                    // in that case.
                    //
                    this.onMouseUp();
                }

                var handlePos = $(this.$refs.handle).offset(),
                    delta = ev.pageX - handlePos.left;

                if (delta <= 0) {
                    this.size = Math.max(this.size + delta, this.minSize);
                } else {
                    this.size = Math.min(this.size + delta, this.containerSize);
                }
            },

            onMouseUp: function onMouseUp() {
                document.removeEventListener('mousemove', this.onMouseMove, { passive: true });
                document.removeEventListener('mouseup', this.onMouseUp);

                $(document.body).removeClass('richeditor-document-connector-resizing');
                this.saveSize();
            },

            onEditorContextChanged: function onEditorContextChanged() {
                this.updateUi();
            },

            onToolbarExternalCommand: function onToolbarExternalCommand(command) {
                if ($(this.$el).is(":visible")) {
                    this.onToolbarCommand(command);
                }
            },

            onToolbarCommand: function onToolbarCommand(commandData) {
                var command = utils.parseCommandString(commandData.command);
                if (command === null) {
                    return;
                }

                if (command.isOctoberCommand) {
                    this.onOctoberCommand(command);
                }

                var $button = $(this.$el).find('.fr-toolbar [data-cmd="' + command.froalaCommand + '"]');

                if (command.parameter && command.parameter.substring(0, 11) !== 'oc-dropdown') {
                    $button = $button.filter('[data-param1="' + command.parameter + '"]');
                }

                if (!$button.length) {
                    return;
                }

                this.triggerEditorButtonClick($button);
                if (this.nonModifyingCommands.indexOf(command.froalaCommand) === -1) {
                    this.updateUi(command.ocParameter);
                }

                if (commandData.ev) {
                    this.alignEditorToolbars($(commandData.ev.currentTarget));
                }
            },

            onOctoberCommand: function onOctoberCommand(command) {
                octoberCommands.invoke(command.froalaCommand, this.$textarea, this);
            }
        },
        mounted: function onMounted() {
            var _this2 = this;

            Vue.nextTick(function () {
                // Richeditor Vue component initializes Froala in the next tick
                // after mount()
                // 
                var size = parseInt(localStorage.getItem(_this2.storageKey + '-size'));

                if (size) {
                    _this2.size = size;
                }

                if (!_this2.hasExternalToolbar) {
                    _this2.extendToolbar();
                } else {
                    _this2.extendExternalToolbar();
                }

                _this2.mountEventBus();
                _this2.initListeners();
                _this2.$on('toolbarcmd', _this2.onToolbarCommand);
                _this2.updateSize();
            });
        },
        beforeDestroy: function beforeDestroy() {
            this.$textarea.off('.connector');
            this.$textarea = null;
            this.$buttons = null;
            this.unmountEventBus();
        },
        watch: {
            size: function watchSize() {
                this.updateSize();
            },
            htmlCode: function watchHtmlCode(html) {
                utils.updateEditorHtml(this, html);
            }
        },
        template: '#backend_vuecomponents_richeditordocumentconnector'
    });
});
