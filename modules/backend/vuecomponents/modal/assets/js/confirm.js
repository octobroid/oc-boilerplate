/*
 * Vue modal confirmation implementation
 */
$.oc.module.register('backend.component.modal.confirm', function () {
    Vue.component('backend-component-modal-confirm', {
        extends: Vue.options.components['backend-component-modal-alert'],
        props: {
            isDanger: {
                type: Boolean,
                default: false
            }
        },
        data: function () {
            return {
            };
        },
        computed: {
        },
        methods: {
            onButtonClick: function onButtonClick() {
                this.$emit('buttonclick');
                this.$refs.modal.hide();
            }
        },
        template: '#backend_vuecomponents_modal_confirm'
    });
});