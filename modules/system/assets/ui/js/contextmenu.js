/*
 * Context Menu
 *
 * - Documentation: ../docs/contextmenu.md
 */
+function ($) { "use strict";

    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype


    // CONTEXTMENU CLASS DEFINITION
    // ============================

    var ContextMenu = function(element, options) {
        this.$el = $(element)

        $.oc.foundation.controlUtils.markDisposable(element)
        Base.call(this)

        this.$menu = $('<menu />').addClass('control-contextmenu')
        $(document.body).append(this.$menu)

        if (options.show) {
            this.openContextMenu(options.pageX, options.pageY, options.items)
        }
    }

    ContextMenu.prototype = Object.create(BaseProto)
    ContextMenu.prototype.constructor = ContextMenu

    ContextMenu.DEFAULTS = {
        show: false,
        pageX: 0,
        pageY: 0,
        items: []
    }

    ContextMenu.prototype.openMenu = function (options) {
        var self = this

        const time = this.isOpen() ? 100 : 0

        this.hide()

        setTimeout(function() {
            if (options.items) {
                self.buildOptions(options.items)
            }

            self.show(options.pageX, options.pageY)
        }, time)

        $(document).on('click', $.proxy(this.hideContextMenu, this))
    }

    ContextMenu.prototype.closeMenu = function () {
        $(document).off('click', $.proxy(this.hideContextMenu, this))

        this.hide()
    }

    ContextMenu.prototype.buildOptions = function (options) {
        var self = this

        this.$menu.empty()

        $.each(options, function(i, option) {
            self.buildOption(option)
        })
    }

    ContextMenu.prototype.buildOption = function (option) {
        var li = $('<li />').addClass('contextmenu-item')
        var item

        if (option.action) {
            var span = $('<span />').addClass('contextmenu-text').html(option.name)
            item = $('<button />').addClass('contextmenu-btn')

            item.append(span)
            li.on('click', option.action)
            li.append(item)
        }
        else {
            item = $('<span />').addClass('contextmenu-title').html(option.name)
            li.append(item)
        }

        if (option.icon) {
            var i = $('<i />').addClass('contextmenu-icon').addClass('icon-'+option.icon)
            item.prepend(i)
        }

        if (option.label) {
            var lbl = $('<span />').addClass('contextmenu-label').html(option.label)
            item.append(lbl)
        }

        this.$menu.append(li)
    }

    ContextMenu.prototype.show = function (x, y) {
        const w = window.innerWidth
        const h = window.innerHeight

        const mw = this.$menu.get(0).offsetWidth
        const mh = this.$menu.get(0).offsetHeight

        if (x + mw > w) { x = x - mw }
        if (y + mh > h) { y = y - mh }

        this.$menu.css('left', x + 'px')
        this.$menu.css('top', y + 'px')
        this.$menu.addClass('is-visible')
    }

    ContextMenu.prototype.hide = function () {
        this.$menu.removeClass('is-visible')
    }

    ContextMenu.prototype.isOpen = function () {
        return this.$menu.hasClass('is-visible')
    }

    ContextMenu.prototype.dispose = function() {
        this.$el.off('dispose-control', this.proxy(this.dispose))
        this.$el.removeData('oc.contextmenu')

        this.$menu = null
        this.$el = null

        // In some cases options could contain callbacks,
        // so it's better to clean them up too.
        this.options = null

        BaseProto.dispose.call(this)
    }

    // CONTEXTMENU PLUGIN DEFINITION
    // ============================

    var old = $.fn.contextMenu

    $.fn.contextMenu = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result
        this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.contextmenu')
            var options = $.extend({}, ContextMenu.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.contextmenu', (data = new ContextMenu(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
    }

    $.fn.contextMenu.Constructor = ContextMenu

    // CONTEXTMENU NO CONFLICT
    // =================

    $.fn.contextMenu.noConflict = function () {
        $.fn.contextMenu = old
        return this
    }

}(jQuery);
