$.oc.module.register('editor.extension.base', function () {
    'use strict';

    var ExtensionBase = function () {
        function ExtensionBase(namespace) {
            babelHelpers.classCallCheck(this, ExtensionBase);
            this.documentControllers = {};

            this.editorNamespace = namespace;

            this.state = {
                navigatorSections: [],
                createMenuItems: [],
                newDocumentData: {},
                settingsForms: {},
                customData: null
            };
        }

        babelHelpers.createClass(ExtensionBase, [{
            key: 'setInitialState',
            value: function setInitialState(initialState) {
                this.state.navigatorSections = initialState.navigatorSections;
                this.state.customData = initialState.customData;
                this.state.createMenuItems = initialState.createMenuItems;
                this.state.newDocumentData = initialState.newDocumentData;
                this.state.settingsForms = initialState.settingsForms;
                this.state.inspectorConfigurations = initialState.inspectorConfigurations;

                // Document controllers require the initial state to be set first.
                //
                this.makeDocumentControllers();
            }
        }, {
            key: 'trans',
            value: function trans(key) {
                return $.oc.editor.getLangStr(key);
            }
        }, {
            key: 'getInspectorConfiguration',
            value: function getInspectorConfiguration(name) {
                if (!this.state.inspectorConfigurations[name]) {
                    throw new Error('Inspector configuration ' + name + ' not found for the extension ' + this.editorNamespace);
                }

                return this.state.inspectorConfigurations[name];
            }
        }, {
            key: 'getDocumentController',
            value: function getDocumentController(documentType) {
                if (this.documentControllers[documentType]) {
                    return this.documentControllers[documentType];
                }

                throw new Error('Document controller not found for the document type ' + documentType);
            }
        }, {
            key: 'listDocumentControllerClasses',
            value: function listDocumentControllerClasses() {}
        }, {
            key: 'makeDocumentControllers',
            value: function makeDocumentControllers() {
                var _this = this;

                var controllerClasses = this.listDocumentControllerClasses();

                controllerClasses.forEach(function (controllerClass) {
                    var documentController = new controllerClass(_this);
                    var documentType = documentController.documentType;

                    if (_this.documentControllers[documentType]) {
                        throw new Error('A controller for document type ' + documentType + ' is already registered for the extension ' + _this.editorNamespace);
                    }

                    _this.documentControllers[documentType] = documentController;
                });
            }
        }, {
            key: 'getNewDocumentData',
            value: function getNewDocumentData(documentType) {
                if (!this.state.newDocumentData[documentType]) {
                    throw new Error('New document data not found for the document type ' + documentType);
                }

                var newDocumentData = this.state.newDocumentData[documentType];
                this.getDocumentController(documentType).preprocessNewDocumentData(newDocumentData);

                return newDocumentData;
            }
        }, {
            key: 'preprocessSettingsFields',
            value: function preprocessSettingsFields(settingsFields, documentType) {
                return this.getDocumentController(documentType).preprocessSettingsFields(settingsFields);
            }
        }, {
            key: 'hasSettingFormFields',
            value: function hasSettingFormFields(documentType) {
                return Array.isArray(this.state.settingsForms[documentType]) && this.state.settingsForms[documentType].length > 0;
            }
        }, {
            key: 'getSettingsFormFields',
            value: function getSettingsFormFields(documentType) {
                if (!this.state.settingsForms[documentType]) {
                    throw new Error('Settings form data not found for the document type ' + documentType);
                }

                var result = this.preprocessSettingsFields(this.state.settingsForms[documentType], documentType);

                return result;
            }
        }, {
            key: 'getNavigatorSections',
            value: function getNavigatorSections() {
                return this.state.navigatorSections;
            }
        }, {
            key: 'getCreateMenuItems',
            value: function getCreateMenuItems() {
                return this.state.createMenuItems;
            }
        }, {
            key: 'openDocumentByUniqueKey',
            value: function openDocumentByUniqueKey(documentUriStr) {
                $.oc.editor.application.openDocument(documentUriStr);
                return false;
            }

            /**
             * Emits an Editor command for the extension and all document controllers registered by the extension.
             * @param {String} commandString Command string. See editor.command.js.
             * @param {any} payload Payload.
             */

        }, {
            key: 'onCommand',
            value: function onCommand(commandString, payload) {
                this.documentControllerArray.forEach(function (documentController) {
                    return documentController.emit(commandString, payload);
                });
            }
        }, {
            key: 'updateNavigatorSections',
            value: function updateNavigatorSections(sections, documentType) {
                var _this2 = this;

                sections.forEach(function (serverSection) {
                    var treeviewUtils = $.oc.vueComponentHelpers.treeviewUtils;
                    var existingSection = _this2.state.navigatorSections.find(function (section) {
                        return section.uniqueKey === serverSection.uniqueKey;
                    });

                    serverSection.nodes.forEach(function (serverNode) {
                        var existingNode = treeviewUtils.findNodeObjectByKeyPathInSections(_this2.state.navigatorSections, [serverNode.uniqueKey]);

                        // Update only inner nodes in existing root nodes. We can't
                        // update the entire root node because it may contain
                        // configuration initialized on the client, for example CMS
                        // pages grouping, display property, sorting, etc.
                        //
                        if (existingNode) {
                            Vue.set(existingNode, 'nodes', serverNode.nodes);
                        } else if (existingSection) {
                            existingSection.nodes.push(serverNode);
                        }
                    });

                    if (!documentType) {
                        // If we are updating entire section -
                        // update the section label and menus as well.
                        //
                        if (existingSection) {
                            existingSection.createMenuItems = serverSection.createMenuItems;
                            existingSection.menuItems = serverSection.menuItems;
                            existingSection.label = serverSection.label;
                        }
                    }
                });
            }
        }, {
            key: 'customData',
            get: function get() {
                return this.state.customData;
            }
        }, {
            key: 'documentControllerArray',
            get: function get() {
                var _this3 = this;

                return Object.keys(this.documentControllers).map(function (key) {
                    return _this3.documentControllers[key];
                });
            }
        }, {
            key: 'editorStore',
            get: function get() {
                return $.oc.editor.store;
            }
        }, {
            key: 'editorApplication',
            get: function get() {
                return $.oc.editor.application;
            }
        }]);
        return ExtensionBase;
    }();

    return ExtensionBase;
});
