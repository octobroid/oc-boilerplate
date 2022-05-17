$.oc.module.register('cms.editor.intellisense.clickhandler.template', function () {
    'use strict';

    var ClickHandlerBase = $.oc.module.import('cms.editor.intellisense.clickhandler.base');

    var ClickHandlerTemplate = function (_ClickHandlerBase) {
        babelHelpers.inherits(ClickHandlerTemplate, _ClickHandlerBase);

        function ClickHandlerTemplate() {
            babelHelpers.classCallCheck(this, ClickHandlerTemplate);
            return babelHelpers.possibleConstructorReturn(this, (ClickHandlerTemplate.__proto__ || Object.getPrototypeOf(ClickHandlerTemplate)).apply(this, arguments));
        }

        babelHelpers.createClass(ClickHandlerTemplate, [{
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
            key: 'getListByName',
            value: function getListByName(listName) {
                if (listName === 'assets') {
                    return this.utils.getAssets().map(function (asset) {
                        return asset.name;
                    });
                }

                if (listName === 'pages') {
                    return this.utils.getPages().map(function (asset) {
                        return asset.name;
                    });
                }
            }
        }, {
            key: 'makeLinks',
            value: function makeLinks(model, re, links, documentType, options) {
                var _this2 = this;

                var matches = model.findMatches(re, false, true, false, null, true);
                var templateList = false;

                matches.forEach(function (findMatch) {
                    var templateName = findMatch.matches[2];

                    if (options) {
                        if (options.allowedExtensions) {
                            var extension = _this2.getFileExtension(templateName);
                            if (extension === null || options.allowedExtensions.indexOf(extension) === -1) {
                                return;
                            }
                        }

                        if (options.checkList) {
                            if (templateList === false) {
                                templateList = _this2.getListByName(options.checkList);
                            }

                            if (!Array.isArray(templateList) || templateList.indexOf(templateName) === -1) {
                                return;
                            }
                        }
                    }

                    var templatePos = findMatch.matches[0].indexOf(templateName);
                    var startColumn = findMatch.range.startColumn + templatePos;

                    links.push({
                        type: documentType,
                        templateName: templateName,
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
                if (!this.intellisense.modelHasTag(model, 'cms-markup')) {
                    return;
                }

                var result = {
                    links: []
                };

                if (this.canManagePartials) {
                    this.makeLinks(model, /\{%\s+partial\s+("|')([a-zA-Z0-9\-\/_]+)\1/gm, result.links, 'cms-partial');
                }

                if (this.canManageContent) {
                    this.makeLinks(model, /\{%\s+content\s+("|')([a-zA-Z0-9\-\/\._]+)\1/gm, result.links, 'cms-content');
                }

                if (this.canManageAssets) {
                    this.makeLinks(model, /\{\{\s+("|')([a-zA-Z0-9\-\/\.\s_@]+)\1/gm, result.links, 'cms-asset', {
                        allowedExtensions: this.editableAssetExtensions,
                        checkList: 'assets'
                    });
                }

                if (this.canManagePages) {
                    this.makeLinks(model, /\{\{\s+("|')([a-zA-Z0-9\-\/\._]+)\1/gm, result.links, 'cms-page', {
                        checkList: 'pages'
                    });
                }

                return result;
            }
        }, {
            key: 'canManagePartials',
            get: function get() {
                return this.options.canManagePartials;
            }
        }, {
            key: 'canManageContent',
            get: function get() {
                return this.options.canManageContent;
            }
        }, {
            key: 'canManageAssets',
            get: function get() {
                return this.options.canManageAssets;
            }
        }, {
            key: 'canManagePages',
            get: function get() {
                return this.options.canManagePages;
            }
        }, {
            key: 'editableAssetExtensions',
            get: function get() {
                return this.options.editableAssetExtensions;
            }
        }]);
        return ClickHandlerTemplate;
    }(ClickHandlerBase);

    return ClickHandlerTemplate;
});
