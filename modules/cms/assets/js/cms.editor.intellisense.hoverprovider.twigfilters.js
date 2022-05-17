$.oc.module.register('cms.editor.intellisense.hoverprovider.twigfilters', function () {
    'use strict';

    var HoverProviderBase = $.oc.module.import('cms.editor.intellisense.hoverprovider.base');

    var HoverProviderTwigFilters = function (_HoverProviderBase) {
        babelHelpers.inherits(HoverProviderTwigFilters, _HoverProviderBase);

        function HoverProviderTwigFilters() {
            babelHelpers.classCallCheck(this, HoverProviderTwigFilters);
            return babelHelpers.possibleConstructorReturn(this, (HoverProviderTwigFilters.__proto__ || Object.getPrototypeOf(HoverProviderTwigFilters)).apply(this, arguments));
        }

        babelHelpers.createClass(HoverProviderTwigFilters, [{
            key: 'getTwigFilters',
            value: function getTwigFilters() {
                return this.intellisense.getCustomData().twigFilters;
            }
        }, {
            key: 'provideHover',
            value: function provideHover(model, position) {
                if (!this.intellisense.modelHasTag(model, 'cms-markup')) {
                    return;
                }

                var textUntilPosition = this.intellisense.utils.textUntilPosition(model, position);
                if (!textUntilPosition.match(/(\{%)|(\{\{).*[^\s]+\|\w*$/)) {
                    return;
                }

                var openingTags = (textUntilPosition.match(/(\{%)|(\{\{)/g) || []).length;
                var closingTags = (textUntilPosition.match(/(%\})|(\}\})/g) || []).length;

                if (openingTags <= closingTags) {
                    return;
                }

                var wordAtPosition = model.getWordAtPosition(position);
                if (!wordAtPosition) {
                    return;
                }

                var word = wordAtPosition.word;
                var filter = this.getTwigFilters().find(function (item) {
                    if (Array.isArray(item.hoverKeyword)) {
                        return item.hoverKeyword.some(function (keyword) {
                            return keyword == word;
                        });
                    }

                    return item.hoverKeyword == word;
                });

                if (!filter) {
                    return;
                }

                return {
                    contents: [{ value: '**' + filter.label + '**' }, {
                        value: this.intellisense.utils.makeTagDocumentationString(filter)
                    }]
                };
            }
        }]);
        return HoverProviderTwigFilters;
    }(HoverProviderBase);

    return HoverProviderTwigFilters;
});
