$.oc.module.register('cms.editor.intellisense.expandcomponentdefinition', function () {
    'use strict';

    var ExpandComponentDefinition = function () {
        function ExpandComponentDefinition(intellisense, editor) {
            babelHelpers.classCallCheck(this, ExpandComponentDefinition);

            this.editor = editor;
            this.intellisense = intellisense;

            this.initEditor();
        }

        babelHelpers.createClass(ExpandComponentDefinition, [{
            key: 'initEditor',
            value: function initEditor() {
                var condition = this.editor.createContextKey('octoberExpandComponentDefinitionCondition', false);
                condition.set(true);
                this.editor.octoberExpandComponentDefinitionCondition = condition;

                this.action = this.editor.addAction({
                    id: 'october-expand-component-definition',
                    label: 'Expand Component Partial (TODO)',
                    precondition: 'octoberExpandComponentDefinitionCondition',
                    contextMenuGroupId: 'navigation',
                    contextMenuOrder: 1.5,
                    run: function run(ed) {
                        alert("i'm running => " + ed.getPosition());
                    }
                });
            }
        }, {
            key: 'dispose',
            value: function dispose() {
                console.log('Disposing action');
                this.action.dispose();
            }
        }]);
        return ExpandComponentDefinition;
    }();

    return ExpandComponentDefinition;
});
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIi4uL2VzNi9jbXMuZWRpdG9yLmludGVsbGlzZW5zZS5leHBhbmRjb21wb25lbnRkZWZpbml0aW9uLmpzIl0sIm5hbWVzIjpbIiQiLCJvYyIsIm1vZHVsZSIsInJlZ2lzdGVyIiwiRXhwYW5kQ29tcG9uZW50RGVmaW5pdGlvbiIsImludGVsbGlzZW5zZSIsImVkaXRvciIsImluaXRFZGl0b3IiLCJjb25kaXRpb24iLCJjcmVhdGVDb250ZXh0S2V5Iiwic2V0Iiwib2N0b2JlckV4cGFuZENvbXBvbmVudERlZmluaXRpb25Db25kaXRpb24iLCJhY3Rpb24iLCJhZGRBY3Rpb24iLCJpZCIsImxhYmVsIiwicHJlY29uZGl0aW9uIiwiY29udGV4dE1lbnVHcm91cElkIiwiY29udGV4dE1lbnVPcmRlciIsInJ1biIsImVkIiwiYWxlcnQiLCJnZXRQb3NpdGlvbiIsImNvbnNvbGUiLCJsb2ciLCJkaXNwb3NlIl0sIm1hcHBpbmdzIjoiQUFBQUEsRUFBRUMsRUFBRixDQUFLQyxNQUFMLENBQVlDLFFBQVosQ0FBcUIsbURBQXJCLEVBQTBFLFlBQVc7QUFDakY7O0FBRGlGLFFBRzNFQyx5QkFIMkU7QUFRN0UsMkNBQVlDLFlBQVosRUFBMEJDLE1BQTFCLEVBQWtDO0FBQUE7O0FBQzlCLGlCQUFLQSxNQUFMLEdBQWNBLE1BQWQ7QUFDQSxpQkFBS0QsWUFBTCxHQUFvQkEsWUFBcEI7O0FBRUEsaUJBQUtFLFVBQUw7QUFDSDs7QUFiNEU7QUFBQTtBQUFBLHlDQWVoRTtBQUNULG9CQUFNQyxZQUFZLEtBQUtGLE1BQUwsQ0FBWUcsZ0JBQVosQ0FBNkIsMkNBQTdCLEVBQTBFLEtBQTFFLENBQWxCO0FBQ0FELDBCQUFVRSxHQUFWLENBQWMsSUFBZDtBQUNBLHFCQUFLSixNQUFMLENBQVlLLHlDQUFaLEdBQXdESCxTQUF4RDs7QUFFQSxxQkFBS0ksTUFBTCxHQUFjLEtBQUtOLE1BQUwsQ0FBWU8sU0FBWixDQUFzQjtBQUNoQ0Msd0JBQUkscUNBRDRCO0FBRWhDQywyQkFBTyxpQ0FGeUI7QUFHaENDLGtDQUFjLDJDQUhrQjtBQUloQ0Msd0NBQW9CLFlBSlk7QUFLaENDLHNDQUFrQixHQUxjO0FBTWhDQyx5QkFBSyxhQUFTQyxFQUFULEVBQWE7QUFDZEMsOEJBQU0sb0JBQW9CRCxHQUFHRSxXQUFILEVBQTFCO0FBQ0g7QUFSK0IsaUJBQXRCLENBQWQ7QUFVSDtBQTlCNEU7QUFBQTtBQUFBLHNDQWdDbkU7QUFDTkMsd0JBQVFDLEdBQVIsQ0FBWSxrQkFBWjtBQUNBLHFCQUFLWixNQUFMLENBQVlhLE9BQVo7QUFDSDtBQW5DNEU7QUFBQTtBQUFBOztBQXNDakYsV0FBT3JCLHlCQUFQO0FBQ0gsQ0F2Q0QiLCJmaWxlIjoiY21zLmVkaXRvci5pbnRlbGxpc2Vuc2UuZXhwYW5kY29tcG9uZW50ZGVmaW5pdGlvbi5qcyIsInNvdXJjZXNDb250ZW50IjpbIiQub2MubW9kdWxlLnJlZ2lzdGVyKCdjbXMuZWRpdG9yLmludGVsbGlzZW5zZS5leHBhbmRjb21wb25lbnRkZWZpbml0aW9uJywgZnVuY3Rpb24oKSB7XG4gICAgJ3VzZSBzdHJpY3QnO1xuXG4gICAgY2xhc3MgRXhwYW5kQ29tcG9uZW50RGVmaW5pdGlvbiB7XG4gICAgICAgIGludGVsbGlzZW5zZTtcbiAgICAgICAgZWRpdG9yO1xuICAgICAgICBhY3Rpb247XG5cbiAgICAgICAgY29uc3RydWN0b3IoaW50ZWxsaXNlbnNlLCBlZGl0b3IpIHtcbiAgICAgICAgICAgIHRoaXMuZWRpdG9yID0gZWRpdG9yO1xuICAgICAgICAgICAgdGhpcy5pbnRlbGxpc2Vuc2UgPSBpbnRlbGxpc2Vuc2U7XG5cbiAgICAgICAgICAgIHRoaXMuaW5pdEVkaXRvcigpO1xuICAgICAgICB9XG5cbiAgICAgICAgaW5pdEVkaXRvcigpIHtcbiAgICAgICAgICAgIGNvbnN0IGNvbmRpdGlvbiA9IHRoaXMuZWRpdG9yLmNyZWF0ZUNvbnRleHRLZXkoJ29jdG9iZXJFeHBhbmRDb21wb25lbnREZWZpbml0aW9uQ29uZGl0aW9uJywgZmFsc2UpO1xuICAgICAgICAgICAgY29uZGl0aW9uLnNldCh0cnVlKTtcbiAgICAgICAgICAgIHRoaXMuZWRpdG9yLm9jdG9iZXJFeHBhbmRDb21wb25lbnREZWZpbml0aW9uQ29uZGl0aW9uID0gY29uZGl0aW9uO1xuXG4gICAgICAgICAgICB0aGlzLmFjdGlvbiA9IHRoaXMuZWRpdG9yLmFkZEFjdGlvbih7XG4gICAgICAgICAgICAgICAgaWQ6ICdvY3RvYmVyLWV4cGFuZC1jb21wb25lbnQtZGVmaW5pdGlvbicsXG4gICAgICAgICAgICAgICAgbGFiZWw6ICdFeHBhbmQgQ29tcG9uZW50IFBhcnRpYWwgKFRPRE8pJyxcbiAgICAgICAgICAgICAgICBwcmVjb25kaXRpb246ICdvY3RvYmVyRXhwYW5kQ29tcG9uZW50RGVmaW5pdGlvbkNvbmRpdGlvbicsXG4gICAgICAgICAgICAgICAgY29udGV4dE1lbnVHcm91cElkOiAnbmF2aWdhdGlvbicsXG4gICAgICAgICAgICAgICAgY29udGV4dE1lbnVPcmRlcjogMS41LFxuICAgICAgICAgICAgICAgIHJ1bjogZnVuY3Rpb24oZWQpIHtcbiAgICAgICAgICAgICAgICAgICAgYWxlcnQoXCJpJ20gcnVubmluZyA9PiBcIiArIGVkLmdldFBvc2l0aW9uKCkpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG5cbiAgICAgICAgZGlzcG9zZSgpIHtcbiAgICAgICAgICAgIGNvbnNvbGUubG9nKCdEaXNwb3NpbmcgYWN0aW9uJyk7XG4gICAgICAgICAgICB0aGlzLmFjdGlvbi5kaXNwb3NlKCk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICByZXR1cm4gRXhwYW5kQ29tcG9uZW50RGVmaW5pdGlvbjtcbn0pO1xuIl19
