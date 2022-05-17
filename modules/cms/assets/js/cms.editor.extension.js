$.oc.module.register('editor.extension.cms.main', function () {
    'use strict';

    var ExtensionBase = $.oc.module.import('editor.extension.base');
    var DocumentUri = $.oc.module.import('editor.documenturi');
    var EditorCommand = $.oc.module.import('editor.command');

    // Declaring this as a scoped variable. For some reason,
    // making this a property of CmsEditorExtension causes
    // stack overflow in Vue.
    var componentListInstance = null;

    var CmsEditorExtension = function (_ExtensionBase) {
        babelHelpers.inherits(CmsEditorExtension, _ExtensionBase);

        function CmsEditorExtension(namespace) {
            babelHelpers.classCallCheck(this, CmsEditorExtension);

            var _this = babelHelpers.possibleConstructorReturn(this, (CmsEditorExtension.__proto__ || Object.getPrototypeOf(CmsEditorExtension)).call(this, namespace));

            Vue.nextTick(function () {
                _this.createComponentListPopup();
            });
            return _this;
        }

        babelHelpers.createClass(CmsEditorExtension, [{
            key: 'setInitialState',
            value: function setInitialState(initialState) {
                var _this2 = this;

                babelHelpers.get(CmsEditorExtension.prototype.__proto__ || Object.getPrototypeOf(CmsEditorExtension.prototype), 'setInitialState', this).call(this, initialState);

                this.intellisense = $.oc.module.import('cms.editor.intellisense').make(this.state.customData);
                this.intellisense.on('onTokenClick', function (tokenClickData) {
                    return _this2.onTokenClick(tokenClickData);
                });
            }
        }, {
            key: 'listDocumentControllerClasses',
            value: function listDocumentControllerClasses() {
                return [$.oc.module.import('cms.editor.extension.documentcontroller.page'), $.oc.module.import('cms.editor.extension.documentcontroller.layout'), $.oc.module.import('cms.editor.extension.documentcontroller.partial'), $.oc.module.import('cms.editor.extension.documentcontroller.content'), $.oc.module.import('cms.editor.extension.documentcontroller.asset')];
            }
        }, {
            key: 'removeFileExtension',
            value: function removeFileExtension(fileName) {
                return fileName.split('.').slice(0, -1).join('.');
            }
        }, {
            key: 'createComponentListPopup',
            value: function createComponentListPopup() {
                var componentClass = Vue.extend(Vue.options.components['cmd-component-list-popup']);
                componentListInstance = new componentClass({});

                componentListInstance.$mount();
                document.body.appendChild(componentListInstance.$el);
            }
        }, {
            key: 'addCmsComponent',
            value: function addCmsComponent(componentData) {
                var documentComponent = $.oc.editor.application.getCurrentDocumentComponent();

                if (!documentComponent || typeof documentComponent.addComponent !== 'function') {
                    return;
                }

                documentComponent.addComponent(componentData);
            }
        }, {
            key: 'getCustomToolbarSettingsButtons',
            value: function getCustomToolbarSettingsButtons(documentType) {
                return this.state.customData.customToolbarSettingsButtons[documentType];
            }
        }, {
            key: 'onCommand',
            value: function onCommand(commandString, payload) {
                babelHelpers.get(CmsEditorExtension.prototype.__proto__ || Object.getPrototypeOf(CmsEditorExtension.prototype), 'onCommand', this).call(this, commandString, payload);

                if (commandString === 'global:application-tab-selected') {
                    if (!payload) {
                        componentListInstance.hide();
                        return;
                    }

                    var uri = DocumentUri.parse(payload);
                    if (uri.namespace !== 'cms') {
                        componentListInstance.hide();
                    }

                    var documentsSupportingComponents = ['cms-page', 'cms-partial', 'cms-layout'];
                    if (documentsSupportingComponents.indexOf(uri.documentType) === -1) {
                        componentListInstance.hide();
                    }

                    return;
                }

                if (commandString === 'cms:add-component') {
                    return this.addCmsComponent(payload);
                }

                if (commandString === 'cms:refresh-navigator') {
                    this.editorStore.refreshExtensionNavigatorNodes(this.editorNamespace).then(function () {});

                    return;
                }

                if (commandString === 'cms:show-component-list') {
                    componentListInstance.show();
                }

                var editorCommand = new EditorCommand(commandString);
                if (editorCommand.basePart === 'cms:set-edit-theme') {
                    this.onSetEditTheme(editorCommand.parameter);
                }
            }
        }, {
            key: 'onTokenClick',
            value: function onTokenClick(tokenClickData) {
                if (tokenClickData.type == 'cms-partial' || tokenClickData.type == 'cms-page') {
                    var path = tokenClickData.path;
                    if (!path.endsWith('.htm')) {
                        path += '.htm';
                    }

                    this.openDocumentByUniqueKey('cms:' + tokenClickData.type + ':' + path);
                }

                if (tokenClickData.type == 'cms-content') {
                    this.openDocumentByUniqueKey('cms:cms-content:' + tokenClickData.path);
                }

                if (tokenClickData.type == 'cms-asset') {
                    this.openDocumentByUniqueKey('cms:cms-asset:' + tokenClickData.path);
                }
            }
        }, {
            key: 'onSetEditTheme',
            value: function () {
                var _ref = babelHelpers.asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(theme) {
                    var _this3 = this;

                    var changingMessageId;
                    return regeneratorRuntime.wrap(function _callee$(_context) {
                        while (1) {
                            switch (_context.prev = _context.next) {
                                case 0:
                                    if (!this.editorApplication.hasChangedTabs()) {
                                        _context.next = 3;
                                        break;
                                    }

                                    $.oc.vueComponentHelpers.modalUtils.showAlert(this.trans('cms::lang.editor.change_edit_theme'), this.trans('cms::lang.editor.edit_theme_saved_changed_tabs'));

                                    return _context.abrupt('return');

                                case 3:

                                    this.editorApplication.closeAllTabs();
                                    this.editorApplication.setNavigatorReadonly(true);

                                    changingMessageId = $.oc.snackbar.show(this.trans('cms::lang.theme.setting_edit_theme'), {
                                        timeout: 5000
                                    });
                                    _context.prev = 6;
                                    _context.next = 9;
                                    return this.editorApplication.ajaxRequest('onCommand', {
                                        extension: this.editorNamespace,
                                        command: 'onSetEditTheme',
                                        documentMetadata: {
                                            theme: theme
                                        }
                                    });

                                case 9:
                                    _context.next = 11;
                                    return this.editorStore.refreshExtensionNavigatorNodes(this.editorNamespace);

                                case 11:

                                    $.oc.snackbar.show(this.trans('cms::lang.theme.edit_theme_changed'), { replace: changingMessageId });
                                    this.customData['theme'] = theme;
                                    this.editorApplication.setNavigatorReadonly(false);

                                    Object.keys(this.state.newDocumentData).forEach(function (documentType) {
                                        _this3.state.newDocumentData[documentType].metadata.theme = theme;
                                    });

                                    _context.next = 23;
                                    break;

                                case 17:
                                    _context.prev = 17;
                                    _context.t0 = _context['catch'](6);

                                    $.oc.snackbar.hide(changingMessageId);
                                    $.oc.editor.page.showAjaxErrorAlert(_context.t0, this.trans('editor::lang.common.error'));
                                    this.editorApplication.setNavigatorReadonly(false);
                                    return _context.abrupt('return', false);

                                case 23:
                                case 'end':
                                    return _context.stop();
                            }
                        }
                    }, _callee, this, [[6, 17]]);
                }));

                function onSetEditTheme(_x) {
                    return _ref.apply(this, arguments);
                }

                return onSetEditTheme;
            }()
        }, {
            key: 'componentList',
            get: function get() {
                return this.state.customData.components;
            }
        }, {
            key: 'cmsTheme',
            get: function get() {
                return this.customData['theme'];
            }
        }]);
        return CmsEditorExtension;
    }(ExtensionBase);

    return CmsEditorExtension;
});
