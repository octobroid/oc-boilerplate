/*
 * Vue Inspector set control implementation
 */
$.oc.module.register('backend.component.inspector.control.set', function () {
    Vue.component('backend-component-inspector-control-set', {
        extends: $.oc.vueComponentHelpers.inspector.controlBase,
        props: {
            layoutUpdateData: {
                type: Object
            },
            inspectorPreferences: Object,
            splitterData: {
                type: Object,
                required: true
            },
            inspectorUniqueId: {
                type: String,
                required: true
            },
            depth: {
                type: Number,
                required: true
            },
            panelUpdateData: {
                type: Object,
                required: true
            },
            layoutUpdateData: {
                type: Object
            }
        },
        data: function () {
            var value = this.computeValue(),
                initialValue = {};

            if ($.isArray(value)) {
                value.forEach(function (element) {
                    initialValue[element] = 1;
                });
            }
            else if (typeof value === 'object') {
                initialValue = value;
            }

            return {
                editedObject: initialValue,
                loadedItems: {}
            };
        },
        computed: {
            nestedControlProperties: function computeNestedControlProperties() {
                var items = this.items,
                    result = [];

                for (var prop in items) {
                    if (!items.hasOwnProperty(prop)) {
                        continue;
                    }

                    result.push({
                        'property': prop,
                        'title': items[prop],
                        'type': 'checkbox',
                        'default': false
                    });
                }
            
                return result;
            },

            items: function computeItems() {
                var items = this.control.items ? this.control.items : this.loadedItems;

                return items;
            },

            groupValue: function computeGroupValue() {
                var value = this.computeValue();
                if ($.isArray(value) && value.length > 0) {
                    var items = this.items,
                        titles = [];
                
                    for (var i = 0; i < value.length; i++) {
                        var currentValue = value[i];
                        if (items[currentValue] != undefined) {
                            titles.push(items[currentValue]);
                        }
                    }

                    if (titles.length > 0) {
                        return '[' + titles.join(', ') + ']';
                    }

                    return '';
                }

                return '';
            }
        },
        methods: {
            updateValue: function updateValue(value) {
                var storedValue = [];
            
                if (typeof value === 'object') {
                    for (var prop in value) {
                        if (!value.hasOwnProperty(prop)) {
                            continue;
                        }

                        if (value[prop]) {
                            storedValue.push(prop);
                        }
                    }
                }

                this.setManagedValue(storedValue);
            },

            dynamicOptionsLoaded: function dynamicOptionsLoaded(data) {
                if (data.options) {
                    this.loadedItems = {};
                    for (var i = 0, len = data.options.length; i < len; i++) {
                        Vue.set(this.loadedItems, data.options[i].value, data.options[i].title);
                    }
                }
            },

            getDefaultValue: function getDefaultValue() {
                return {};
            },
        },
        created: function created() {

        },
        mounted: function mounted() {
            this.$emit('hidefullwidthlabel');
            this.$emit('hidebottomborder');
        
            if (!this.control.items) {
                this.loadDynamicOptions();
            }
        },
        watch: {
            editedObject: {
                deep: true,
                handler: function (newValue, oldValue) {
                    this.updateValue(newValue);
                }
            }
        },
        template: '#backend_vuecomponents_inspector_control_set'
    });
});