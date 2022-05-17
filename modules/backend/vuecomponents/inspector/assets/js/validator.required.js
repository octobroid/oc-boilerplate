+function ($) { "use strict";
    var ValidatorBase = $.oc.vueComponentHelpers.inspector.validators.base;

    var RequiredValidator = function(propertyName, options) {
        ValidatorBase.call(this, propertyName, options);
    };

    RequiredValidator.prototype = Object.create(ValidatorBase.prototype);
    RequiredValidator.prototype.constructor = RequiredValidator;

    RequiredValidator.prototype.validate = function(value) {
        if (value === undefined || value === null) {
            return this.getMessage();
        }

        if (typeof value === 'boolean') {
            return value ? null : this.getMessage();
        }

        if (typeof value === 'object') {
            return !$.isEmptyObject(value) ? null : this.getMessage();
        }

        return $.trim(String(value)).length > 0 ? null : this.getMessage();
    };

    $.oc.vueComponentHelpers.inspector.validators.required = RequiredValidator;
}(window.jQuery);