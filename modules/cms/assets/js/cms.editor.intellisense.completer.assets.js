$.oc.module.register('cms.editor.intellisense.completer.assets', function () {
    'use strict';

    var CompleterBase = $.oc.module.import('cms.editor.intellisense.completer.base');

    var CompleterAssets = function (_CompleterBase) {
        babelHelpers.inherits(CompleterAssets, _CompleterBase);

        function CompleterAssets() {
            babelHelpers.classCallCheck(this, CompleterAssets);
            return babelHelpers.possibleConstructorReturn(this, (CompleterAssets.__proto__ || Object.getPrototypeOf(CompleterAssets)).apply(this, arguments));
        }

        babelHelpers.createClass(CompleterAssets, [{
            key: 'getNormalizedAssets',
            value: function getNormalizedAssets(range) {
                return this.utils.getAssets().map(function (asset) {
                    var result = {
                        label: asset.name,
                        insertText: asset.name,
                        kind: monaco.languages.CompletionItemKind.Enum,
                        range: range,
                        detail: 'Asset'
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
                    suggestions: this.getNormalizedAssets(range)
                };
            }
        }, {
            key: 'triggerCharacters',
            get: function get() {
                return ['"', "'", '/', '-', '.', '@'].concat(babelHelpers.toConsumableArray(this.alphaNumCharacters));
            }
        }]);
        return CompleterAssets;
    }(CompleterBase);

    return CompleterAssets;
});
