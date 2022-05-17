$.oc.module.register('editor.store.tabmanager', function () {
    'use strict';

    var TabManager = function () {
        function TabManager(store) {
            babelHelpers.classCallCheck(this, TabManager);

            this.store = store;
        }

        babelHelpers.createClass(TabManager, [{
            key: 'findTab',
            value: function findTab(key) {
                var tabs = this.store.state.editorTabs;
                var index = tabs.findIndex(function (tab) {
                    return tab.key == key;
                });

                if (index === -1) {
                    return null;
                }

                return {
                    index: index,
                    tab: tabs[index]
                };
            }
        }, {
            key: 'createTab',
            value: function createTab(tabData) {
                if (!tabData.key) {
                    throw new Error('Tab data must contain the key element');
                }

                if (!tabData.component) {
                    throw new Error('Tab data must contain the component element');
                }

                var existingTab = this.findTab(tabData.key);
                if (existingTab) {
                    return existingTab.tab.key;
                }

                tabData.componentData = tabData.componentData || {};
                tabData.componentData.store = this.store;
                tabData.componentData.tabIcon = tabData.icon;

                this.store.state.editorTabs.push(tabData);

                return tabData.key;
            }
        }, {
            key: 'closeTab',
            value: function closeTab(index) {
                if (index !== null) {
                    this.store.state.editorTabs.splice(index, 1);
                    Vue.nextTick(function () {
                        $.oc.octoberTooltips.clear();
                    }, 1);
                }
            }
        }, {
            key: 'closeTabByKey',
            value: function closeTabByKey(key) {
                var tab = this.findTab(key);
                if (!tab) {
                    return;
                }

                this.closeTab(tab.index);
            }
        }, {
            key: 'updateTabLabel',
            value: function updateTabLabel(label, key) {
                var tab = this.findTab(key);
                if (!tab) {
                    return;
                }

                if (typeof label !== 'string' || !label.length) {
                    label = 'No name';
                }

                tab.tab.label = label;
            }
        }, {
            key: 'setTabHasChanges',
            value: function setTabHasChanges(hasChanges, key) {
                var tab = this.findTab(key);
                if (!tab) {
                    return;
                }

                Vue.set(tab.tab, 'hasChanges', hasChanges);
            }
        }, {
            key: 'hasChangedTabs',
            value: function hasChangedTabs() {
                return this.store.state.editorTabs.some(function (tab) {
                    return tab.hasChanges;
                });
            }
        }, {
            key: 'setTabKey',
            value: function setTabKey(oldKey, newKey) {
                var tab = this.findTab(oldKey);
                if (!tab) {
                    return;
                }

                tab.tab.key = newKey;
            }
        }, {
            key: 'storePersistentTabs',
            value: function storePersistentTabs(tabs) {
                this.setPersistentTabKeys(tabs.map(function (tab) {
                    return tab.key;
                }));
            }
        }, {
            key: 'setPersistentTabKeys',
            value: function setPersistentTabKeys(tabKeys) {
                localStorage.setItem('editor-tabs', JSON.stringify(tabKeys));
            }
        }, {
            key: 'getPersistentTabKeys',
            value: function getPersistentTabKeys() {
                var tabsStr = localStorage.getItem('editor-tabs');
                if (tabsStr === null) {
                    return [];
                }

                try {
                    var result = JSON.parse(tabsStr);
                    if (!result || !Array.isArray(result)) {
                        return [];
                    }

                    return result;
                } catch (e) {
                    return [];
                }
            }
        }]);
        return TabManager;
    }();

    return TabManager;
});
