Vue.component('editor-document-info-popup', {
    props: {},
    data: function() {
        return {
            items: [],
            title: ''
        };
    },
    computed: {},
    methods: {
        show: function show(items, title) {
            this.items = items;
            this.title = title;
            this.$refs.modal.show();
        }
    },
    template: '#editor_vuecomponents_documentinfopopup'
});
