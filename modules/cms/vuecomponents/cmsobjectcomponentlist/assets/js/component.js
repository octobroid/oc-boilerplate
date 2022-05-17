Vue.component('cmd-object-component-list-component', {
    props: {
        component: Object
    },
    computed: {
        isInspectable: function computeIsInspectable() {
            if (this.component.inspectorEnabled) {
                return true;
            }
        },

        componentIcon: function computeComponentIcon() {
            var result = this.component.icon;

            if (result.substring(0, 3) !== 'oc-') {
                result = 'oc-' + result;
            }

            return result;
        },

        inspectorTitle: function computeInspectorTitle() {
            if (!this.component.inspectorEnabled) {
                return null;
            }

            return this.component.title;
        },

        inspectorDescription: function computeInspectorDescription() {
            if (!this.component.inspectorEnabled) {
                return null;
            }

            return this.component.description;
        },

        inspectorConfig: function computeInspectorConfig() {
            if (!this.component.inspectorEnabled) {
                return null;
            }

            return this.component.propertyConfig;
        },

        inspectorClass: function computeInspectorClass() {
            if (!this.component.inspectorEnabled) {
                return null;
            }

            return this.component.className;
        }
    },
    methods: {
        onInspectorHidden: function onInspectorHidden(ev) {
            var values = this.$refs.component_properties.value;
            this.component.propertyValues = values;

            values = JSON.parse(values);
            this.component.alias = values['oc.alias'];
        },

        onInspectorHiding: function onInspectorHiding(ev, values) {
            this.$emit('inspectorhiding', { ev: ev, values: values });
        }
    },
    mounted: function mounted() {
        $(this.$el).on('hidden.oc.inspector', this.onInspectorHidden);
        $(this.$el).on('hiding.oc.inspector', this.onInspectorHiding);
    },
    beforeDestroy: function beforeDestroy() {
        $(this.$el).off('hidden.oc.inspector', this.onInspectorHidden);
        $(this.$el).off('hiding.oc.inspector', this.onInspectorHiding);
    },
    template: '#cms_vuecomponents_cmsobjectcomponentlist_component'
});
