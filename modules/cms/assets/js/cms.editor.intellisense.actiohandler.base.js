$.oc.module.register('cms.editor.intellisense.actionhandler.base', function () {
    'use strict';

    var ActionHandlerBase = function () {
        function ActionHandlerBase(intellisense, editor) {
            babelHelpers.classCallCheck(this, ActionHandlerBase);

            this.editor = editor;
            this.intellisense = intellisense;

            this.initEditor();
        }

        babelHelpers.createClass(ActionHandlerBase, [{
            key: 'initEditor',
            value: function initEditor() {}
        }, {
            key: 'dispose',
            value: function dispose() {
                if (this.action) {
                    console.log('Disposing action');
                    this.action.dispose();
                }
            }
        }]);
        return ActionHandlerBase;
    }();

    return ActionHandlerBase;
});
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIi4uL2VzNi9jbXMuZWRpdG9yLmludGVsbGlzZW5zZS5hY3Rpb2hhbmRsZXIuYmFzZS5qcyJdLCJuYW1lcyI6WyIkIiwib2MiLCJtb2R1bGUiLCJyZWdpc3RlciIsIkFjdGlvbkhhbmRsZXJCYXNlIiwiaW50ZWxsaXNlbnNlIiwiZWRpdG9yIiwiaW5pdEVkaXRvciIsImFjdGlvbiIsImNvbnNvbGUiLCJsb2ciLCJkaXNwb3NlIl0sIm1hcHBpbmdzIjoiQUFBQUEsRUFBRUMsRUFBRixDQUFLQyxNQUFMLENBQVlDLFFBQVosQ0FBcUIsNENBQXJCLEVBQW1FLFlBQVc7QUFDMUU7O0FBRDBFLFFBR3BFQyxpQkFIb0U7QUFRdEUsbUNBQVlDLFlBQVosRUFBMEJDLE1BQTFCLEVBQWtDO0FBQUE7O0FBQzlCLGlCQUFLQSxNQUFMLEdBQWNBLE1BQWQ7QUFDQSxpQkFBS0QsWUFBTCxHQUFvQkEsWUFBcEI7O0FBRUEsaUJBQUtFLFVBQUw7QUFDSDs7QUFicUU7QUFBQTtBQUFBLHlDQWV6RCxDQUFFO0FBZnVEO0FBQUE7QUFBQSxzQ0FpQjVEO0FBQ04sb0JBQUksS0FBS0MsTUFBVCxFQUFpQjtBQUNiQyw0QkFBUUMsR0FBUixDQUFZLGtCQUFaO0FBQ0EseUJBQUtGLE1BQUwsQ0FBWUcsT0FBWjtBQUNIO0FBQ0o7QUF0QnFFO0FBQUE7QUFBQTs7QUF5QjFFLFdBQU9QLGlCQUFQO0FBQ0gsQ0ExQkQiLCJmaWxlIjoiY21zLmVkaXRvci5pbnRlbGxpc2Vuc2UuYWN0aW9oYW5kbGVyLmJhc2UuanMiLCJzb3VyY2VzQ29udGVudCI6WyIkLm9jLm1vZHVsZS5yZWdpc3RlcignY21zLmVkaXRvci5pbnRlbGxpc2Vuc2UuYWN0aW9uaGFuZGxlci5iYXNlJywgZnVuY3Rpb24oKSB7XG4gICAgJ3VzZSBzdHJpY3QnO1xuXG4gICAgY2xhc3MgQWN0aW9uSGFuZGxlckJhc2Uge1xuICAgICAgICBpbnRlbGxpc2Vuc2U7XG4gICAgICAgIGVkaXRvcjtcbiAgICAgICAgYWN0aW9uO1xuXG4gICAgICAgIGNvbnN0cnVjdG9yKGludGVsbGlzZW5zZSwgZWRpdG9yKSB7XG4gICAgICAgICAgICB0aGlzLmVkaXRvciA9IGVkaXRvcjtcbiAgICAgICAgICAgIHRoaXMuaW50ZWxsaXNlbnNlID0gaW50ZWxsaXNlbnNlO1xuXG4gICAgICAgICAgICB0aGlzLmluaXRFZGl0b3IoKTtcbiAgICAgICAgfVxuXG4gICAgICAgIGluaXRFZGl0b3IoKSB7fVxuXG4gICAgICAgIGRpc3Bvc2UoKSB7XG4gICAgICAgICAgICBpZiAodGhpcy5hY3Rpb24pIHtcbiAgICAgICAgICAgICAgICBjb25zb2xlLmxvZygnRGlzcG9zaW5nIGFjdGlvbicpO1xuICAgICAgICAgICAgICAgIHRoaXMuYWN0aW9uLmRpc3Bvc2UoKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH1cblxuICAgIHJldHVybiBBY3Rpb25IYW5kbGVyQmFzZTtcbn0pO1xuIl19
