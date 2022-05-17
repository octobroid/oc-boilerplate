/*
 * Field Repeater - Builder Mode
 *
 * Logic for the builder visual mode
 *
 */
+function ($) {
    "use strict";

    var RepeaterBuilder = $.fn.fieldRepeater.Constructor;

    // OVERLOADED MODULE
    // =================

    var overloadedInit = RepeaterBuilder.prototype.init;

    RepeaterBuilder.prototype.init = function () {
        if (this.options.displayMode === 'builder') {
            this.initBuilderMode();
            overloadedInit.apply(this);
        }
        else {
            overloadedInit.apply(this);
        }
    }

    // NEW MODULE
    // =================

    RepeaterBuilder.prototype.initBuilderMode = function() {
        // Overrides
        this.selectorToolbar = '> .field-repeater-builder > .field-repeater-toolbar:first';
        this.selectorHeader = '> .field-repeater-builder > .field-repeater-groups > .field-repeater-group > .repeater-header';
        this.selectorSortable = '> .field-repeater-builder > .field-repeater-groups';
        this.selectorChecked = '> .field-repeater-builder > .field-repeater-groups > .field-repeater-group > .repeater-header input[type=checkbox]:checked';
        this.eventSortableOnEnd = this.builderSortableOnEnd;
        this.eventOnChange = null;
        this.eventOnAddItem = this.builderOnAddItem;
        this.eventOnRemoveItem = this.builderOnRemoveItem;
        this.eventMenuFilter = this.builderMenuFilter;

        // Locals
        this.$sidebar = $('> .field-repeater-builder > .field-repeater-groups:first', this.$el);
        this.$sidebar.on('click', '> li', this.proxy(this.clickBuilderItem));

        // Core logic
        $(document).on('render', this.proxy(this.builderOnRender));
        this.transferBuilderItemHeaders();

        this.selectBuilderItem();
    }

    RepeaterBuilder.prototype.disposeBuilderMode = function() {
        this.$sidebar = null;

        $(document).off('render', this.proxy(this.builderOnRender));
    }

    RepeaterBuilder.prototype.builderMenuFilter = function($item, $list) {
        // Hide/show remove button and divider
        $('[data-repeater-remove]', $list).closest('li').toggleClass('disabled', !this.canRemove);

        // Hide/show up/down
        $('[data-repeater-move-up]', $list).closest('li').toggle(!!$item.prev().length);
        $('[data-repeater-move-down]', $list).closest('li').toggle(!!$item.next().length);

        // Hide/show expand/collapse
        $('[data-repeater-expand]', $list).closest('li').hide();
        $('[data-repeater-collapse]', $list).closest('li').hide();
    }

    RepeaterBuilder.prototype.builderSortableOnEnd = function() {
        var self = this;

        $('> li', this.$sidebar).each(function() {
            var itemIndex = $(this).data('repeater-index');
            self.$itemContainer.append(self.findItemFromIndex(itemIndex));
        });
    }

    RepeaterBuilder.prototype.builderOnRender = function() {
        this.transferBuilderItemHeaders();
    }

    RepeaterBuilder.prototype.builderOnAddItem = function() {
        var templateHtml = $('> [data-group-loading-template]', this.$el).html(),
            $loadingItem = $(templateHtml);

        this.$sidebar.append($loadingItem);
    }

    RepeaterBuilder.prototype.builderOnRemoveItem = function($item) {
        var itemIndex = $item.data('repeater-index'),
            $containerItem = this.findItemFromIndex(itemIndex);

        this.diposeItem($containerItem);
        $containerItem.remove();
    }

    RepeaterBuilder.prototype.clickBuilderItem = function(ev) {
        var $item = $(ev.target).closest('.field-repeater-group'),
            inControlArea = $(ev.target).closest('.group-controls').length;

        if (inControlArea) {
            return;
        }

        this.selectBuilderItem($item.data('repeater-index'));
    }

    RepeaterBuilder.prototype.selectBuilderItem = function(itemIndex) {
        if (itemIndex === undefined) {
            itemIndex = $('> li:first', this.$sidebar).data('repeater-index');
        }

        $('> li.is-selected', this.$sidebar).removeClass('is-selected');
        $('> li[data-repeater-index='+itemIndex+']', this.$sidebar).addClass('is-selected');

        $('> li.is-selected', this.$itemContainer).removeClass('is-selected');
        $('> li[data-repeater-index='+itemIndex+']', this.$itemContainer).addClass('is-selected');

        this.setCollapsedTitles();
    }

    RepeaterBuilder.prototype.setCollapsedTitles = function() {
        var self = this;

        $('> .field-repeater-item', this.$itemContainer).each(function() {
            var $item = $(this),
                itemIndex = $item.data('repeater-index'),
                $groupItem = $('> li[data-repeater-index='+itemIndex+']', self.$sidebar);

            $('[data-group-title]:first', $groupItem).html(self.getCollapseTitle($item));
        });
    }

    RepeaterBuilder.prototype.transferBuilderItemHeaders = function() {
        var self = this,
            templateHtml = $('> [data-group-template]', this.$el).html();

        $('> .field-repeater-item > .repeater-header', this.$itemContainer).each(function() {
            var $groupItem = $(templateHtml),
                $item = $(this).closest('li');

            self.$sidebar.append($groupItem);
            $('[data-group-controls]:first', $groupItem).replaceWith($(this).addClass('group-controls'));
            $('[data-group-image]:first > i', $groupItem).addClass($item.data('item-icon'));
            $('[data-group-title]:first', $groupItem).html($item.data('item-title'));
            $('[data-group-description]:first', $groupItem).html($item.data('item-description'));

            $groupItem.attr('data-repeater-index', $item.data('repeater-index'));
            $groupItem.attr('data-repeater-group', $item.data('repeater-group'));

            // Remove last loader if there is one
            $('li.is-placeholder:first', self.$sidebar).remove();

            // Select this item
            self.selectBuilderItem($item.data('repeater-index'));
        });
    }

}(window.jQuery);
