/*
 * Vue modal alert implementation
 */
$.oc.module.register('backend.component.modal.alert', function () {
    Vue.component('backend-component-modal-alert', {
        props: {
            title: {
                type: String,
                required: true
            },
            text: {
                type: String,
                required: true
            },
            buttonText: {
                type: String,
                required: false
            },
            size: {
                type: String,
                default: 'normal',
                validator: function (value) {
                    return ['small', 'normal', 'large'].indexOf(value) !== -1;
                }
            }
        },
        data: function () {
            return {
                uniqueKey: $.oc.domIdManager.generate('modal-alert'),
                modalTitleId: $.oc.domIdManager.generate('modal-alert-title'),
                primaryButtonText: ""
            };
        },
        computed: {
        },
        methods: {
            onHidden: function onHidden() {
                this.$destroy();
            }
        },
        mounted: function onMounted() {
            if (this.buttonText) {
                this.primaryButtonText = this.buttonText;
            }
            else {
                this.primaryButtonText = $(this.$el).attr('data-default-button-text');
            }

            this.$refs.modal.show();
        },
        beforeDestroy: function beforeDestroy() {
            
        },
        template: '#backend_vuecomponents_modal_alert'
    });
});