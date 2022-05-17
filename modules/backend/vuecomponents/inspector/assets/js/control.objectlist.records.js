/*
 * Vue Inspector object list records control implementation
 */
$.oc.module.register('backend.component.inspector.control.objectlist.records', function () {
    Vue.component('backend-component-inspector-control-objectlist-records', {
        extends: $.oc.vueComponentHelpers.inspector.controlBase,
        props: {
            obj: {
                type: [Object, Array],
                required: true
            },
            layoutUpdateData: {
                type: Object
            },
            inspectorPreferences: Object
        },
        data: function () {
            return {
                lang: {
                    addItem: ""
                }
            };
        },
        computed: {
            hasValues: function computeHasValues() {
                return !$.oc.vueComponentHelpers.inspector.utils.isValueEmpty(this.obj);
            }
        },
        methods: {
            getRecordTitle: function getRecordTitle(record) {
                return record[this.control.titleProperty];
            },

            onRemoveItemClick: function onRemoveItemClick(index) {
                if ($.isArray(this.obj)) {
                    this.obj.splice(index, 1);
                }
                else {
                    Vue.delete(this.obj, index);
                }
            },

            onAddItemClick: function onAddItemClick() {
                if (this.inspectorPreferences.readOnly) {
                    return;
                }

                this.$emit('inspectorcommand', {
                    command: 'addItem'
                });
            },

            onItemClick: function onItemClick(key) {
                if (this.inspectorPreferences.readOnly) {
                    return;
                }

                this.$emit('inspectorcommand', {
                    command: 'editItem',
                    key: key
                });
            }
        },
        mounted: function mounted() {
            this.$emit('hidefullwidthlabel');
            this.$emit('hidebottomborder');
            this.lang.addItem = this.$el.getAttribute('data-lang-add-item');
        },
        template: '#backend_vuecomponents_inspector_control_objectlist_records'
    });
});