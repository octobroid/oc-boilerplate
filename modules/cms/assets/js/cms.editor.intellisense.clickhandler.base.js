$.oc.module.register('cms.editor.intellisense.clickhandler.base', function () {
    'use strict';

    var ClickHandlerBase = function () {
        function ClickHandlerBase(intellisense, options) {
            babelHelpers.classCallCheck(this, ClickHandlerBase);

            this.intellisense = intellisense;
            this.options = options;
        }

        babelHelpers.createClass(ClickHandlerBase, [{
            key: 'getFileExtension',
            value: function getFileExtension(fileName) {
                var parts = fileName.split('/').pop().split('.');
                if (parts.length < 2) {
                    return null;
                }

                return parts.pop();
            }
        }, {
            key: 'addExtensionIfMissing',
            value: function addExtensionIfMissing(fileName, extension) {
                if (this.getFileExtension(fileName) !== null) {
                    return fileName;
                }

                return fileName + '.' + extension;
            }
        }, {
            key: 'resolveRelativeFilePath',
            value: function resolveRelativeFilePath(base, relative) {
                var parts = relative.split('/');
                var stack = base.split('/');

                stack.pop();
                parts.forEach(function (part) {
                    if (part === '.') {
                        return;
                    }

                    if (part === '..') {
                        stack.pop();
                    } else {
                        stack.push(part);
                    }
                });

                return stack.join('/');
            }
        }, {
            key: 'provideLinks',
            value: function provideLinks(model, token) {}
        }, {
            key: 'resolveLink',
            value: function resolveLink(link, token) {}
        }, {
            key: 'utils',
            get: function get() {
                return this.intellisense.utils;
            }
        }]);
        return ClickHandlerBase;
    }();

    return ClickHandlerBase;
});
