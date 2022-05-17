/*
 * Vue Inspector table control implementation
 */
$.oc.module.register('backend.component.inspector.control.table.headcell', function () {
    Vue.component('backend-component-inspector-control-table-headcell', {
        props: {
            column: Object,
            columnIndex: Number,
            columnWidth: Object
        },
        data: function () {
            return {
            };
        },
        computed: {
            cellStyle: function computeCellStyle() {
                if (this.columnWidth[this.columnIndex] === undefined) {
                    return {};
                }

                return {
                    width: this.columnWidth[this.columnIndex] + 'px'
                }
            }
        },
        methods: {
        },
        template: '#backend_vuecomponents_inspector_control_table_headcell'
    });
});