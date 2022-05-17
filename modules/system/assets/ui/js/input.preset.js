/*
 * An input preset converter.
 *
 * The API allows to convert text entered into an element to a URL, slug or file name
 * value in another input element.
 *
 * Supported data attributes:
 * - data-input-preset: specifies a CSS selector for a source input element
 * - data-input-preset-closest-parent: optional, specifies a CSS selector for a closest common parent
 *   for the source and destination input elements.
 * - data-input-preset-type: specifies the conversion type. Supported values are:
 *   url, file, slug, camel.
 * - data-input-preset-prefix-input: optional, prefixes the converted value with the value found
 *   in the supplied input element using a CSS selector.
 * - data-input-preset-remove-words: optional, use removeList to filter stop words of source string.
 *
 * Example: <input type="text" id="name" value=""/>
 *          <input type="text"
 *             data-input-preset="#name"
 *             data-input-preset-type="file">
 *
 * JavaScript API:
 * $('#filename').inputPreset({inputPreset: '#name', inputPresetType: 'file'})
 */
+function ($) { "use strict";
    var InputPreset = function (element, options) {
        var $el = this.$el = $(element)
        this.options = options || {}
        this.cancelled = false

        var parent = options.inputPresetClosestParent !== undefined
                ? $el.closest(options.inputPresetClosestParent)
                : undefined,
            self = this,
            prefix = ''

        if (options.inputPresetPrefixInput !== undefined)
            prefix = $(options.inputPresetPrefixInput, parent).val()

        if (prefix === undefined)
            prefix = ''

        // Do not update the element if it already has a value and the value doesn't match the prefix
        if ($el.val().length && $el.val() != prefix)
            return

        $el.val(prefix).trigger('oc.inputPreset.afterUpdate')

        this.$src = $(options.inputPreset, parent)

        this.$src.on('input paste', function(event) { 
            if (self.cancelled) 
                return 
 
            var timeout = event.type === 'paste' ? 100 : 0 
            var updateValue = function(self, el, prefix) { 
                if (el.data('update') === false) {
                    return
                }
                el   
                    .val(prefix + $.oc.presetEngine.formatValue(options, self.$src.val())) 
                    .trigger('oc.inputPreset.afterUpdate') 
            } 
 
            var src = $(this) 
            setTimeout(function() { 
                $el.trigger('oc.inputPreset.beforeUpdate', [src]) 
                setTimeout(updateValue, 100, self, $el, prefix) 
            }, timeout) 
        }) 

        this.$el.on('change', function() {
            self.cancelled = true
        })
    }

    InputPreset.DEFAULTS = {
        inputPreset: '',
        inputPresetType: 'slug',
        inputPresetClosestParent: undefined,
        inputPresetPrefixInput: undefined,
        inputPresetRemoveWords: true
    }

    // INPUT CONVERTER PLUGIN DEFINITION
    // ============================

    var old = $.fn.inputPreset

    $.fn.inputPreset = function (option) {
        return this.each(function () {
            var $this = $(this)
            var data  = $this.data('oc.inputPreset')
            var options = $.extend({}, InputPreset.DEFAULTS, $this.data(), typeof option == 'object' && option)

            if (!data) $this.data('oc.inputPreset', (data = new InputPreset(this, options)))
        })
    }

    $.fn.inputPreset.Constructor = InputPreset

    // INPUT CONVERTER NO CONFLICT
    // =================

    $.fn.inputPreset.noConflict = function () {
        $.fn.inputPreset = old
        return this
    }

    // INPUT CONVERTER DATA-API
    // ===============

    $(document).render(function() {
        $('[data-input-preset]').inputPreset()
    })

}(window.jQuery);
