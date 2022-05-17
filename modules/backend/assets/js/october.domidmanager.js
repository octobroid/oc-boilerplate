/*
 * Generates unique DOM element identifiers
 */

+function ($) { "use strict";
    var DomIdManager = function() {
        var indexes = 1;

        this.generate = function(prefix) {
            var result = 'id-' + (indexes ++);

            if (typeof prefix == 'string') {
                result = prefix + '-' + result;
            }

            return result;
        };
    };

    $.oc.domIdManager = new DomIdManager();
}(window.jQuery);