$.oc.module.register('backend.vuecomponents.richeditordocumentconnector.utils', function () {
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
            key: 'buttonFromButton',
            value: function buttonFromButton(component, $button) {
                var cmd = $button.attr('data-cmd');

                var $wrapper = $button.closest('.fr-btn-wrap');
                if ($wrapper.length && $button.next('.fr-btn.fr-dropdown')) {
                    // Dropdowns have two buttons,
                    // ignore the first one.
                    return;
                }

                var buttonCmd = cmd;
                var knownButtonConfig = component.buttonConfig[cmd];
                if (knownButtonConfig && knownButtonConfig.cmd) {
                    buttonCmd = knownButtonConfig.cmd;
                }

                var codeEditing = component.loadingCodeEditingMode || component.codeEditingMode;

                component.toolbarContainer.push({
                    type: 'button',
                    icon: 'octo-icon-' + this.mapIconName(component, cmd),
                    command: 'richeditor-toolbar-' + buttonCmd,
                    tooltip: $button.attr('title'),
                    pressed: $button.hasClass('fr-active') || codeEditing && cmd === 'html',
                    disabled: $button.hasClass('fr-disabled') || codeEditing && cmd !== 'html'
                });
            }
        }, {
            key: 'dropdownFromButton',
            value: function dropdownFromButton(component, $button, updateDropdownId) {
                var cmd = $button.attr('data-cmd');
                var knownButtonConfig = component.buttonConfig[cmd];

                if (knownButtonConfig && knownButtonConfig.convertToButtonGroup) {
                    return this.buttonGroupFromButton(component, $button);
                }

                var buttonId = $button.attr('data-oc-button-id');
                if (!buttonId) {
                    buttonId = $.oc.domIdManager.generate('oc-richedit-button');
                    $button.attr('data-oc-button-id', buttonId);
                }

                if (updateDropdownId && buttonId !== updateDropdownId) {
                    return;
                }

                var $wrapper = $button.closest('.fr-btn-wrap');
                var isWrapped = $wrapper.length > 0;

                var $items = null;
                if (!knownButtonConfig || !knownButtonConfig.dropdown) {
                    $items = $wrapper.find('.fr-dropdown-menu li > a');
                    if ($items.length === 0) {
                        $items = $button.next('.fr-dropdown-menu').find('li a');
                    }
                }

                var buttonIconName = '';
                var buttonTitle = '';
                var pressed = false;
                var noPressedState = knownButtonConfig && knownButtonConfig.noPressedState;

                if (isWrapped) {
                    var $prevButton = $button.prev('.fr-btn');
                    buttonIconName = $wrapper.find('.fr-btn i').attr('class');
                    buttonTitle = $prevButton.find('.fr-sr-only').text();
                    cmd = $prevButton.attr('data-cmd');
                    pressed = !noPressedState && $prevButton.hasClass('fr-active');
                } else {
                    buttonIconName = $button.find('i').attr('class');
                    buttonTitle = $button.find('.fr-sr-only').text();
                    pressed = !noPressedState && $button.hasClass('fr-active');
                }

                var type = 'button';
                var dropdownItemType = 'text';

                if (knownButtonConfig && knownButtonConfig.checkboxDropdown) {
                    dropdownItemType = 'checkbox';
                }

                if (knownButtonConfig && knownButtonConfig.dropdownOnly || !isWrapped) {
                    type = 'dropdown';
                }

                var buttonConfig = {};
                var emitCommandBeforeMenu = null;

                if (dropdownItemType == 'checkbox') {
                    emitCommandBeforeMenu = 'richeditor-toolbar-' + cmd + '@oc-dropdown|' + buttonId;
                }

                if (!updateDropdownId && knownButtonConfig && knownButtonConfig.separatorBefore) {
                    this.addSeparator(component);
                }

                if (!updateDropdownId) {
                    var labelFromSelectedItem = knownButtonConfig && knownButtonConfig.checkedToLabel;
                    var mappedIcon = this.mapIconName(component, buttonIconName);

                    var codeEditing = component.loadingCodeEditingMode || component.codeEditingMode;

                    buttonConfig = {
                        type: type,
                        icon: mappedIcon ? 'octo-icon-' + mappedIcon : null,
                        command: 'richeditor-toolbar-' + cmd,
                        emitCommandBeforeMenu: emitCommandBeforeMenu,
                        tooltip: buttonTitle,
                        pressed: pressed,
                        menuitems: [],
                        richeditorButtonId: buttonId,
                        labelFromSelectedItem: labelFromSelectedItem,
                        disabled: $button.hasClass('fr-disabled') || codeEditing
                    };
                } else {
                    buttonConfig = component.toolbarContainer.find(function (buttonElement) {
                        return buttonElement.richeditorButtonId === updateDropdownId;
                    });
                    buttonConfig.menuitems = [];
                }

                var firstItem = null;
                var checkedFound = false;

                if ($items !== null) {
                    $items.each(function () {
                        var $item = $(this);
                        var cmd = $item.attr('data-cmd');
                        var param = $item.attr('data-param1');
                        var menuItem = {
                            type: dropdownItemType,
                            command: 'richeditor-toolbar-' + cmd + '@' + param,
                            label: $item.text(),
                            checked: $item.attr('aria-selected') === 'true'
                        };

                        if (knownButtonConfig && knownButtonConfig.applyItemStyle) {
                            menuItem.style = $item.attr('style');
                        }

                        checkedFound = checkedFound || menuItem.checked;

                        if (!firstItem) {
                            firstItem = menuItem;
                        }

                        buttonConfig.menuitems.push(menuItem);
                    });
                }

                if (knownButtonConfig && knownButtonConfig.dropdown) {
                    knownButtonConfig.dropdown.forEach(function (customDropdownItem) {
                        buttonConfig.menuitems.push({
                            type: dropdownItemType,
                            command: 'richeditor-toolbar-' + customDropdownItem.command,
                            label: component.trans(customDropdownItem.label)
                        });
                    });
                }

                if (!checkedFound && firstItem) {
                    firstItem.checked = true;
                }

                if (!updateDropdownId) {
                    component.toolbarContainer.push(buttonConfig);
                    if (knownButtonConfig && knownButtonConfig.separatorAfter) {
                        this.addSeparator(component);
                    }
                }
            }
        }, {
            key: 'buttonGroupFromButton',
            value: function buttonGroupFromButton(component, $button) {
                this.addSeparator(component);
                var $dropdownItems = $button.next('.fr-dropdown-menu').find('li > a');
                var buttonIconName = $button.find('i').attr('class');
                var that = this;

                $dropdownItems.each(function () {
                    var $dropdownButton = $(this);
                    var cmd = $dropdownButton.attr('data-cmd');
                    var param = $dropdownButton.attr('data-param1');

                    var codeEditing = component.loadingCodeEditingMode || component.codeEditingMode;

                    var buttonConfig = {
                        type: 'button',
                        icon: 'octo-icon-' + that.mapIconName(component, cmd + '-' + param),
                        command: 'richeditor-toolbar-' + cmd + '@' + param,
                        tooltip: $dropdownButton.attr('title'),
                        buttonGroup: true,
                        disabled: $button.hasClass('fr-disabled') || codeEditing
                    };

                    if ($dropdownButton.find('i').attr('class') == buttonIconName) {
                        buttonConfig.pressed = true;
                    }

                    component.toolbarContainer.push(buttonConfig);
                });

                this.addSeparator(component);
            }
        }, {
            key: 'parseCommandString',
            value: function parseCommandString(command) {
                if (!/^richeditor\-toolbar-/.test(command) || !/^[0-9a-z\-\@:\|;,\s]+$/i.test(command)) {
                    return null;
                }

                var froalaCommand = command.substring(19);
                var parameter = '';
                var cmdParts = froalaCommand.split('@');
                if (cmdParts.length > 1) {
                    froalaCommand = cmdParts[0];
                    parameter = cmdParts[1];
                }

                var parameterParts = parameter.split('|');
                var ocParameter = null;
                if (parameterParts.length > 1) {
                    parameter = parameterParts[0];
                    ocParameter = parameterParts[1];
                }

                var isOctoberCommand = froalaCommand.substring(0, 3) === 'oc-';

                return {
                    froalaCommand: froalaCommand,
                    isOctoberCommand: isOctoberCommand,
                    parameter: parameter,
                    ocParameter: ocParameter
                };
            }
        }, {
            key: 'hasActiveFroalaPopup',
            value: function hasActiveFroalaPopup() {
                return $(document.body).find('.fr-popup.fr-active').length > 0;
            }
        }, {
            key: 'makeTicks',
            value: function makeTicks(component, interval) {
                var tickCount = Math.ceil(component.size / interval);
                var result = [];
                for (var index = 0; index < tickCount; index++) {
                    result.push({
                        style: {
                            left: index * interval + 'px'
                        }
                    });
                }

                return result;
            }
        }, {
            key: 'updateEditorHtml',
            value: function updateEditorHtml(component, html) {
                component.$textarea.froalaEditor('html.set', html);
                component.$textarea.trigger('froalaEditor.contentChanged.richeditor');
            }
        }]);
        return Utils;
    }();

    return new Utils();
});
