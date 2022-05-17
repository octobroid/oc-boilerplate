$.oc.module.register('cms.editor.intellisense', function () {
    'use strict';

    var CompleterOctoberTags = $.oc.module.import('cms.editor.intellisense.completer.octobertags');
    var CompleterTwigFilters = $.oc.module.import('cms.editor.intellisense.completer.twigfilters');
    var CompleterOctoberPartials = $.oc.module.import('cms.editor.intellisense.completer.partials');
    var CompleterAssets = $.oc.module.import('cms.editor.intellisense.completer.assets');
    var CompleterPages = $.oc.module.import('cms.editor.intellisense.completer.pages');
    var CompleterContent = $.oc.module.import('cms.editor.intellisense.completer.content');

    var ClickHandlerTemplate = $.oc.module.import('cms.editor.intellisense.clickhandler.template');
    var ClickHandlerCssImports = $.oc.module.import('cms.editor.intellisense.clickhandler.cssimports');
    var HoverProviderOctoberTags = $.oc.module.import('cms.editor.intellisense.hoverprovider.octobertags');
    var HoverProviderTwigFilters = $.oc.module.import('cms.editor.intellisense.hoverprovider.twigfilters');
    var IntellisenseUtils = $.oc.module.import('cms.editor.intellisense.utils.js');
    var ActionHandlerExpandComponent = $.oc.module.import('cms.editor.intellisense.actionhandlers.expandcomponent');

    var instance = null;

    var CmsIntellisense = function () {
        function CmsIntellisense(cmsCustomData) {
            babelHelpers.classCallCheck(this, CmsIntellisense);

            this.customData = cmsCustomData.intellisense;
            this.globalInitialized = false;
            this.listeners = new Map();
            this.utils = new IntellisenseUtils(this);

            this.completers = {
                octoberTags: new CompleterOctoberTags(this),
                octoberPartials: new CompleterOctoberPartials(this),
                twigFilters: new CompleterTwigFilters(this),
                assets: new CompleterAssets(this),
                pages: new CompleterPages(this),
                content: new CompleterContent(this)
            };

            this.linkProviders = {
                octoberTemplates: new ClickHandlerTemplate(this, {
                    canManagePartials: cmsCustomData.canManagePartials,
                    canManageContent: cmsCustomData.canManageContent,
                    canManageAssets: cmsCustomData.canManageAssets,
                    canManagePages: cmsCustomData.canManagePages,
                    editableAssetExtensions: cmsCustomData.editableAssetExtensions
                }),

                lessImports: new ClickHandlerCssImports(this, {
                    extension: 'less'
                }),

                scssImports: new ClickHandlerCssImports(this, {
                    extension: 'scss'
                })
            };

            this.hoverProviders = {
                octoberTags: new HoverProviderOctoberTags(this),
                twigFilters: new HoverProviderTwigFilters(this)
            };

            this.actionHandlers = new Map();
        }

        babelHelpers.createClass(CmsIntellisense, [{
            key: 'getCustomData',
            value: function getCustomData() {
                return this.customData;
            }
        }, {
            key: 'init',
            value: function init(monaco, editor, monacoComponent) {
                if (!this.globalInitialized) {
                    this.globalInitialized = true;
                    monaco.languages.registerCompletionItemProvider('twig', this.completers.octoberTags);
                    monaco.languages.registerCompletionItemProvider('twig', this.completers.octoberPartials);
                    monaco.languages.registerCompletionItemProvider('twig', this.completers.twigFilters);
                    monaco.languages.registerCompletionItemProvider('twig', this.completers.assets);
                    monaco.languages.registerCompletionItemProvider('twig', this.completers.pages);
                    monaco.languages.registerCompletionItemProvider('twig', this.completers.content);

                    monaco.languages.registerLinkProvider('twig', this.linkProviders.octoberTemplates);
                    monaco.languages.registerLinkProvider('less', this.linkProviders.lessImports);
                    monaco.languages.registerLinkProvider('scss', this.linkProviders.scssImports);

                    monaco.languages.registerHoverProvider('twig', this.hoverProviders.octoberTags);
                    monaco.languages.registerHoverProvider('twig', this.hoverProviders.twigFilters);
                }

                this.addActionHandler(editor, new ActionHandlerExpandComponent(this, editor, monacoComponent));
            }
        }, {
            key: 'trans',
            value: function trans(key) {
                return $.oc.editor.getLangStr(key);
            }
        }, {
            key: 'addActionHandler',
            value: function addActionHandler(editor, handler) {
                if (this.actionHandlers.has(editor)) {
                    var handlers = this.actionHandlers.get(editor);
                    handlers.push(handler);
                    return;
                }

                this.actionHandlers.set(editor, [handler]);
            }
        }, {
            key: 'on',
            value: function on(eventType, listener) {
                this.listeners.set(listener, eventType);
            }
        }, {
            key: 'off',
            value: function off(listener) {
                this.listeners.delete(listener);
            }
        }, {
            key: 'emit',
            value: function emit(eventType, payload) {
                this.listeners.forEach(function (listenerEventType, listener) {
                    if (listenerEventType == eventType) {
                        listener(payload);
                    }
                });
            }
        }, {
            key: 'escapeHtml',
            value: function escapeHtml(str) {
                return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            }
        }, {
            key: 'modelHasTag',
            value: function modelHasTag(model, tag) {
                if (!model.octoberEditorCmsTags) {
                    return false;
                }

                return model.octoberEditorCmsTags.indexOf(tag) !== -1;
            }
        }, {
            key: 'getModelCustomAttribute',
            value: function getModelCustomAttribute(model, name) {
                if (model.octoberEditorAttributes === undefined) {
                    return undefined;
                }

                return model.octoberEditorAttributes[name];
            }
        }, {
            key: 'disposeForEditor',
            value: function disposeForEditor(editor) {
                this.actionHandlers.forEach(function (handlers, currentEditor) {
                    if (currentEditor == editor) {
                        handlers.forEach(function (handler) {
                            return handler.dispose();
                        });
                    }
                });
            }
        }, {
            key: 'onContextMenu',
            value: function onContextMenu(payload) {
                this.actionHandlers.forEach(function (handlers, editor) {
                    if (editor == payload.editor) {
                        handlers.forEach(function (handler) {
                            handler.onContextMenu(payload.editor, payload.target);
                        });
                    }
                });
            }
        }, {
            key: 'onFilterSupportedActions',
            value: function onFilterSupportedActions(payload) {
                this.actionHandlers.forEach(function (handlers, editor) {
                    if (editor == payload.editor) {
                        handlers.forEach(function (handler) {
                            return handler.onFilterSupportedActions(payload);
                        });
                    }
                });
            }
        }]);
        return CmsIntellisense;
    }();

    return {
        make: function make(cmsCustomData) {
            if (instance !== null) {
                return instance;
            }

            return instance = new CmsIntellisense(cmsCustomData);
        }
    };
});
