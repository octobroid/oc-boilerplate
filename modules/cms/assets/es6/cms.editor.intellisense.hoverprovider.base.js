$.oc.module.register('cms.editor.intellisense.hoverprovider.base', function() {
    'use strict';

    class HoverProviderBase {
        intellisense;

        constructor(intellisense) {
            this.intellisense = intellisense;
        }
    }

    return HoverProviderBase;
});
