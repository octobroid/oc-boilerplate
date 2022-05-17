$.oc.module.register('editor.store', function() {
    'use strict';

    const StoreTabManager = $.oc.module.import('editor.store.tabmanager');
    const EditorCommand = $.oc.module.import('editor.command');
    const DocumentUri = $.oc.module.import('editor.documenturi');

    class EditorStore {
        state = {};
        tabManager;
        extensions;

        constructor() {
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

        initNavigatorSections() {
            for (let namespace in this.extensions) {
                if (!this.extensions.hasOwnProperty(namespace)) {
                    continue;
                }

                const extensionNavigatorSections = this.extensions[namespace].getNavigatorSections();
                this.state.navigatorSections = this.state.navigatorSections.concat(extensionNavigatorSections);
            }
        }

        initCreateMenuItems() {
            Object.keys(this.extensions).forEach((namespace) => {
                const extensionMenuItems = this.extensions[namespace].getCreateMenuItems();
                this.state.navigatorCreateMenuItems = this.state.navigatorCreateMenuItems.concat(extensionMenuItems);
            });
        }

        getExtension(namespace) {
            if (this.extensions[namespace] === undefined) {
                throw new Error(`Editor extension instance not found for the namespace ${namespace}`);
            }

            return this.extensions[namespace];
        }

        triggerDocumentNodesUpdatedEvent(uri) {
            if (!uri) {
                return;
            }

            let commandName = uri.namespace + ':navigator-nodes-updated';
            if (uri.documentType) {
                commandName += '@' + uri.documentType;
            }

            this.dispatchCommand(commandName);
        }

        refreshExtensionNavigatorNodes(namespace, documentType) {
            return $.oc.editor.application
                .ajaxRequest('onListExtensionNavigatorSections', {
                    extension: namespace,
                    documentType: documentType ? documentType : ''
                })
                .then((data) => {
                    this.getExtension(namespace).updateNavigatorSections(data.sections, documentType);
                    this.triggerDocumentNodesUpdatedEvent(new DocumentUri(namespace, documentType, null));
                });
        }

        setInitialState(initialState) {
            const extensionStates = initialState.extensions;

            for (let namespace in extensionStates) {
                if (!extensionStates.hasOwnProperty(namespace)) {
                    continue;
                }

                const extensionClassNamespace = 'editor.extension.' + namespace + '.main';
                if (!$.oc.module.exists(extensionClassNamespace)) {
                    throw new Error(`Editor extension module is not registered: ${extensionClassNamespace}`);
                }

                const ExtensionClass = $.oc.module.import(extensionClassNamespace);
                const extension = new ExtensionClass(namespace);
                const extensionInitialState = extensionStates[namespace];

                extension.setInitialState(extensionInitialState);
                if (typeof extensionInitialState.langStrings === 'object') {
                    $.extend(this.state.lang, extensionInitialState.langStrings);
                }

                this.extensions[namespace] = extension;
            }

            $.extend(this.state.lang, initialState.langStrings);
            $.extend(this.state.userData, initialState.userData);
            $.extend(this.state.params, typeof initialState.params === 'object' ? initialState.params : {});


            this.initNavigatorSections();
            this.initCreateMenuItems();
        }

        /**
         * Dispatches Editor command for an extension.
         * @param {String} cmd Command name or EditorCommand object. The name must start with the "extension:" prefix.
         * @param {any} payload Payload
         */
        dispatchCommand(command, payload) {
            let namespace = '';
            if (typeof command === 'string') {
                const namespaceAndCmd = command.split(':');

                if (namespaceAndCmd.length < 2) {
                    throw new Error(`Cannot dispatch command without an extension namespace: ${command}`);
                }

                namespace = namespaceAndCmd[0];
            }
            else {
                if (command instanceof EditorCommand) {
                    namespace = command.namespace;
                    if (!namespace) {
                        throw new Error(
                            `Cannot dispatch command without an extension namespace: ${command.fullCommand}`
                        );
                    }
                }
                else {
                    throw new Error('store.dispatch can accept only a string or EditorCommand object');
                }
            }

            if (namespace === 'global') {
                Object.keys(this.extensions).forEach((namespace) => {
                    this.extensions[namespace].onCommand(command, payload);
                });
            }
            else {
                this.getExtension(namespace).onCommand(command, payload);
            }
        }

        findNavigatorNode(key) {
            return $.oc.vueComponentHelpers.treeviewUtils.findNodeByKeyInSections(this.state.navigatorSections, key);
        }

        deleteNavigatorNode(key) {
            $.oc.vueComponentHelpers.treeviewUtils.deleteNodeByKeyInSections(this.state.navigatorSections, key);

            this.triggerDocumentNodesUpdatedEvent(DocumentUri.parse(key, true));
        }
    }

    return EditorStore;
});
