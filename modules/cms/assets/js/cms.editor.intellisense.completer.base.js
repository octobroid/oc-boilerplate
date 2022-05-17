$.oc.module.register('cms.editor.intellisense.completer.base', function () {
    'use strict';

    var CompleterBase = function () {
        function CompleterBase(intellisense) {
            babelHelpers.classCallCheck(this, CompleterBase);

            this.intellisense = intellisense;
        }

        babelHelpers.createClass(CompleterBase, [{
            key: 'provideCompletionItems',
            value: function provideCompletionItems(model, position) {}
        }, {
            key: 'alphaNumCharacters',
            get: function get() {
                return ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
            }
        }, {
            key: 'utils',
            get: function get() {
                return this.intellisense.utils;
            }
        }]);
        return CompleterBase;
    }();

    return CompleterBase;
});
