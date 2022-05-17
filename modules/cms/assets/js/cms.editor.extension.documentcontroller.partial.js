$.oc.module.register('cms.editor.extension.documentcontroller.partial', function () {
    'use strict';

    var DocumentControllerBase = $.oc.module.import('editor.extension.documentcontroller.base');

    var treeviewUtils = $.oc.vueComponentHelpers.treeviewUtils;

    var DocumentControllerPartial = function (_DocumentControllerBa) {
        babelHelpers.inherits(DocumentControllerPartial, _DocumentControllerBa);

        function DocumentControllerPartial() {
            babelHelpers.classCallCheck(this, DocumentControllerPartial);
            return babelHelpers.possibleConstructorReturn(this, (DocumentControllerPartial.__proto__ || Object.getPrototypeOf(DocumentControllerPartial)).apply(this, arguments));
        }

        babelHelpers.createClass(DocumentControllerPartial, [{
            key: 'initListeners',
            value: function initListeners() {
                this.on('cms:navigator-nodes-updated', this.onNavigatorNodesUpdated);
            }
        }, {
            key: 'getAllPartialFilenames',
            value: function getAllPartialFilenames() {
                if (this.cachedPartialList) {
                    return this.cachedPartialList;
                }

                var partialsNavigatorNode = treeviewUtils.findNodeByKeyInSections(this.parentExtension.state.navigatorSections, 'cms:cms-partial');

                var partialList = [];

                if (partialsNavigatorNode) {
                    partialList = treeviewUtils.getFlattenNodes(partialsNavigatorNode.nodes).map(function (partialNode) {
                        return partialNode.userData.filename;
                    });
                } else {
                    partialList = this.parentExtension.state.customData.partials;
                }

                this.cachedPartialList = partialList;
                return partialList;
            }
        }, {
            key: 'onNavigatorNodesUpdated',
            value: function onNavigatorNodesUpdated(cmd) {
                this.cachedPartialList = null;
            }
        }, {
            key: 'documentType',
            get: function get() {
                return 'cms-partial';
            }
        }, {
            key: 'vueEditorComponentName',
            get: function get() {
                return 'cms-editor-component-partial-editor';
            }
        }]);
        return DocumentControllerPartial;
    }(DocumentControllerBase);

    return DocumentControllerPartial;
});
