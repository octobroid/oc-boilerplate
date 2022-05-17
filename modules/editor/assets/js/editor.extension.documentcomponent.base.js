$.oc.module.register('editor.extension.documentcomponent.base', function () {
    'use strict';

    var EditorTimeoutPromise = $.oc.module.import('editor.timeoutpromise');
    var DocumentUri = $.oc.module.import('editor.documenturi');

    function patchDocumentMetadata(documentMetadata, responseMetadata) {
        if (!responseMetadata) {
            return;
        }

        Object.keys(responseMetadata).forEach(function (property) {
            documentMetadata[property] = responseMetadata[property];
        });

        Vue.delete(documentMetadata, 'isNewDocument');
    }

    var DocumentComponentBase = {
        mixins: [$.oc.vueHotkeyMixin],
        props: {
            componentData: Object
        },
        data: function data() {
            var result = {
                initializing: true,
                processing: false,
                errorLoadingDocument: null,
                documentHeaderCollapsed: false,
                documentFullScreen: false,
                documentData: {},
                lastSavedDocumentData: {},
                documentMetadata: {},
                ajaxQueue: new Queue(1, 10000),
                loadingPromises: [],
                autoUpdateNavigatorNodeLabel: true,

                documentSavedMessage: $.oc.editor.getLangStr('editor::lang.common.document_saved'),
                documentReloadedMessage: $.oc.editor.getLangStr('editor::lang.common.document_reloaded'),
                documentDeletedMessage: $.oc.editor.getLangStr('editor::lang.common.document_deleted'),
                documentSettingsPopupTitle: $.oc.editor.getLangStr('editor::lang.common.document'),
                documentTitleProperty: 'title',

                componentHotkeys: {
                    'shift+option+w': this.onCloseTabHotkey
                }
            };

            if (this.componentData.metadata) {
                result.documentMetadata = $.oc.vueUtils.getCleanObject(this.componentData.metadata);
            }

            if (this.componentData.document) {
                result.documentData = $.oc.vueUtils.getCleanObject(this.componentData.document);
            }

            return result;
        },

        computed: {
            isDocumentChanged: function computeIsDocumentChanged() {
                if (this.initializing) {
                    return false;
                }

                if (this.isNewDocument) {
                    return true;
                }

                var current = JSON.stringify(this.cleanDocumentData);
                var saved = JSON.stringify(this.lastSavedDocumentData);

                return current != saved;
            },

            cleanDocumentData: function computeCleanDocumentData() {
                return $.oc.vueUtils.getCleanObject(this.documentData);
            },

            documentNavigatorNode: function computeDocumentNavigatorNode() {
                return this.store.findNavigatorNode(this.documentUri);
            },

            namespace: function computeNamespace() {
                return this.componentData.namespace;
            },

            extension: function computeExtension() {
                return this.store.getExtension(this.namespace);
            },

            documentType: function computeDocumentType() {
                return this.componentData.documentType;
            },

            isNewDocument: function computeIsNewDocument() {
                if (this.documentMetadata.isNewDocument) {
                    return true;
                }

                return false;
            },

            documentUriObj: function computeDocumentUriObj() {
                return new DocumentUri(this.namespace, this.documentType, this.componentData.key);
            },

            documentUri: function computeDocumentUri() {
                return this.documentUriObj.uri;
            },

            store: function computeStore() {
                return $.oc.editor.store;
            },

            application: function computeApplication() {
                return $.oc.editor.application;
            },

            hasSettingsForm: function computeHasSettingsForm() {
                return this.extension.hasSettingFormFields(this.documentType);
            },

            editorUserData: function computeUserData() {
                return this.store.state.userData;
            },

            isDirectDocumentMode: function computeIsDirectDocumentMode() {
                return $.oc.editor.application.isDirectDocumentMode;
            },

            directDocumentIcon: function computeDirectDocumentIcon() {
                return this.isDirectDocumentMode ? this.componentData.tabIcon : null;
            }
        },

        methods: {
            ajaxRequest: function ajaxRequest(handler, requestData) {
                var promise = this.ajaxQueue.add(function () {
                    return $.oc.editor.application.ajaxRequest(handler, requestData);
                });

                this.loadingPromises.push(promise);

                return promise;
            },

            trans: function trans(key) {
                return $.oc.editor.getLangStr(key);
            },

            updateTabLabel: function updateTabLabel(label) {
                this.store.tabManager.updateTabLabel(label, this.documentUri);
            },

            setTabHasChanges: function setTabHasChanges(hasChanges) {
                this.store.tabManager.setTabHasChanges(hasChanges, this.documentUri);
            },

            documentLoaded: function documentLoaded(data) {},

            documentCreated: function documentCreated() {},

            documentCreatedOrLoaded: function documentCreatedOrLoaded() {},

            documentSaved: function documentSaved(data, prevData) {},

            requestDocumentFromServer: function () {
                var _ref = babelHelpers.asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(extraData, suppressGlobalDocumentError) {
                    var data;
                    return regeneratorRuntime.wrap(function _callee$(_context) {
                        while (1) {
                            switch (_context.prev = _context.next) {
                                case 0:
                                    _context.prev = 0;
                                    _context.next = 3;
                                    return this.loadDocument(this.namespace, {
                                        type: this.documentType,
                                        key: this.componentData.key
                                    }, extraData, suppressGlobalDocumentError);

                                case 3:
                                    data = _context.sent;


                                    this.lastSavedDocumentData = $.oc.vueUtils.getCleanObject(data.document);
                                    this.documentData = data.document;
                                    this.documentMetadata = data.metadata;

                                    return _context.abrupt('return', data);

                                case 10:
                                    _context.prev = 10;
                                    _context.t0 = _context['catch'](0);

                                    if (!suppressGlobalDocumentError) {
                                        this.$emit('tabfatalerror');
                                    }
                                    return _context.abrupt('return', _context.t0);

                                case 14:
                                case 'end':
                                    return _context.stop();
                            }
                        }
                    }, _callee, this, [[0, 10]]);
                }));

                function requestDocumentFromServer(_x, _x2) {
                    return _ref.apply(this, arguments);
                }

                return requestDocumentFromServer;
            }(),

            loadDocument: function () {
                var _ref2 = babelHelpers.asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(extension, documentData, extraData, suppressGlobalDocumentError) {
                    var timeoutPromise, data;
                    return regeneratorRuntime.wrap(function _callee2$(_context2) {
                        while (1) {
                            switch (_context2.prev = _context2.next) {
                                case 0:
                                    timeoutPromise = new EditorTimeoutPromise();
                                    _context2.prev = 1;
                                    _context2.next = 4;
                                    return this.ajaxRequest('onCommand', {
                                        extension: extension,
                                        command: 'onOpenDocument',
                                        documentData: documentData,
                                        extraData: (typeof extraData === 'undefined' ? 'undefined' : babelHelpers.typeof(extraData)) === 'object' ? extraData : {}
                                    });

                                case 4:
                                    data = _context2.sent;
                                    _context2.next = 7;
                                    return timeoutPromise.make(data);

                                case 7:

                                    this.initializing = false;
                                    this.processing = false;

                                    return _context2.abrupt('return', data);

                                case 12:
                                    _context2.prev = 12;
                                    _context2.t0 = _context2['catch'](1);

                                    if (!suppressGlobalDocumentError) {
                                        if (_context2.t0.status === 0) {
                                            this.errorLoadingDocument = 'Error connecting to the server.';
                                        } else {
                                            this.errorLoadingDocument = _context2.t0.responseText;
                                        }
                                    }
                                    this.initializing = false;
                                    this.processing = false;

                                    return _context2.abrupt('return', _context2.t0);

                                case 18:
                                case 'end':
                                    return _context2.stop();
                            }
                        }
                    }, _callee2, this, [[1, 12]]);
                }));

                function loadDocument(_x3, _x4, _x5, _x6) {
                    return _ref2.apply(this, arguments);
                }

                return loadDocument;
            }(),

            getSaveDocumentData: function getSaveDocumentData(inspectorDocumentData) {
                throw new Error('getSaveDocumentData must be implemented in DocumentComponentBase descendants.');
            },

            getMainUiDocumentProperties: function getMainUiDocumentProperties() {
                throw new Error('getMainUiDocumentProperties must be implemented in DocumentComponentBase descendants. This method must return a list of properties that can be edited without opening the Settings popup.');
            },

            getConflictResolver: function getConflictResolver() {
                if (!this.$refs.conflictResolver) {
                    throw new Error('conflictResolver reference must exist.');
                }

                return this.$refs.conflictResolver;
            },

            getDocumentSavedMessage: function getDocumentSavedMessage(responseData) {
                return this.documentSavedMessage;
            },

            saveDocument: function () {
                var _ref3 = babelHelpers.asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee3(force, inspectorDocumentData, extraData) {
                    var _this = this;

                    var documentData, timeoutPromise, lastSavedData, isNewDocument, data, prevDocumentData;
                    return regeneratorRuntime.wrap(function _callee3$(_context3) {
                        while (1) {
                            switch (_context3.prev = _context3.next) {
                                case 0:
                                    $(document).trigger('vue.beforeRequest');

                                    documentData = this.getSaveDocumentData(inspectorDocumentData);
                                    timeoutPromise = new EditorTimeoutPromise();
                                    lastSavedData = inspectorDocumentData ? inspectorDocumentData : this.documentData;
                                    isNewDocument = this.documentMetadata.isNewDocument;


                                    this.processing = true;

                                    _context3.prev = 6;
                                    _context3.next = 9;
                                    return this.ajaxRequest('onCommand', {
                                        extension: this.namespace,
                                        command: 'onSaveDocument',
                                        documentData: documentData,
                                        documentMetadata: this.documentMetadata,
                                        documentForceSave: force ? 1 : 0,
                                        extraData: (typeof extraData === 'undefined' ? 'undefined' : babelHelpers.typeof(extraData)) === 'object' ? extraData : null
                                    });

                                case 9:
                                    data = _context3.sent;
                                    _context3.next = 12;
                                    return timeoutPromise.make(data);

                                case 12:

                                    this.processing = false;

                                    if (!data.mtimeMismatch) {
                                        _context3.next = 17;
                                        break;
                                    }

                                    return _context3.abrupt('return', this.handleDocumentTimeMismatch(inspectorDocumentData));

                                case 17:
                                    patchDocumentMetadata(this.documentMetadata, data.metadata);

                                    prevDocumentData = this.lastSavedDocumentData;

                                    this.lastSavedDocumentData = $.oc.vueUtils.getCleanObject(lastSavedData);

                                    if (isNewDocument) {
                                        this.store.refreshExtensionNavigatorNodes(this.namespace, this.documentType).then(function () {
                                            $.oc.editor.application.revealNavigatorNode(_this.documentUri);
                                        });
                                    }

                                    $.oc.snackbar.show(this.getDocumentSavedMessage(data));

                                    this.documentSaved(data, prevDocumentData);

                                    $.oc.editor.application.postDirectDocumentSavedMessage();

                                    return _context3.abrupt('return', data);

                                case 25:
                                    _context3.next = 30;
                                    break;

                                case 27:
                                    _context3.prev = 27;
                                    _context3.t0 = _context3['catch'](6);
                                    return _context3.abrupt('return', this.handleDocumentSaveError(_context3.t0, inspectorDocumentData));

                                case 30:
                                case 'end':
                                    return _context3.stop();
                            }
                        }
                    }, _callee3, this, [[6, 27]]);
                }));

                function saveDocument(_x7, _x8, _x9) {
                    return _ref3.apply(this, arguments);
                }

                return saveDocument;
            }(),

            handleDocumentTimeMismatch: function () {
                var _ref4 = babelHelpers.asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee4(inspectorDocumentData) {
                    var resolution, data;
                    return regeneratorRuntime.wrap(function _callee4$(_context4) {
                        while (1) {
                            switch (_context4.prev = _context4.next) {
                                case 0:
                                    resolution = null;
                                    _context4.prev = 1;
                                    _context4.next = 4;
                                    return this.getConflictResolver().requestResolution();

                                case 4:
                                    resolution = _context4.sent;
                                    _context4.next = 10;
                                    break;

                                case 7:
                                    _context4.prev = 7;
                                    _context4.t0 = _context4['catch'](1);
                                    return _context4.abrupt('return', _context4.t0);

                                case 10:
                                    if (!(resolution == 'save')) {
                                        _context4.next = 12;
                                        break;
                                    }

                                    return _context4.abrupt('return', this.saveDocument(true, inspectorDocumentData));

                                case 12:

                                    // Reloading the document
                                    //
                                    this.processing = true;
                                    _context4.next = 15;
                                    return this.requestDocumentFromServer();

                                case 15:
                                    data = _context4.sent;

                                    $.oc.snackbar.show(this.documentReloadedMessage);

                                    // The order of these hooks are important
                                    //
                                    this.documentCreatedOrLoaded();
                                    this.documentLoaded(data);
                                    return _context4.abrupt('return', data);

                                case 20:
                                case 'end':
                                    return _context4.stop();
                            }
                        }
                    }, _callee4, this, [[1, 7]]);
                }));

                function handleDocumentTimeMismatch(_x10) {
                    return _ref4.apply(this, arguments);
                }

                return handleDocumentTimeMismatch;
            }(),

            handleDocumentSaveError: function handleDocumentSaveError(error, inspectorDocumentData) {
                this.processing = false;
                var errorText = error.responseText;
                if (error.responseJSON && error.responseJSON.validationErrors) {
                    var validationErrors = error.responseJSON.validationErrors;
                    var keys = Object.keys(validationErrors);
                    var firstFieldName = keys[0];
                    var message = validationErrors[firstFieldName][0];

                    if (message) {
                        errorText = message;
                    }

                    if (!inspectorDocumentData && firstFieldName && this.getMainUiDocumentProperties().indexOf(firstFieldName) === -1) {
                        this.openSettingsForm();
                    }
                }

                if (!errorText && error.status === 0) {
                    errorText = 'Error connecting to the server.';
                }

                $.oc.vueComponentHelpers.modalUtils.showAlert($.oc.editor.getLangStr('editor::lang.common.error_saving'), errorText);

                return error;
            },

            closeDocumentTab: function closeDocumentTab(force) {
                if (force) {
                    this.setTabHasChanges(false);
                }

                this.store.tabManager.closeTabByKey(this.documentUri);
            },

            deleteDocument: function () {
                var _ref5 = babelHelpers.asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee5(extension) {
                    var data;
                    return regeneratorRuntime.wrap(function _callee5$(_context5) {
                        while (1) {
                            switch (_context5.prev = _context5.next) {
                                case 0:
                                    _context5.prev = 0;
                                    _context5.next = 3;
                                    return $.oc.vueComponentHelpers.modalUtils.showConfirm($.oc.editor.getLangStr('backend::lang.form.delete'), $.oc.editor.getLangStr('editor::lang.common.confirm_delete'), {
                                        isDanger: true,
                                        buttonText: $.oc.editor.getLangStr('backend::lang.form.confirm')
                                    });

                                case 3:
                                    _context5.next = 8;
                                    break;

                                case 5:
                                    _context5.prev = 5;
                                    _context5.t0 = _context5['catch'](0);
                                    return _context5.abrupt('return', _context5.t0);

                                case 8:

                                    this.processing = true;

                                    _context5.prev = 9;
                                    _context5.next = 12;
                                    return this.ajaxRequest('onCommand', {
                                        extension: this.namespace,
                                        command: 'onDeleteDocument',
                                        documentMetadata: this.documentMetadata
                                    });

                                case 12:
                                    data = _context5.sent;


                                    this.processing = false;
                                    this.closeDocumentTab(true);
                                    this.store.deleteNavigatorNode(this.documentUri);
                                    $.oc.snackbar.show(this.documentDeletedMessage);

                                    return _context5.abrupt('return', data);

                                case 20:
                                    _context5.prev = 20;
                                    _context5.t1 = _context5['catch'](9);

                                    this.processing = false;

                                    $.oc.editor.page.showAjaxErrorAlert(_context5.t1, $.oc.editor.getLangStr('editor::lang.common.error_deleting'));

                                    return _context5.abrupt('return', _context5.t1);

                                case 25:
                                case 'end':
                                    return _context5.stop();
                            }
                        }
                    }, _callee5, this, [[0, 5], [9, 20]]);
                }));

                function deleteDocument(_x11) {
                    return _ref5.apply(this, arguments);
                }

                return deleteDocument;
            }(),

            openSettingsForm: function openSettingsForm() {
                var settingsFields = this.extension.getSettingsFormFields(this.documentType);

                $.oc.vueComponentHelpers.inspector.host.showModal(this.documentSettingsPopupTitle, this.documentData, settingsFields, 'editor-document-settings', {
                    buttonText: this.trans('editor::lang.common.apply_and_save'),
                    resizableWidth: true,
                    beforeApplyCallback: this.onBeforeSettingsInspectorApply
                }).then($.noop, $.noop);
            },

            updateNavigatorNodeUserData: function updateNavigatorNodeUserData(title) {},

            updateEditorUiForDocument: function updateEditorUiForDocument() {
                var title = this.documentData[this.documentTitleProperty].length > 0 ? this.documentData[this.documentTitleProperty] : 'No name';
                this.updateTabLabel(title);

                if (this.documentNavigatorNode && this.documentMetadata.uniqueKey) {
                    if (this.autoUpdateNavigatorNodeLabel) {
                        this.store.triggerDocumentNodesUpdatedEvent(this.documentUriObj);
                        this.documentNavigatorNode.label = title;
                    }

                    this.updateNavigatorNodeUserData(title);
                }

                // This propagates the new unique key to the
                // Navigator and Tabs.
                //
                if (this.documentMetadata.uniqueKey) {
                    this.componentData.key = this.documentMetadata.uniqueKey;
                }
            },

            isDocumentTabVisible: function isDocumentTabVisible() {
                return $(this.$el).is(':visible');
            },

            onBeforeSettingsInspectorApply: function onBeforeSettingsInspectorApply(inspectorDocumentData) {
                return this.saveDocument(false, inspectorDocumentData);
            },

            onParentTabClose: function onParentTabClose() {
                if (!this.isDocumentChanged || this.errorLoadingDocument) {
                    return Promise.resolve();
                }

                return $.oc.vueComponentHelpers.modalUtils.showConfirm($.oc.editor.getLangStr('backend::lang.tabs.close'), $.oc.editor.getLangStr('backend::lang.form.confirm_tab_close'), {
                    isDanger: true,
                    buttonText: $.oc.editor.getLangStr('editor::lang.common.discard_changes')
                });
            },

            onApplicationCommand: function onApplicationCommand(command, payload) {},

            handleBasicDocumentCommands: function handleBasicDocumentCommands(command, isHotkey) {
                if (isHotkey && (!this.isDocumentTabVisible() || $.oc.modalFocusManager.hasHotkeyBlockingAbove(null))) {
                    return;
                }

                if (command == 'save') {
                    this.saveDocument().then($.noop, $.noop);
                }

                if (command == 'delete') {
                    this.deleteDocument(this.namespace);
                }

                if (command == 'settings') {
                    this.openSettingsForm();
                }

                if (command == 'document:toggleToolbar') {
                    this.documentHeaderCollapsed = !this.documentHeaderCollapsed;
                }

                if (command == 'document:toggleFullscreen') {
                    this.documentFullScreen = !this.documentFullScreen;
                }
            },

            onCloseTabHotkey: function onCloseTabHotkey(ev) {
                ev.preventDefault();

                if (!this.isDocumentTabVisible() || $.oc.modalFocusManager.hasHotkeyBlockingAbove(null)) {
                    return;
                }

                this.$emit('tabclose');
            },

            onDocumentCloseClick: function onDocumentCloseClick() {
                $.oc.editor.application.onCloseDirectDocumentClick();
            }
        },

        beforeDestroy: function onBeforeDestroy() {
            this.loadingPromises.forEach(function (promise) {
                if (promise.isPending()) {
                    promise.cancel();
                }
            });
        },

        watch: {
            isDocumentChanged: {
                handler: function watchIsDocumentChanged(value) {
                    this.setTabHasChanges(value);
                },
                immediate: true
            },

            lastSavedDocumentData: function watchSavedDocumentData(value) {
                this.updateEditorUiForDocument(value);
            },

            'componentData.key': function watchComponentDataKey(value, oldValue) {
                var oldUri = new DocumentUri(this.namespace, this.documentType, oldValue).uri;
                var navigatorNode = this.store.findNavigatorNode(oldUri);

                if (navigatorNode) {
                    navigatorNode.uniqueKey = this.documentUri;
                    $.oc.editor.application.navigatorNodeKeyChanged(oldUri, this.documentUri);
                }

                this.store.tabManager.setTabKey(oldUri, this.documentUri);
                this.$emit('tabkeychanged', oldUri, this.documentUri);
            },

            initializing: function onInitializingChanged(value) {
                var _this2 = this;

                if (!value) {
                    Vue.nextTick(function () {
                        if (_this2.$refs.documentHeader) {
                            _this2.$refs.documentHeader.focusTitle();
                        }
                    });
                }
            }
        },

        mounted: function onMounted() {
            var _this3 = this;

            if (!this.documentMetadata || !this.documentMetadata.isNewDocument) {
                this.requestDocumentFromServer().then(function (data) {
                    // The order of these hooks are important
                    //
                    _this3.documentCreatedOrLoaded();
                    _this3.documentLoaded(data);
                }, $.noop);
            } else {
                this.initializing = false;
                // The order of these hooks are important
                //
                this.documentCreatedOrLoaded();
                this.documentCreated();
            }
        }
    };

    return DocumentComponentBase;
});
