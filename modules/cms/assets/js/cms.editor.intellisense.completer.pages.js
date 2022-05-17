$.oc.module.register('cms.editor.intellisense.completer.pages', function () {
    'use strict';

    var CompleterBase = $.oc.module.import('cms.editor.intellisense.completer.base');

    var CompleterPages = function (_CompleterBase) {
        babelHelpers.inherits(CompleterPages, _CompleterBase);

        function CompleterPages() {
            babelHelpers.classCallCheck(this, CompleterPages);
            return babelHelpers.possibleConstructorReturn(this, (CompleterPages.__proto__ || Object.getPrototypeOf(CompleterPages)).apply(this, arguments));
        }

        babelHelpers.createClass(CompleterPages, [{
            key: 'getNormalizedPages',
            value: function getNormalizedPages(range) {
                return this.utils.getPages().map(function (asset) {
                    var result = {
                        label: asset.name,
                        insertText: asset.name,
                        kind: monaco.languages.CompletionItemKind.EnumMember,
                        range: range,
                        detail: 'CMS Page'
                    };

                    return result;
                });
            }
        }, {
            key: 'provideCompletionItems',
            value: function provideCompletionItems(model, position) {
                if (!this.intellisense.modelHasTag(model, 'cms-markup')) {
                    return;
                }

                var textUntilPosition = this.intellisense.utils.textUntilPosition(model, position);
                var textAfterPosition = this.intellisense.utils.textAfterPosition(model, position);
                var wordMatches = textUntilPosition.match(/\{\{\s+("|')(\w|\/|\-|\.|@)*$/);
                if (!wordMatches) {
                    return;
                }

                var wordMatchBefore = textUntilPosition.match(/("|')[\w\/\-\.@]*$/);
                if (!wordMatchBefore) {
                    return;
                }

                var wordMatchAfter = textAfterPosition.match(/[\w\/\-\.@]?("|')/);
                if (!wordMatchAfter) {
                    return;
                }

                var range = {
                    startLineNumber: position.lineNumber,
                    endLineNumber: position.lineNumber,
                    startColumn: wordMatchBefore.index + 2,
                    endColumn: position.column + wordMatchAfter[0].length - 1
                };

                return {
                    suggestions: this.getNormalizedPages(range)
                };
            }
        }, {
            key: 'triggerCharacters',
            get: function get() {
                return ['"', "'", '/', '-', '.', '@'].concat(babelHelpers.toConsumableArray(this.alphaNumCharacters));
            }
        }]);
        return CompleterPages;
    }(CompleterBase);

    return CompleterPages;
});
