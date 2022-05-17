/*
 * Allows multiple Vue containers to exist on a page
 * and maintains the global page state. Provides a basic
 * connection between legacy forms and Vue frameworks.
 */
$.oc.module.register('backend.october.vueapp', function () {
    'use strict';

    var VueApp = function () {
        function VueApp() {
            var _this = this;

            babelHelpers.classCallCheck(this, VueApp);

            this.methods = {};
            this.containers = {};

            this.state = {
                processing: false,
                lang: {}
            };
            this.registerMethods();

            document.addEventListener('DOMContentLoaded', function () {
                _this.loadInitialState();
                _this.initListeners();
                _this.initContainers();
            }, false);
        }

        babelHelpers.createClass(VueApp, [{
            key: 'registerMethod',
            value: function registerMethod(name, fn) {
                this.methods[name] = fn;
            }
        }, {
            key: 'registerMethods',
            value: function registerMethods() {
                var _this2 = this;

                this.registerMethod('onCommand', function (command, isHotkey, ev, targetElement, customData) {
                    return _this2.onCommand(command, isHotkey, ev, targetElement, customData);
                });
            }
        }, {
            key: 'getLangString',
            value: function getLangString(key) {
                return this.state.lang[key];
            }
        }, {
            key: 'loadInitialState',
            value: function loadInitialState() {
                var _this3 = this;

                var stateElements = document.querySelectorAll('[data-vue-state]');
                stateElements.forEach(function (element) {
                    var stateIndex = element.getAttribute('data-vue-state');
                    _this3.state[stateIndex] = JSON.parse(element.innerHTML);
                });
            }
        }, {
            key: 'onBeforeContainersInit',
            value: function onBeforeContainersInit() {}

            /**
             * Handles commands starting with the "form:" prefix.
             * All other commands must be handled in a child class.
             * @param String command 
             * @param Boolean isHotkey 
             * @param Event ev 
             */

        }, {
            key: 'onCommand',
            value: function () {
                var _ref = babelHelpers.asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(command, isHotkey, ev, targetElement, customData) {
                    var _this4 = this;

                    var parts, requestData, customDataOptions;
                    return regeneratorRuntime.wrap(function _callee$(_context) {
                        while (1) {
                            switch (_context.prev = _context.next) {
                                case 0:
                                    parts = command.split(':');

                                    if (!(parts.length == 2 && parts[0] !== 'form')) {
                                        _context.next = 3;
                                        break;
                                    }

                                    throw new Error('Unknown command: ' + command);

                                case 3:
                                    requestData = {};
                                    customDataOptions = customData || {};


                                    if (customDataOptions.request) {
                                        requestData = customData.request;
                                    }

                                    if (!customDataOptions.confirm) {
                                        _context.next = 15;
                                        break;
                                    }

                                    _context.prev = 7;
                                    _context.next = 10;
                                    return $.oc.confirmPromise(customDataOptions.confirm);

                                case 10:
                                    _context.next = 15;
                                    break;

                                case 12:
                                    _context.prev = 12;
                                    _context.t0 = _context['catch'](7);
                                    return _context.abrupt('return', Promise.reject());

                                case 15:

                                    this.state.processing = true;
                                    return _context.abrupt('return', this.ajaxRequest(targetElement, parts[1], requestData).finally(function () {
                                        _this4.state.processing = false;
                                    }));

                                case 17:
                                case 'end':
                                    return _context.stop();
                            }
                        }
                    }, _callee, this, [[7, 12]]);
                }));

                function onCommand(_x, _x2, _x3, _x4, _x5) {
                    return _ref.apply(this, arguments);
                }

                return onCommand;
            }()
        }, {
            key: 'isFormCommand',
            value: function isFormCommand(command) {
                var parts = command.split(':');
                return parts.length === 2 && parts[0] === 'form';
            }
        }, {
            key: 'ajaxRequest',
            value: function ajaxRequest(element, handler, requestData) {
                return new Promise(function (resolve, reject, onCancel) {
                    var request = $(element).request(handler, requestData);

                    if (request.fail) {
                        request.fail(function (data) {
                            reject(data);
                        });
                    }

                    if (request.done) {
                        request.done(function (data) {
                            resolve(data);
                        });
                    }

                    onCancel(function () {
                        request.abort();
                    });
                });
            }
        }, {
            key: 'initContainers',
            value: function initContainers() {
                var _this5 = this;

                document.querySelectorAll('[data-vue-container]').forEach(function (element) {
                    _this5.initContainerLang(element);
                });

                this.onBeforeContainersInit();

                document.querySelectorAll('[data-vue-container]').forEach(function (element) {
                    var containerName = element.getAttribute('data-vue-container');
                    var vm = new Vue({
                        data: {
                            state: _this5.state
                        },
                        el: element,
                        methods: _this5.methods
                    });

                    if (containerName) {
                        _this5.containers[containerName] = vm;
                    }
                });
            }
        }, {
            key: 'initContainerLang',
            value: function initContainerLang(element) {
                var _this6 = this;

                var dataElements = Object.assign({}, element.dataset);
                var dataKeys = Object.keys(dataElements);

                dataKeys.forEach(function (key, index) {
                    if (key.startsWith('lang')) {
                        _this6.state.lang[key.substring(4)] = dataElements[key];
                    }
                });
            }
        }, {
            key: 'initListeners',
            value: function initListeners() {}
        }]);
        return VueApp;
    }();

    return VueApp;
});
