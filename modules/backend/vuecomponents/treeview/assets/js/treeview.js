/*
 * Vue treeview implementation
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Roles/Tab_Role
 * @see https://www.w3.org/WAI/GL/wiki/Using_ARIA_trees
 */
$.oc.module.register('backend.component.treeview', function () {
    Vue.component('backend-component-treeview', {
        props: {
            sections: Array,
            ariaLabel: {
                type: String,
                default: ''
            },
            searchable: {
                type: Boolean,
                default: true
            },
            selectedUniqueKey: {
                type: String
            },
            uniqueKey: {
                type: String,
                required: true
            },
            containerCssClass: {
                type: String,
                default: ''
            },
            readonly: {
                type: Boolean,
                default: false
            },
            hideSections: {
                type: Boolean,
                default: false
            }
        },
        data: function () {
            var Selection = $.oc.module.import('backend.vuecomponents.treeview.selection'),
                selection = new Selection();

            return {
                lastSelectedNodeElement: null,
                selection: selection,
                selectedKeys: selection.selectedKeys,
                searchTimeoutId: null,
                store: {
                    contextMenu: {
                        items: [],
                        node: null,
                        labeledById: null,
                        menuId: null
                    }
                },
                searchQuery: '',
                searchQueryTrimmed: '',
                defaultFolderIcon: {
                    cssClass: 'backend-icon-background treeview-folder',
                    backgroundColor: 'transparent'
                }
            };
        },
        computed: {
            selectedNodesData: function computeSelectedNodesData() {
                var utils = $.oc.vueComponentHelpers.treeviewUtils,
                    sections = this.sections,
                    result = [];

                this.selectedKeys.forEach(function (selectedKey) {
                    var nodeInfo = utils.findNodeInfoByKeyInSections(sections, selectedKey);

                    if (nodeInfo) {
                        result.push(nodeInfo);
                    }
                });

                return result;
            }
        },
        methods: {
            setSelectedKey: function setSelectedKey(uniqueKey) {
                this.selection.set(uniqueKey);
            },

            setSearchQueryTrimmed: function setSearchQueryTrimmed() {
                this.searchTimeoutId = null;
                this.searchQueryTrimmed = this.searchQuery.toLowerCase().trim();
                this.displayedSections = {};
            },

            nodeKeyChanged: function nodeKeyChanged(oldValue, newValue) {
                if (this.selectedKeys.length > 1 || this.selectedKeys.indexOf(newValue) === -1) {
                    this.selection.set(newValue);
                }
            },

            revealNode: function revealNode(nodeUniqueKey) {
                var utils = $.oc.vueComponentHelpers.treeviewUtils,
                    path = utils.findNodePathByKeyInSections(this.sections, nodeUniqueKey),
                    thisElement = $(this.$el);

                if (!path) {
                    return;
                }

                function revealNextNode(nodes, index, sectionComponent) {
                    var currentKey = path[index],
                        currentNode = utils.findNodeComponentByKey(nodes, currentKey);

                    if (!currentNode) {
                        return;
                    }

                    if (index < path.length - 1) {
                        currentNode.expand();
                    }
                    else {
                        var scrollableEl = thisElement.find('.scrollable').get(0);
                        currentNode.scrollIntoView(function () {
                            currentNode.focus();

                            // A simple solution against a revealed node
                            // being hidden behind a section element.
                            scrollableEl.scrollTop -= 40;
                        });
                    }

                    if (index < path.length - 1) {
                        sectionComponent.expand();
                        Vue.nextTick(function () {
                            revealNextNode(currentNode.$children, index + 1, sectionComponent);
                        });
                    }
                }

                // The first level of children are sections
                //
                for (var index = 0; index < this.$refs.scrollablePanel.$children.length; index++) {
                    var sectionComponent = this.$refs.scrollablePanel.$children[index];
                    revealNextNode(sectionComponent.$children, 0, sectionComponent);
                }
            },

            handleKeyDown: function handleKeyDown(ev) {
                // See w3.org treeview accessibility requirements for details.
                //
                var key = 'which' in ev ? ev.which : ev.keyCode,
                    currentFocus = document.activeElement;

                // It is simpler to work with the DOM tree than with the node
                // tree here.
                //

                if (!currentFocus || (currentFocus.tagName !== 'LI' && currentFocus.tagName !== 'BUTTON')) {
                    return;
                }

                var isInnerButton = currentFocus.tagName === 'BUTTON';
                if (isInnerButton) {
                    currentFocus = $(currentFocus).closest('li').get(0);
                }

                switch (key) {
                    case 40:
                        return $.oc.vueComponentHelpers.treeviewNavigation.navigateNext(this.$el, currentFocus, ev);
                    case 38:
                        return $.oc.vueComponentHelpers.treeviewNavigation.navigatePrev(this.$el, currentFocus, ev);
                    case 39:
                        return $.oc.vueComponentHelpers.treeviewNavigation.navigateRight(currentFocus);
                    case 37:
                        return $.oc.vueComponentHelpers.treeviewNavigation.navigateLeft(currentFocus);
                    case 36:
                        return $.oc.vueComponentHelpers.treeviewNavigation.navigateHome(this.$el, ev);
                    case 35:
                        return $.oc.vueComponentHelpers.treeviewNavigation.navigateEnd(this.$el, ev);
                    case 106: // Asterisk
                        return $.oc.vueComponentHelpers.treeviewNavigation.expandSameLevelSiblings(this.$el, currentFocus);
                    case 13:
                    case 32:
                        if (isInnerButton) {
                            return;
                        }

                        return $(currentFocus).find('> .item-label-outer-container > .item-label-container').click();
                }

                $.oc.vueComponentHelpers.treeviewNavigation.navigateNextStartingWith(ev.key, this.$el, currentFocus);
            },

            showQuickAccess: function () {
                if (!this.readonly) {
                    this.$refs.quickAccess.show();
                }
            },

            onKeyDown: function onKeyDown(ev) {
                var result = this.handleKeyDown(ev);
                this.lastSelectedNodeElement = $(document.activeElement).closest('li[data-treenode]').get(0);

                if (
                    ev.shiftKey &&
                    result === 'selection-changed' &&
                    $(this.lastSelectedNodeElement).hasClass('selectable-node')
                ) {
                    this.selection.addOrInvert(this.lastSelectedNodeElement.getAttribute('data-unique-key'));
                }

                return result;
            },

            onNodeSelected: function onNodeSelected(ev) {
                var prevSelectedNodeElement = this.lastSelectedNodeElement;
                this.lastSelectedNodeElement = ev.nodeEl;

                if (ev.type === 'add') {
                    this.selection.addOrInvert(ev.uniqueKey);
                    return;
                }

                if (ev.type === 'range') {
                    this.selection.addRange(ev.uniqueKey, ev.nodeEl, prevSelectedNodeElement);
                    return;
                }

                this.selection.set(ev.uniqueKey);
            },

            onDragOver: function onDragOver(ev) {
                if (this.readonly) {
                    return;
                }

                $.oc.vueComponentHelpers.treeviewDragAndDrop.onDragOver(ev);
            },

            onDragEnter: function onDragEnter(ev) {
                if (this.readonly) {
                    return;
                }

                $.oc.vueComponentHelpers.treeviewDragAndDrop.onDragEnter(ev);
            },

            onDragLeave: function onDragLeave(ev) {
                $.oc.vueComponentHelpers.treeviewDragAndDrop.onDragLeave(ev);
            },

            onDragEnd: function onDragEnd(ev) {
                $.oc.vueComponentHelpers.treeviewDragAndDrop.onDragEnd(ev);
            },

            onMouseDown: function onMouseDown(ev) {
                if (this.readonly) {
                    return;
                }

                $.oc.vueComponentHelpers.treeviewDragAndDrop.mouseDown(ev);
            },

            onMenuItemCommand: function onMenuItemCommand(command) {
                var keyPath = null;

                if (this.store.contextMenu.elementType !== 'section') {
                    keyPath = $.oc.vueUtils.getCleanObject(this.store.contextMenu.node.keyPath);
                }

                this.$emit(
                    'command',
                    command,
                    keyPath,
                    this.store.contextMenu.node,
                    this.store.contextMenu.selectedNodesData
                );
            },

            onNodeMenuTriggerClick: function onNodeMenuTriggerClick(data) {
                var menuItems = data.sectionCreateMenu ? data.node.createMenuItems : data.node.nodeMenuitems;

                if (!$.isArray(menuItems)) {
                    menuItems = [];
                }

                var selectedNodesData = this.selectedNodesData;
                if (data.node.nodeData.hasApiMenuItems) {
                    this.$emit('nodecontextmenudisplay', data.node.nodeData, menuItems, {
                        selectedNodes: selectedNodesData,
                        clickedNode: data.node.nodeData,
                        clickedIsSelected: this.selectedKeys.indexOf(data.node.nodeData.uniqueKey) !== -1
                    });
                }

                this.store.contextMenu.items = menuItems;
                this.store.contextMenu.node = data.node;
                this.store.contextMenu.menuId = data.node.menuId;
                this.store.contextMenu.selectedNodesData = this.selectedNodesData;
                this.store.contextMenu.labeledById = data.node.menuLabelId;
                this.store.contextMenu.elementType = data.elementType;

                if (data.type == 'trigger') {
                    this.$refs.contextmenu.showMenu(data.node.$refs.contextmenuTrigger);
                }
                else {
                    this.$refs.contextmenu.showMenu(data.ev);
                }
            },

            onShowMenu: function onShowMenu(payload) { },

            onMenuShown: function onMenuShown() {
                this.store.contextMenu.node.onMenuShown();
            },

            onMenuHidden: function onMenuHidden() {
                this.store.contextMenu.node.onMenuHidden();
            },

            onMenuClosedWithEsc: function () {
                this.store.contextMenu.node.onMenuClosedWithEsc();
            },

            onQuickAccessNodeSelected: function (nodeKey, ev) {
                var utils = $.oc.vueComponentHelpers.treeviewUtils,
                    nodeData = utils.findNodeAndPathByKeyInSections(this.sections, nodeKey);

                if (!nodeData) {
                    return;
                }

                var ev = $.Event('treviewnodeclick', {
                    // See also treeview.js onQuickAccessNodeSelected()

                    treeviewData: {
                        keyPath: utils.makeKeyPath(nodeData.path, nodeData.node),
                        uniqueKey: nodeData.node.uniqueKey,
                        nodeData: nodeData.node,
                        rootNodeData: nodeData.path[0]
                    }
                });

                this.$emit('nodeclick', ev);
            },

            onQuickAccessCommand: function (command, ev) {
                var keyPath = null;

                // Quick access can only trigger section commands,
                // it's safe to pass nulls to the node
                // and selectedNodesData arguments.
                this.$emit('command', command, keyPath, null, null);
            }
        },
        beforeMount: function beforeMount() {
            this.selection.set(this.selectedUniqueKey);

            var storedQuery = localStorage.getItem(this.uniqueKey + '-search');
            if (storedQuery === null) {
                storedQuery = '';
            }

            this.searchQuery = storedQuery;
            this.setSearchQueryTrimmed();

            var that = this;

            if (storedQuery != '') {
                Vue.nextTick(function () {
                    $.oc.vueComponentHelpers.treeviewUtils.updateRootNodesInSections(that.sections);
                });
            }
        },
        beforeDestroy: function beforeDestroy() { },
        watch: {
            selectedUniqueKey: function (uniqueKey) {
                this.selection.set(uniqueKey);
            },

            searchQuery: function (query) {
                localStorage.setItem(this.uniqueKey + '-search', query);

                if (this.searchTimeoutId) {
                    clearTimeout(this.searchTimeoutId);
                }

                this.searchTimeoutId = setTimeout(this.setSearchQueryTrimmed, 100);
            }
        },
        template: '#backend_vuecomponents_treeview'
    });
});