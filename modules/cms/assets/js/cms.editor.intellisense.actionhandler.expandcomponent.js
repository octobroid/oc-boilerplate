$.oc.module.register('cms.editor.intellisense.actionhandlers.expandcomponent', function () {
    'use strict';

    var ActionHandlerBase = $.oc.module.import('cms.editor.intellisense.actionhandler.base');

    var ActionHandlerExpandComponent = function (_ActionHandlerBase) {
        babelHelpers.inherits(ActionHandlerExpandComponent, _ActionHandlerBase);

        function ActionHandlerExpandComponent(intellisense, editor, monacoComponent) {
            babelHelpers.classCallCheck(this, ActionHandlerExpandComponent);

            var _this = babelHelpers.possibleConstructorReturn(this, (ActionHandlerExpandComponent.__proto__ || Object.getPrototypeOf(ActionHandlerExpandComponent)).call(this, intellisense, editor, monacoComponent));

            _this.tagRePattern = '{%\\s+component\\s*[^}]+\\s*%}';
            return _this;
        }

        babelHelpers.createClass(ActionHandlerExpandComponent, [{
            key: 'initEditor',
            value: function initEditor() {
                var condition = this.editor.createContextKey('octoberExpandComponentDefinitionCondition', false);
                condition.set(true);
                this.editor.octoberExpandComponentDefinitionCondition = condition;
                var that = this;

                this.action = this.editor.addAction({
                    id: 'october-expand-component-definition',
                    label: $.oc.editor.getLangStr('cms::lang.component.expand_partial'),
                    precondition: 'octoberExpandComponentDefinitionCondition',
                    contextMenuGroupId: 'navigation',
                    contextMenuOrder: 1.5,
                    run: function run(editor) {
                        that.run(editor);
                        return null;
                    }
                });
            }
        }, {
            key: 'run',
            value: function run(editor) {
                var position = editor.getPosition();
                var tag = this.getTagAtPosition(editor, this.tagRePattern, position);
                if (!tag) {
                    return;
                }

                var re = /^{%\s*component\s(['"])([^"']+)(?:\1)[^(?:%})]+%}$/;
                var matches = re.exec(tag.tag);

                if (!matches || !matches[2]) {
                    return;
                }

                this.monacoComponent.emitCustomEvent('expandComponent', {
                    alias: matches[2],
                    range: tag.range,
                    model: editor.getModel()
                });
            }
        }, {
            key: 'onFilterSupportedActions',
            value: function onFilterSupportedActions(payload) {
                payload.actions = payload.actions.filter(function (action) {
                    return action.id.indexOf('october-expand-component-definition') === -1;
                });
            }
        }, {
            key: 'onContextMenu',
            value: function onContextMenu(editor, target) {
                if (!editor.octoberExpandComponentDefinitionCondition || !target) {
                    return;
                }

                if (!this.modelHasTag(editor, 'cms-markup')) {
                    editor.octoberExpandComponentDefinitionCondition.set(false);
                    return;
                }

                var tag = this.getTagAtPosition(editor, this.tagRePattern, target.position);
                if (!tag) {
                    editor.octoberExpandComponentDefinitionCondition.set(false);
                    return;
                }

                editor.octoberExpandComponentDefinitionCondition.set(true);
            }
        }]);
        return ActionHandlerExpandComponent;
    }(ActionHandlerBase);

    return ActionHandlerExpandComponent;
});
