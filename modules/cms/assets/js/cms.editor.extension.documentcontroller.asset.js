$.oc.module.register('cms.editor.extension.documentcontroller.asset', function () {
    'use strict';

    var DocumentControllerBase = $.oc.module.import('editor.extension.documentcontroller.base');
    var treeviewUtils = $.oc.vueComponentHelpers.treeviewUtils;
    var EditorCommand = $.oc.module.import('editor.command');

    var DocumentControllerAsset = function (_DocumentControllerBa) {
        babelHelpers.inherits(DocumentControllerAsset, _DocumentControllerBa);

        function DocumentControllerAsset() {
            babelHelpers.classCallCheck(this, DocumentControllerAsset);
            return babelHelpers.possibleConstructorReturn(this, (DocumentControllerAsset.__proto__ || Object.getPrototypeOf(DocumentControllerAsset)).apply(this, arguments));
        }

        babelHelpers.createClass(DocumentControllerAsset, [{
            key: 'beforeDocumentOpen',
            value: function beforeDocumentOpen(commandObj, nodeData) {
                if (!nodeData.userData) {
                    return false;
                }

                if (nodeData.userData.isFolder) {
                    return false;
                }

                if (nodeData.userData.isEditable) {
                    return true;
                }

                return false;
            }
        }, {
            key: 'initListeners',
            value: function initListeners() {
                this.on('cms:navigator-context-menu-display', this.getNavigatorContextMenuItems);
                this.on('cms:cms-asset-create-directory', this.onCreateDirectory);
                this.on('cms:cms-asset-delete', this.onDeleteAssetOrDirectory);
                this.on('cms:cms-asset-rename', this.onRenameAssetOrDirectory);
                this.on('cms:navigator-node-moved', this.onNavigatorNodeMoved);
                this.on('cms:navigator-external-drop', this.onNavigatorExternalDrop);
                this.on('cms:cms-asset-upload', this.onUploadDocument);
                this.on('cms:cms-asset-open', this.onOpenAsset);
                this.on('cms:navigator-nodes-updated', this.onNavigatorNodesUpdated);
            }
        }, {
            key: 'getNavigatorContextMenuItems',
            value: function getNavigatorContextMenuItems(commandObj, payload) {
                var DocumentUri = $.oc.module.import('editor.documenturi');
                var uri = DocumentUri.parse(payload.nodeData.uniqueKey);
                var parentPath = payload.nodeData.userData.path;

                if (uri.documentType !== this.documentType) {
                    return;
                }

                if (payload.nodeData.userData.isFolder) {
                    payload.menuItems.push({
                        type: 'text',
                        command: new EditorCommand('cms:create-document@' + this.documentType, {
                            path: parentPath
                        }),
                        label: this.trans('cms::lang.asset.new')
                    });

                    payload.menuItems.push({
                        type: 'text',
                        command: new EditorCommand('cms:cms-asset-upload@' + this.documentType, {
                            path: parentPath
                        }),
                        label: this.trans('cms::lang.asset.upload_files')
                    });

                    payload.menuItems.push({
                        type: 'text',
                        command: 'cms:cms-asset-create-directory@' + parentPath,
                        label: this.trans('cms::lang.asset.create_directory')
                    });

                    payload.menuItems.push({
                        type: 'separator'
                    });
                } else {
                    if (!payload.nodeData.userData.isEditable) {
                        payload.menuItems.push({
                            type: 'text',
                            command: new EditorCommand('cms:cms-asset-open@' + parentPath, {
                                url: payload.nodeData.userData.url
                            }),
                            label: this.trans('cms::lang.asset.open')
                        });
                    }
                }

                payload.menuItems.push({
                    type: 'text',
                    command: new EditorCommand('cms:cms-asset-rename@' + parentPath, {
                        fileName: payload.nodeData.userData.filename
                    }),
                    label: this.trans('cms::lang.asset.rename')
                });

                payload.menuItems.push({
                    type: 'text',
                    command: new EditorCommand('cms:cms-asset-delete@' + parentPath, {
                        itemsDetails: payload.itemsDetails
                    }),
                    label: this.trans('cms::lang.asset.delete')
                });
            }
        }, {
            key: 'getAllAssetFilenames',
            value: function getAllAssetFilenames() {
                if (this.cachedAssetList) {
                    return this.cachedAssetList;
                }

                var assetsNavigatorNode = treeviewUtils.findNodeByKeyInSections(this.parentExtension.state.navigatorSections, 'cms:cms-asset');

                var assetList = [];

                if (assetsNavigatorNode) {
                    assetList = treeviewUtils.getFlattenNodes(assetsNavigatorNode.nodes).filter(function (assetNode) {
                        return !assetNode.userData.isFolder;
                    }).map(function (assetNode) {
                        return assetNode.userData.path;
                    });
                } else {
                    assetList = this.parentExtension.state.customData.assets;
                }

                this.cachedAssetList = assetList;
                return assetList;
            }
        }, {
            key: 'onBeforeDocumentCreated',
            value: function onBeforeDocumentCreated(commandObj, payload, documentData) {
                var parentPath = '';

                if (commandObj.userData && commandObj.userData.path) {
                    parentPath = commandObj.userData.path;
                }

                if (parentPath.length > 0) {
                    documentData.document.fileName = parentPath + '/' + documentData.document.fileName;
                }
            }
        }, {
            key: 'onCreateDirectory',
            value: function onCreateDirectory(cmd, payload) {
                var _this2 = this;

                var inspectorConfiguration = this.parentExtension.getInspectorConfiguration('asset-dir-create');
                var data = {
                    name: ''
                };
                var parent = cmd.hasParameter ? cmd.parameter : '';

                $.oc.vueComponentHelpers.inspector.host.showModal(inspectorConfiguration.title, data, inspectorConfiguration.config, 'assets-directory-name', {
                    beforeApplyCallback: function beforeApplyCallback(updatedData) {
                        return _this2.onCreateDirectoryConfirmed(updatedData.name, parent, payload);
                    }
                }).then($.noop, $.noop);
            }
        }, {
            key: 'onCreateDirectoryConfirmed',
            value: function () {
                var _ref = babelHelpers.asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(name, parent, payload) {
                    var theme;
                    return regeneratorRuntime.wrap(function _callee$(_context) {
                        while (1) {
                            switch (_context.prev = _context.next) {
                                case 0:
                                    _context.prev = 0;
                                    theme = this.parentExtension.cmsTheme;
                                    _context.next = 4;
                                    return $.oc.editor.application.ajaxRequest('onCommand', {
                                        extension: this.editorNamespace,
                                        command: 'onAssetCreateDirectory',
                                        documentData: { name: name, parent: parent },
                                        documentMetadata: {
                                            theme: theme
                                        }
                                    });

                                case 4:

                                    this.editorStore.refreshExtensionNavigatorNodes(this.editorNamespace, this.documentType).then(function () {
                                        payload.treeNode.expand();
                                    });
                                    _context.next = 11;
                                    break;

                                case 7:
                                    _context.prev = 7;
                                    _context.t0 = _context['catch'](0);

                                    $.oc.editor.page.showAjaxErrorAlert(_context.t0, this.trans('editor::lang.common.error'));
                                    return _context.abrupt('return', false);

                                case 11:
                                case 'end':
                                    return _context.stop();
                            }
                        }
                    }, _callee, this, [[0, 7]]);
                }));

                function onCreateDirectoryConfirmed(_x, _x2, _x3) {
                    return _ref.apply(this, arguments);
                }

                return onCreateDirectoryConfirmed;
            }()
        }, {
            key: 'onDeleteAssetOrDirectory',
            value: function () {
                var _ref2 = babelHelpers.asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(cmd, payload) {
                    var _this3 = this;

                    var theme, itemsDetails, files, deletedUris, message;
                    return regeneratorRuntime.wrap(function _callee2$(_context2) {
                        while (1) {
                            switch (_context2.prev = _context2.next) {
                                case 0:
                                    theme = this.parentExtension.cmsTheme;
                                    itemsDetails = cmd.userData.itemsDetails;
                                    files = [];
                                    deletedUris = [];


                                    if (!itemsDetails.clickedIsSelected) {
                                        files.push(itemsDetails.clickedNode.userData.path);
                                        deletedUris.push(itemsDetails.clickedNode.uniqueKey);
                                    } else {
                                        itemsDetails.selectedNodes.forEach(function (selectedNode) {
                                            files.push(selectedNode.nodeData.userData.path);
                                            deletedUris.push(selectedNode.nodeData.uniqueKey);
                                        });
                                    }

                                    message = files.length > 1 ? $.oc.editor.getLangStr('cms::lang.asset.delete_confirm') : $.oc.editor.getLangStr('cms::lang.asset.delete_confirm_single');
                                    _context2.prev = 6;
                                    _context2.next = 9;
                                    return $.oc.vueComponentHelpers.modalUtils.showConfirm($.oc.editor.getLangStr('backend::lang.form.delete'), message, {
                                        isDanger: true,
                                        buttonText: $.oc.editor.getLangStr('backend::lang.form.confirm')
                                    });

                                case 9:
                                    _context2.next = 14;
                                    break;

                                case 11:
                                    _context2.prev = 11;
                                    _context2.t0 = _context2['catch'](6);
                                    return _context2.abrupt('return');

                                case 14:
                                    _context2.prev = 14;
                                    _context2.next = 17;
                                    return $.oc.editor.application.ajaxRequest('onCommand', {
                                        extension: this.editorNamespace,
                                        command: 'onAssetDelete',
                                        documentData: {
                                            files: files
                                        },
                                        documentMetadata: {
                                            theme: theme
                                        }
                                    });

                                case 17:

                                    deletedUris.forEach(function (deletedUri) {
                                        _this3.editorStore.deleteNavigatorNode(deletedUri);
                                        _this3.editorStore.tabManager.closeTabByKey(deletedUri);
                                    });
                                    _context2.next = 24;
                                    break;

                                case 20:
                                    _context2.prev = 20;
                                    _context2.t1 = _context2['catch'](14);

                                    $.oc.editor.page.showAjaxErrorAlert(_context2.t1, this.trans('editor::lang.common.error'));
                                    this.editorStore.refreshExtensionNavigatorNodes(this.editorNamespace);

                                case 24:
                                case 'end':
                                    return _context2.stop();
                            }
                        }
                    }, _callee2, this, [[6, 11], [14, 20]]);
                }));

                function onDeleteAssetOrDirectory(_x4, _x5) {
                    return _ref2.apply(this, arguments);
                }

                return onDeleteAssetOrDirectory;
            }()
        }, {
            key: 'onRenameAssetOrDirectory',
            value: function onRenameAssetOrDirectory(cmd, payload) {
                var _this4 = this;

                var inspectorConfiguration = this.parentExtension.getInspectorConfiguration('asset-rename');
                var data = {
                    name: cmd.userData.fileName
                };
                var originalPath = cmd.hasParameter ? cmd.parameter : '';

                $.oc.vueComponentHelpers.inspector.host.showModal(inspectorConfiguration.title, data, inspectorConfiguration.config, 'assets-rename', {
                    beforeApplyCallback: function beforeApplyCallback(updatedData) {
                        return _this4.onRenameConfirmed(updatedData.name, originalPath, payload);
                    }
                }).then($.noop, $.noop);
            }
        }, {
            key: 'onRenameConfirmed',
            value: function () {
                var _ref3 = babelHelpers.asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee3(name, originalPath, payload) {
                    var theme;
                    return regeneratorRuntime.wrap(function _callee3$(_context3) {
                        while (1) {
                            switch (_context3.prev = _context3.next) {
                                case 0:
                                    _context3.prev = 0;
                                    theme = this.parentExtension.cmsTheme;
                                    _context3.next = 4;
                                    return $.oc.editor.application.ajaxRequest('onCommand', {
                                        extension: this.editorNamespace,
                                        command: 'onAssetRename',
                                        documentData: { name: name, originalPath: originalPath },
                                        documentMetadata: {
                                            theme: theme
                                        }
                                    });

                                case 4:

                                    this.editorStore.refreshExtensionNavigatorNodes(this.editorNamespace, this.documentType);
                                    _context3.next = 11;
                                    break;

                                case 7:
                                    _context3.prev = 7;
                                    _context3.t0 = _context3['catch'](0);

                                    $.oc.editor.page.showAjaxErrorAlert(_context3.t0, this.trans('editor::lang.common.error'));
                                    return _context3.abrupt('return', false);

                                case 11:
                                case 'end':
                                    return _context3.stop();
                            }
                        }
                    }, _callee3, this, [[0, 7]]);
                }));

                function onRenameConfirmed(_x6, _x7, _x8) {
                    return _ref3.apply(this, arguments);
                }

                return onRenameConfirmed;
            }()
        }, {
            key: 'onNavigatorNodeMoved',
            value: function () {
                var _ref4 = babelHelpers.asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee4(cmd) {
                    var movingMessageId, theme, movedNodePaths;
                    return regeneratorRuntime.wrap(function _callee4$(_context4) {
                        while (1) {
                            switch (_context4.prev = _context4.next) {
                                case 0:
                                    cmd.userData.event.preventDefault();

                                    $.oc.editor.application.setNavigatorReadonly(true);
                                    movingMessageId = $.oc.snackbar.show(this.trans('cms::lang.asset.moving'), {
                                        timeout: 5000
                                    });
                                    theme = this.parentExtension.cmsTheme;
                                    movedNodePaths = [];


                                    cmd.userData.movedNodes.map(function (movedNode) {
                                        movedNodePaths.push(movedNode.nodeData.userData.path);
                                    });

                                    _context4.prev = 6;
                                    _context4.next = 9;
                                    return $.oc.editor.application.ajaxRequest('onCommand', {
                                        extension: this.editorNamespace,
                                        command: 'onAssetMove',
                                        documentData: {
                                            source: movedNodePaths,
                                            destination: cmd.userData.movedToNodeData.userData.path
                                        },
                                        documentMetadata: {
                                            theme: theme
                                        }
                                    });

                                case 9:
                                    _context4.next = 11;
                                    return this.editorStore.refreshExtensionNavigatorNodes(this.editorNamespace, this.documentType);

                                case 11:
                                    $.oc.snackbar.show(this.trans('cms::lang.asset.moved'), { replace: movingMessageId });
                                    $.oc.editor.application.setNavigatorReadonly(false);
                                    _context4.next = 20;
                                    break;

                                case 15:
                                    _context4.prev = 15;
                                    _context4.t0 = _context4['catch'](6);

                                    $.oc.editor.application.setNavigatorReadonly(false);
                                    $.oc.snackbar.hide(movingMessageId);
                                    $.oc.editor.page.showAjaxErrorAlert(_context4.t0, this.trans('editor::lang.common.error'));

                                case 20:
                                case 'end':
                                    return _context4.stop();
                            }
                        }
                    }, _callee4, this, [[6, 15]]);
                }));

                function onNavigatorNodeMoved(_x9) {
                    return _ref4.apply(this, arguments);
                }

                return onNavigatorNodeMoved;
            }()
        }, {
            key: 'onNavigatorExternalDrop',
            value: function onNavigatorExternalDrop(cmd) {
                var _this5 = this;

                var uploaderUtils = $.oc.module.import('backend.vuecomponents.uploader.utils');
                var dataTransfer = cmd.userData.ev.dataTransfer;

                if (!dataTransfer || !dataTransfer.files || !dataTransfer.files.length) {
                    return;
                }

                var targetNodeData = cmd.userData.nodeData;
                var extraData = {
                    extension: this.editorNamespace,
                    command: 'onAssetUpload',
                    destination: targetNodeData.userData.path,
                    theme: this.parentExtension.cmsTheme
                };

                uploaderUtils.uploadFile('onCommand', dataTransfer.files, 'file', extraData).then(function () {
                    _this5.editorStore.refreshExtensionNavigatorNodes(_this5.editorNamespace, _this5.documentType);
                }, function () {
                    _this5.editorStore.refreshExtensionNavigatorNodes(_this5.editorNamespace, _this5.documentType);
                });
            }
        }, {
            key: 'onUploadDocument',
            value: function onUploadDocument(cmd) {
                var _this6 = this;

                var input = $('<input type="file" style="display:none" name="file" multiple/>');
                input.attr('accept', this.parentExtension.customData['assetExtensionList']);

                $(document.body).append(input);

                input.one('change', function () {
                    _this6.onFilesSelected(input, cmd.userData.path ? cmd.userData.path : '/');
                });

                input.click();
            }
        }, {
            key: 'onFilesSelected',
            value: function () {
                var _ref5 = babelHelpers.asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee5(input, path) {
                    var uploaderUtils, extraData;
                    return regeneratorRuntime.wrap(function _callee5$(_context5) {
                        while (1) {
                            switch (_context5.prev = _context5.next) {
                                case 0:
                                    uploaderUtils = $.oc.module.import('backend.vuecomponents.uploader.utils');
                                    _context5.prev = 1;
                                    extraData = {
                                        extension: this.editorNamespace,
                                        command: 'onAssetUpload',
                                        destination: path,
                                        theme: this.parentExtension.cmsTheme
                                    };
                                    _context5.next = 5;
                                    return uploaderUtils.uploadFile('onCommand', input.get(0).files, 'file', extraData);

                                case 5:
                                    _context5.next = 9;
                                    break;

                                case 7:
                                    _context5.prev = 7;
                                    _context5.t0 = _context5['catch'](1);

                                case 9:

                                    this.editorStore.refreshExtensionNavigatorNodes(this.editorNamespace, this.documentType);
                                    input.remove();

                                case 11:
                                case 'end':
                                    return _context5.stop();
                            }
                        }
                    }, _callee5, this, [[1, 7]]);
                }));

                function onFilesSelected(_x10, _x11) {
                    return _ref5.apply(this, arguments);
                }

                return onFilesSelected;
            }()
        }, {
            key: 'onOpenAsset',
            value: function onOpenAsset(cmd, payload) {
                window.open(cmd.userData.url);
            }
        }, {
            key: 'onNavigatorNodesUpdated',
            value: function onNavigatorNodesUpdated(cmd) {
                this.cachedAssetList = null;
            }
        }, {
            key: 'documentType',
            get: function get() {
                return 'cms-asset';
            }
        }, {
            key: 'vueEditorComponentName',
            get: function get() {
                return 'cms-editor-component-asset-editor';
            }
        }]);
        return DocumentControllerAsset;
    }(DocumentControllerBase);

    return DocumentControllerAsset;
});
