+(function($) {
    'use strict';

    function OctoberModuleRegistry() {
        this.modules = new Map();

        this.register = function(namespace, registrationFn) {
            if (this.modules.has(namespace)) {
                console.info('Module namespace is already registered: ' + namespace);
                return;
            }

            this.modules.set(namespace, registrationFn());
        };

        this.import = function(namespace) {
            if (!this.exists(namespace)) {
                throw new Error('Module namespace is not registered: ' + namespace);
            }

            return this.modules.get(namespace);
        };

        this.exists = function(namespace) {
            return this.modules.has(namespace);
        };
    }

    $.oc.module = new OctoberModuleRegistry();
})($);
