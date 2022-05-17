$.oc.module.register('cms.editor.extension.documentcontroller.content', function () {
    'use strict';

    var DocumentControllerBase = $.oc.module.import('editor.extension.documentcontroller.base');

    var treeviewUtils = $.oc.vueComponentHelpers.treeviewUtils;

    var DocumentControllerContent = function (_DocumentControllerBa) {
        babelHelpers.inherits(DocumentControllerContent, _DocumentControllerBa);

        function DocumentControllerContent() {
            babelHelpers.classCallCheck(this, DocumentControllerContent);
            return babelHelpers.possibleConstructorReturn(this, (DocumentControllerContent.__proto__ || Object.getPrototypeOf(DocumentControllerContent)).apply(this, arguments));
        }

        babelHelpers.createClass(DocumentControllerContent, [{
            key: 'initListeners',
            value: function initListeners() {
                this.on('cms:navigator-nodes-updated', this.onNavigatorNodesUpdated);
            }
        }, {
            key: 'getAllContentFilenames',
            value: function getAllContentFilenames() {
                if (this.cachedContentList) {
                    return this.cachedContentList;
                }

                var contentNavigatorNode = treeviewUtils.findNodeByKeyInSections(this.parentExtension.state.navigatorSections, 'cms:cms-content');

                var contentList = [];

                if (contentNavigatorNode) {
                    contentList = treeviewUtils.getFlattenNodes(contentNavigatorNode.nodes).map(function (contentNode) {
                        return contentNode.userData.filename;
                    });
                } else {
                    contentList = this.parentExtension.state.customData.content;
                }

                this.cachedContentList = contentList;
                return contentList;
            }
        }, {
            key: 'onNavigatorNodesUpdated',
            value: function onNavigatorNodesUpdated(cmd) {
                this.cachedContentList = null;
            }
        }, {
            key: 'documentType',
            get: function get() {
                return 'cms-content';
            }
        }, {
            key: 'vueEditorComponentName',
            get: function get() {
                return 'cms-editor-component-content-editor';
            }
        }]);
        return DocumentControllerContent;
    }(DocumentControllerBase);

    return DocumentControllerContent;
});
