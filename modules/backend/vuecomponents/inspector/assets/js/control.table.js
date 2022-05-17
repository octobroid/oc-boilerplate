/*
 * Vue Inspector table control implementation
 */
$.oc.module.register('backend.component.inspector.control.table', function () {
    Vue.component('backend-component-inspector-control-table', {
        extends: $.oc.vueComponentHelpers.inspector.controlBase,
        props: {
            layoutUpdateData: {
                type: Object
            },
            inspectorPreferences: Object
        },
        data: function () {
            return {
                edited: false,
                lang: {
                    addItem: ""
                }
            };
        },
        computed: {
            columns: function computeColumns() {
                if (!Array.isArray(this.control.columns)) {
                    throw new Error('The columns property not found in the Inspector table definition. Property: '
                        + this.control.property);
                }

                return this.control.columns;
            },

            tableConfiguration: function computeTableConfiguration() {
                return {
                    deleting: this.control.deleting || this.control.deleting === undefined,
                    adding: this.control.adding || this.control.adding === undefined
                };
            }
        },
        methods: {
            updateValue: function updateValue() {
                this.edited = true;
                this.setManagedValue(this.$refs.input.value);
            },

            getTableRowComponents: function getTableRowComponents() {
                return this.$children.filter(function (child) {
                    return child.isTableRow;
                });
            },

            getDefaultValue: function getDefaultValue() {
                return [];
            },

            focusControl: function focusControl() {
                var rows = this.getTableRowComponents();
                if (rows.length) {
                    rows[0].focusFirst();
                }
            },

            onRemoveRowClick: function onRemoveRowClick(index) {
                this.value.splice(index, 1);
            },

            onAddItemClick: function onAddItemClick() {
                if (this.inspectorPreferences.readOnly) {
                    return;
                }

                var newObj = {},
                    utils = $.oc.vueComponentHelpers.inspector.utils;

                this.columns.forEach(function (column) {
                    newObj[column.column] = null;
                });

                this.value.push(newObj);

                var that = this;
                Vue.nextTick(function () {
                    var rows = that.getTableRowComponents();
                    rows[rows.length - 1].focusFirst();
                });
            }
        },
        created: function created() {

        },
        mounted: function mounted() {
            this.lang.addItem = this.$el.getAttribute('data-lang-add-item');
            this.$emit('hidebottomborder');

            if (!this.control.title) {
                this.$emit('hidefullwidthlabel');
            }
        },
        watch: {
            'layoutUpdateData.modalShown': function onModalShown() {
                if (this.control.defaultFocus) {
                    // Focus after visbility animations are ready
                    var self = this;
                    setTimeout(function () {
                        self.focusControl();
                    }, 100);
                }
            }
        },
        template: '#backend_vuecomponents_inspector_control_table'
    });
});