$.oc.module.register('cms.editor.intellisense.completer.content', function () {
    'use strict';

    var CompleterBase = $.oc.module.import('cms.editor.intellisense.completer.base');

    var CompleterOctoberContent = function (_CompleterBase) {
        babelHelpers.inherits(CompleterOctoberContent, _CompleterBase);

        function CompleterOctoberContent() {
            babelHelpers.classCallCheck(this, CompleterOctoberContent);
            return babelHelpers.possibleConstructorReturn(this, (CompleterOctoberContent.__proto__ || Object.getPrototypeOf(CompleterOctoberContent)).apply(this, arguments));
        }

        babelHelpers.createClass(CompleterOctoberContent, [{
            key: 'getNormalizedContentFiles',
            value: function getNormalizedContentFiles(range) {
                return this.utils.getContentFiles().map(function (content) {
                    var result = {
                        label: content.name,
                        insertText: content.name,
                        kind: monaco.languages.CompletionItemKind.Enum,
                        range: range,
                        detail: 'Content file'
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
                var wordMatches = textUntilPosition.match(/\{%\s+content\s+("|')(\w|\/|\-|\.)*$/);
                if (!wordMatches) {
                    return;
                }

                var wordMatchBefore = textUntilPosition.match(/("|')[\w\/\-\.]*$/);
                if (!wordMatchBefore) {
                    return;
                }

                var wordMatchAfter = textAfterPosition.match(/[\w\/\-\.]?("|')/);
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
                    suggestions: this.getNormalizedContentFiles(range)
                };
            }
        }, {
            key: 'triggerCharacters',
            get: function get() {
                return ['"', "'", '/', '-', '.'].concat(babelHelpers.toConsumableArray(this.alphaNumCharacters));
            }
        }]);
        return CompleterOctoberContent;
    }(CompleterBase);

    return CompleterOctoberContent;
});
