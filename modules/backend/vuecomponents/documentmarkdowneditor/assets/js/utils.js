$.oc.module.register('backend.vuecomponents.documentmarkdowneditor.utils', function () {
    'use strict';

    var Utils = function () {
        function Utils() {
            babelHelpers.classCallCheck(this, Utils);
        }

        babelHelpers.createClass(Utils, [{
            key: 'isLastElementSeparator',
            value: function isLastElementSeparator(component) {
                var elements = component.toolbarContainer;
                var element = elements[elements.length - 1];

                if (!element) {
                    return false;
                }

                return element.type === 'separator';
            }
        }, {
            key: 'mapIconName',
            value: function mapIconName(component, editorIconName) {
                if (component.iconMap[editorIconName] === undefined) {
                    return editorIconName;
                }

                return component.iconMap[editorIconName];
            }
        }, {
            key: 'addSeparator',
            value: function addSeparator(component) {
                if (!this.isLastElementSeparator(component)) {
                    component.toolbarContainer.push({ type: 'separator' });
                }
            }
        }, {
            key: 'getButtonCommand',
            value: function getButtonCommand($button) {
                var classes = $button.attr('class').split(' ');

                for (var index = 0; index < classes.length; index++) {
                    var className = classes[index];
                    if (['active'].indexOf(className) === -1) {
                        return className;
                    }
                }

                return null;
            }
        }, {
            key: 'dropdownFromButton',
            value: function dropdownFromButton(component, $button) {
                var cmd = this.getButtonCommand($button);
                var basicConfig = this.basicButtonConfig(component, $button);
                basicConfig.type = 'dropdown';
                basicConfig.menuitems = [];

                var knownButtonConfig = component.buttonConfig[cmd];

                knownButtonConfig.dropdown.forEach(function (dropdownItem) {
                    basicConfig.menuitems.push({
                        type: 'text',
                        command: 'markdowneditor-toolbar-' + dropdownItem.command,
                        label: component.trans(dropdownItem.label)
                    });
                });

                component.toolbarContainer.push(basicConfig);
            }
        }, {
            key: 'basicButtonConfig',
            value: function basicButtonConfig(component, $button) {
                var cmd = this.getButtonCommand($button);

                if (cmd === null) {
                    console.log('Markdown button command not found', $button);
                    return;
                }

                var buttonCmd = cmd;
                var knownButtonConfig = component.buttonConfig[cmd];
                if (knownButtonConfig && knownButtonConfig.cmd) {
                    buttonCmd = knownButtonConfig.cmd;
                }

                var ignorePressState = false;
                if (knownButtonConfig) {
                    ignorePressState = knownButtonConfig.ignorePressState;
                }

                return {
                    type: 'button',
                    icon: 'octo-icon-' + this.mapIconName(component, cmd),
                    command: 'markdowneditor-toolbar-' + buttonCmd,
                    tooltip: component.trans($button.attr('title')),
                    pressed: !ignorePressState && $button.hasClass('active')
                };
            }
        }, {
            key: 'buttonFromButton',
            value: function buttonFromButton(component, $button) {
                component.toolbarContainer.push(this.basicButtonConfig(component, $button));
            }
        }, {
            key: 'parseCommandString',
            value: function parseCommandString(command) {
                if (!/^markdowneditor\-toolbar-/.test(command) || !/^[0-9a-z\-\@:\|;,\s]+$/i.test(command)) {
                    return null;
                }

                var editorCommand = command.substring(23);
                var isOctoberCommand = editorCommand.substring(0, 3) === 'oc-';

                return {
                    editorCommand: editorCommand,
                    isOctoberCommand: isOctoberCommand
                };
            }
        }]);
        return Utils;
    }();

    return new Utils();
});
