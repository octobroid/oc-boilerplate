/*
 * Sensitive field widget plugin
 *
 * Data attributes:
 * - data-control="sensitive" - enables the plugin on an element
 *
 * JavaScript API:
 * $('div#someElement').sensitive({...})
 */
+function ($) { "use strict";
    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype;

    var Sensitive = function(element, options) {
        this.$el = $(element);
        this.options = options;
        this.clean = !!this.$el.data('sensitive-clean');
        this.hidden = true;

        this.$input = $('[data-sensitive-input]:first', this.$el);
        this.$toggle = $('[data-sensitive-toggle]:first', this.$el);
        this.$icon = $('[data-sensitive-icon]:first', this.$el);
        this.$loader = $('[data-sensitive-loader]:first', this.$el);
        this.$copy = $('[data-sensitive-copy]:first', this.$el);
        this.$facade = $('[data-sensitive-facade]:first', this.$el);

        $.oc.foundation.controlUtils.markDisposable(element);
        Base.call(this);
        this.init();
    }

    Sensitive.DEFAULTS = {
        readOnly: false,
        disabled: false,
        revealHandler: null,
        hideOnTabChange: false,
        displayMode: 'text'
    }

    Sensitive.prototype = Object.create(BaseProto);
    Sensitive.prototype.constructor = Sensitive;

    Sensitive.prototype.init = function() {
        this.$input.on('keydown', this.proxy(this.onInput));
        this.$toggle.on('click', this.proxy(this.onToggle));

        if (this.options.hideOnTabChange) {
            document.addEventListener('visibilitychange', this.proxy(this.onTabChange));
        }

        if (this.$copy.length) {
            this.$copy.on('click', this.proxy(this.onCopy));
        }

        this.$el.one('dispose-control', this.proxy(this.dispose));
    }

    Sensitive.prototype.dispose = function () {
        this.$input.off('keydown', this.proxy(this.onInput));
        this.$toggle.off('click', this.proxy(this.onToggle));

        if (this.options.hideOnTabChange) {
            document.removeEventListener('visibilitychange', this.proxy(this.onTabChange));
        }

        if (this.$copy.length) {
            this.$copy.off('click', this.proxy(this.onCopy));
        }

        this.$el.off('dispose-control', this.proxy(this.dispose));
        this.$el.removeData('oc.sensitive');

        this.$input = null;
        this.$toggle = null;
        this.$icon = null;
        this.$loader = null;
        this.$el = null;

        BaseProto.dispose.call(this);
    }

    Sensitive.prototype.onInput = function() {
        if (this.clean) {
            this.clean = false;
            this.$input.val('');
        }

        return true
    }

    Sensitive.prototype.onToggle = function() {
        if (this.$input.val() !== '' && this.clean) {
            this.reveal();
        }
        else {
            this.toggleVisibility();
        }

        return true;
    }

    Sensitive.prototype.onTabChange = function() {
        if (document.hidden && !this.hidden) {
            this.toggleVisibility();
        }
    }

    Sensitive.prototype.onCopy = function() {
        var self = this,
            deferred = $.Deferred(),
            isHidden = this.hidden,
            isDisabled = this.$input.is(':disabled');

        deferred.then(function () {
            if (isDisabled) {
                self.$input.attr('disabled', false);
            }
            if (self.hidden) {
                self.toggleVisibility();
            }

            self.$input.focus();
            self.$input.select();

            try {
                document.execCommand('copy');
            }
            catch (err) {
            }

            self.$input.blur();

            if (isDisabled) {
                self.$input.attr('disabled', true);
            }

            if (isHidden) {
                self.toggleVisibility();
            }
        })

        if (this.$input.val() !== '' && this.clean) {
            this.reveal(deferred);
        }
        else {
            deferred.resolve();
        }
    }

    Sensitive.prototype.toggleVisibility = function() {

        if (this.options.displayMode === 'textarea') {
            this.$facade.toggle();
            this.$input.toggle();
        }
        else {
            if (this.hidden) {
                this.$input.attr('type', 'text');
            }
            else {
                this.$input.attr('type', 'password');
            }
        }

        this.$icon.toggleClass('icon-eye icon-eye-slash');

        this.hidden = !this.hidden;
    }

    Sensitive.prototype.reveal = function(deferred) {
        var self = this;
        this.$icon.css({
            visibility: 'hidden'
        });
        this.$loader.removeClass('hide');

        this.$input.request(this.options.revealHandler, {
            success: function (data) {
                self.$input.val(data.value);
                self.clean = false;

                self.$icon.css({
                    visibility: 'visible'
                });
                self.$loader.addClass('hide');

                self.toggleVisibility();

                if (deferred) {
                    deferred.resolve();
                }
            }
        });
    }

    var old = $.fn.sensitive;

    $.fn.sensitive = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result;
        this.each(function () {
            var $this   = $(this);
            var data    = $this.data('oc.sensitive');
            var options = $.extend({}, Sensitive.DEFAULTS, $this.data(), typeof option == 'object' && option);
            if (!data) $this.data('oc.sensitive', (data = new Sensitive(this, options)));
            if (typeof option == 'string') result = data[option].apply(data, args);
            if (typeof result != 'undefined') return false;
        })

        return result ? result : this;
    }

    $.fn.sensitive.noConflict = function () {
        $.fn.sensitive = old;
        return this;
    }

    $(document).render(function () {
        $('[data-control="sensitive"]').sensitive();
    });

}(window.jQuery);
