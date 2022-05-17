$.oc.module.register('cms.editor.extension.documentcontroller.page', function () {
    'use strict';

    var DocumentControllerBase = $.oc.module.import('editor.extension.documentcontroller.base');

    var treeviewUtils = $.oc.vueComponentHelpers.treeviewUtils;
    var menuUtils = $.oc.module.import('backend.component.dropdownmenu.utils');

    var DocumentControllerPage = function (_DocumentControllerBa) {
        babelHelpers.inherits(DocumentControllerPage, _DocumentControllerBa);

        function DocumentControllerPage(editorExtension) {
            babelHelpers.classCallCheck(this, DocumentControllerPage);

            var _this = babelHelpers.possibleConstructorReturn(this, (DocumentControllerPage.__proto__ || Object.getPrototypeOf(DocumentControllerPage)).call(this, editorExtension));

            _this.loadPageSorting();
            return _this;
        }

        babelHelpers.createClass(DocumentControllerPage, [{
            key: 'loadPageSorting',
            value: function loadPageSorting() {
                var _this2 = this;

                var knownSortingModes = ['cms-pages-sort-cmd', 'cms-pages-group-cmd', 'cms-pages-display-cmd'];

                knownSortingModes.forEach(function (item) {
                    var itemValue = localStorage.getItem(item);
                    if (itemValue !== null) {
                        _this2.emit(itemValue);
                    }
                });
            }
        }, {
            key: 'initListeners',
            value: function initListeners() {
                this.on('cms:sort-pages@title, cms:sort-pages@url, cms:sort-pages@filename', this.sortPages);
                this.on('cms:group-pages@path, cms:group-pages@url', this.groupPages);
                this.on('cms:display-pages@title, cms:display-pages@url, cms:display-pages@filename', this.setDisplayMode);
                this.on('cms:navigator-nodes-updated', this.onNavigatorNodesUpdated);
            }
        }, {
            key: 'sortPages',
            value: function sortPages(commandObj) {
                if (!commandObj.hasParameter) {
                    throw new Error('cms:sort-pages command excepts a parameter: ' + commandObj.fullCommand);
                }

                var pagesNode = this.rootNavigatorNodeSafe;
                if (!pagesNode) {
                    return;
                }

                var sortingItem = menuUtils.findMenuItem(pagesNode.topLevelMenuitems, ['sorting'], 'command');

                menuUtils.checkItemInGroup(sortingItem.items, commandObj.fullCommand);
                pagesNode.sortBy = commandObj.parameter;

                localStorage.setItem('cms-pages-sort-cmd', commandObj.fullCommand);
            }
        }, {
            key: 'setDisplayMode',
            value: function setDisplayMode(commandObj) {
                if (!commandObj.hasParameter) {
                    throw new Error('cms:display-pages command excepts a parameter: ' + commandObj.fullCommand);
                }

                var pagesNode = this.rootNavigatorNodeSafe;
                if (!pagesNode) {
                    return;
                }

                var displayItem = menuUtils.findMenuItem(pagesNode.topLevelMenuitems, ['display'], 'command');

                menuUtils.checkItemInGroup(displayItem.items, commandObj.fullCommand);
                pagesNode.displayProperty = commandObj.parameter;

                localStorage.setItem('cms-pages-display-cmd', commandObj.fullCommand);
            }
        }, {
            key: 'groupPages',
            value: function groupPages(commandObj) {
                if (!commandObj.hasParameter) {
                    throw new Error('cms:group-pages command excepts a parameter: ' + commandObj.fullCommand);
                }

                var pagesNode = this.rootNavigatorNodeSafe;
                if (!pagesNode) {
                    return;
                }

                var groupingItem = menuUtils.findMenuItem(pagesNode.topLevelMenuitems, ['grouping'], 'command');

                menuUtils.checkItemInGroup(groupingItem.items, commandObj.fullCommand);
                pagesNode.groupBy = commandObj.parameter;

                if (commandObj.parameter == 'path') {
                    pagesNode.groupByMode = 'folders';
                    pagesNode.groupByRegex = null;
                    pagesNode.groupFolderDisplayPathProps = ['filename'];
                } else {
                    pagesNode.groupByMode = 'nesting';
                    pagesNode.groupByRegex = '^[a-z0-9_\\-]*$';
                    pagesNode.groupByFolderDisplayPathProps = null;
                }

                localStorage.setItem('cms-pages-group-cmd', commandObj.fullCommand);
            }
        }, {
            key: 'preprocessSettingsFields',
            value: function preprocessSettingsFields(settingsFields) {
                var _this3 = this;

                var layoutList = this.parentExtension.getDocumentController('cms-layout').getAllLayoutFilenames();

                layoutList.sort();

                var layouts = {};
                layouts[''] = $.oc.editor.getLangStr('cms::lang.page.no_layout');
                layoutList.forEach(function (fileName) {
                    var path = _this3.parentExtension.removeFileExtension(fileName);
                    layouts[path] = path;
                });

                settingsFields.some(function (field) {
                    if (field.property == 'layout') {
                        field.options = layouts;

                        return true;
                    }
                });

                if ($.oc.editor.application.isDirectDocumentMode) {
                    settingsFields = settingsFields.filter(function (field) {
                        return ['layout', 'url', 'fileName', 'is_hidden'].indexOf(field.property) === -1;
                    });
                }

                return settingsFields;
            }
        }, {
            key: 'preprocessNewDocumentData',
            value: function preprocessNewDocumentData(newDocumentData) {
                var _this4 = this;

                var layoutList = this.parentExtension.getDocumentController('cms-layout').getAllLayoutFilenames();

                if (layoutList.some(function (fileName) {
                    return _this4.parentExtension.removeFileExtension(fileName) === 'default';
                })) {
                    newDocumentData.document.layout = 'default';
                }
            }
        }, {
            key: 'getAllPageFilenames',
            value: function getAllPageFilenames() {
                if (this.cachedPageList) {
                    return this.cachedPageList;
                }

                var pagesNavigatorNode = treeviewUtils.findNodeByKeyInSections(this.parentExtension.state.navigatorSections, 'cms:cms-page');

                var pageList = [];

                if (pagesNavigatorNode) {
                    pageList = treeviewUtils.getFlattenNodes(pagesNavigatorNode.nodes).map(function (pageNode) {
                        return pageNode.userData.filename;
                    });
                } else {
                    pageList = this.parentExtension.state.customData.pages;
                }

                this.cachedPageList = pageList;
                return pageList;
            }
        }, {
            key: 'onNavigatorNodesUpdated',
            value: function onNavigatorNodesUpdated(cmd) {
                this.cachedPageList = null;
            }
        }, {
            key: 'documentType',
            get: function get() {
                return 'cms-page';
            }
        }, {
            key: 'vueEditorComponentName',
            get: function get() {
                return 'cms-editor-component-page-editor';
            }
        }]);
        return DocumentControllerPage;
    }(DocumentControllerBase);

    return DocumentControllerPage;
});
