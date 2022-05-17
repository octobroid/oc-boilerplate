$.oc.module.register('backend.component.monacoeditor', function () {
    let environmentInitialized = false;
    let emmetInitialized = false;

    function initEnvironment(baseUrl) {
        if (environmentInitialized) {
            return;
        }

        environmentInitialized = true;

        require.config({
            paths: { vs: baseUrl + '/vs' }
        });
        window.MonacoEnvironment = { getWorkerUrl: () => proxy };

        const proxy = URL.createObjectURL(
            new Blob(
                [
                    `
                        self.MonacoEnvironment = {
                            baseUrl: '${baseUrl}/'
                        };
                        importScripts('${baseUrl}/vs/base/worker/workerMain.js');
                    `
                ],
                { type: 'text/javascript' }
            )
        );
    }

    function initEditor(component, options) {
        component.modelReferences = component.modelDefinitions.map((def) => {
            return def.makeModelReference(options);
        });

        if (!emmetInitialized) {
            emmetMonaco.emmetHTML(monaco);
            emmetMonaco.emmetCSS(monaco);
            emmetInitialized = true;
        }

        component.editor = monaco.editor.create(component.$refs.editorContainer, options);

        if (component.modelReferences.length > 0) {
            component.editor.setModel(component.modelReferences[0].model);
        }

        const getSupportedActions = component.editor.getSupportedActions;
        component.editor.getSupportedActions = function() {
            const actions = getSupportedActions.apply(this);
            const payload = {
                editor: component.editor,
                actions: actions
            };
            component.$emit('filtersupportedactions', payload);

            return payload.actions;
        };

        const contextmenu = component.editor.getContribution('editor.contrib.contextmenu');
        const onContextMenu = contextmenu._onContextMenu;
        contextmenu._onContextMenu = function(eventData) {
            const payload = {
                editor: component.editor,
                target: eventData.target
            };
            component.$emit('contextmenu', payload);
            onContextMenu.apply(contextmenu, arguments);
        };

        component.timerId = setInterval(component.autoLayout, 100);
    }

    const ModelDefinition = $.oc.module.import('backend.vuecomponents.monacoeditor.modeldefinition');

    /*
     * Vue Monaco editor implementation.
     */
    Vue.component('backend-component-monacoeditor', {
        props: {
            fullHeight: {
                type: Boolean,
                default: true
            },
            containerCssClass: {
                type: String,
                default: ''
            },
            supportDragEvents: {
                type: Boolean,
                default: false
            },
            forceSingleTab: {
                type: Boolean,
                default: false
            },
            modelDefinitions: {
                type: Array,
                required: true,
                validator: (value) => {
                    return !value.some((definition) => !definition instanceof ModelDefinition);
                }
            }
        },
        computed: {
            cssClass: function computeCssClass() {
                var result = '';

                if (this.fullHeight) {
                    result += ' full-height-strict';
                }

                result += ' ' + this.containerCssClass;

                return result;
            },

            showTabs: function computeShowTabs() {
                return this.modelDefinitions.length > 1 || this.forceSingleTab;
            },

            editorTabs: function computeEditorTabs() {
                return this.modelDefinitions.map((def) => {
                    const result = {
                        label: def.tabTitle,
                        key: def.uriString
                    };

                    if (def.iconCssClass) {
                        result.icon = {
                            cssClass: def.iconCssClass
                        };
                    }

                    return result;
                });
            },

            tabsContainerCssClass: function tabsContainerCssClass() {
                return 'monaco-tabs-' + this.theme;
            },

            editorContainerCssClass: function computeEditorContainerCssClass() {
                return 'editor-container editor-container-' + this.theme;
            }
        },
        data: function() {
            return {
                editor: null,
                modelReferences: [],
                timerId: null,
                lastWidth: null,
                lastHeight: null,
                theme: 'vs-dark'
            };
        },
        methods: {
            findModelReferenceByUri: function findModelReferenceByUri(uriString) {
                return this.modelReferences.find((ref) => ref.uriString == uriString);
            },

            updateValue: function updateValue(modelDefinition, value) {
                const ref = this.findModelReferenceByUri(modelDefinition.uriString);
                if (ref && ref.model.getValue != value) {
                    ref.model.setValue(modelDefinition.preprocess(value));
                }
            },

            updateLanguage: function updateLanguage(modelDefinition, value) {
                const ref = this.findModelReferenceByUri(modelDefinition.uriString);
                if (ref && ref.model.getLanguageIdentifier().language != value) {
                    monaco.editor.setModelLanguage(ref.model, value);
                }
            },

            setModelCustomAttribute: function setModelCustomAttribute(modelDefinition, name, value) {
                const ref = this.findModelReferenceByUri(modelDefinition.uriString);
                if (!ref || !ref.model) {
                    return false;
                }

                ref.model.octoberEditorAttributes[name] = value;

                return true;
            },

            layout: function layout() {
                this.editor.layout();
            },

            formatCode: function formatCode() {
                this.editor.getAction('editor.action.formatDocument').run();
            },

            emitCustomEvent: function emitCustomEvent(eventName, payload) {
                this.$emit('customevent', eventName, payload);
            },

            autoLayout: function autoLayout() {
                if (this.lastWidth === null) {
                    this.lastWidth = this.$el.clientWidth;
                    this.lastHeight = this.$el.clientHeight;

                    return;
                }

                if (this.lastWidth != this.$el.clientWidth || this.lastHeight != this.$el.clientHeight) {
                    window.requestAnimationFrame(() => {
                        this.layout();
                    });

                    this.lastWidth = this.$el.clientWidth;
                    this.lastHeight = this.$el.clientHeight;
                }
            },

            insertText: function insertText(str) {
                const position = this.editor.getPosition();
                this.editor.executeEdits('', [
                    {
                        range: {
                            startLineNumber: position.lineNumber,
                            startColumn: position.column,
                            endLineNumber: position.lineNumber,
                            endColumn: position.column
                        },
                        text: str
                    }
                ]);
                this.editor.setSelection(
                    new monaco.Selection(position.lineNumber, position.column, position.lineNumber, position.column)
                );
            },

            replaceText: function replaceText(text, replacement, modelDefinition) {
                const ref = this.findModelReferenceByUri(modelDefinition.uriString);
                if (!ref) {
                    return;
                }

                const matches = ref.model.findMatches(text, false, false, false, null, true);
                matches.forEach((match) => {
                    ref.model.pushEditOperations(
                        [],
                        [
                            {
                                range: match.range,
                                text: replacement
                            }
                        ]
                    );
                });
            },

            replaceAsSnippet: function replaceAsSnippet(model, range, str) {
                const tab = ' '.repeat(range.startColumn - 1);
                const paddedStr = str.replace(/^/gm, tab).replace(/^\s+/, '');

                model.pushEditOperations(
                    [],
                    [
                        {
                            range: range,
                            text: paddedStr
                        }
                    ]
                );
            },

            getCurrentModelUri: function getCurrentModelUri() {
                if (!this.editor) {
                    return null;
                }

                return this.editor.getModel().uri.path.replace(/^\//, '');
            },

            onDragOver: function onDragOver(ev) {
                if (!this.supportDragEvents || !this.editor) {
                    return;
                }

                const targetAtPoint = this.editor.getTargetAtClientPoint(ev.clientX, ev.clientY);
                if (!targetAtPoint || !targetAtPoint.position) {
                    return;
                }

                if (!this.editor.hasTextFocus()) {
                    this.editor.focus();
                }

                this.editor.setPosition(targetAtPoint.position);

                ev.preventDefault();
                return false;
            },

            onDragDrop: function onDragDrop(ev) {
                if (!this.supportDragEvents || !this.editor) {
                    return;
                }

                ev.stopPropagation();
                ev.preventDefault();

                this.$emit('drop', this.editor, ev);
            },

            onTabSelected: function onTabSelected(newKey, oldKey) {
                const newRef = this.findModelReferenceByUri(newKey);
                const oldRef = this.findModelReferenceByUri(oldKey);

                if (oldRef) {
                    oldRef.setViewState(this.editor.saveViewState());
                }

                if (newRef) {
                    this.editor.setModel(newRef.model);
                    if (newRef.viewState) {
                        this.editor.restoreViewState(newRef.viewState);
                    }
                }
            }
        },
        mounted: function mounted() {
            const options = JSON.parse(this.$el.getAttribute('data-configuration'));
            initEnvironment(options.vendorPath);

            this.theme = options.theme;
            if (!this.theme) {
                this.theme = 'vs-dark';
            }

            options.theme = this.theme;
            options.tabCompletion = 'on';
            options.automaticLayout = true;

            options.minimap = {
                enabled: true
            };

            require(['vs/editor/editor.main'], () => {
                initEditor(this, options);
                this.$emit('monacoloaded', monaco, this.editor);
            });
        },
        beforeDestroy: function beforeDestroy() {
            this.$emit('dispose', this.editor);
            this.editor && this.editor.dispose();
            this.modelReferences.forEach((ref) => ref.dispose());

            if (this.timerId) {
                clearInterval(this.timerId);
            }
        },
        template: '#backend_vuecomponents_monacoeditor'
    });
});
