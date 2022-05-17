$.oc.module.register('backend.component.document', function () {
    Vue.component('backend-component-document', {
        props: {
            fullHeight: {
                type: Boolean,
                default: true
            },
            headerCollapsed: {
                type: Boolean,
                default: false
            },
            fullScreen: {
                type: Boolean,
                default: false
            },
            loading: {
                type: Boolean,
                default: false
            },
            processing: {
                type: Boolean,
                default: false
            },
            errorLoadingDocument: {
                type: String,
                default: ''
            },
            errorLoadingDocumentHeader: {
                type: String,
                default: 'Error loading document'
            },
            containerCssClass: {
                type: String,
                default: ''
            },
            builtInMode: {
                type: Boolean,
                default: false
            },
            toolbarCommandEventBus: null
        },
        computed: {
            cssClass: function computeCssClass() {
                var result = '';

                if (this.fullHeight) {
                    result += ' full-height-strict';
                }

                if (this.fullScreen) {
                    result += ' full-screen';
                }

                if (this.loading || this.errorLoadingDocument) {
                    result += ' justify-center align-center';
                }

                if (this.builtInMode) {
                    result += ' built-in-mode';
                }

                if (!this.$slots.header) {
                    result += ' no-header-elements';
                }

                if (!this.$slots.toolbar) {
                    result += ' no-toolbar-elements';
                }

                result += ' ' + this.containerCssClass;

                return result;
            }
        },
        methods: {
            onDocumentToolbarCommand: function onDocumentToolbarCommand(ev, eventData) {
                for (var index = 0; index < this.$children.length; index++) {
                    this.$children[index].$emit('toolbarcmd', eventData);
                }

                if (this.toolbarCommandEventBus) {
                    this.toolbarCommandEventBus.$emit('toolbarcmd', eventData);
                }
            }
        },
        mounted: function onMounted() {
            $(this.$el).on('documenttoolbarcmd.document', this.onDocumentToolbarCommand);
        },
        watch: {
            fullScreen: function watchFullScreen(value) {
                $(document.body).toggleClass('component-backend-document-fullscreen', value);
            }
        },
        beforeDestroy: function beforeDestroy() {
            $(this.$el).off('.document');
        },
        template: '#backend_vuecomponents_document'
    });
});