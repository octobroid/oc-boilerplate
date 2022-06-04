/*
 *  Read-only information table implementation
 */
$.oc.module.register('backend.component.infotable', function () {
    Vue.component('backend-component-infotable', {
        props: {
            items: {
                type: Array,
                required: true
            }
        },
        data: function () {
            return {};
        },
        computed: {},
        methods: {},
        template: '#backend_vuecomponents_infotable'
    });
});