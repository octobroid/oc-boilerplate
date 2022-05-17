$.oc.module.register('editor.extension.documentcontroller.base', function () {
    'use strict';

    var EditorCommand = $.oc.module.import('editor.command');
    var DocumentUri = $.oc.module.import('editor.documenturi');

    var DocumentControllerBase = function () {
        function DocumentControllerBase(parentExtension) {
            babelHelpers.classCallCheck(this, DocumentControllerBase);

            this.parentExtension = parentExtension;
            this.commandListeners = [];

            this.initDefaultListeners();
            this.initListeners();
        }

        /**
         * Returns a document type name this controller can handle.
         */


        babelHelpers.createClass(DocumentControllerBase, [{
            key: 'on',


            /**
             * Adds handlers for one or more Editor commands.
             * Handlers are automatically bound to the document controller object.
             * If the command argument does not specify the command parameter (@xxx)
             * explicitly, the handler will be triggered for commands with any argument,
             * value. E.g. cms:create-document@cms-page would trigger handlers 
             * registered for both cms:create-document and cms:create-document@cms-page.
             * @param {String} commands A list of commands separated with a comma.
             * @param {function} callback A command handler function. Handlers receive the
             * Editor command object and optional payload.
             */
            value: function on(commands, callback) {
                var _this = this;

                var commandNames = commands.split(',').map(function (command) {
                    return command.trim();
                });

                commandNames.forEach(function (commandName) {
                    _this.commandListeners.push({
                        command: new EditorCommand(commandName),
                        callback: callback
                    });
                });
            }

            /**
             * Emits an Editor command and triggers all handlers previously registered 
             * for the command. Used by Editor internally.
             * @param {String} commandString Command string. See editor.command.js.
             * @param {any} payload Command payload.
             */

        }, {
            key: 'emit',
            value: function emit(commandString, payload) {
                var _this2 = this;

                var commandObj = new EditorCommand(commandString);

                this.commandListeners.forEach(function (listenerInfo) {
                    if (commandObj.matches(listenerInfo.command)) {
                        listenerInfo.callback.apply(_this2, [commandObj, payload]);
                    }
                });
            }
        }, {
            key: 'preprocessSettingsFields',


            /**
             * Updates JSON definition of the document settings form.
             * @param {Array} settingsFields 
             * @returns Array Returns updated JSON string
             */
            value: function preprocessSettingsFields(settingsFields) {
                return settingsFields;
            }

            /**
             * This method can update a new document object before it is passed to the document editor component.
             * @param {Object} newDocumentData 
             */

        }, {
            key: 'preprocessNewDocumentData',
            value: function preprocessNewDocumentData(newDocumentData) {}
        }, {
            key: 'initListeners',
            value: function initListeners() {}
        }, {
            key: 'initDefaultListeners',
            value: function initDefaultListeners() {
                this.on(this.editorNamespace + ':navigator-selected', this.onNavigatorNodeSelected);
                this.on(this.editorNamespace + ':create-document', this.onCreateDocument);
            }
        }, {
            key: 'extractDocumentUniqueKeyFromNodeKey',
            value: function extractDocumentUniqueKeyFromNodeKey(navigatorNodeKey) {
                // Node keys have format namespace:document-type:unique-key.
                // When we are creating or opening documents, we are only
                // interested in the document unique key.

                var parts = navigatorNodeKey.split(':');
                if (parts.length != 3) {
                    throw new Error('Navigator node unique keys must have format namespace:document-type:unique-key');
                }

                return parts[2];
            }
        }, {
            key: 'beforeDocumentOpen',
            value: function beforeDocumentOpen(commandObj, nodeData) {
                return true;
            }
        }, {
            key: 'trans',
            value: function trans(key) {
                return $.oc.editor.getLangStr(key);
            }

            /**
             * This method is called before a new document tab is opened.
             * The method can update the document data object.
             */

        }, {
            key: 'onBeforeDocumentCreated',
            value: function onBeforeDocumentCreated(commandObj, payload, documentData) {}
        }, {
            key: 'onNavigatorNodeSelected',
            value: function onNavigatorNodeSelected(commandObj, nodeData) {
                var uri = DocumentUri.parse(nodeData.uniqueKey);

                if (uri.namespaceAndDocType != this.rootNavigatorNodeKey) {
                    return;
                }

                if (this.beforeDocumentOpen(commandObj, nodeData) === false) {
                    return;
                }

                $.oc.editor.application.openTab({
                    key: nodeData.uniqueKey,
                    label: nodeData.label,
                    icon: nodeData.icon,
                    component: this.vueEditorComponentName,
                    componentData: {
                        key: this.extractDocumentUniqueKeyFromNodeKey(nodeData.uniqueKey),
                        namespace: this.editorNamespace,
                        documentType: this.documentType
                    }
                });
            }
        }, {
            key: 'onCreateDocument',
            value: function onCreateDocument(commandObj, payload) {
                if (!commandObj.hasParameter) {
                    throw new Error('Invalid create-document command: ' + commandObj.fullCommand + '. The command parameter is missing.');
                }

                if (commandObj.parameter != this.documentType) {
                    return;
                }

                var documentKey = $.oc.domIdManager.generate(this.documentType);
                var documentData = $.oc.vueUtils.getCleanObject(this.parentExtension.getNewDocumentData(this.documentType));

                this.onBeforeDocumentCreated(commandObj, payload, documentData);

                $.oc.editor.application.openTab({
                    key: this.editorNamespace + ':' + this.documentType + ':' + documentKey,
                    label: documentData.label,
                    icon: documentData.icon,
                    component: this.vueEditorComponentName,
                    componentData: {
                        key: documentKey,
                        metadata: documentData.metadata,
                        document: documentData.document,
                        namespace: this.editorNamespace,
                        documentType: this.documentType
                    }
                });
            }
        }, {
            key: 'documentType',
            get: function get() {
                throw new Error('documentType property must return string');
            }

            /**
             * Returns name of  Vue component that edits documents of this type.
             */

        }, {
            key: 'vueEditorComponentName',
            get: function get() {
                throw new Error('vueEditorComponent property must return string');
            }
        }, {
            key: 'editorNamespace',
            get: function get() {
                return this.parentExtension.editorNamespace;
            }
        }, {
            key: 'editorStore',
            get: function get() {
                return $.oc.editor.store;
            }
        }, {
            key: 'rootNavigatorNodeKey',
            get: function get() {
                return this.editorNamespace + ':' + this.documentType;
            }
        }, {
            key: 'rootNavigatorNodeSafe',
            get: function get() {
                var treeviewUtils = $.oc.vueComponentHelpers.treeviewUtils;
                var result = treeviewUtils.findNodeObjectByKeyPathInSections(this.parentExtension.state.navigatorSections, [this.rootNavigatorNodeKey]);

                if (!result) {
                    return null;
                }

                return result;
            }
        }, {
            key: 'rootNavigatorNode',
            get: function get() {
                var node = this.rootNavigatorNodeSafe;
                if (node === null) {
                    throw new Error('Navigator node with the key ' + this.documentType + ' is not found');
                }

                return node;
            }
        }]);
        return DocumentControllerBase;
    }();

    return DocumentControllerBase;
});
