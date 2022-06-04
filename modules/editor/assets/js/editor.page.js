$(document).ready(function() {
    const EditorStore = $.oc.module.import('editor.store');

    class EditorPage {
        constructor(height, width) {
            this.store = new EditorStore();

            this.init();
        }

        init() {
            this.initVue();
            this.initListeners();
        }

        initVue() {
            const initialState = $('#editor-initial-state').html();
            this.store.setInitialState(JSON.parse(initialState));

            // Components need access to the store
            // during initialization.
            //
            $.oc.editor = $.oc.editor || {};
            $.oc.editor.store = this.getStore();

            this.vm = new Vue({
                data: {
                    store: this.store
                },
                el: '#page-container'
            });
        }

        initListeners() {
            window.addEventListener('beforeunload', function(event) {
                if ($.oc.editor.application.hasChangedTabs()) {
                    event.preventDefault();
                    event.returnValue = 'There are unsaved changes.';
                }
            });
        }

        getApplication() {
            return this.vm.$refs.application;
        }

        getLangStr(str) {
            return this.store.state.lang[str];
        }

        getStore() {
            return this.store;
        }

        showAjaxErrorAlert(error, title) {
            let responseText = error.responseText;

            if (!responseText && error.status === 0) {
                responseText = 'Error connecting to the server.';
            }

            $.oc.vueComponentHelpers.modalUtils.showAlert(title, responseText);
        }
    }

    var editorPage = new EditorPage();

    $.oc.editor.application = editorPage.getApplication();
    $.oc.editor.getLangStr = editorPage.getLangStr;
    $.oc.editor.page = editorPage;
});
