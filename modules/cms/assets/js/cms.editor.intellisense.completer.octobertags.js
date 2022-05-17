$.oc.module.register('cms.editor.intellisense.completer.octobertags', function () {
    'use strict';

    var CompleterBase = $.oc.module.import('cms.editor.intellisense.completer.base');

    var CompleterOctoberTags = function (_CompleterBase) {
        babelHelpers.inherits(CompleterOctoberTags, _CompleterBase);

        function CompleterOctoberTags() {
            babelHelpers.classCallCheck(this, CompleterOctoberTags);
            return babelHelpers.possibleConstructorReturn(this, (CompleterOctoberTags.__proto__ || Object.getPrototypeOf(CompleterOctoberTags)).apply(this, arguments));
        }

        babelHelpers.createClass(CompleterOctoberTags, [{
            key: 'getOctoberTags',
            value: function getOctoberTags() {
                return this.intellisense.getCustomData().octoberTags;
            }
        }, {
            key: 'getNormalizedTags',
            value: function getNormalizedTags() {
                var _this2 = this;

                if (this.normalizedTags) {
                    return this.normalizedTags;
                }

                this.normalizedTags = this.getOctoberTags().map(function (tag) {
                    var result = $.oc.vueUtils.getCleanObject(tag);

                    result.kind = monaco.languages.CompletionItemKind.Function;
                    result.insertTextRules = monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet;
                    result.detail = 'October CMS tag';
                    result.documentation = {
                        value: _this2.intellisense.utils.makeTagDocumentationString(result)
                    };

                    return result;
                });

                return this.normalizedTags;
            }
        }, {
            key: 'getPartialEnum',
            value: function getPartialEnum() {
                var partialNameList = this.utils.getPartials().map(function (partial) {
                    return partial.name;
                });

                if (!partialNameList.length) {
                    return '';
                }

                return '|' + partialNameList.join(',') + '|';
            }
        }, {
            key: 'getContentEnum',
            value: function getContentEnum() {
                var contentNameList = this.utils.getContentFiles().map(function (contentFile) {
                    return contentFile.name;
                });

                if (!contentNameList.length) {
                    return '';
                }

                return '|' + contentNameList.join(',') + '|';
            }
        }, {
            key: 'applyNormalizedTags',
            value: function applyNormalizedTags(range) {
                var _this3 = this;

                var tags = this.getNormalizedTags();

                return tags.map(function (tag) {
                    var result = $.oc.vueUtils.getCleanObject(tag);
                    if (tag.insertText && tag.insertText.indexOf('%}') !== -1) {
                        result.range = range;
                    }

                    if (tag.insertText && tag.insertText.indexOf('{partial-list}' !== -1)) {
                        tag.insertText = tag.insertText.replace('{partial-list}', _this3.getPartialEnum());
                    }

                    if (tag.insertText && tag.insertText.indexOf('{content-list}' !== -1)) {
                        tag.insertText = tag.insertText.replace('{content-list}', _this3.getContentEnum());
                    }

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

                var wordMatches = textUntilPosition.match(/\{%\s+\w*$/);
                if (!wordMatches) {
                    return;
                }

                var word = model.getWordUntilPosition(position);
                if (word.word === '%') {
                    return;
                }

                var range = null;
                var matches = textAfterPosition.match(/\s+%}/);
                if (matches) {
                    range = {
                        startLineNumber: position.lineNumber,
                        endLineNumber: position.lineNumber,
                        startColumn: word.endColumn - wordMatches[0].length,
                        endColumn: word.endColumn + matches[0].length
                    };
                }

                return {
                    suggestions: this.applyNormalizedTags(range)
                };
            }
        }, {
            key: 'triggerCharacters',
            get: function get() {
                return [' '].concat(babelHelpers.toConsumableArray(this.alphaNumCharacters));
            }
        }]);
        return CompleterOctoberTags;
    }(CompleterBase);

    return CompleterOctoberTags;
});
