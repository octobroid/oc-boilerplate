$.oc.module.register('cms.editor.intellisense.actionhandler.base', function () {
    'use strict';

    var ActionHandlerBase = function () {
        function ActionHandlerBase(intellisense, editor, monacoComponent) {
            babelHelpers.classCallCheck(this, ActionHandlerBase);

            this.editor = editor;
            this.intellisense = intellisense;
            this.monacoComponent = monacoComponent;

            this.initEditor();
        }

        babelHelpers.createClass(ActionHandlerBase, [{
            key: 'initEditor',
            value: function initEditor() {}
        }, {
            key: 'getModelTags',
            value: function getModelTags(editor) {
                var tags = editor.getModel().octoberEditorCmsTags;
                if (!Array.isArray(tags)) {
                    return [];
                }

                return tags;
            }
        }, {
            key: 'modelHasTag',
            value: function modelHasTag(editor, tag) {
                var tags = this.getModelTags(editor);
                return tags.indexOf(tag) !== -1;
            }
        }, {
            key: 'getTagAtPosition',
            value: function getTagAtPosition(editor, tagRegexPattern, position) {
                var tagRegex = new RegExp(tagRegexPattern, 'ig');
                var model = editor.getModel();
                var mouseColumnIndex = position.column - 1;
                var lineContent = model.getLineContent(position.lineNumber);

                var match = '';
                while ((match = tagRegex.exec(lineContent)) !== null) {
                    var tag = match[0];
                    if (match.index <= mouseColumnIndex && mouseColumnIndex < match.index + tag.length) {
                        return {
                            tag: tag,
                            range: {
                                endColumn: match.index + tag.length + 1,
                                endLineNumber: position.lineNumber,
                                startColumn: match.index + 1,
                                startLineNumber: position.lineNumber
                            }
                        };
                    }
                }

                return null;
            }
        }, {
            key: 'run',
            value: function run(editor) {}
        }, {
            key: 'onFilterSupportedActions',
            value: function onFilterSupportedActions(payload) {}
        }, {
            key: 'onContextMenu',
            value: function onContextMenu(editor, target) {}
        }, {
            key: 'dispose',
            value: function dispose() {
                this.monacoComponent = null;

                if (this.action) {
                    this.action.dispose();
                }
            }
        }]);
        return ActionHandlerBase;
    }();

    return ActionHandlerBase;
});
