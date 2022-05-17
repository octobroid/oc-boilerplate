/*
 * Field Repeater - Accordion Mode
 *
 * Logic for the accordion visual mode
 *
 */
+function ($) {
    "use strict";

    var RepeaterAccordion = $.fn.fieldRepeater.Constructor;

    // OVERLOADED MODULE
    // =================

    var overloadedInit = RepeaterAccordion.prototype.init;

    RepeaterAccordion.prototype.init = function () {
        if (this.options.displayMode === 'accordion') {
            this.initAccordionMode();
            overloadedInit.apply(this);
            this.applyExpandedItems();
        }
        else {
            overloadedInit.apply(this);
        }
    }

    // NEW MODULE
    // =================

    RepeaterAccordion.prototype.initAccordionMode = function() {
        // Overrides
        this.selectorToolbar = '> .field-repeater-toolbar:first';
        this.selectorHeader = '> .field-repeater-items > .field-repeater-item > .repeater-header';
        this.selectorSortable = '> .field-repeater-items';
        this.selectorChecked = '> .field-repeater-items > .field-repeater-item > .repeater-header input[type=checkbox]:checked';
        this.eventSortableOnEnd = null;
        this.eventOnChange = null;
        this.eventOnAddItem = this.accordionOnAddItem;
        this.eventOnRemoveItem = null;
        this.eventMenuFilter = this.accordionMenuFilter;

        // Items
        var headSelect = this.selectorHeader;
        this.$el.on('click', headSelect, this.proxy(this.clickItemHeader));
        this.$el.on('click', headSelect + ' [data-repeater-expand]', this.proxy(this.toggleCollapse));
        this.$el.on('click', headSelect + ' [data-repeater-collapse]', this.proxy(this.toggleCollapse));
    }

    RepeaterAccordion.prototype.disposeAccordionMode = function() {
        // Items
        var headSelect = this.selectorHeader;
        this.$el.off('click', headSelect, this.proxy(this.clickItemHeader));
        this.$el.off('click', headSelect + ' [data-repeater-expand]', this.proxy(this.toggleCollapse));
        this.$el.off('click', headSelect + ' [data-repeater-collapse]', this.proxy(this.toggleCollapse));
    }

    RepeaterAccordion.prototype.accordionOnAddItem = function() {
        if (!this.options.itemsExpanded) {
            this.collapseAll();
        }
    }

    RepeaterAccordion.prototype.accordionMenuFilter = function($item, $list) {
        // Hide/show remove button and divider
        $('[data-repeater-remove]', $list).closest('li').toggleClass('disabled', !this.canRemove);

        // Hide/show up/down
        $('[data-repeater-move-up]', $list).closest('li').toggle(!!$item.prev().length);
        $('[data-repeater-move-down]', $list).closest('li').toggle(!!$item.next().length);

        // Hide/show expand/collapse
        $('[data-repeater-expand]', $list).closest('li').toggle($item.hasClass('collapsed'));
        $('[data-repeater-collapse]', $list).closest('li').toggle(!$item.hasClass('collapsed'));
    }

    RepeaterAccordion.prototype.clickItemHeader = function(ev) {
        var $target = $(ev.target);
        if (
            !$target.hasClass('repeater-header') &&
            !$target.hasClass('repeater-item-title') &&
            !$target.hasClass('repeater-item-checkbox')
        ) {
            return;
        }

        var $item = $target.closest('.field-repeater-item'),
            isCollapsed = $item.hasClass('collapsed');

        if (!this.options.itemsExpanded) {
            this.collapseAll();
        }

        isCollapsed ? this.expand($item) : this.collapse($item);
    }

    RepeaterAccordion.prototype.applyExpandedItems = function() {
        if (this.options.itemsExpanded) {
            return;
        }

        var items = $(this.$el).children('.field-repeater-items').children('.field-repeater-item'),
            self = this;

        $.each(items, function(key, item) {
            self.collapse($(item));
        });
    }

    RepeaterAccordion.prototype.toggleCollapse = function(ev) {
        var self = this,
            $item = $(ev.target).closest('.field-repeater-item'),
            isCollapsed = $item.hasClass('collapsed');

        ev.preventDefault();

        var $items = this.getCheckedItemsOrItem($item);
        $.each($items, function(k, item) {
            isCollapsed ? self.expand($(item)) : self.collapse($(item));
        });
    }

    RepeaterAccordion.prototype.collapseAll = function() {
        var self = this,
            $items = $('> .field-repeater-item', this.$itemContainer);

        $.each($items, function(key, item){
            self.collapse($(item));
        });
    }

    RepeaterAccordion.prototype.expandAll = function() {
        var self = this,
            $items = $('> .field-repeater-item', this.$itemContainer);

        $.each($items, function(key, item){
            self.expand($(item));
        });
    }

    RepeaterAccordion.prototype.collapse = function($item) {
        $item.addClass('collapsed');

        $('> .repeater-header > .repeater-item-title', $item).text(this.getCollapseTitle($item));
    }

    RepeaterAccordion.prototype.expand = function($item) {
        $item.removeClass('collapsed');
    }

}(window.jQuery);
