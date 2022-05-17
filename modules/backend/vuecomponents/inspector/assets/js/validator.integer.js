+function ($) { "use strict";
    var ValidatorBase = $.oc.vueComponentHelpers.inspector.validators.numberbase;

    var IntegerValidator = function(propertyName, options) {
        ValidatorBase.call(this, propertyName, options);
    };

    IntegerValidator.prototype = Object.create(ValidatorBase.prototype);
    IntegerValidator.prototype.constructor = IntegerValidator;

    IntegerValidator.prototype.validate = function(value) {
        if (!this.isScalar(value) || typeof value == 'boolean') {
            this.throwError('The Integer Inspector validator can only be used with string values.');
        }

        if (value === undefined || value === null) {
            return null;
        }

        var string = $.trim(String(value));

        if (string.length === 0) {
            return null;
        }

        var testResult = this.options.allowNegative ? 
            /^\-?[0-9]*$/.test(string) : 
            /^[0-9]*$/.test(string);

        if (!testResult) {
            var defaultMessage = this.options.allowNegative ?
                'The value should be an integer.' :
                'The value should be a positive integer.';

            return this.getMessage(defaultMessage);
        }

        return this.doCommonChecks(parseInt(string));
    };

    $.oc.vueComponentHelpers.inspector.validators.integer = IntegerValidator;
}(window.jQuery);