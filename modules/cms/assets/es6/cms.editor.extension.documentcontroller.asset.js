$.oc.module.register('cms.editor.extension.documentcontroller.asset', function() {
    'use strict';

    const DocumentControllerBase = $.oc.module.import('editor.extension.documentcontroller.base');
    const treeviewUtils = $.oc.vueComponentHelpers.treeviewUtils;
    const EditorCommand = $.oc.module.import('editor.command');

    class DocumentControllerAsset extends DocumentControllerBase {
        get documentType() {
            return 'cms-asset';
        }

        get vueEditorComponentName() {
            return 'cms-editor-component-asset-editor';
        }

        beforeDocumentOpen(commandObj, nodeData) {
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

        initListeners() {
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

        getNavigatorContextMenuItems(commandObj, payload) {
            const DocumentUri = $.oc.module.import('editor.documenturi');
            const uri = DocumentUri.parse(payload.nodeData.uniqueKey);
            const parentPath = payload.nodeData.userData.path;

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
            }
            else {
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

        getAllAssetFilenames() {
            if (this.cachedAssetList) {
                return this.cachedAssetList;
            }

            const assetsNavigatorNode = treeviewUtils.findNodeByKeyInSections(
                this.parentExtension.state.navigatorSections,
                'cms:cms-asset'
            );

            let assetList = [];

            if (assetsNavigatorNode) {
                assetList = treeviewUtils
                    .getFlattenNodes(assetsNavigatorNode.nodes)
                    .filter((assetNode) => {
                        return !assetNode.userData.isFolder;
                    })
                    .map((assetNode) => {
                        return assetNode.userData.path;
                    });
            }
            else {
                assetList = this.parentExtension.state.customData.assets;
            }

            this.cachedAssetList = assetList;
            return assetList;
        }

        onBeforeDocumentCreated(commandObj, payload, documentData) {
            let parentPath = '';

            if (commandObj.userData && commandObj.userData.path) {
                parentPath = commandObj.userData.path;
            }

            if (parentPath.length > 0) {
                documentData.document.fileName = parentPath + '/' + documentData.document.fileName;
            }
        }

        onCreateDirectory(cmd, payload) {
            const inspectorConfiguration = this.parentExtension.getInspectorConfiguration('asset-dir-create');
            const data = {
                name: ''
            };
            const parent = cmd.hasParameter ? cmd.parameter : '';

            $.oc.vueComponentHelpers.inspector.host
                .showModal(inspectorConfiguration.title, data, inspectorConfiguration.config, 'assets-directory-name', {
                    beforeApplyCallback: (updatedData) =>
                        this.onCreateDirectoryConfirmed(updatedData.name, parent, payload)
                })
                .then($.noop, $.noop);
        }

        async onCreateDirectoryConfirmed(name, parent, payload) {
            try {
                const theme = this.parentExtension.cmsTheme;

                await $.oc.editor.application.ajaxRequest('onCommand', {
                    extension: this.editorNamespace,
                    command: 'onAssetCreateDirectory',
                    documentData: { name, parent },
                    documentMetadata: {
                        theme: theme
                    }
                });

                this.editorStore.refreshExtensionNavigatorNodes(this.editorNamespace, this.documentType).then(() => {
                    payload.treeNode.expand();
                });
            } catch (error) {
                $.oc.editor.page.showAjaxErrorAlert(error, this.trans('editor::lang.common.error'));
                return false;
            }
        }

        async onDeleteAssetOrDirectory(cmd, payload) {
            const theme = this.parentExtension.cmsTheme;
            const itemsDetails = cmd.userData.itemsDetails;
            const files = [];
            const deletedUris = [];

            if (!itemsDetails.clickedIsSelected) {
                files.push(itemsDetails.clickedNode.userData.path);
                deletedUris.push(itemsDetails.clickedNode.uniqueKey);
            }
            else {
                itemsDetails.selectedNodes.forEach((selectedNode) => {
                    files.push(selectedNode.nodeData.userData.path);
                    deletedUris.push(selectedNode.nodeData.uniqueKey);
                });
            }

            const message =
                files.length > 1
                    ? $.oc.editor.getLangStr('cms::lang.asset.delete_confirm')
                    : $.oc.editor.getLangStr('cms::lang.asset.delete_confirm_single');

            try {
                await $.oc.vueComponentHelpers.modalUtils.showConfirm(
                    $.oc.editor.getLangStr('backend::lang.form.delete'),
                    message,
                    {
                        isDanger: true,
                        buttonText: $.oc.editor.getLangStr('backend::lang.form.confirm')
                    }
                );
            } catch (error) {
                return;
            }

            try {
                await $.oc.editor.application.ajaxRequest('onCommand', {
                    extension: this.editorNamespace,
                    command: 'onAssetDelete',
                    documentData: {
                        files: files
                    },
                    documentMetadata: {
                        theme: theme
                    }
                });

                deletedUris.forEach((deletedUri) => {
                    this.editorStore.deleteNavigatorNode(deletedUri);
                    this.editorStore.tabManager.closeTabByKey(deletedUri);
                });
            } catch (error) {
                $.oc.editor.page.showAjaxErrorAlert(error, this.trans('editor::lang.common.error'));
                this.editorStore.refreshExtensionNavigatorNodes(this.editorNamespace);
            }
        }

        onRenameAssetOrDirectory(cmd, payload) {
            const inspectorConfiguration = this.parentExtension.getInspectorConfiguration('asset-rename');
            const data = {
                name: cmd.userData.fileName
            };
            const originalPath = cmd.hasParameter ? cmd.parameter : '';

            $.oc.vueComponentHelpers.inspector.host
                .showModal(inspectorConfiguration.title, data, inspectorConfiguration.config, 'assets-rename', {
                    beforeApplyCallback: (updatedData) => {
                        return this.onRenameConfirmed(updatedData.name, originalPath, payload);
                    }
                })
                .then($.noop, $.noop);
        }

        async onRenameConfirmed(name, originalPath, payload) {
            try {
                const theme = this.parentExtension.cmsTheme;

                await $.oc.editor.application.ajaxRequest('onCommand', {
                    extension: this.editorNamespace,
                    command: 'onAssetRename',
                    documentData: { name, originalPath },
                    documentMetadata: {
                        theme: theme
                    }
                });

                this.editorStore.refreshExtensionNavigatorNodes(this.editorNamespace, this.documentType);
            } catch (error) {
                $.oc.editor.page.showAjaxErrorAlert(error, this.trans('editor::lang.common.error'));
                return false;
            }
        }

        async onNavigatorNodeMoved(cmd) {
            cmd.userData.event.preventDefault();

            $.oc.editor.application.setNavigatorReadonly(true);
            const movingMessageId = $.oc.snackbar.show(this.trans('cms::lang.asset.moving'), {
                timeout: 5000
            });

            const theme = this.parentExtension.cmsTheme;
            const movedNodePaths = [];

            cmd.userData.movedNodes.map((movedNode) => {
                movedNodePaths.push(movedNode.nodeData.userData.path);
            });

            try {
                await $.oc.editor.application.ajaxRequest('onCommand', {
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

                await this.editorStore.refreshExtensionNavigatorNodes(this.editorNamespace, this.documentType);
                $.oc.snackbar.show(this.trans('cms::lang.asset.moved'), { replace: movingMessageId });
                $.oc.editor.application.setNavigatorReadonly(false);
            } catch (error) {
                $.oc.editor.application.setNavigatorReadonly(false);
                $.oc.snackbar.hide(movingMessageId);
                $.oc.editor.page.showAjaxErrorAlert(error, this.trans('editor::lang.common.error'));
            }
        }

        onNavigatorExternalDrop(cmd) {
            const uploaderUtils = $.oc.module.import('backend.vuecomponents.uploader.utils');
            const dataTransfer = cmd.userData.ev.dataTransfer;

            if (!dataTransfer || !dataTransfer.files || !dataTransfer.files.length) {
                return;
            }

            const targetNodeData = cmd.userData.nodeData;
            const extraData = {
                extension: this.editorNamespace,
                command: 'onAssetUpload',
                destination: targetNodeData.userData.path,
                theme: this.parentExtension.cmsTheme
            };

            uploaderUtils.uploadFile('onCommand', dataTransfer.files, 'file', extraData).then(
                () => {
                    this.editorStore.refreshExtensionNavigatorNodes(this.editorNamespace, this.documentType);
                },
                () => {
                    this.editorStore.refreshExtensionNavigatorNodes(this.editorNamespace, this.documentType);
                }
            );
        }

        onUploadDocument(cmd) {
            const input = $('<input type="file" style="display:none" name="file" multiple/>');
            input.attr('accept', this.parentExtension.customData['assetExtensionList']);

            $(document.body).append(input);

            input.one('change', () => {
                this.onFilesSelected(input, cmd.userData.path ? cmd.userData.path : '/');
            });

            input.click();
        }

        async onFilesSelected(input, path) {
            const uploaderUtils = $.oc.module.import('backend.vuecomponents.uploader.utils');

            try {
                const extraData = {
                    extension: this.editorNamespace,
                    command: 'onAssetUpload',
                    destination: path,
                    theme: this.parentExtension.cmsTheme
                };

                await uploaderUtils.uploadFile('onCommand', input.get(0).files, 'file', extraData);
            } catch (error) {}

            this.editorStore.refreshExtensionNavigatorNodes(this.editorNamespace, this.documentType);
            input.remove();
        }

        onOpenAsset(cmd, payload) {
            window.open(cmd.userData.url);
        }

        onNavigatorNodesUpdated(cmd) {
            this.cachedAssetList = null;
        }
    }

    return DocumentControllerAsset;
});
