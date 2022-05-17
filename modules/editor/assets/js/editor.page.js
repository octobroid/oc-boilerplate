$(document).ready(function () {
    var EditorStore = $.oc.module.import('editor.store');

    var EditorPage = function () {
        function EditorPage(height, width) {
            babelHelpers.classCallCheck(this, EditorPage);

            this.store = new EditorStore();

            this.init();
        }

        babelHelpers.createClass(EditorPage, [{
            key: 'init',
            value: function init() {
                this.initVue();
                this.initListeners();
            }
        }, {
            key: 'initVue',
            value: function initVue() {
                var initialState = $('#editor-initial-state').html();
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
        }, {
            key: 'initListeners',
            value: function initListeners() {
                window.addEventListener('beforeunload', function (event) {
                    if ($.oc.editor.application.hasChangedTabs()) {
                        event.preventDefault();
                        event.returnValue = 'There are unsaved changes.';
                    }
                });
            }
        }, {
            key: 'getApplication',
            value: function getApplication() {
                return this.vm.$refs.application;
            }
        }, {
            key: 'getLangStr',
            value: function getLangStr(str) {
                return this.store.state.lang[str];
            }
        }, {
            key: 'getStore',
            value: function getStore() {
                return this.store;
            }
        }, {
            key: 'showAjaxErrorAlert',
            value: function showAjaxErrorAlert(error, title) {
                var responseText = error.responseText;

                if (!responseText && error.status === 0) {
                    responseText = 'Error connecting to the server.';
                }

                $.oc.vueComponentHelpers.modalUtils.showAlert(title, responseText);
            }
        }]);
        return EditorPage;
    }();

    var editorPage = new EditorPage();

    $.oc.editor.application = editorPage.getApplication();
    $.oc.editor.getLangStr = editorPage.getLangStr;
    $.oc.editor.page = editorPage;
});
