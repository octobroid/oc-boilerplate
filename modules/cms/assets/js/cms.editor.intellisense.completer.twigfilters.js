$.oc.module.register('cms.editor.intellisense.completer.twigfilters', function () {
    'use strict';

    var CompleterBase = $.oc.module.import('cms.editor.intellisense.completer.base');

    var CompleterTwigFilters = function (_CompleterBase) {
        babelHelpers.inherits(CompleterTwigFilters, _CompleterBase);

        function CompleterTwigFilters() {
            babelHelpers.classCallCheck(this, CompleterTwigFilters);
            return babelHelpers.possibleConstructorReturn(this, (CompleterTwigFilters.__proto__ || Object.getPrototypeOf(CompleterTwigFilters)).apply(this, arguments));
        }

        babelHelpers.createClass(CompleterTwigFilters, [{
            key: 'getTwigFilters',
            value: function getTwigFilters() {
                return this.intellisense.getCustomData().twigFilters;
            }
        }, {
            key: 'getNormalizedFilters',
            value: function getNormalizedFilters() {
                var _this2 = this;

                return this.getTwigFilters().map(function (filter) {
                    var result = $.oc.vueUtils.getCleanObject(filter);

                    result.kind = monaco.languages.CompletionItemKind.Function;
                    result.insertTextRules = monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet;
                    result.detail = filter.isNativeTwigFilter ? 'Twig filter' : 'October CMS filter';
                    result.documentation = {
                        value: _this2.intellisense.utils.makeTagDocumentationString(result)
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
                if (!textUntilPosition.match(/((\{%)|(\{\{)).*[^\s]+\|\w*$/)) {
                    return;
                }

                var openingTags = (textUntilPosition.match(/(\{%)|(\{\{)/g) || []).length;
                var closingTags = (textUntilPosition.match(/(%\})|(\}\})/g) || []).length;

                if (openingTags <= closingTags) {
                    return;
                }

                return {
                    suggestions: this.getNormalizedFilters()
                };
            }
        }, {
            key: 'triggerCharacters',
            get: function get() {
                return ['|'].concat(babelHelpers.toConsumableArray(this.alphaNumCharacters));
            }
        }]);
        return CompleterTwigFilters;
    }(CompleterBase);

    return CompleterTwigFilters;
});
