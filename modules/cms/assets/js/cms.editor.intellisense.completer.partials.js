$.oc.module.register('cms.editor.intellisense.completer.partials', function () {
    'use strict';

    var CompleterBase = $.oc.module.import('cms.editor.intellisense.completer.base');

    var CompleterOctoberPartials = function (_CompleterBase) {
        babelHelpers.inherits(CompleterOctoberPartials, _CompleterBase);

        function CompleterOctoberPartials() {
            babelHelpers.classCallCheck(this, CompleterOctoberPartials);
            return babelHelpers.possibleConstructorReturn(this, (CompleterOctoberPartials.__proto__ || Object.getPrototypeOf(CompleterOctoberPartials)).apply(this, arguments));
        }

        babelHelpers.createClass(CompleterOctoberPartials, [{
            key: 'getNormalizedPartials',
            value: function getNormalizedPartials(range) {
                return this.utils.getPartials().map(function (partial) {
                    var result = {
                        label: partial.name,
                        insertText: partial.name,
                        kind: monaco.languages.CompletionItemKind.Enum,
                        range: range,
                        detail: 'October CMS partial'
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
                var wordMatches = textUntilPosition.match(/\{%\s+partial\s+("|')(\w|\/|\-)*$/);
                if (!wordMatches) {
                    return;
                }

                var wordMatchBefore = textUntilPosition.match(/("|')[\w\/\-]*$/);
                if (!wordMatchBefore) {
                    return;
                }

                var wordMatchAfter = textAfterPosition.match(/[\w\/\-]?("|')/);
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
                    suggestions: this.getNormalizedPartials(range)
                };
            }
        }, {
            key: 'triggerCharacters',
            get: function get() {
                return ['"', "'", '/', '-'].concat(babelHelpers.toConsumableArray(this.alphaNumCharacters));
            }
        }]);
        return CompleterOctoberPartials;
    }(CompleterBase);

    return CompleterOctoberPartials;
});
