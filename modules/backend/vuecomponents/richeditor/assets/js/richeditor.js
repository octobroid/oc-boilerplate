$.oc.module.register('backend.component.richeditor', function () {
    function initFroala(component) {
        const options = JSON.parse(component.$el.getAttribute('data-configuration'));
        const $textarea = $(component.$refs.textarea);
        let froalaOptions = {
            editorClass: 'control-richeditor',
            language: options.editorLang,
            toolbarSticky: false
        };

        if (Array.isArray(component.toolbarButtons) && component.toolbarButtons.length > 0) {
            froalaOptions.toolbarButtons = component.toolbarButtons;
        }
        else {
            if (options.globalToolbarButtons) {
                froalaOptions.toolbarButtons = options.globalToolbarButtons;
            }
            else {
                froalaOptions.toolbarButtons = component.defaultButtons;
            }
        }

        froalaOptions.imageStyles = options.imageStyles
            ? options.imageStyles
            : {
                  'oc-img-rounded': 'Rounded',
                  'oc-img-bordered': 'Bordered'
              };

        froalaOptions.linkStyles = options.linkStyles
            ? options.linkStyles
            : {
                  'oc-link-green': 'Green',
                  'oc-link-strong': 'Thick'
              };

        froalaOptions.paragraphStyles = options.paragraphStyles
            ? options.paragraphStyles
            : {
                  'oc-text-gray': 'Gray',
                  'oc-text-bordered': 'Bordered',
                  'oc-text-spaced': 'Spaced',
                  'oc-text-uppercase': 'Uppercase'
              };

        froalaOptions.paragraphFormat = options.paragraphFormat
            ? options.paragraphFormat
            : {
              'N': 'Normal',
              'H1': 'Heading 1',
              'H2': 'Heading 2',
              'H3': 'Heading 3',
              'H4': 'Heading 4',
              'PRE': 'Code'
            }

        froalaOptions.tableStyles = options.tableStyles
            ? options.tableStyles
            : {
                  'oc-dashed-borders': 'Dashed Borders',
                  'oc-alternate-rows': 'Alternate Rows'
              };

        froalaOptions.tableCellStyles = options.tableCellStyles
            ? options.tableCellStyles
            : {
                  'oc-cell-highlighted': 'Highlighted',
                  'oc-cell-thick-border': 'Thick'
              };

        froalaOptions.toolbarButtonsMD = froalaOptions.toolbarButtons;
        froalaOptions.toolbarButtonsSM = froalaOptions.toolbarButtons;
        froalaOptions.toolbarButtonsXS = froalaOptions.toolbarButtons;

        if (options.htmlAllowedEmptyTags) {
            froalaOptions.allowEmptyTags = options.htmlAllowedEmptyTags.split(/[\s,]+/);
        }

        if (options.allowTags) {
            froalaOptions.htmlAllowedTags = options.allowTags.split(/[\s,]+/);
        }

        if (options.allowAttrs) {
            froalaOptions.htmlAllowedAttrs = $.FroalaEditor.DEFAULTS.htmlAllowedAttrs.concat(options.allowAttrs.split(/[\s,]+/));
        }

        froalaOptions.htmlDoNotWrapTags = options.noWrapTags
            ? options.noWrapTags.split(/[\s,]+/)
            : ['figure', 'script', 'style'];

        if (options.removeTags) {
            froalaOptions.htmlRemoveTags = options.removeTags.split(/[\s,]+/);
        }

        froalaOptions.lineBreakerTags = options.lineBreakerTags
            ? options.lineBreakerTags.split(/[\s,]+/)
            : ['figure, table, hr, iframe, form, dl'];

        froalaOptions.shortcutsEnabled = ['show', 'bold', 'italic', 'underline', 'indent', 'outdent', 'undo', 'redo'];

        // File upload
        froalaOptions.imageUploadURL = froalaOptions.fileUploadURL = window.location;
        froalaOptions.imageUploadParam = froalaOptions.fileUploadParam = 'file_data';
        froalaOptions.imageUploadParams = froalaOptions.fileUploadParams = {
            X_OCTOBER_MEDIA_MANAGER_QUICK_UPLOAD: 1,
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        var placeholder = component.placeholder;
        froalaOptions.placeholderText = placeholder ? placeholder : '';

        froalaOptions.height = Infinity;
        froalaOptions.iframeStyleFiles = [options.iframeStylesFile];

        if (component.fullPage) {
            froalaOptions.fullPage = true;
        }

        if (!options.useMediaManager) {
            delete $.FroalaEditor.PLUGINS.mediaManager;
        }

        if (!component.usePageLinks) {
            delete $.FroalaEditor.PLUGINS.pageLinks;
        }

        $.FroalaEditor.ICON_TEMPLATES = {
            font_awesome: '<i class="icon-[NAME]"></i>',
            text: '<span style="text-align: center;">[NAME]</span>',
            image: '<img src=[SRC] alt=[ALT] />'
        };

        // $textarea.on('froalaEditor.initialized.richeditor', component.initialized);
        $textarea.on('froalaEditor.contentChanged.richeditor', component.onChange);
        $textarea.on('froalaEditor.focus.richeditor', component.onFocus);
        $textarea.on('froalaEditor.blur.richeditor', component.onBlur);
        $textarea.closest('form').on('oc.beforeRequest', component.onFormBeforeRequest);
        $(document).on('vue.beforeRequest', component.onVueFormBeforeRequest);
        // $textarea.on('froalaEditor.html.get.richeditor', this.proxy(this.onSyncContent));
        // $textarea.on('froalaEditor.html.set.richeditor', this.proxy(this.onSetContent));

        $textarea.froalaEditor(froalaOptions);

        component.editor = $textarea.data('froala.editor');

        if (component.readOnly) {
            component.editor.edit.off();
        }

        // this.$el.on('keydown', '.fr-view figure', this.proxy(this.onFigureKeydown));
    }

    Vue.component('backend-component-richeditor', {
        props: {
            fullHeight: {
                type: Boolean,
                default: true
            },
            readOnly: Boolean,
            toolbarButtons: Array,
            fullPage: {
                type: Boolean,
                default: false
            },
            containerCssClass: {
                type: String,
                default: ''
            },
            value: String,
            placeholder: String
        },
        data: function() {
            return {
                editorId: $.oc.domIdManager.generate('richeditor'),
                defaultButtons: [
                    'paragraphFormat',
                    'align',
                    'bold',
                    'italic',
                    'underline',
                    '-',
                    'formatOL',
                    'formatUL',
                    '-',
                    'insertTable',
                    'insertLink',
                    'insertImage',
                    'insertHR',
                    'html'
                ],
                editor: null,
                lastCachedValue: this.value
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
            }
        },
        methods: {
            getEditor: function getEditor() {
                return this.editor;
            },

            onChange: function onChange() {
                this.lastCachedValue = this.editor.html.get();
                this.$emit('input', this.lastCachedValue);
            },

            onFocus: function onFocus() {
                this.$emit('focus');
            },

            onBlur: function onBlur() {
                this.$emit('blur');
            },

            onFormBeforeRequest: function onFormBeforeRequest() {
                const $textarea = $(this.$refs.textarea).closest('div.field-richeditor.vue-mode').find('[data-richeditor-textarea]');
                $textarea.val(this.editor.html.get());
            },

            onVueFormBeforeRequest: function onVueFormBeforeRequest() {
                this.onChange();
            }
        },
        mounted: function onMounted() {
            Vue.nextTick(() => {
                initFroala(this);
                this.editor.html.set(this.value);
            });
        },
        beforeDestroy: function beforeDestroy() {
            const $textarea = $(this.$refs.textarea);

            $textarea.off('.richeditor');
            $textarea.froalaEditor('destroy');
            $textarea.closest('form').off('oc.beforeRequest', this.onFormBeforeRequest);
            $(document).off('vue.beforeRequest', this.onVueFormBeforeRequest);

            this.editor = null;
        },
        watch: {
            value: function onValueChanged(newValue, oldValue) {
                if (!this.editor) {
                    return;
                }

                if (this.lastCachedValue == newValue) {
                    return;
                }

                if (newValue === null) {
                    newValue = '';
                }

                this.editor.html.set(newValue);
            }
        },
        template: '#backend_vuecomponents_richeditor'
    });
});
