$.oc.module.register('cms.editor.intellisense.completer.assets', function() {
    'use strict';

    const CompleterBase = $.oc.module.import('cms.editor.intellisense.completer.base');

    class CompleterAssets extends CompleterBase {
        get triggerCharacters() {
            return [...['"', "'", '/', '-', '.', '@'], ...this.alphaNumCharacters];
        }

        getNormalizedAssets(range) {
            return this.utils.getAssets().map((asset) => {
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

        provideCompletionItems(model, position) {
            if (!this.intellisense.modelHasTag(model, 'cms-markup')) {
                return;
            }

            const textUntilPosition = this.intellisense.utils.textUntilPosition(model, position);
            const textAfterPosition = this.intellisense.utils.textAfterPosition(model, position);
            const wordMatches = textUntilPosition.match(/\{\{\s+("|')(\w|\/|\-|\.|@)*$/);
            if (!wordMatches) {
                return;
            }

            const wordMatchBefore = textUntilPosition.match(/("|')[\w\/\-\.@]*$/);
            if (!wordMatchBefore) {
                return;
            }

            const wordMatchAfter = textAfterPosition.match(/[\w\/\-\.@]?("|')/);
            if (!wordMatchAfter) {
                return;
            }

            const range = {
                startLineNumber: position.lineNumber,
                endLineNumber: position.lineNumber,
                startColumn: wordMatchBefore.index + 2,
                endColumn: position.column + wordMatchAfter[0].length - 1
            };

            return {
                suggestions: this.getNormalizedAssets(range)
            };
        }
    }

    return CompleterAssets;
});
