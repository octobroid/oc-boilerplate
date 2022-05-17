/*
 * Allows multiple Vue containers to exist on a page
 * and maintains the global page state. Provides a basic
 * connection between legacy forms and Vue frameworks.
 */
$.oc.module.register('backend.october.vueapp', function () {
    'use strict';

    class VueApp {
        state;
        methods;
        containers;

        constructor() {
            this.methods = {};
            this.containers = {};

            this.state = {
                processing: false,
                lang: {}
            };
            this.registerMethods();
            
            document.addEventListener('DOMContentLoaded', () => {
                this.loadInitialState();
                this.initListeners();
                this.initContainers();
            }, false);
        }

        registerMethod(name, fn) {
            this.methods[name] = fn;
        }

        registerMethods() {
            this.registerMethod('onCommand', (command, isHotkey, ev, targetElement, customData) => this.onCommand(command, isHotkey, ev, targetElement, customData));
        }

        getLangString(key) {
            return this.state.lang[key];
        }

        loadInitialState() {
            const stateElements = document.querySelectorAll('[data-vue-state]');
            stateElements.forEach(element => {
                const stateIndex = element.getAttribute('data-vue-state');
                this.state[stateIndex] = JSON.parse(element.innerHTML);
            });
        }

        onBeforeContainersInit() {}

        /**
         * Handles commands starting with the "form:" prefix.
         * All other commands must be handled in a child class.
         * @param String command 
         * @param Boolean isHotkey 
         * @param Event ev 
         */
        async onCommand(command, isHotkey, ev, targetElement, customData) {
            let parts = command.split(':');
            if (parts.length == 2 && parts[0] !== 'form') {
                throw new Error('Unknown command: ' + command);
            }

            let requestData = {};
            let customDataOptions = customData || {};

            if (customDataOptions.request) {
                requestData = customData.request;
            }

            if (customDataOptions.confirm) {
                try {
                    await $.oc.confirmPromise(customDataOptions.confirm);
                } catch (error) {
                    return Promise.reject();
                }
            }

            this.state.processing = true;
            return this.ajaxRequest(targetElement, parts[1], requestData)
                .finally(() => {
                    this.state.processing = false;
                });
        }

        isFormCommand(command) {
            let parts = command.split(':');
            return parts.length === 2 && parts[0] === 'form';
        }

        ajaxRequest(element, handler, requestData) {
            return new Promise(function (resolve, reject, onCancel) {
                const request = $(element).request(handler, requestData);

                if (request.fail) {
                    request.fail(
                        function (data) {
                            reject(data);
                        }
                    );
                }

                if (request.done) {
                    request.done(
                        function (data) {
                            resolve(data);
                        }
                    );
                }

                onCancel(function() {
                    request.abort();
                });
            });
        }

        initContainers() {
            document.querySelectorAll('[data-vue-container]').forEach(element => {
                this.initContainerLang(element);
            });

            this.onBeforeContainersInit();

            document.querySelectorAll('[data-vue-container]').forEach(element => {
                const containerName = element.getAttribute('data-vue-container');
                const vm = new Vue({
                    data: {
                        state: this.state
                    },
                    el: element,
                    methods: this.methods
                });

                if (containerName) {
                    this.containers[containerName] = vm;
                }
            });
        }

        initContainerLang(element) {
            const dataElements = Object.assign({}, element.dataset);
            const dataKeys = Object.keys(dataElements);

            dataKeys.forEach((key, index) => {
                if (key.startsWith('lang')) {
                    this.state.lang[key.substring(4)] = dataElements[key];
                }
            });
        }

        initListeners() {}
    }

    return VueApp;
});
