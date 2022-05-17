+function ($) { "use strict";
    var ValidatorBase = $.oc.vueComponentHelpers.inspector.validators.base;

    var RegexValidator = function(propertyName, options) {
        ValidatorBase.call(this, propertyName, options);
    };

    RegexValidator.prototype = Object.create(ValidatorBase.prototype);
    RegexValidator.prototype.constructor = RegexValidator;

    RegexValidator.prototype.validate = function(value) {
        if (this.options.pattern === undefined) {
            this.throwError('The pattern parameter is not defined in the Regex Inspector validator configuration.');
        }

        if (!this.isScalar(value)) {
            this.throwError('The Regex Inspector validator can only be used with string values.');
        }

        if (value === undefined || value === null) {
            return null;
        }

        var string = $.trim(String(value));
        if (string.length === 0) {
            return null;
        }

        var regexObj = new RegExp(this.options.pattern, this.options.modifiers);
        return regexObj.test(string) ? null : this.getMessage();
    };

    $.oc.vueComponentHelpers.inspector.validators.regex = RegexValidator;
}(window.jQuery);