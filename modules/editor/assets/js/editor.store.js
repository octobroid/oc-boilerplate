$.oc.module.register('editor.store', function () {
    'use strict';

    var StoreTabManager = $.oc.module.import('editor.store.tabmanager');
    var EditorCommand = $.oc.module.import('editor.command');
    var DocumentUri = $.oc.module.import('editor.documenturi');

    var EditorStore = function () {
        function EditorStore() {
            babelHelpers.classCallCheck(this, EditorStore);
            this.state = {};

            this.state = {
                navigatorSections: [],
                navigatorCreateMenuItems: [],
                navigatorSelectedUniqueKey: '',
                editorTabs: [],
                userData: {},
                params: {},
                lang: {}
            };

            this.tabManager = new StoreTabManager(this);
            this.extensions = {};
        }

        babelHelpers.createClass(EditorStore, [{
            key: 'initNavigatorSections',
            value: function initNavigatorSections() {
                for (var namespace in this.extensions) {
                    if (!this.extensions.hasOwnProperty(namespace)) {
                        continue;
                    }

                    var extensionNavigatorSections = this.extensions[namespace].getNavigatorSections();
                    this.state.navigatorSections = this.state.navigatorSections.concat(extensionNavigatorSections);
                }
            }
        }, {
            key: 'initCreateMenuItems',
            value: function initCreateMenuItems() {
                var _this = this;

                Object.keys(this.extensions).forEach(function (namespace) {
                    var extensionMenuItems = _this.extensions[namespace].getCreateMenuItems();
                    _this.state.navigatorCreateMenuItems = _this.state.navigatorCreateMenuItems.concat(extensionMenuItems);
                });
            }
        }, {
            key: 'getExtension',
            value: function getExtension(namespace) {
                if (this.extensions[namespace] === undefined) {
                    throw new Error('Editor extension instance not found for the namespace ' + namespace);
                }

                return this.extensions[namespace];
            }
        }, {
            key: 'triggerDocumentNodesUpdatedEvent',
            value: function triggerDocumentNodesUpdatedEvent(uri) {
                if (!uri) {
                    return;
                }

                var commandName = uri.namespace + ':navigator-nodes-updated';
                if (uri.documentType) {
                    commandName += '@' + uri.documentType;
                }

                this.dispatchCommand(commandName);
            }
        }, {
            key: 'refreshExtensionNavigatorNodes',
            value: function refreshExtensionNavigatorNodes(namespace, documentType) {
                var _this2 = this;

                return $.oc.editor.application.ajaxRequest('onListExtensionNavigatorSections', {
                    extension: namespace,
                    documentType: documentType ? documentType : ''
                }).then(function (data) {
                    _this2.getExtension(namespace).updateNavigatorSections(data.sections, documentType);
                    _this2.triggerDocumentNodesUpdatedEvent(new DocumentUri(namespace, documentType, null));
                });
            }
        }, {
            key: 'setInitialState',
            value: function setInitialState(initialState) {
                var extensionStates = initialState.extensions;

                for (var namespace in extensionStates) {
                    if (!extensionStates.hasOwnProperty(namespace)) {
                        continue;
                    }

                    var extensionClassNamespace = 'editor.extension.' + namespace + '.main';
                    if (!$.oc.module.exists(extensionClassNamespace)) {
                        throw new Error('Editor extension module is not registered: ' + extensionClassNamespace);
                    }

                    var ExtensionClass = $.oc.module.import(extensionClassNamespace);
                    var extension = new ExtensionClass(namespace);
                    var extensionInitialState = extensionStates[namespace];

                    extension.setInitialState(extensionInitialState);
                    if (babelHelpers.typeof(extensionInitialState.langStrings) === 'object') {
                        $.extend(this.state.lang, extensionInitialState.langStrings);
                    }

                    this.extensions[namespace] = extension;
                }

                $.extend(this.state.lang, initialState.langStrings);
                $.extend(this.state.userData, initialState.userData);
                $.extend(this.state.params, babelHelpers.typeof(initialState.params) === 'object' ? initialState.params : {});

                this.initNavigatorSections();
                this.initCreateMenuItems();
            }

            /**
             * Dispatches Editor command for an extension.
             * @param {String} cmd Command name or EditorCommand object. The name must start with the "extension:" prefix.
             * @param {any} payload Payload
             */

        }, {
            key: 'dispatchCommand',
            value: function dispatchCommand(command, payload) {
                var _this3 = this;

                var namespace = '';
                if (typeof command === 'string') {
                    var namespaceAndCmd = command.split(':');

                    if (namespaceAndCmd.length < 2) {
                        throw new Error('Cannot dispatch command without an extension namespace: ' + command);
                    }

                    namespace = namespaceAndCmd[0];
                } else {
                    if (command instanceof EditorCommand) {
                        namespace = command.namespace;
                        if (!namespace) {
                            throw new Error('Cannot dispatch command without an extension namespace: ' + command.fullCommand);
                        }
                    } else {
                        throw new Error('store.dispatch can accept only a string or EditorCommand object');
                    }
                }

                if (namespace === 'global') {
                    Object.keys(this.extensions).forEach(function (namespace) {
                        _this3.extensions[namespace].onCommand(command, payload);
                    });
                } else {
                    this.getExtension(namespace).onCommand(command, payload);
                }
            }
        }, {
            key: 'findNavigatorNode',
            value: function findNavigatorNode(key) {
                return $.oc.vueComponentHelpers.treeviewUtils.findNodeByKeyInSections(this.state.navigatorSections, key);
            }
        }, {
            key: 'deleteNavigatorNode',
            value: function deleteNavigatorNode(key) {
                $.oc.vueComponentHelpers.treeviewUtils.deleteNodeByKeyInSections(this.state.navigatorSections, key);

                this.triggerDocumentNodesUpdatedEvent(DocumentUri.parse(key, true));
            }
        }]);
        return EditorStore;
    }();

    return EditorStore;
});
