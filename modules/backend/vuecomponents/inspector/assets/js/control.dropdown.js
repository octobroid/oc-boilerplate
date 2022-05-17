/*
 * Vue Inspector dropdown control implementation
 */
$.oc.module.register('backend.component.inspector.control.dropdown', function () {
    Vue.component('backend-component-inspector-control-dropdown', {
        extends: $.oc.vueComponentHelpers.inspector.controlBase,
        props: {},
        data: function () {
            return {
                dynamicOptions: {},
                selectedValue: null,
                editorFocused: false
            };
        },
        computed: {
            options: function computeOptions() {
                var options = this.control.options ? this.control.options : this.dynamicOptions,
                    optionKeys = Object.keys(options),
                    result = [];

                optionKeys.forEach(function (key) {
                    result.push({
                        label: options[key],
                        code: key
                    });
                });

                return result;
            },

            containerTabIndex: function computeContainerTabIndex() {
                return this.editorFocused ? -1 : 0;
            }
        },
        methods: {
            focusControl: function focusControl() {
                this.$refs.input.activate();
                this.editorFocused = true;
            },

            updateValue: function updateValue(option) {
                var value = option ? option.code : null;
                this.setManagedValue(value);
            },

            findOptionByValue: function findOptionByValue(value) {
                if (!this.options) {
                    return null;
                }

                for (var index = 0; index < this.options.length; index++) {
                    if (this.options[index].code == value) {
                        return this.options[index];
                    }
                }
            },

            setInitialValue: function () {
                var value = this.value;

                // TODO - make this conversion configurable.
                // It works for CMS page layouts where we get null
                // as an input value but want to return an empty string
                // if the empty value is selected.
                if (value === null) {
                    value = '';
                }

                if (value !== undefined) {
                    this.selectedValue = this.findOptionByValue(value);
                }
            },

            dynamicOptionsLoaded: function dynamicOptionsLoaded(data) {
                this.dynamicOptions = {};

                if (data.options) {
                    for (var i = 0, len = data.options.length; i < len; i++) {
                        Vue.set(this.dynamicOptions, data.options[i].value, data.options[i].title);
                    }
                }

                this.setInitialValue();
            },

            onDropdownMounted: function onDropdownMounted() {
                $(this.$el).find('.multiselect__select').addClass('backend-icon-background-pseudo');
            },

            onFocus: function onFocus() {
                this.$emit('focus', { target: this.$refs.input.$el });
                this.editorFocused = true;
            },

            onBlur: function onBlur() {
                this.$emit('blur', { target: this.$refs.input.$el });
                this.editorFocused = false;
            },

            onInspectorLabelClick: function onInspectorLabelClick() {
                this.$refs.input.activate();
            },

            onContainerFocus: function onContainerFocus() {
                this.$refs.input.activate();
            }
        },
        mounted: function () {
            if (!this.control.options) {
                this.loadDynamicOptions();
            }
            else {
                this.setInitialValue();
            }
        },
        template: '#backend_vuecomponents_inspector_control_dropdown'
    });
});