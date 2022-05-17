+function ($) { "use strict";
    var ValidatorBase = $.oc.vueComponentHelpers.inspector.validators.base;

    var NumberBase = function(propertyName, options) {
        ValidatorBase.call(this, propertyName, options);
    };

    NumberBase.prototype = Object.create(ValidatorBase.prototype);
    NumberBase.prototype.constructor = NumberBase;

    NumberBase.prototype.doCommonChecks = function (value) {
        if (this.options.min !== undefined || this.options.max !== undefined) {
            if (this.options.min !== undefined) {
                if (this.options.min.value === undefined) {
                    throw new Error('The min.value parameter is not defined in the Inspector validator configuration');
                }
 
                if (value < this.options.min.value) {
                    return this.options.min.message !== undefined ?
                        this.options.min.message :
                        "The value should not be less than " + this.options.min.value;
                }
            }
 
            if (this.options.max !== undefined) {
                if (this.options.max.value === undefined) {
                    throw new Error('The max.value parameter is not defined in the table Inspector validator configuration');
                }
 
                if (value > this.options.max.value) {
                    return this.options.max.message !== undefined ?
                        this.options.max.message :
                        "The value should not be greater than " + this.options.max.value;
                }
            }
        }
    };

    $.oc.vueComponentHelpers.inspector.validators.numberbase = NumberBase;
}(window.jQuery);