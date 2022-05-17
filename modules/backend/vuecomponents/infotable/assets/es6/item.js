/*
 *  Read-only information table item implementation
 */
$.oc.module.register('backend.component.infotable.item', function () {
    Vue.component('backend-component-infotable-item', {
        props: {
            title: {
                type: String,
                required: true
            },
            value: {
                type: String,
                required: true
            }
        },
        data: function () {
            return {};
        },
        computed: {},
        methods: {},
        template: '#backend_vuecomponents_infotable_item'
    });
});