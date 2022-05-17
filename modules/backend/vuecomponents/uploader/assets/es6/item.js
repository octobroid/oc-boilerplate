/*
 *  Universal file uploader item implementation
 */
$.oc.module.register('backend.component.uploader.item', function () {
    Vue.component('backend-component-uploader-item', {
        props: {
            errorMessage: String,
            fileName: String,
            progress: Number,
            status: String
        },
        data: function () {
            return {};
        },
        computed: {
            cssClass: function computeCssClass() {
                return {
                    'status-completed': this.status === 'completed',
                    'status-uploading': this.status === 'uploading',
                    'status-error': this.status === 'error'
                };
            }
        },
        methods: {},
        template: '#backend_vuecomponents_uploader_item'
    });
});