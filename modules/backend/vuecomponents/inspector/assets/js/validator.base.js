+function ($) { "use strict";
    var BaseValidator = function(propertyName, options) {
        this.options = options;
        this.propertyName = propertyName;
        this.defaultMessage = 'Invalid property value.';
    };

    BaseValidator.prototype.getMessage = function(defaultMessage) {
        if (this.options.message !== undefined) {
            return this.options.message;
        }

        if (defaultMessage !== undefined) {
            return defaultMessage;
        }

        return this.defaultMessage;
    };

    BaseValidator.prototype.isScalar = function(value) {
        if (value === undefined || value === null) {
            return true;
        }

        return !!(typeof value === 'string' || typeof value == 'number' || typeof value == 'boolean');
    }

    BaseValidator.prototype.throwError = function(errorMessage) {
        throw new Error(errorMessage + ' Property: ' + this.propertyName);
    };

    BaseValidator.prototype.validate = function(value) {
        return null;
    };

    if ($.oc.vueComponentHelpers === undefined) {
        $.oc.vueComponentHelpers = {};
    }

    if ($.oc.vueComponentHelpers.inspector === undefined) {
        $.oc.vueComponentHelpers.inspector = {};
    }

    if ($.oc.vueComponentHelpers.inspector.validators === undefined) {
        $.oc.vueComponentHelpers.inspector.validators = {};
    }

    $.oc.vueComponentHelpers.inspector.validators.base = BaseValidator;
}(window.jQuery);