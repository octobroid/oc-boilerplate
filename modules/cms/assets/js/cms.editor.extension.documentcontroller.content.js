$.oc.module.register('cms.editor.extension.documentcontroller.content', function() {
    'use strict';

    const DocumentControllerBase = $.oc.module.import('editor.extension.documentcontroller.base');

    const treeviewUtils = $.oc.vueComponentHelpers.treeviewUtils;

    class DocumentControllerContent extends DocumentControllerBase {
        get documentType() {
            return 'cms-content';
        }

        get vueEditorComponentName() {
            return 'cms-editor-component-content-editor';
        }

        initListeners() {
            this.on('cms:navigator-nodes-updated', this.onNavigatorNodesUpdated);
        }

        getAllContentFilenames() {
            if (this.cachedContentList) {
                return this.cachedContentList;
            }

            const contentNavigatorNode = treeviewUtils.findNodeByKeyInSections(
                this.parentExtension.state.navigatorSections,
                'cms:cms-content'
            );

            let contentList = [];

            if (contentNavigatorNode) {
                contentList = treeviewUtils.getFlattenNodes(contentNavigatorNode.nodes).map((contentNode) => {
                    return contentNode.userData.filename;
                });
            }
            else {
                contentList = this.parentExtension.state.customData.content;
            }

            this.cachedContentList = contentList;
            return contentList;
        }

        onNavigatorNodesUpdated(cmd) {
            this.cachedContentList = null;
        }
    }

    return DocumentControllerContent;
});
