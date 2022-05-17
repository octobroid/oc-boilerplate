/*
 * Vue Inspector table control implementation
 */
$.oc.module.register('backend.component.inspector.control.table.row', function () {
    Vue.component('backend-component-inspector-control-table-row', {
        props: {
            columns: Array,
            row: Object,
            inspectorPreferences: Object,
            tableConfiguration: Object,
            rowIndex: Number
        },
        data: function () {
            return {
                focused: false,
                isTableRow: true,
                hasErrors: false
            };
        },
        computed: {
        },
        methods: {
            focusFirst: function focusFirst() {
                if (this.$children.length) {
                    this.$children[0].focusControl();
                }
            },
        
            onCellFocus: function onCellFocus() {
                this.focused = true;
            },

            onCellBlur: function onCellBlur() {
                this.focused = false;
            },

            onValid: function onValid() {
                this.hasErrors = false;
            },

            onInvalid: function onInvalid() {
                this.hasErrors = true;
            }
        },
        template: '#backend_vuecomponents_inspector_control_table_row'
    });
});