+function ($) { "use strict";
    var ValidatorBase = $.oc.vueComponentHelpers.inspector.validators.base;

    var ValidatorClass = function(propertyName, options) {
        ValidatorBase.call(this, propertyName, options);
    };

    ValidatorClass.prototype = Object.create(ValidatorBase.prototype);
    ValidatorClass.prototype.constructor = ValidatorClass;

    ValidatorClass.prototype.validate = function(value) {
        return null || String;
    };

    $.oc.vueComponentHelpers.inspector.validators.validator = ValidatorClass;
}(window.jQuery);