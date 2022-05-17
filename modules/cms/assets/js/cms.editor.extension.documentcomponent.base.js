$.oc.module.register('cms.editor.extension.documentcomponent.base', function () {
    'use strict';

    var EditorDocumentComponentBase = {
        extends: $.oc.module.import('editor.extension.documentcomponent.base'),
        data: function data() {
            return {
                savingDocument: false,
                documentSavedMessage: this.trans('cms::lang.template.saved'),
                documentReloadedMessage: this.trans('cms::lang.template.reloaded'),
                documentDeletedMessage: this.trans('cms::lang.template.deleted')
            };
        },

        computed: {
            databaseTemplatesEnabled: function computeDatabaseTemplatesEnabled() {
                return this.extension.customData.databaseTemplatesEnabled;
            },

            hasVisibleComponents: function computeHasVisibleComponents() {
                if (!this.documentData.components) {
                    return false;
                }

                return this.documentData.components.some(function (component) {
                    return !component.isHidden;
                });
            },

            customToolbarButtons: function computeCustomToolbarButtons() {
                var buttons = this.extension.getCustomToolbarSettingsButtons(this.documentType);

                if (!Array.isArray(buttons) || !buttons.length) {
                    return [];
                }

                var result = [];

                result.push({
                    type: 'separator'
                });

                buttons.forEach(function (button, index) {
                    var buttonDefinition = {
                        type: 'button',
                        icon: typeof button.icon === 'string' ? button.icon : undefined,
                        label: button.button,
                        command: 'custom-toolbar-button@' + index
                    };

                    result.push(buttonDefinition);
                });

                result.push({
                    type: 'separator'
                });

                return result;
            },

            viewBagComponent: function computeViewBagComponent() {
                if (!this.documentData.components) {
                    return undefined;
                }

                if (!Array.isArray(this.documentData.components)) {
                    return undefined;
                }

                return this.documentData.components.find(function (component) {
                    return component.className === 'Cms\\Components\\ViewBag';
                });
            }
        },

        methods: {
            getRootProperties: function getRootProperties() {
                throw new Error('getRootProperties must be implemented in CmsDocumentEditorComponentBase descendants.');
            },

            monacoLoaded: function monacoLoaded() {
                // Overwrite this method in subclasses
            },

            getInfoPopupItems: function getInfoPopupItems() {
                var mtime = this.documentMetadata.mtime;
                var storage = [this.trans('cms::lang.template.storage_filesystem')];

                if (this.documentMetadata.canResetFromTemplateFile) {
                    storage.push(this.trans('cms::lang.template.storage_database'));
                }

                return [{
                    title: this.trans('cms::lang.template.last_modified'),
                    value: moment.unix(mtime).format('ll LTS')
                }, {
                    title: this.trans('cms::lang.template.storage'),
                    value: storage.join(', ')
                }, {
                    title: this.trans('cms::lang.template.template_file'),
                    value: this.documentMetadata.fullPath
                }];
            },

            getSaveDocumentData: function getSaveDocumentData(inspectorDocumentData) {
                var rootProperties = this.getRootProperties();
                var documentData = inspectorDocumentData ? inspectorDocumentData : this.documentData;

                var data = $.oc.vueUtils.getCleanObject(documentData);
                var result = {
                    settings: {}
                };

                // Copy root properties
                //
                Object.keys(data).forEach(function (property) {
                    if (property === 'settings') {
                        return;
                    }

                    if (rootProperties.indexOf(property) !== -1) {
                        result[property] = data[property];
                    } else {
                        result.settings[property] = data[property];
                    }
                });

                // Copy custom settings properties
                //
                if (babelHelpers.typeof(data.settings) === 'object') {
                    Object.keys(data.settings).forEach(function (property) {
                        if (rootProperties.indexOf(property) === -1 && result.settings[property] === undefined) {
                            result.settings[property] = data.settings[property];
                        }
                    });
                }

                return result;
            },

            getDocumentSavedMessage: function getDocumentSavedMessage(responseData) {
                if (responseData.templateFileUpdated) {
                    return this.trans('cms::lang.template.file_updated');
                }

                if (this.databaseTemplatesEnabled) {
                    // TODO - this must be ignored for assets
                    return this.trans('cms::lang.template.saved_to_db');
                }

                return this.documentSavedMessage;
            },

            resetFromTemplateFile: function () {
                var _ref = babelHelpers.asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
                    var result, errorText;
                    return regeneratorRuntime.wrap(function _callee$(_context) {
                        while (1) {
                            switch (_context.prev = _context.next) {
                                case 0:
                                    _context.prev = 0;
                                    _context.next = 3;
                                    return this.requestDocumentFromServer({
                                        resetFromTemplateFile: true
                                    }, true);

                                case 3:
                                    result = _context.sent;

                                    this.documentCreatedOrLoaded();
                                    this.documentLoaded(result);
                                    $.oc.snackbar.show(this.trans('cms::lang.template.reset_from_template_success'));
                                    _context.next = 13;
                                    break;

                                case 9:
                                    _context.prev = 9;
                                    _context.t0 = _context['catch'](0);
                                    errorText = _context.t0.responseText;

                                    $.oc.vueComponentHelpers.modalUtils.showAlert(this.trans('editor::lang.common.error'), errorText);

                                case 13:
                                case 'end':
                                    return _context.stop();
                            }
                        }
                    }, _callee, this, [[0, 9]]);
                }));

                function resetFromTemplateFile() {
                    return _ref.apply(this, arguments);
                }

                return resetFromTemplateFile;
            }(),

            addComponent: function addComponent(componentData) {
                if (!Array.isArray(this.documentData.components)) {
                    return;
                }

                var counter = 1,
                    originalAlias = componentData.alias,
                    alias = componentData.alias;

                while (this.documentData.components.some(function (component) {
                    return component.alias == alias;
                })) {
                    alias = originalAlias + ++counter;
                }

                componentData.alias = alias;
                componentData.propertyValues['oc.alias'] = alias;
                componentData.propertyValues = JSON.stringify(componentData.propertyValues);
                this.documentData.components.push(componentData);

                return alias;
            },

            postProcessToolbarElements: function postProcessToolbarElements(toolbarElements, noDatabaseTemplateFeatures) {
                var dbOperationsMenu = undefined;
                if (this.databaseTemplatesEnabled && !noDatabaseTemplateFeatures) {
                    dbOperationsMenu = [{
                        type: 'text',
                        command: 'update-template-file',
                        label: this.trans('cms::lang.template.update_file'),
                        disabled: !this.documentMetadata.canUpdateTemplateFile || this.isDocumentChanged
                    }, {
                        type: 'text',
                        command: 'reset-from-template-file',
                        label: this.trans('cms::lang.template.reset_from_file'),
                        disabled: !this.documentMetadata.canResetFromTemplateFile || this.isDocumentChanged
                    }];
                }

                toolbarElements.some(function (element) {
                    if (element.command === 'save') {
                        element.menuitems = dbOperationsMenu;
                        return true;
                    }
                });

                if ($.oc.editor.application.isDirectDocumentMode) {
                    toolbarElements.forEach(function (element) {
                        if (element.command === 'document:toggleToolbar' || element.command === 'delete' || element.visibilityTag === 'hide-for-direct-document') {
                            element.hidden = true;
                        }
                    });
                }

                return toolbarElements;
            },

            showTemplateInfo: function showTemplateInfo() {
                this.application.showEditorDocumentInfoPopup(this.getInfoPopupItems(), this.documentMetadata.typeName);
            },

            expandComponent: function () {
                var _ref2 = babelHelpers.asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(payload) {
                    var data;
                    return regeneratorRuntime.wrap(function _callee2$(_context2) {
                        while (1) {
                            switch (_context2.prev = _context2.next) {
                                case 0:
                                    this.processing = true;

                                    _context2.prev = 1;
                                    _context2.next = 4;
                                    return this.ajaxRequest('onCommand', {
                                        extension: this.namespace,
                                        command: 'onExpandCmsComponent',
                                        documentMetadata: this.documentMetadata,
                                        documentData: this.documentData,
                                        componentAlias: payload.alias
                                    });

                                case 4:
                                    data = _context2.sent;


                                    this.processing = false;
                                    this.$refs.editor.replaceAsSnippet(payload.model, payload.range, data.content.trim());
                                    _context2.next = 13;
                                    break;

                                case 9:
                                    _context2.prev = 9;
                                    _context2.t0 = _context2['catch'](1);

                                    this.processing = false;
                                    $.oc.vueComponentHelpers.modalUtils.showAlert($.oc.editor.getLangStr('editor::lang.common.error'), _context2.t0.responseText);

                                case 13:
                                case 'end':
                                    return _context2.stop();
                            }
                        }
                    }, _callee2, this, [[1, 9]]);
                }));

                function expandComponent(_x) {
                    return _ref2.apply(this, arguments);
                }

                return expandComponent;
            }(),

            handleCustomToolbarButton: function handleCustomToolbarButton(command) {
                var _this = this;

                var parts = command.split('@');
                var buttonIndex = parts[1];
                var buttons = this.extension.getCustomToolbarSettingsButtons(this.documentType);

                if (!buttons[buttonIndex]) {
                    return;
                }

                var button = buttons[buttonIndex];
                var settingsProperties = button.properties;

                if ((typeof settingsProperties === 'undefined' ? 'undefined' : babelHelpers.typeof(settingsProperties)) !== 'object') {
                    throw new Error('Custom button properties must be an array');
                }

                var dataHolder = this.documentData;
                if (button.useViewBag) {
                    if (!this.viewBagComponent) {
                        throw new Error('View bag component not found');
                    }

                    dataHolder = JSON.parse(this.viewBagComponent.propertyValues);
                }

                $.oc.vueComponentHelpers.inspector.host.showModal(button.popupTitle ? button.popupTitle : this.documentSettingsPopupTitle, dataHolder, settingsProperties, 'cms-custom-settings', {
                    buttonText: this.trans('backend::lang.form.apply'),
                    resizableWidth: true,
                    beforeApplyCallback: function () {
                        var _ref3 = babelHelpers.asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee3(updatedDataObj) {
                            return regeneratorRuntime.wrap(function _callee3$(_context3) {
                                while (1) {
                                    switch (_context3.prev = _context3.next) {
                                        case 0:
                                            if (button.useViewBag) {
                                                _this.viewBagComponent.propertyValues = JSON.stringify(updatedDataObj);
                                            }

                                        case 1:
                                        case 'end':
                                            return _context3.stop();
                                    }
                                }
                            }, _callee3, _this);
                        }));

                        return function beforeApplyCallback(_x2) {
                            return _ref3.apply(this, arguments);
                        };
                    }()
                }).then($.noop, $.noop);
            },

            onEditorDragDrop: function onDragDrop(editor, ev) {
                ev.preventDefault();

                if (!this.defMarkup) {
                    return;
                }

                var currentModelUri = this.$refs.editor.getCurrentModelUri();
                if (currentModelUri != this.defMarkup.uriString) {
                    return;
                }

                var componentDefinitionStr = ev.dataTransfer.getData('application/october-component');
                if (!componentDefinitionStr) {
                    return;
                }

                var componentDefinition = JSON.parse(componentDefinitionStr);
                var alias = this.addComponent(componentDefinition);

                this.$refs.editor.insertText("{% component '" + alias + "' %}");
            },

            onComponentRemove: function onComponentRemove(component) {
                if (this.documentData.markup && this.defMarkup) {
                    var componentStr = "{% component '" + component.alias + "' %}";
                    this.$refs.editor.replaceText(componentStr, '', this.defMarkup);
                }
            },

            onParentTabSelected: function onParentTabSelected() {
                var _this2 = this;

                if (this.$refs.editor) {
                    this.$nextTick(function () {
                        return _this2.$refs.editor.layout();
                    });
                }
            },

            onToolbarCommand: function onToolbarCommand(command, isHotkey) {
                this.handleBasicDocumentCommands(command, isHotkey);

                if (command === 'show-components') {
                    this.store.dispatchCommand('cms:show-component-list');
                }

                if (command === 'update-template-file') {
                    this.saveDocument(false, null, {
                        updateTemplateFile: true
                    }).then($.noop, $.noop);
                }

                if (command === 'reset-from-template-file') {
                    this.resetFromTemplateFile().then($.noop, $.noop);
                }

                if (command === 'show-template-info') {
                    this.showTemplateInfo();
                }

                if (command.startsWith('custom-toolbar-button@')) {
                    this.handleCustomToolbarButton(command);
                }
            },

            onMonacoLoaded: function onMonacoLoaded(monaco, editor) {
                this.extension.intellisense.init(monaco, editor, this.$refs.editor);
                this.monacoLoaded();
            },

            onMonacoDispose: function onMonacoDispose(editor) {
                this.extension.intellisense.disposeForEditor(editor);
            },

            onEditorContextMenu: function onEditorContextMenu(payload) {
                this.extension.intellisense.onContextMenu(payload);
            },

            onEditorFilterSupportedActions: function onEditorFilterSupportedActions(payload) {
                this.extension.intellisense.onFilterSupportedActions(payload);
            },

            onEditorCustomEvent: function onEditorCustomEvent(eventName, payload) {
                if (eventName == 'expandComponent') {
                    this.expandComponent(payload);
                }
            },

            onApplicationCommand: function onApplicationCommand(command, payload) {
                if (command === 'cms:intellisense-trigger-completion') {
                    // Not in use
                    this.$refs.editor.editor.trigger(payload.source, 'editor.action.triggerSuggest', payload.options);
                }
            }
        }
    };

    return EditorDocumentComponentBase;
});
