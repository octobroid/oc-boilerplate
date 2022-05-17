$.oc.module.register('backend.component.documentmarkdowneditor.formwidgetconnector', function () {
    Vue.component('backend-component-documentmarkdowneditor-formwidgetconnector', {
        props: {
            textarea: null,
            lang: {},
            useMediaManager: {
                type: Boolean,
                default: false
            },
            options: Object
        },
        data: function data() {
            var toolbarExtensionPoint = [];

            return {
                toolbarExtensionPoint: toolbarExtensionPoint,
                fullScreen: false,
                value: ''
            };
        },
        computed: {
            toolbarElements: function computeToolbarElements() {
                return [this.toolbarExtensionPoint, {
                    type: 'button',
                    icon: this.fullScreen ? 'octo-icon-fullscreen-collapse' : 'octo-icon-fullscreen',
                    command: 'document:toggleFullscreen',
                    pressed: this.fullScreen,
                    fixedRight: true,
                    tooltip: this.lang.langFullscreen
                }];
            },

            externalToolbarEventBus: function computeExternalToolbarEventBus() {
                return this.options.externalToolbarEventBus;
            },

            toolbarExtensionPointProxy: function computeToolbarExtensionPointProxy() {
                if (!this.options.externalToolbarAppState) {
                    return this.toolbarExtensionPoint;
                }

                // Expected format: tailor.app::toolbarExtensionPoint
                var parts = this.options.externalToolbarAppState.split('::');
                if (parts.length !== 2) {
                    throw new Error('Invalid externalToolbarAppState format. Expected format: module.name::stateElementName');
                }

                var app = $.oc.module.import(parts[0]);
                return app.state[parts[1]];
            },

            hasExternalToolbar: function computeHasExternalToolbar() {
                return !!this.options.externalToolbarAppState;
            }
        },
        mounted: function onMounted() {
            var _this = this;

            this.value = this.textarea.value;

            Vue.nextTick(function () {
                _this.$refs.markdownEditor.clearHistory();
            });
        },
        methods: {
            onFocus: function onFocus() {
                this.$emit('focus');
            },

            onBlur: function onBlur() {
                this.$emit('blur');
            },

            onToolbarCommand: function onToolbarCommand(cmd) {
                var _this2 = this;

                if (cmd == 'document:toggleFullscreen') {
                    this.fullScreen = !this.fullScreen;

                    Vue.nextTick(function () {
                        _this2.$refs.markdownEditor.refresh();
                    });
                }
            }
        },
        beforeDestroy: function beforeDestroy() {
            if (this.$refs.toolbar) {
                this.$refs.toolbar.$destroy();
            }

            this.$refs.document.$destroy();
            this.textarea = null;
        },
        watch: {
            value: function watchValue(newValue) {
                if (newValue != this.textarea.value) {
                    this.textarea.value = newValue;
                    this.$emit('change');
                }
            }
        },
        template: '#backend_vuecomponents_documentmarkdowneditor_formwidgetconnector'
    });
});
