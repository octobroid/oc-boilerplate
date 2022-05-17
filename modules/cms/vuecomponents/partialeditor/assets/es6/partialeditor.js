Vue.component('cms-editor-component-partial-editor', {
    extends: $.oc.module.import('cms.editor.extension.documentcomponent.base'),
    data: function() {
        const EditorModelDefinition = $.oc.module.import('backend.vuecomponents.monacoeditor.modeldefinition');
        const defMarkup = new EditorModelDefinition(
            'twig',
            this.trans('cms::lang.page.editor_markup'),
            {},
            'markup',
            'backend-icon-background monaco-document html'
        );

        defMarkup.setModelTags(['cms-markup']);

        const defCode = new EditorModelDefinition(
            'php',
            this.trans('cms::lang.page.editor_code'),
            {},
            'code',
            'backend-icon-background monaco-document php'
        );

        defCode.setAutoPrefix('<?php\n\n', /^\s*\<\?(php)?\n*/);

        return {
            documentData: {
                markup: '',
                code: ''
            },
            documentSettingsPopupTitle: this.trans('cms::lang.editor.partial'),
            documentTitleProperty: 'fileName',
            codeEditorModelDefinitions: [defMarkup, defCode],
            defMarkup: defMarkup,
            defCode: defCode
        };
    },
    computed: {
        toolbarElements: function computeToolbarElements() {
            return this.postProcessToolbarElements([
                {
                    type: 'button',
                    icon: 'octo-icon-save',
                    label: this.trans('backend::lang.form.save'),
                    hotkey: 'ctrl+s, cmd+s',
                    tooltip: this.trans('backend::lang.form.save'),
                    tooltipHotkey: '⌃S, ⌘S',
                    command: 'save'
                },
                {
                    type: 'button',
                    icon: 'octo-icon-settings',
                    label: this.trans('editor::lang.common.settings'),
                    command: 'settings',
                    hidden: !this.hasSettingsForm
                },
                this.customToolbarButtons,
                {
                    type: 'button',
                    icon: 'octo-icon-components',
                    label: this.trans('cms::lang.editor.component_list'),
                    command: 'show-components'
                },
                {
                    type: 'separator'
                },
                {
                    type: 'button',
                    icon: 'octo-icon-info',
                    label: this.trans('cms::lang.editor.info'),
                    command: 'show-template-info',
                    disabled: this.isNewDocument
                },
                {
                    type: 'separator'
                },
                {
                    type: 'button',
                    icon: 'octo-icon-delete',
                    disabled: this.isNewDocument,
                    command: 'delete',
                    hotkey: 'shift+option+d',
                    tooltip: this.trans('backend::lang.form.delete'),
                    tooltipHotkey: '⇧⌥D'
                },
                {
                    type: 'button',
                    icon: this.documentHeaderCollapsed ? 'octo-icon-angle-down' : 'octo-icon-angle-up',
                    command: 'document:toggleToolbar',
                    fixedRight: true,
                    tooltip: this.trans('editor::lang.common.toggle_document_header')
                }
            ]);
        }
    },
    methods: {
        getRootProperties: function() {
            return ['components', 'fileName', 'markup', 'code'];
        },

        getMainUiDocumentProperties: function getMainUiDocumentProperties() {
            return ['fileName', 'markup', 'code', 'components'];
        },

        updateNavigatorNodeUserData: function updateNavigatorNodeUserData(title) {
            this.documentNavigatorNode.userData.filename = this.documentMetadata.path;
            this.documentNavigatorNode.userData.path = this.documentMetadata.navigatorPath;
        },

        documentLoaded: function documentLoaded(data) {
            if (this.$refs.editor) {
                this.$refs.editor.updateValue(this.defMarkup, this.documentData.markup);
                this.$refs.editor.updateValue(this.defCode, this.documentData.code);
            }
        },

        documentCreatedOrLoaded: function documentCreatedOrLoaded() {
            this.defMarkup.setHolderObject(this.documentData);
            this.defCode.setHolderObject(this.documentData);
        }
    },
    template: '#cms_vuecomponents_partialeditor'
});
