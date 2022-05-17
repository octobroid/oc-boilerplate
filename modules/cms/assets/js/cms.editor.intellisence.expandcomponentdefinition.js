$.oc.module.register('cms.editor.intellisense.expandcomponentdefinition', function() {
    'use strict';

    var ExpandComponentDefinition = (function() {
        function ExpandComponentDefinition(intellisense, editor) {
            babelHelpers.classCallCheck(this, ExpandComponentDefinition);

            this.editor = editor;
            this.intellisense = intellisense;

            this.initEditor();
        }

        babelHelpers.createClass(ExpandComponentDefinition, [
            {
                key: 'initEditor',
                value: function initEditor() {
                    var condition = editor.createContextKey('octoberExpandComponentDefinitionCondition', false);
                    condition.set(false);
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
            },
            {
                key: 'dispose',
                value: function dispose() {
                    console.log('Disposing action');
                    this.action.dispose();
                }
            }
        ]);
        return ExpandComponentDefinition;
    })();

    return ExpandComponentDefinition;
});
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIi4uL2VzNi9jbXMuZWRpdG9yLmludGVybGxpc2VuY2UuZXhwYW5kY29tcG9uZW50ZGVmaW5pdGlvbi5qcyJdLCJuYW1lcyI6WyIkIiwib2MiLCJtb2R1bGUiLCJyZWdpc3RlciIsIkV4cGFuZENvbXBvbmVudERlZmluaXRpb24iLCJpbnRlbGxpc2Vuc2UiLCJlZGl0b3IiLCJpbml0RWRpdG9yIiwiY29uZGl0aW9uIiwiY3JlYXRlQ29udGV4dEtleSIsInNldCIsIm9jdG9iZXJFeHBhbmRDb21wb25lbnREZWZpbml0aW9uQ29uZGl0aW9uIiwiYWN0aW9uIiwiYWRkQWN0aW9uIiwiaWQiLCJsYWJlbCIsInByZWNvbmRpdGlvbiIsImNvbnRleHRNZW51R3JvdXBJZCIsImNvbnRleHRNZW51T3JkZXIiLCJydW4iLCJlZCIsImFsZXJ0IiwiZ2V0UG9zaXRpb24iLCJjb25zb2xlIiwibG9nIiwiZGlzcG9zZSJdLCJtYXBwaW5ncyI6IkFBQUFBLEVBQUVDLEVBQUYsQ0FBS0MsTUFBTCxDQUFZQyxRQUFaLENBQXFCLG1EQUFyQixFQUEwRSxZQUFXO0FBQ2pGOztBQURpRixRQUczRUMseUJBSDJFO0FBUTdFLDJDQUFZQyxZQUFaLEVBQTBCQyxNQUExQixFQUFrQztBQUFBOztBQUM5QixpQkFBS0EsTUFBTCxHQUFjQSxNQUFkO0FBQ0EsaUJBQUtELFlBQUwsR0FBb0JBLFlBQXBCOztBQUVBLGlCQUFLRSxVQUFMO0FBQ0g7O0FBYjRFO0FBQUE7QUFBQSx5Q0FlaEU7QUFDVCxvQkFBTUMsWUFBWUYsT0FBT0csZ0JBQVAsQ0FBd0IsMkNBQXhCLEVBQXFFLEtBQXJFLENBQWxCO0FBQ0FELDBCQUFVRSxHQUFWLENBQWMsS0FBZDtBQUNBLHFCQUFLSixNQUFMLENBQVlLLHlDQUFaLEdBQXdESCxTQUF4RDs7QUFFQSxxQkFBS0ksTUFBTCxHQUFjLEtBQUtOLE1BQUwsQ0FBWU8sU0FBWixDQUFzQjtBQUNoQ0Msd0JBQUkscUNBRDRCO0FBRWhDQywyQkFBTyxpQ0FGeUI7QUFHaENDLGtDQUFjLDJDQUhrQjtBQUloQ0Msd0NBQW9CLFlBSlk7QUFLaENDLHNDQUFrQixHQUxjO0FBTWhDQyx5QkFBSyxhQUFTQyxFQUFULEVBQWE7QUFDZEMsOEJBQU0sb0JBQW9CRCxHQUFHRSxXQUFILEVBQTFCO0FBQ0g7QUFSK0IsaUJBQXRCLENBQWQ7QUFVSDtBQTlCNEU7QUFBQTtBQUFBLHNDQWdDbkU7QUFDTkMsd0JBQVFDLEdBQVIsQ0FBWSxrQkFBWjtBQUNBLHFCQUFLWixNQUFMLENBQVlhLE9BQVo7QUFDSDtBQW5DNEU7QUFBQTtBQUFBOztBQXNDakYsV0FBT3JCLHlCQUFQO0FBQ0gsQ0F2Q0QiLCJmaWxlIjoiY21zLmVkaXRvci5pbnRlcmxsaXNlbmNlLmV4cGFuZGNvbXBvbmVudGRlZmluaXRpb24uanMiLCJzb3VyY2VzQ29udGVudCI6WyIkLm9jLm1vZHVsZS5yZWdpc3RlcignY21zLmVkaXRvci5pbnRlbGxpc2Vuc2UuZXhwYW5kY29tcG9uZW50ZGVmaW5pdGlvbicsIGZ1bmN0aW9uKCkge1xuICAgICd1c2Ugc3RyaWN0JztcblxuICAgIGNsYXNzIEV4cGFuZENvbXBvbmVudERlZmluaXRpb24ge1xuICAgICAgICBpbnRlbGxpc2Vuc2U7XG4gICAgICAgIGVkaXRvcjtcbiAgICAgICAgYWN0aW9uO1xuXG4gICAgICAgIGNvbnN0cnVjdG9yKGludGVsbGlzZW5zZSwgZWRpdG9yKSB7XG4gICAgICAgICAgICB0aGlzLmVkaXRvciA9IGVkaXRvcjtcbiAgICAgICAgICAgIHRoaXMuaW50ZWxsaXNlbnNlID0gaW50ZWxsaXNlbnNlO1xuXG4gICAgICAgICAgICB0aGlzLmluaXRFZGl0b3IoKTtcbiAgICAgICAgfVxuXG4gICAgICAgIGluaXRFZGl0b3IoKSB7XG4gICAgICAgICAgICBjb25zdCBjb25kaXRpb24gPSBlZGl0b3IuY3JlYXRlQ29udGV4dEtleSgnb2N0b2JlckV4cGFuZENvbXBvbmVudERlZmluaXRpb25Db25kaXRpb24nLCBmYWxzZSk7XG4gICAgICAgICAgICBjb25kaXRpb24uc2V0KGZhbHNlKTtcbiAgICAgICAgICAgIHRoaXMuZWRpdG9yLm9jdG9iZXJFeHBhbmRDb21wb25lbnREZWZpbml0aW9uQ29uZGl0aW9uID0gY29uZGl0aW9uO1xuXG4gICAgICAgICAgICB0aGlzLmFjdGlvbiA9IHRoaXMuZWRpdG9yLmFkZEFjdGlvbih7XG4gICAgICAgICAgICAgICAgaWQ6ICdvY3RvYmVyLWV4cGFuZC1jb21wb25lbnQtZGVmaW5pdGlvbicsXG4gICAgICAgICAgICAgICAgbGFiZWw6ICdFeHBhbmQgQ29tcG9uZW50IFBhcnRpYWwgKFRPRE8pJyxcbiAgICAgICAgICAgICAgICBwcmVjb25kaXRpb246ICdvY3RvYmVyRXhwYW5kQ29tcG9uZW50RGVmaW5pdGlvbkNvbmRpdGlvbicsXG4gICAgICAgICAgICAgICAgY29udGV4dE1lbnVHcm91cElkOiAnbmF2aWdhdGlvbicsXG4gICAgICAgICAgICAgICAgY29udGV4dE1lbnVPcmRlcjogMS41LFxuICAgICAgICAgICAgICAgIHJ1bjogZnVuY3Rpb24oZWQpIHtcbiAgICAgICAgICAgICAgICAgICAgYWxlcnQoXCJpJ20gcnVubmluZyA9PiBcIiArIGVkLmdldFBvc2l0aW9uKCkpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG5cbiAgICAgICAgZGlzcG9zZSgpIHtcbiAgICAgICAgICAgIGNvbnNvbGUubG9nKCdEaXNwb3NpbmcgYWN0aW9uJyk7XG4gICAgICAgICAgICB0aGlzLmFjdGlvbi5kaXNwb3NlKCk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICByZXR1cm4gRXhwYW5kQ29tcG9uZW50RGVmaW5pdGlvbjtcbn0pO1xuIl19
