$.oc.module.register('backend.component.documentmarkdowneditor', function () {
    var utils = $.oc.module.import('backend.vuecomponents.documentmarkdowneditor.utils');
    var octoberCommands = $.oc.module.import('backend.vuecomponents.documentmarkdowneditor.octobercommands');

    Vue.component('backend-component-documentmarkdowneditor', {
        props: {
            toolbarContainer: Array,
            fullHeight: {
                type: Boolean,
                default: true
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
            value: String,
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

            if (this.useMediaManager) {
                imageDropdownItems.push({
                    command: 'oc-browse-image',
                    label: 'browse'
                });

                fileDropdownItems.push({
                    command: 'oc-browse-file',
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

            return {
                editor: null,
                editorId: null,
                $buttons: null,
                updateDebounceTimeoutId: null,
                lastCachedValue: this.value,
                config: null,
                defaultButtons: ['bold', 'italic', 'strikethrough', 'heading-1', 'heading-2', 'heading-3', '|', 'code', 'quote', 'unordered-list', 'ordered-list', 'clean-block', '|', 'link', 'image', {
                    name: 'attachment',
                    action: 'attachment',
                    className: 'fa fa-bold',
                    title: 'add_file_title'
                }, 'table', 'horizontal-rule', 'side-by-side'],
                buttonConfig: {
                    heading: {
                        ignore: true
                    },
                    preview: {
                        ignore: true
                    },
                    guide: {
                        ignore: true
                    },
                    undo: {
                        ignore: true
                    },
                    redo: {
                        ignore: true
                    },
                    fullscreen: {
                        ignore: true
                    },
                    link: {
                        ignorePressState: true
                    },
                    image: {
                        dropdown: imageDropdownItems,
                        ignorePressState: true
                    },
                    attachment: {
                        dropdown: fileDropdownItems
                    }
                },
                iconMap: {
                    strikethrough: 'text-strikethrough',
                    'heading-1': 'text-h1',
                    'heading-2': 'text-h2',
                    'heading-3': 'text-h3',
                    code: 'text-code-block',
                    'unordered-list': 'text-format-ul',
                    'ordered-list': 'text-format-ol',
                    'clean-block': 'eraser',
                    image: 'text-image',
                    table: 'text-insert-table',
                    'horizontal-rule': 'horizontal-line',
                    'side-by-side': 'window-split'
                }
            };
        },
        computed: {
            cssClass: function computeCssClass() {
                var result = '';

                if (this.fullHeight) {
                    result += ' full-height-strict';
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
            extendToolbar: function extendToolbar() {
                if (!this.$buttons) {
                    this.$buttons = $(this.$el).find('.editor-toolbar button, .editor-toolbar i.separator');
                }

                this.toolbarContainer.splice(0, this.toolbarContainer.length);
                var that = this;

                if (!this.builtInMode || this.hasExternalToolbar) {
                    utils.addSeparator(that);
                }

                this.$buttons.each(function () {
                    var $button = $(this);

                    if ($button.hasClass('separator')) {
                        utils.addSeparator(that);
                        return;
                    }

                    var cmd = utils.getButtonCommand($button);
                    if (that.buttonConfig[cmd] && that.buttonConfig[cmd].ignore) {
                        return;
                    }

                    var hasCustomDropdown = that.buttonConfig[cmd] && that.buttonConfig[cmd].dropdown;
                    if (!hasCustomDropdown) {
                        utils.buttonFromButton(that, $button);
                    } else {
                        utils.dropdownFromButton(that, $button);
                    }
                });

                var lastIndex = this.toolbarContainer.length - 1;
                if (this.toolbarContainer[lastIndex].type === 'separator') {
                    this.toolbarContainer.pop();
                }
            },

            extendExternalToolbar: function extendExternalToolbar() {
                if ($(this.$el).is(":visible")) {
                    this.extendToolbar();
                }
            },

            updateUi: function updateUi() {
                if (this.updateDebounceTimeoutId !== null) {
                    clearTimeout(this.updateDebounceTimeoutId);
                }

                this.updateDebounceTimeoutId = setTimeout(this.extendToolbar, 30);
            },

            trans: function trans(key) {
                if (this.configuration.lang[key] === undefined) {
                    return key;
                }

                return this.configuration.lang[key];
            },

            enableSideBySide: function enableSideBySide() {
                this.onToolbarCommand({
                    command: 'markdowneditor-toolbar-side-by-side'
                });
            },

            refresh: function refresh() {
                if (this.editor) {
                    this.editor.codemirror.refresh();
                }
            },

            clearHistory: function clearHistory() {
                if (this.editor) {
                    this.editor.codemirror.doc.clearHistory();
                }
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

                var $button = $(this.$el).find('.editor-toolbar button[class*="' + command.editorCommand + '"]');

                if (!$button.length) {
                    return;
                }

                $button.trigger('click');
                this.updateUi();
            },

            onEditorContextChanged: function onEditorContextChanged() {
                this.updateUi();
            },

            onOctoberCommand: function onOctoberCommand(command) {
                octoberCommands.invoke(command.editorCommand, this.editor, this);
            },

            onChange: function onChange() {
                this.onEditorContextChanged();

                this.lastCachedValue = this.editor.value();
                this.$emit('input', this.lastCachedValue);
            },

            onFocus: function onFocus() {
                this.$emit('focus');
            },

            onBlur: function onBlur() {
                this.$emit('blur');
            }
        },
        mounted: function onMounted() {
            this.editorId = $.oc.domIdManager.generate('markdowneditor');
            this.$on('toolbarcmd', this.onToolbarCommand);

            this.editor = new EasyMDE({
                element: this.$refs.textarea,
                toolbar: this.defaultButtons,
                previewImagesInEditor: false,
                sideBySideFullscreen: false,
                autoDownloadFontAwesome: false,
                syncSideBySidePreviewScroll: true,
                status: true,
                previewRender: function previewRender(plainText) {
                    return DOMPurify.sanitize(marked(plainText));
                }
            });

            this.editor.codemirror.on('cursorActivity', this.onEditorContextChanged);
            this.editor.codemirror.on('change', this.onChange);
            this.editor.codemirror.on('focus', this.onFocus);
            this.editor.codemirror.on('blur', this.onBlur);

            if (!this.hasExternalToolbar) {
                this.extendToolbar();
            } else {
                this.extendExternalToolbar();
            }

            this.mountEventBus();
            this.enableSideBySide();

            this.editor.value(this.value);
        },
        beforeDestroy: function beforeDestroy() {
            if (this.editor) {
                this.editor.toTextArea();
                this.editor.codemirror.off('cursorActivity', this.onEditorContextChanged);
                this.editor.codemirror.off('change', this.onEditorContextChanged);
                this.editor.codemirror.off('focus', this.onFocus);
                this.editor.codemirror.off('blur', this.onBlur);
            }

            this.unmountEventBus();

            this.editor = null;
            this.$buttons = null;
        },
        watch: {
            value: function onValueChanged(newValue) {
                if (this.editor) {
                    if (newValue === null) {
                        newValue = '';
                    }

                    if (newValue == this.lastCachedValue) {
                        return;
                    }
                }

                this.editor.value(newValue);
            }
        },
        template: '#backend_vuecomponents_documentmarkdowneditor'
    });
});
