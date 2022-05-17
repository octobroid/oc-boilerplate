$.oc.module.register('editor.command', function () {
    'use strict';
    /**
     * Represents Editor client-side commands.
     * Command syntax: "namespace:command@parameter". The namespace
     * and parameter parts are optional.
     */

    var EditorCommand = function () {
        function EditorCommand(commandString, userData) {
            babelHelpers.classCallCheck(this, EditorCommand);

            if ((typeof commandString === 'undefined' ? 'undefined' : babelHelpers.typeof(commandString)) === 'object' && commandString instanceof EditorCommand) {
                return commandString;
            }

            var re = /^(([^:]+):)?([^@]+)(@(.*))?$/; // Can't use named capture groups because of IE11
            var matchData = commandString.match(re);

            if (!matchData.length) {
                throw new Error('Editor commands must have format "command", "namespace:command" or "namespace:command@parameter". Invalid command string: ' + commandString);
            }

            this.fullCommand = commandString;
            this.namespace = matchData[2] || null;
            this.parameter = matchData[5] || null;
            this.command = matchData[3];
            this.userData = userData === undefined ? {} : userData;
        }

        babelHelpers.createClass(EditorCommand, [{
            key: 'matches',
            value: function matches(commandObject) {
                if (commandObject.fullCommand == this.fullCommand) {
                    return true;
                }

                if (commandObject.hasParameter) {
                    return false;
                }

                return this.basePart == commandObject.basePart;
            }
        }, {
            key: 'hasParameter',
            get: function get() {
                return this.parameter != null;
            }
        }, {
            key: 'basePart',
            get: function get() {
                if (this.namespace === null) {
                    return this.command;
                }

                return this.namespace + ':' + this.command;
            }
        }]);
        return EditorCommand;
    }();

    return EditorCommand;
});
