+function ($) { "use strict";
    var ValidatorSet = function(options, propertyName) {
        var validators = [];

        function init() {
            makeValidators();
        }

        function throwError(errorMessage) {
            throw new Error(errorMessage + ' Property: ' + propertyName);
        }

        function makeValidators() {
            // Handle legacy validation syntax properties:
            //
            // - required
            // - validationPattern
            // - validationMessage 

            if ((options.required !== undefined ||
                options.validationPattern !== undefined ||
                options.validationMessage !== undefined) &&
                options.validation !== undefined) {
                throwError('Legacy and new validation syntax should not be mixed.');
            }

            var knownValidators = $.oc.vueComponentHelpers.inspector.validators;

            if (options.required !== undefined && options.required) {
                var validator = new knownValidators.required(propertyName, {
                        message: options.validationMessage
                    });

                validators.push(validator);
            }

            if (options.validationPattern !== undefined) {
                var validator = new knownValidators.regex(propertyName, {
                        message: options.validationMessage,
                        pattern: options.validationPattern
                    });

                validators.push(validator);
            }

            //
            // Handle new validation syntax
            //

            if (options.validation === undefined) {
                return;
            }

            for (var validatorName in options.validation) {
                if (knownValidators[validatorName] == undefined) {
                    throwError('Inspector validator "' + validatorName + '" is not found in the $.oc.vueComponentHelpers.inspector.validators namespace.');
                }

                validators.push(
                    new knownValidators[validatorName] (
                        propertyName,
                        options.validation[validatorName]
                    )
                );
            }
        }

        this.validate = function(value) {
            try {
                for (var i=0; i<validators.length; i++) {
                    var validator = validators[i],
                        errorMessage = validator.validate(value);

                    if (typeof errorMessage === 'string') {
                        return errorMessage;
                    }
                }

                return null;
            }
            catch (err) {
                throwError(err);
            }
        };

        init();
    };

    if ($.oc.vueComponentHelpers === undefined) {
        $.oc.vueComponentHelpers = {};
    }

    if ($.oc.vueComponentHelpers === undefined) {
        $.oc.vueComponentHelpers.inspector = {};
    }

    $.oc.vueComponentHelpers.inspector.validatorSet = ValidatorSet;
}(window.jQuery);