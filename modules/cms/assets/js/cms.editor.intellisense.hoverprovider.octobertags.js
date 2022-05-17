$.oc.module.register('cms.editor.intellisense.hoverprovider.octobertags', function () {
    'use strict';

    var HoverProviderBase = $.oc.module.import('cms.editor.intellisense.hoverprovider.base');

    var HoverProviderOctoberTags = function (_HoverProviderBase) {
        babelHelpers.inherits(HoverProviderOctoberTags, _HoverProviderBase);

        function HoverProviderOctoberTags() {
            babelHelpers.classCallCheck(this, HoverProviderOctoberTags);
            return babelHelpers.possibleConstructorReturn(this, (HoverProviderOctoberTags.__proto__ || Object.getPrototypeOf(HoverProviderOctoberTags)).apply(this, arguments));
        }

        babelHelpers.createClass(HoverProviderOctoberTags, [{
            key: 'getOctoberTags',
            value: function getOctoberTags() {
                return this.intellisense.getCustomData().octoberTags;
            }
        }, {
            key: 'provideHover',
            value: function provideHover(model, position) {
                if (!this.intellisense.modelHasTag(model, 'cms-markup')) {
                    return;
                }

                var textUntilPosition = this.intellisense.utils.textUntilPosition(model, position);
                if (!/{%\s+\w+$/.test(textUntilPosition)) {
                    return;
                }

                var wordAtPosition = model.getWordAtPosition(position);
                if (!wordAtPosition) {
                    return;
                }

                var word = wordAtPosition.word;
                var tag = this.getOctoberTags().find(function (item) {
                    if (Array.isArray(item.hoverKeyword)) {
                        return item.hoverKeyword.some(function (keyword) {
                            return keyword == word;
                        });
                    }

                    return item.hoverKeyword == word;
                });

                if (!tag) {
                    return;
                }

                return {
                    contents: [{ value: '**' + tag.label + '**' }, {
                        value: this.intellisense.utils.makeTagDocumentationString(tag)
                    }]
                };
            }
        }]);
        return HoverProviderOctoberTags;
    }(HoverProviderBase);

    return HoverProviderOctoberTags;
});
