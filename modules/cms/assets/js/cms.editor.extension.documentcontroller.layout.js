$.oc.module.register('cms.editor.extension.documentcontroller.layout', function () {
    'use strict';

    var DocumentControllerBase = $.oc.module.import('editor.extension.documentcontroller.base');

    var treeviewUtils = $.oc.vueComponentHelpers.treeviewUtils;

    var DocumentControllerLayout = function (_DocumentControllerBa) {
        babelHelpers.inherits(DocumentControllerLayout, _DocumentControllerBa);

        function DocumentControllerLayout() {
            babelHelpers.classCallCheck(this, DocumentControllerLayout);
            return babelHelpers.possibleConstructorReturn(this, (DocumentControllerLayout.__proto__ || Object.getPrototypeOf(DocumentControllerLayout)).apply(this, arguments));
        }

        babelHelpers.createClass(DocumentControllerLayout, [{
            key: 'initListeners',
            value: function initListeners() {
                this.on('cms:navigator-nodes-updated', this.onNavigatorNodesUpdated);
            }
        }, {
            key: 'getAllLayoutFilenames',
            value: function getAllLayoutFilenames() {
                if (this.cachedLayoutList) {
                    return this.cachedLayoutList;
                }

                var layoutsNavigatorNode = treeviewUtils.findNodeByKeyInSections(this.parentExtension.state.navigatorSections, 'cms:cms-layout');

                var layoutList = [];

                if (layoutsNavigatorNode) {
                    layoutList = treeviewUtils.getFlattenNodes(layoutsNavigatorNode.nodes).map(function (layoutNode) {
                        return layoutNode.userData.filename;
                    });
                } else {
                    layoutList = this.parentExtension.state.customData.layouts;
                }

                this.cachedLayoutList = layoutList;
                return layoutList;
            }
        }, {
            key: 'onNavigatorNodesUpdated',
            value: function onNavigatorNodesUpdated(cmd) {
                this.cachedLayoutList = null;
            }
        }, {
            key: 'documentType',
            get: function get() {
                return 'cms-layout';
            }
        }, {
            key: 'vueEditorComponentName',
            get: function get() {
                return 'cms-editor-component-layout-editor';
            }
        }]);
        return DocumentControllerLayout;
    }(DocumentControllerBase);

    return DocumentControllerLayout;
});
