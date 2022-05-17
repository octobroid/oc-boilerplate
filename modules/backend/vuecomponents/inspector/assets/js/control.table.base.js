$.oc.module.register('backend.component.inspector.tablecontrolbase', function () {
    var TableControlBase = {
        props: {
            row: Object,
            column: Object,
            cellIndex: Number,
            inspectorPreferences: Object
        },
        methods: {
            focusControl: function focusControl() {
            },

            validatePropertyValue: function validate() {
                var validatorSet = new $.oc.vueComponentHelpers.inspector.validatorSet(
                        this.column,
                        this.column.column
                    ),
                    result = validatorSet.validate(this.row[this.column.column]);

                if (result !== null) {
                    this.$emit('invalid');
                }
                else {
                    this.$emit('valid');
                }

                return result;
            },
        }
    };

    if ($.oc.vueComponentHelpers === undefined) {
        $.oc.vueComponentHelpers = {};
    }

    if ($.oc.vueComponentHelpers === undefined) {
        $.oc.vueComponentHelpers.inspector = {};
    }

    if ($.oc.vueComponentHelpers.inspector.table === undefined) {
        $.oc.vueComponentHelpers.inspector.table = {};
    }

    $.oc.vueComponentHelpers.inspector.table.controlBase = TableControlBase;
});