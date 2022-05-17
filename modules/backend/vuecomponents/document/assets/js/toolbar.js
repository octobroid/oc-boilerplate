$.oc.module.register('backend.component.toolbar', function () {
    Vue.component('backend-component-document-toolbar', {
        props: {
            elements: Array,
            disabled: Boolean
        },
        methods: {
            onElementCommand: function onElementCommand(command, isHotkey, ev, targetElement, customData) {
                this.$emit('command', command, isHotkey, ev, targetElement, customData);
                $(this.$el).trigger('documenttoolbarcmd', {
                    command: command,
                    isHotkey: isHotkey,
                    ev
                });
            },

            onDropdownContentShown: function onDropdownContentShown() {
                $(this.$refs.scrollable).dragScroll('pause');
            },

            onDropdownContentHidden: function onDropdownContentHidden() {
                $(this.$refs.scrollable).dragScroll('resume');
            }
        },
        computed: {
            flattenedElements: function computeFlattenedElements() {
                var result = [];

                for (var index = 0; index < this.elements.length; index++) {
                    var element = this.elements[index];
                    if (!$.isArray(element)) {
                        result.push(element);
                    }
                    else {
                        result = result.concat(element);
                    }
                }

                return result;
            },

            scrollableElements: function computeScrollableElements() {
                return this.flattenedElements.filter(function (el) {
                    return !el.fixedRight;
                });
            },

            fixedRightElements: function computeFixedRightElements() {
                return this.flattenedElements.filter(function (el) {
                    return el.fixedRight;
                });
            }
        },
        mounted: function mounted() {
            $(this.$refs.scrollable).dragScroll({
                useDrag: true,
                useNative: false,
                noScrollClasses: false,
                scrollClassContainer: this.$refs.toolbarContainer
            });
        },
        beforeDestroy: function beforeDestroy() {
            $(this.$refs.scrollable).dragScroll('dispose');
        },
        watch: {
            elements: function watchElements() {
                $(this.$refs.scrollable).dragScroll('fixScrollClasses');
            }
        },
        template: '#backend_vuecomponents_document_toolbar'
    });
});