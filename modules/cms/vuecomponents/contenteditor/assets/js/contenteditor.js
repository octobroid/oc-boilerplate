Vue.component('cms-editor-component-content-editor', {
    extends: $.oc.module.import('cms.editor.extension.documentcomponent.base'),
    data: function data() {
        var EditorModelDefinition = $.oc.module.import('backend.vuecomponents.monacoeditor.modeldefinition');
        var defMarkup = new EditorModelDefinition('html', this.trans('cms::lang.content.editor_content'), {}, 'markup', 'backend-icon-background monaco-document html');

        return {
            documentData: {
                markup: '',
                components: []
            },
            documentSettingsPopupTitle: this.trans('cms::lang.editor.content'),
            documentTitleProperty: 'fileName',
            codeEditorModelDefinitions: [defMarkup],
            savedDocumentLanguage: '',
            defMarkup: defMarkup,
            toolbarExtensionPoint: []
        };
    },
    computed: {
        toolbarElements: function computeToolbarElements() {
            return this.postProcessToolbarElements([{
                type: 'button',
                icon: 'octo-icon-save',
                label: this.trans('backend::lang.form.save'),
                hotkey: 'ctrl+s, cmd+s',
                tooltip: this.trans('backend::lang.form.save'),
                tooltipHotkey: '⌃S, ⌘S',
                command: 'save'
            }, {
                type: 'button',
                icon: 'octo-icon-settings',
                label: this.trans('editor::lang.common.settings'),
                command: 'settings',
                hidden: !this.hasSettingsForm
            }, {
                type: 'separator'
            }, {
                type: 'button',
                icon: 'octo-icon-info',
                label: this.trans('cms::lang.editor.info'),
                command: 'show-template-info',
                disabled: this.isNewDocument
            }, {
                type: 'separator',
                visibilityTag: 'hide-for-direct-document'
            }, {
                type: 'button',
                icon: 'octo-icon-delete',
                disabled: this.isNewDocument,
                command: 'delete',
                hotkey: 'shift+option+d',
                tooltip: this.trans('backend::lang.form.delete'),
                tooltipHotkey: '⇧⌥D'
            }, this.toolbarExtensionPoint, {
                type: 'button',
                icon: this.documentHeaderCollapsed ? 'octo-icon-angle-down' : 'octo-icon-angle-up',
                command: 'document:toggleToolbar',
                fixedRight: true,
                tooltip: this.trans('editor::lang.common.toggle_document_header')
            }]);
        },

        isHtmlDocument: function computeIsHtmlDocument() {
            return this.savedDocumentLanguage === 'html';
        },

        isMarkdownDocument: function isMarkdownDocument() {
            return this.savedDocumentLanguage === 'markdown';
        }
    },
    methods: {
        getRootProperties: function getRootProperties() {
            return ['components', 'fileName', 'markup'];
        },

        getMainUiDocumentProperties: function getMainUiDocumentProperties() {
            return ['fileName', 'markup', 'description', 'components'];
        },

        updateNavigatorNodeUserData: function updateNavigatorNodeUserData(title) {
            this.documentNavigatorNode.userData.filename = this.documentMetadata.path;
            this.documentNavigatorNode.userData.path = this.documentMetadata.navigatorPath;
        },

        updateDocumentLanguage: function updateDocumentLanguage() {
            this.savedDocumentLanguage = this.getDocumentLanguage(this.documentData.fileName);
            this.$refs.editor.updateLanguage(this.defMarkup, this.savedDocumentLanguage);
        },

        documentLoaded: function documentLoaded(data) {
            if (this.$refs.editor) {
                this.$refs.editor.updateValue(this.defMarkup, this.documentData.markup);
                this.updateDocumentLanguage();
            }
        },

        documentSaved: function documentSaved() {
            if (this.$refs.editor) {
                this.updateDocumentLanguage();
            }
        },

        getDocumentLanguage: function getDocumentLanguage(fileName) {
            if (fileName.endsWith('.txt')) {
                return 'plaintext';
            }

            if (fileName.endsWith('.md')) {
                return 'markdown';
            }

            return 'html';
        },

        documentCreatedOrLoaded: function documentCreatedOrLoaded() {
            this.defMarkup.setHolderObject(this.documentData);
        },

        monacoLoaded: function monacoLoaded() {
            this.updateDocumentLanguage();
        },

        onParentTabSelected: function onParentTabSelected() {
            var _this = this;

            if (this.$refs.editor) {
                this.$nextTick(function () {
                    return _this.$refs.editor.layout();
                });
            }

            if (this.$refs.markdownEditor) {
                this.$nextTick(function () {
                    return _this.$refs.markdownEditor.refresh();
                });
            }
        }
    },
    watch: {
        isHtmlDocument: function watchIsHtmlDocument(value) {
            if (!value) {
                this.toolbarExtensionPoint = [];
            }
        },

        isMarkdownDocument: function watchIsMarkdownDocument(value) {
            if (!value) {
                this.toolbarExtensionPoint = [];
            }
        }
    },
    template: '#cms_vuecomponents_contenteditor'
});
