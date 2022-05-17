/*
 * List Widget
 *
 * Dependences:
 * - Row Link Plugin (system/assets/ui/js/list.rowlink.js)
 */
+function ($) { "use strict";

    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype;

    var ListWidget = function (element, options) {
        this.$el = $(element);
        this.options = options || {};

        this.$head = $('thead', this.$el);
        this.$body = $('tbody', this.$el);
        this.$lastCheckbox = null;
        this.isLastChecked = true;

        $.oc.foundation.controlUtils.markDisposable(element);
        Base.call(this);

        this.init();
    }

    ListWidget.prototype = Object.create(BaseProto);
    ListWidget.prototype.constructor = ListWidget;

    ListWidget.DEFAULTS = {
        checkboxSelector: '.list-checkbox input[type="checkbox"]'
    }

    ListWidget.prototype.init = function() {
        var scrollClassContainer = this.options.scrollClassContainer !== undefined
            ? this.options.scrollClassContainer
            : this.$el.parent();

        this.$el.dragScroll({
            scrollClassContainer: scrollClassContainer,
            scrollSelector: 'thead',
            dragSelector: 'thead'
        });

        this.$el.on('ajaxSetup', this.proxy(this.beforeAjaxRequest));
        this.$body.on('click', '.list-checkbox > .checkbox', this.proxy(this.clickBodyCheckbox));
        this.$body.on('change', this.options.checkboxSelector, this.proxy(this.toggleBodyCheckbox));
        this.$head.on('change', this.options.checkboxSelector, this.proxy(this.toggleHeadCheckbox));

        this.$el.one('dispose-control', this.proxy(this.dispose));

        this.updateUi();
    }

    ListWidget.prototype.dispose = function() {
        this.$el.off('ajaxSetup', this.proxy(this.beforeAjaxRequest));
        this.$body.off('click', '.list-checkbox > .checkbox', this.proxy(this.clickBodyCheckbox));
        this.$body.off('change', this.options.checkboxSelector, this.proxy(this.toggleBodyCheckbox));
        this.$head.off('change', this.options.checkboxSelector, this.proxy(this.toggleHeadCheckbox));

        this.$el.off('dispose-control', this.proxy(this.dispose));
        this.$el.removeData('oc.listwidget');

        this.$el = null;

        // In some cases options could contain callbacks,
        // so it's better to clean them up too.
        this.options = null;

        BaseProto.dispose.call(this);
    }

    ListWidget.prototype.updateUi = function() {
        $(this.options.checkboxSelector, this.$body).each(function(){
            var $el = $(this);
            if ($el.is(':checked')) {
                $el.closest('tr').addClass('active');
            }
        });

        this.checkIndeterminate();
    }

    ListWidget.prototype.checkIndeterminate = function() {
        var $all = $(this.options.checkboxSelector, this.$body),
            $headCb = $(this.options.checkboxSelector, this.$head),
            checkedCount = $all.filter(':checked').length;

        if (checkedCount && $all.length !== checkedCount) {
            $headCb
                .addClass('is-indeterminate')
                .prop('indeterminate', true);
        }
        else {
            $headCb
                .removeClass('is-indeterminate')
                .prop('indeterminate', false);
        }

        $headCb.prop('checked', !!checkedCount);
    }

    ListWidget.prototype.toggleHeadCheckbox = function(ev) {
        var $el = $(ev.target),
            checked = $el.is(':checked');

        $(this.options.checkboxSelector, this.$body)
            .prop('checked', checked)
            .trigger('change');

        if (checked) {
            $('tr', this.$body).addClass('active');
        }
        else {
            $('tr', this.$body).removeClass('active');
        }
    }

    ListWidget.prototype.toggleBodyCheckbox = function(ev) {
        var $el = $(ev.target),
            checked = $el.is(':checked');

        if (checked) {
            $el.closest('tr').addClass('active');
        }
        else {
            $(this.options.checkboxSelector, this.$head).prop('checked', false);
            $el.closest('tr').removeClass('active');
        }

        this.$lastCheckbox = $el;
        this.isLastChecked = checked;

        this.checkIndeterminate();
    }

    ListWidget.prototype.clickBodyCheckbox = function(ev) {
        var $el = $(ev.target);

        if (this.$lastCheckbox && this.$lastCheckbox.length && ev.shiftKey) {
            this.selectCheckboxRange($el, this.$lastCheckbox);
        }
    }

    ListWidget.prototype.selectCheckboxesIn = function(rows, isChecked) {
        var self = this;
        $.each(rows, function() {
            $(self.options.checkboxSelector, this)
                .prop('checked', isChecked)
                .trigger('change');
        });
    }

    ListWidget.prototype.selectCheckboxRange = function($el, $prevEl) {
        var $tr = $el.closest('tr'),
            $prevTr = $prevEl.closest('tr'),
            toSelect = [];

        var $nextRow = $tr;
        while ($nextRow.length) {
            if ($nextRow.get(0) === $prevTr.get(0)) {
                this.selectCheckboxesIn(toSelect, this.isLastChecked);
                return;
            }

            toSelect.push($nextRow);
            $nextRow = $nextRow.next();
        }

        toSelect = [];
        var $prevRow = $tr;
        while ($prevRow.length) {
            if ($prevRow.get(0) === $prevTr.get(0)) {
                this.selectCheckboxesIn(toSelect, this.isLastChecked);
                return;
            }

            toSelect.push($prevRow);
            $prevRow = $prevRow.prev();
        }
    }

    ListWidget.prototype.getAllChecked = function() {
        return this.getChecked().concat(this.getCheckedFromLocker());
    }

    ListWidget.prototype.getChecked = function() {
        return $(this.options.checkboxSelector, this.$body)
            .map(function(){
                var $el = $(this)
                if ($el.is(':checked')) {
                    return $el.val();
                }
            })
            .get();
    }

    ListWidget.prototype.getUnchecked = function() {
        return $(this.options.checkboxSelector, this.$body)
            .map(function(){
                var $el = $(this)
                if (!$el.is(':checked')) {
                    return $el.val();
                }
            })
            .get();
    }

    ListWidget.prototype.getCheckedFromLocker = function() {
        try {
            var locker = JSON.parse($('[data-list-datalocker-checked]', this.$el).val());

            $.each(this.getUnchecked(), function(k, value) {
                var index = locker.indexOf(value);
                if (index > -1) {
                    locker.splice(index, 1);
                }
            });

            return locker;
        }
        catch(err) {
            return [];
        }
    }

    ListWidget.prototype.toggleChecked = function(el) {
        var $checkbox = $(this.options.checkboxSelector, $(el).closest('tr'));

        $checkbox
            .prop('checked', !$checkbox.is(':checked'))
            .trigger('change');
    }

    ListWidget.prototype.beforeAjaxRequest = function(ev, context) {
        context.options.data.allChecked = this.getAllChecked();
    }

    // LIST WIDGET PLUGIN DEFINITION
    // ============================

    var old = $.fn.listWidget

    $.fn.listWidget = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result

        this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.listwidget')
            var options = $.extend({}, ListWidget.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.listwidget', (data = new ListWidget(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        });

        return result ? result : this;
      }

    $.fn.listWidget.Constructor = ListWidget

    // LIST WIDGET NO CONFLICT
    // =================

    $.fn.listWidget.noConflict = function () {
        $.fn.listWidget = old;
        return this;
    }

    // LIST WIDGET HELPERS
    // =================

    if ($.oc === undefined) {
        $.oc = {};
    }

    $.oc.listToggleChecked = function(el) {
        $(el)
            .closest('[data-control="listwidget"]')
            .listWidget('toggleChecked', el);
    }

    $.oc.listGetChecked = function(el) {
        return $(el)
            .closest('[data-control="listwidget"]')
            .listWidget('getChecked');
    }

    // LIST WIDGET DATA-API
    // ==============

    $(document).render(function(){
        $('[data-control="listwidget"]').listWidget();
    });

    // LIST HELPER DATA-API
    // ==============

    $.fn.listCheckedTriggerOn = function() {
        this.each(function() {
            var $buttonEl = $(this),
                listId = $buttonEl.closest('[data-list-linkage]').data('list-linkage');

            // No list or already bound
            if (!listId || $buttonEl.data('oc.listCheckedTriggerOn')) {
                $buttonEl.trigger('oc.triggerOn.update');
                return;
            }

            $buttonEl.triggerOn({
                triggerAction: 'enable',
                triggerCondition: 'checked',
                trigger: '#' + listId + ' > .control-list:first .list-checkbox input[type=checkbox]'
            });

            $buttonEl.data('oc.listCheckedTriggerOn', true);
        });

        return this;
    }

    $.fn.listCheckedRequest = function() {
        this.each(function() {
            var $buttonEl = $(this),
                listId = $buttonEl.closest('[data-list-linkage]').data('list-linkage');

            // No list or already bound
            if (!listId || $buttonEl.data('oc.listCheckedRequest')) {
                return;
            }

            $buttonEl.on('ajaxSetup', function (ev, context) {
                context.options.data.checked = $.oc.listGetChecked('#' + listId + ' > .control-list:first');
            });

            $buttonEl.data('oc.listCheckedRequest', true);
        });

        return this;
    }

    $(document).render(function(){
        $('[data-list-checked-trigger]').listCheckedTriggerOn();
        $('[data-list-checked-request]').listCheckedRequest();
    });

}(window.jQuery);
