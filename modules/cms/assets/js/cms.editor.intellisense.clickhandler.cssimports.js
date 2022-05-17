$.oc.module.register('cms.editor.intellisense.clickhandler.cssimports', function () {
    'use strict';

    var ClickHandlerBase = $.oc.module.import('cms.editor.intellisense.clickhandler.base');

    var ClickHandlerCssImport = function (_ClickHandlerBase) {
        babelHelpers.inherits(ClickHandlerCssImport, _ClickHandlerBase);

        function ClickHandlerCssImport() {
            babelHelpers.classCallCheck(this, ClickHandlerCssImport);
            return babelHelpers.possibleConstructorReturn(this, (ClickHandlerCssImport.__proto__ || Object.getPrototypeOf(ClickHandlerCssImport)).apply(this, arguments));
        }

        babelHelpers.createClass(ClickHandlerCssImport, [{
            key: 'resolveLink',
            value: function resolveLink(link, token) {
                var path = link.templateName;
                if (link.type === 'cms-asset') {
                    path = path.replace(/^assets\//, '');
                }

                this.intellisense.emit('onTokenClick', {
                    type: link.type,
                    path: path
                });
            }
        }, {
            key: 'addUnderscore',
            value: function addUnderscore(fileName) {
                var parts = fileName.split('/');
                var baseName = '_' + parts.pop();

                return parts.join('/') + '/' + baseName;
            }
        }, {
            key: 'getAssets',
            value: function getAssets() {
                return this.utils.getAssets().map(function (asset) {
                    return asset.name;
                });
            }
        }, {
            key: 'makeLinks',
            value: function makeLinks(model, re, links, basePath, options) {
                var _this2 = this;

                var matches = model.findMatches(re, false, true, false, null, true);
                var templateList = false;

                matches.forEach(function (findMatch) {
                    var templateName = findMatch.matches[2];
                    var fullTemplateName = null;

                    var extension = _this2.getFileExtension(templateName);
                    if (options.allowedExtensions.indexOf(extension) === -1) {
                        return;
                    }

                    if (templateList === false) {
                        templateList = _this2.getAssets();
                    }

                    fullTemplateName = _this2.addExtensionIfMissing(templateName, _this2.options.extension);
                    fullTemplateName = _this2.resolveRelativeFilePath(basePath, fullTemplateName);

                    if (templateList.indexOf(fullTemplateName) === -1) {
                        fullTemplateName = _this2.addUnderscore(fullTemplateName);
                        if (templateList.indexOf(fullTemplateName) === -1) {
                            return;
                        }
                    }

                    var templatePos = findMatch.matches[0].indexOf(templateName);
                    var startColumn = findMatch.range.startColumn + templatePos;

                    links.push({
                        type: 'cms-asset',
                        templateName: fullTemplateName,
                        range: {
                            endColumn: startColumn + templateName.length,
                            endLineNumber: findMatch.range.endLineNumber,
                            startColumn: startColumn,
                            startLineNumber: findMatch.range.startLineNumber
                        }
                    });
                });
            }
        }, {
            key: 'provideLinks',
            value: function provideLinks(model, token) {
                if (typeof this.options.extension !== 'string') {
                    throw new Error('options.extension must be set for cssimports click handler');
                }

                if (!this.intellisense.modelHasTag(model, 'cms-asset-contents')) {
                    return;
                }

                var basePath = 'assets/' + this.intellisense.getModelCustomAttribute(model, 'filePath');
                var result = {
                    links: []
                };

                this.makeLinks(model, /@import\s+("|')([a-zA-Z0-9\-\/_\.]+)\1/gm, result.links, basePath, {
                    allowedExtensions: [this.options.extension, null]
                });

                return result;
            }
        }]);
        return ClickHandlerCssImport;
    }(ClickHandlerBase);

    return ClickHandlerCssImport;
});
