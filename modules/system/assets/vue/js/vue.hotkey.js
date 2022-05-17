+function ($) {
    'use strict';

    var KeyCondition = function () {
        function KeyCondition(hotkeyStr) {
            babelHelpers.classCallCheck(this, KeyCondition);

            this.shift = false;
            this.ctrl = false;
            this.cmd = false;
            this.alt = false;
            this.specific = -1;

            this.parseHotkeyStr(hotkeyStr);
        }

        babelHelpers.createClass(KeyCondition, [{
            key: 'parseHotkeyStr',
            value: function parseHotkeyStr(hotkeyStr) {
                var _this = this;

                var keys = hotkeyStr.trim().split('+');

                keys.forEach(function (key) {
                    switch (key.trim()) {
                        case 'shift':
                            _this.shift = true;
                            break;
                        case 'ctrl':
                            _this.ctrl = true;
                            break;
                        case 'command':
                        case 'cmd':
                        case 'meta':
                            _this.cmd = true;
                            break;
                        case 'alt':
                        case 'option':
                            _this.alt = true;
                            break;
                    }
                });

                this.specific = KeyCondition.keyMap[keys[keys.length - 1]];
                if (this.specific === undefined) {
                    this.specific = keys[keys.length - 1].toUpperCase().charCodeAt();
                }
            }
        }, {
            key: 'match',
            value: function match(ev) {
                var code = ev.which ? ev.which : ev.keyCode;

                return code === this.specific && ev.shiftKey === this.shift && ev.ctrlKey === this.ctrl && ev.metaKey === this.cmd && ev.altKey === this.alt;
            }
        }]);
        return KeyCondition;
    }();

    KeyCondition.keyMap = {
        esc: 27,
        tab: 9,
        space: 32,
        return: 13,
        enter: 13,
        backspace: 8,
        scroll: 145,
        capslock: 20,
        numlock: 144,
        pause: 19,
        break: 19,
        insert: 45,
        home: 36,
        delete: 46,
        suppr: 46,
        end: 35,
        pageup: 33,
        pagedown: 34,
        left: 37,
        up: 38,
        right: 39,
        down: 40,
        f1: 112,
        f2: 113,
        f3: 114,
        f4: 115,
        f5: 116,
        f6: 117,
        f7: 118,
        f8: 119,
        f9: 120,
        f10: 121,
        f11: 122,
        f12: 123
    };

    var ListenerRegistry = function () {
        function ListenerRegistry() {
            var _this2 = this;

            babelHelpers.classCallCheck(this, ListenerRegistry);

            this.listeners = new Map();
            document.addEventListener('keydown', function (ev) {
                return _this2.onKeyDown(ev);
            });
        }

        babelHelpers.createClass(ListenerRegistry, [{
            key: 'makeConditions',
            value: function makeConditions(hotkeyStr) {
                var conditions = [];

                hotkeyStr.split(',').forEach(function (keyBinding) {
                    conditions.push(new KeyCondition(keyBinding));
                });

                return conditions;
            }
        }, {
            key: 'addListener',
            value: function addListener(listener, hotkeyStr) {
                this.listeners.set(listener, this.makeConditions(hotkeyStr));
            }
        }, {
            key: 'removeListener',
            value: function removeListener(listener) {
                this.listeners.delete(listener);
            }
        }, {
            key: 'onKeyDown',
            value: function onKeyDown(ev) {
                this.listeners.forEach(function (conditions, listener) {
                    if (conditions.some(function (condition) {
                        return condition.match(ev);
                    })) {
                        listener(ev);
                    }
                });
            }
        }]);
        return ListenerRegistry;
    }();

    var registry = new ListenerRegistry();

    Vue.directive('oc-hotkey', {
        bind: function bind(el, binding, vnode) {
            if (!binding.arg) {
                return;
            }

            registry.addListener(binding.value, binding.arg);
        },

        unbind: function unbind(el, binding, vnode) {
            if (!binding.arg) {
                return;
            }

            registry.removeListener(binding.value);
        }
    });

    $.oc.vueHotkeyMixin = {
        data: function data() {
            return {
                componentHotkeys: {}
            };
        },

        created: function created() {
            var _this3 = this;

            Object.keys(this.componentHotkeys).forEach(function (hotkey) {
                registry.addListener(_this3.componentHotkeys[hotkey], hotkey);
            });
        },

        beforeDestroy: function beforeDestroy() {
            var _this4 = this;

            Object.keys(this.componentHotkeys).forEach(function (hotkey) {
                registry.removeListener(_this4.componentHotkeys[hotkey]);
            });
        }
    };
}($);
