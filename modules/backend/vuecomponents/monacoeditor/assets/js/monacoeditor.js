$.oc.module.register('backend.component.monacoeditor', function () {
    var environmentInitialized = false;
    var emmetInitialized = false;

    function initEnvironment(baseUrl) {
        if (environmentInitialized) {
            return;
        }

        environmentInitialized = true;

        require.config({
            paths: { vs: baseUrl + '/vs' }
        });
        window.MonacoEnvironment = { getWorkerUrl: function getWorkerUrl() {
                return proxy;
            } };

        var proxy = URL.createObjectURL(new Blob(['\n                        self.MonacoEnvironment = {\n                            baseUrl: \'' + baseUrl + '/\'\n                        };\n                        importScripts(\'' + baseUrl + '/vs/base/worker/workerMain.js\');\n                    '], { type: 'text/javascript' }));
    }

    function initEditor(component, options) {
        component.modelReferences = component.modelDefinitions.map(function (def) {
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

        var getSupportedActions = component.editor.getSupportedActions;
        component.editor.getSupportedActions = function () {
            var actions = getSupportedActions.apply(this);
            var payload = {
                editor: component.editor,
                actions: actions
            };
            component.$emit('filtersupportedactions', payload);

            return payload.actions;
        };

        var contextmenu = component.editor.getContribution('editor.contrib.contextmenu');
        var onContextMenu = contextmenu._onContextMenu;
        contextmenu._onContextMenu = function (eventData) {
            var payload = {
                editor: component.editor,
                target: eventData.target
            };
            component.$emit('contextmenu', payload);
            onContextMenu.apply(contextmenu, arguments);
        };

        component.timerId = setInterval(component.autoLayout, 100);
    }

    var ModelDefinition = $.oc.module.import('backend.vuecomponents.monacoeditor.modeldefinition');

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
                validator: function validator(value) {
                    return !value.some(function (definition) {
                        return !definition instanceof ModelDefinition;
                    });
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
                return this.modelDefinitions.map(function (def) {
                    var result = {
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
        data: function data() {
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
                return this.modelReferences.find(function (ref) {
                    return ref.uriString == uriString;
                });
            },

            updateValue: function updateValue(modelDefinition, value) {
                var ref = this.findModelReferenceByUri(modelDefinition.uriString);
                if (ref && ref.model.getValue != value) {
                    ref.model.setValue(modelDefinition.preprocess(value));
                }
            },

            updateLanguage: function updateLanguage(modelDefinition, value) {
                var ref = this.findModelReferenceByUri(modelDefinition.uriString);
                if (ref && ref.model.getLanguageIdentifier().language != value) {
                    monaco.editor.setModelLanguage(ref.model, value);
                }
            },

            setModelCustomAttribute: function setModelCustomAttribute(modelDefinition, name, value) {
                var ref = this.findModelReferenceByUri(modelDefinition.uriString);
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
                var _this = this;

                if (this.lastWidth === null) {
                    this.lastWidth = this.$el.clientWidth;
                    this.lastHeight = this.$el.clientHeight;

                    return;
                }

                if (this.lastWidth != this.$el.clientWidth || this.lastHeight != this.$el.clientHeight) {
                    window.requestAnimationFrame(function () {
                        _this.layout();
                    });

                    this.lastWidth = this.$el.clientWidth;
                    this.lastHeight = this.$el.clientHeight;
                }
            },

            insertText: function insertText(str) {
                var position = this.editor.getPosition();
                this.editor.executeEdits('', [{
                    range: {
                        startLineNumber: position.lineNumber,
                        startColumn: position.column,
                        endLineNumber: position.lineNumber,
                        endColumn: position.column
                    },
                    text: str
                }]);
                this.editor.setSelection(new monaco.Selection(position.lineNumber, position.column, position.lineNumber, position.column));
            },

            replaceText: function replaceText(text, replacement, modelDefinition) {
                var ref = this.findModelReferenceByUri(modelDefinition.uriString);
                if (!ref) {
                    return;
                }

                var matches = ref.model.findMatches(text, false, false, false, null, true);
                matches.forEach(function (match) {
                    ref.model.pushEditOperations([], [{
                        range: match.range,
                        text: replacement
                    }]);
                });
            },

            replaceAsSnippet: function replaceAsSnippet(model, range, str) {
                var tab = ' '.repeat(range.startColumn - 1);
                var paddedStr = str.replace(/^/gm, tab).replace(/^\s+/, '');

                model.pushEditOperations([], [{
                    range: range,
                    text: paddedStr
                }]);
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

                var targetAtPoint = this.editor.getTargetAtClientPoint(ev.clientX, ev.clientY);
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
                var newRef = this.findModelReferenceByUri(newKey);
                var oldRef = this.findModelReferenceByUri(oldKey);

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
            var _this2 = this;

            var options = JSON.parse(this.$el.getAttribute('data-configuration'));
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

            require(['vs/editor/editor.main'], function () {
                initEditor(_this2, options);
                _this2.$emit('monacoloaded', monaco, _this2.editor);
            });
        },
        beforeDestroy: function beforeDestroy() {
            this.$emit('dispose', this.editor);
            this.editor && this.editor.dispose();
            this.modelReferences.forEach(function (ref) {
                return ref.dispose();
            });

            if (this.timerId) {
                clearInterval(this.timerId);
            }
        },
        template: '#backend_vuecomponents_monacoeditor'
    });
});
