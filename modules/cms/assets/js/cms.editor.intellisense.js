$.oc.module.register('cms.editor.intellisense', function() {
    'use strict';

    const CompleterOctoberTags = $.oc.module.import('cms.editor.intellisense.completer.octobertags');
    const CompleterTwigFilters = $.oc.module.import('cms.editor.intellisense.completer.twigfilters');
    const CompleterOctoberPartials = $.oc.module.import('cms.editor.intellisense.completer.partials');
    const CompleterAssets = $.oc.module.import('cms.editor.intellisense.completer.assets');
    const CompleterPages = $.oc.module.import('cms.editor.intellisense.completer.pages');
    const CompleterContent = $.oc.module.import('cms.editor.intellisense.completer.content');

    const ClickHandlerTemplate = $.oc.module.import('cms.editor.intellisense.clickhandler.template');
    const ClickHandlerCssImports = $.oc.module.import('cms.editor.intellisense.clickhandler.cssimports');
    const HoverProviderOctoberTags = $.oc.module.import('cms.editor.intellisense.hoverprovider.octobertags');
    const HoverProviderTwigFilters = $.oc.module.import('cms.editor.intellisense.hoverprovider.twigfilters');
    const IntellisenseUtils = $.oc.module.import('cms.editor.intellisense.utils.js');
    const ActionHandlerExpandComponent = $.oc.module.import('cms.editor.intellisense.actionhandlers.expandcomponent');

    let instance = null;

    class CmsIntellisense {
        customData;
        completers;
        globalInitialized;
        linkProviders;
        hoverProviders;
        listeners;
        utils;
        actionHandlers;

        constructor(cmsCustomData) {
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

        getCustomData() {
            return this.customData;
        }

        init(monaco, editor, monacoComponent) {
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

        trans(key) {
            return $.oc.editor.getLangStr(key);
        }

        addActionHandler(editor, handler) {
            if (this.actionHandlers.has(editor)) {
                const handlers = this.actionHandlers.get(editor);
                handlers.push(handler);
                return;
            }

            this.actionHandlers.set(editor, [handler]);
        }

        on(eventType, listener) {
            this.listeners.set(listener, eventType);
        }

        off(listener) {
            this.listeners.delete(listener);
        }

        emit(eventType, payload) {
            this.listeners.forEach((listenerEventType, listener) => {
                if (listenerEventType == eventType) {
                    listener(payload);
                }
            });
        }

        escapeHtml(str) {
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        modelHasTag(model, tag) {
            if (!model.octoberEditorCmsTags) {
                return false;
            }

            return model.octoberEditorCmsTags.indexOf(tag) !== -1;
        }

        getModelCustomAttribute(model, name) {
            if (model.octoberEditorAttributes === undefined) {
                return undefined;
            }

            return model.octoberEditorAttributes[name];
        }

        disposeForEditor(editor) {
            this.actionHandlers.forEach((handlers, currentEditor) => {
                if (currentEditor == editor) {
                    handlers.forEach((handler) => handler.dispose());
                }
            });
        }

        onContextMenu(payload) {
            this.actionHandlers.forEach((handlers, editor) => {
                if (editor == payload.editor) {
                    handlers.forEach((handler) => {
                        handler.onContextMenu(payload.editor, payload.target);
                    });
                }
            });
        }

        onFilterSupportedActions(payload) {
            this.actionHandlers.forEach((handlers, editor) => {
                if (editor == payload.editor) {
                    handlers.forEach((handler) => handler.onFilterSupportedActions(payload));
                }
            });
        }
    }

    return {
        make: function(cmsCustomData) {
            if (instance !== null) {
                return instance;
            }

            return (instance = new CmsIntellisense(cmsCustomData));
        }
    };
});
