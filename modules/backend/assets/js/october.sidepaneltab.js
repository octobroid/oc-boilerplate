/*
 * Side Panel Tabs
 */

+function ($) { "use strict";

    var SidePanelTab = function(element, options) {
        this.options = options;
        this.$el = $(element);
        this.init();
    }

    SidePanelTab.prototype.init = function() {
        var self = this;
        this.tabOpenDelay = 200;
        this.tabOpenTimeout = undefined;
        this.panelOpenTimeout = undefined;
        this.$sideNav = $('#layout-sidenav');
        this.$sideNavRes = $('#layout-sidenav-responsive');
        this.$sideNavItems = $('ul li', this.$sideNav);
        this.$sidePanelItems = $('[data-content-id]', this.$el);
        this.sideNavWidth = $('#layout-sidenav').outerWidth();
        this.mainNavHeight = $.oc.backendCalculateTopContainerOffset();
        this.panelVisible = false;
        this.visibleItemId = false;

        this.$sideNavItems.click(function() {
            if ($(this).data('no-side-panel')) {
                return
            }

            if ($(window).width() < self.options.breakpoint) {
                if ($(this).data('menu-item') == self.visibleItemId && self.panelVisible) {
                    self.hideSidePanel()
                    return
                }
                else {
                    self.displaySidePanel()
                }
            }

            self.displayTab(this)

            return false
        })

        $('#layout-body').click(function() {
            if (self.panelVisible) {
                self.hideSidePanel()
                return false
            }
        })

        self.$el.on('close.oc.sidePanel', function() {
            self.hideSidePanel()
        })

        this.updateActiveTab()
    }

    SidePanelTab.prototype.displayTab = function(menuItem) {
        var menuItemId = $(menuItem).data('menu-item')

        this.visibleItemId = menuItemId

        $.oc.sideNav.setActiveItem(menuItemId)

        this.$sidePanelItems.each(function() {
            var  $el = $(this)
            $el.toggleClass('hide', $el.data('content-id') != menuItemId)
        })

        $(window).trigger('resize')
    }

    SidePanelTab.prototype.displaySidePanel = function() {
        $(document.body).addClass('display-side-panel')

        if (this.$sideNavRes.is(':visible')) {
            this.mainNavHeight = $.oc.backendCalculateTopContainerOffset() + this.$sideNavRes.outerHeight();
        }

        if ($('#layout-sidenav').is(':visible')) {
            this.sideNavWidth = $('#layout-sidenav').outerWidth();
        }
        else {
            this.sideNavWidth = 0;
        }

        this.$el.appendTo('#layout-canvas')
        this.panelVisible = true
        this.$el.css({
            left: this.sideNavWidth,
            top: this.mainNavHeight
        })

        this.updatePanelPosition()
        $(window).trigger('resize')
    }

    SidePanelTab.prototype.hideSidePanel = function() {
        $(document.body).removeClass('display-side-panel')
        if (this.$el.next('#layout-body').length == 0) {
            $('#layout-body').before(this.$el)
        }

        this.panelVisible = false

        this.updateActiveTab()
    }

    SidePanelTab.prototype.updatePanelPosition = function() {
        this.$el.height($(document).height() - this.mainNavHeight)

        if (this.panelVisible && $(window).width() > this.options.breakpoint) {
            this.hideSidePanel()
        }
    }

    SidePanelTab.prototype.updateActiveTab = function() {
        if (!this.panelVisible && ($(window).width() < this.options.breakpoint)) {
            $.oc.sideNav.unsetActiveItem()
        }
        else {
            $.oc.sideNav.setActiveItem(this.visibleItemId)
        }
    }

    SidePanelTab.DEFAULTS = {
        breakpoint: 769
    }

    // PLUGIN DEFINITION
    // ============================

    var old = $.fn.sidePanelTab

    $.fn.sidePanelTab = function (option) {
        return this.each(function() {
            var $this = $(this)
            var data = $this.data('oc.sidePanelTab')
            var options = $.extend({}, SidePanelTab.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.sidePanelTab', (data = new SidePanelTab(this, options)))
            if (typeof option == 'string') data[option].call(data)
        })
    }

    $.fn.sidePanelTab.Constructor = SidePanelTab

    // NO CONFLICT
    // =================

    $.fn.sidePanelTab.noConflict = function() {
        $.fn.sidePanelTab = old
        return this
    }

    // DATA-API
    // ============

    $(document).ready(function(){
        $('[data-control=layout-sidepanel]').sidePanelTab()
    })

}(window.jQuery);
