/*
 * Vue Inspector panel implementation
 */
$.oc.module.register('backend.component.inspector.panel', function () {
    Vue.component('backend-component-inspector-panel', {
        props: {
            controls: {
                type: Array,
                required: true
            },
            obj: {
                type: Object,
                required: true
            },
            inspectorUniqueId: {
                type: String,
                required: true
            },
            layoutUpdateData: {
                type: Object
            },
            inspectorPreferences: {
                type: Object
            }
        },
        data: function () {
            var storageKey = $.oc.vueComponentHelpers.inspector.utils.getLocalStorageKey(this, 'splitter'),
                splitterPosition = parseInt(localStorage.getItem(storageKey));

            return {
                splitterData: {
                    position: !splitterPosition ? 200 : splitterPosition,
                    minSize: 100
                },
                panelUpdateData: {
                    tabChanged: 0
                }
            };
        },
        computed: {
            untabbedControls: function computeUntabbedControls() {
                return this.controls.filter(function (propDefinition) {
                    if (!propDefinition.tab) {
                        return true;
                    }
                });
            },

            tabbedControls: function computeTabbedControls() {
                var result = {};

                this.controls.forEach(function (propDefinition) {
                    if (propDefinition.tab) {
                        if (result[propDefinition.tab] === undefined) {
                            result[propDefinition.tab] = [];
                        }

                        result[propDefinition.tab].push(propDefinition);
                    }
                });

                return result;
            },

            tabs: function computeTabs() {
                var result = [];

                Object.keys(this.tabbedControls).forEach(function (tabName) {
                    result.push({
                        label: tabName,
                        key: tabName
                    });
                });

                return result;
            }
        },
        methods: {
            validate: function () {
                var that = this;

                $.oc.vueComponentHelpers.inspector.utils.clearPanelValidationErrors(that);

                return new Promise(function (resolve, reject) {
                    var result = $.oc.vueComponentHelpers.inspector.utils.validatePanelControls(that);
                    if (result === null) {
                        resolve();
                    }
                    else {
                        return $.oc.vueComponentHelpers.inspector.utils.expandControlParents(result.component)
                            .then(function () {
                                var controlTab = $.oc.vueComponentHelpers.inspector.utils.findErrorComponentTab(result.component);
                                if (controlTab && that.tabbedControls[controlTab]) {
                                    that.$refs.tabs.selectTab(controlTab);
                                }

                                return $.oc.vueComponentHelpers.modalUtils
                                    .showAlert(that.$el.getAttribute('data-validation-alert-title'), result.message)
                                    .then(function () {
                                        result.component.focusControl();
                                        reject(result.message);
                                    });
                            });
                    }
                });
            },

            onTabSelected: function () {
                this.panelUpdateData.tabChanged++;
            }
        },
        template: '#backend_vuecomponents_inspector_panel'
    });
});