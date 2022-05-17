$.oc.module.register('cms.editor.intellisense.utils.js', function () {
    'use strict';

    var IntellisenseUtils = function () {
        function IntellisenseUtils(intellisense) {
            babelHelpers.classCallCheck(this, IntellisenseUtils);

            this.intellisense = intellisense;
        }

        babelHelpers.createClass(IntellisenseUtils, [{
            key: 'getPartials',
            value: function getPartials() {
                var cmsExtension = $.oc.editor.store.getExtension('cms');
                var partialDocController = cmsExtension.getDocumentController('cms-partial');
                var partials = partialDocController.getAllPartialFilenames();

                return partials.map(function (partial) {
                    return {
                        name: cmsExtension.removeFileExtension(partial)
                    };
                });
            }
        }, {
            key: 'getPages',
            value: function getPages() {
                var cmsExtension = $.oc.editor.store.getExtension('cms');
                var pagesDocController = cmsExtension.getDocumentController('cms-page');
                var pages = pagesDocController.getAllPageFilenames();

                return pages.map(function (page) {
                    return {
                        name: cmsExtension.removeFileExtension(page)
                    };
                });
            }
        }, {
            key: 'getAssets',
            value: function getAssets() {
                var cmsExtension = $.oc.editor.store.getExtension('cms');
                var assetDocController = cmsExtension.getDocumentController('cms-asset');
                var assets = assetDocController.getAllAssetFilenames();

                return assets.map(function (fileName) {
                    return {
                        name: 'assets/' + fileName
                    };
                });
            }
        }, {
            key: 'getContentFiles',
            value: function getContentFiles() {
                var cmsExtension = $.oc.editor.store.getExtension('cms');
                var contentDocController = cmsExtension.getDocumentController('cms-content');
                var content = contentDocController.getAllContentFilenames();

                return content.map(function (fileName) {
                    return {
                        name: fileName
                    };
                });
            }
        }, {
            key: 'makeTagDocumentationString',
            value: function makeTagDocumentationString(tag) {
                var documentation = tag.documentation;

                if (tag.docUrl) {
                    var e = this.intellisense.escapeHtml;
                    var learnMore = ' [' + e(this.intellisense.trans('cms::lang.intellisense.learn_more')) + ']';
                    documentation += '\n' + learnMore + '(' + e(tag.docUrl) + ')';
                }

                return documentation;
            }
        }, {
            key: 'textUntilPosition',
            value: function textUntilPosition(model, position) {
                return model.getValueInRange({
                    startLineNumber: position.lineNumber,
                    startColumn: 1,
                    endLineNumber: position.lineNumber,
                    endColumn: position.column
                });
            }
        }, {
            key: 'textAfterPosition',
            value: function textAfterPosition(model, position) {
                return model.getValueInRange({
                    startLineNumber: position.lineNumber,
                    startColumn: position.column,
                    endLineNumber: position.lineNumber,
                    endColumn: model.getLineLength(position.lineNumber) + 1
                });
            }
        }]);
        return IntellisenseUtils;
    }();

    return IntellisenseUtils;
});
