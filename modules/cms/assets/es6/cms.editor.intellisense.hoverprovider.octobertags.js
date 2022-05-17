$.oc.module.register('cms.editor.intellisense.hoverprovider.octobertags', function() {
    'use strict';

    const HoverProviderBase = $.oc.module.import('cms.editor.intellisense.hoverprovider.base');

    class HoverProviderOctoberTags extends HoverProviderBase {
        getOctoberTags() {
            return this.intellisense.getCustomData().octoberTags;
        }

        provideHover(model, position) {
            if (!this.intellisense.modelHasTag(model, 'cms-markup')) {
                return;
            }

            const textUntilPosition = this.intellisense.utils.textUntilPosition(model, position);
            if (!/{%\s+\w+$/.test(textUntilPosition)) {
                return;
            }

            const wordAtPosition = model.getWordAtPosition(position);
            if (!wordAtPosition) {
                return;
            }

            const word = wordAtPosition.word;
            const tag = this.getOctoberTags().find((item) => {
                if (Array.isArray(item.hoverKeyword)) {
                    return item.hoverKeyword.some((keyword) => keyword == word);
                }

                return item.hoverKeyword == word;
            });

            if (!tag) {
                return;
            }

            return {
                contents: [
                    { value: '**' + tag.label + '**' },
                    {
                        value: this.intellisense.utils.makeTagDocumentationString(tag)
                    }
                ]
            };
        }
    }

    return HoverProviderOctoberTags;
});
