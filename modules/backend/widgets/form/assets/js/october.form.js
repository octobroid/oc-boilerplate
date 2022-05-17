/*
 * Form Widget
 *
 * Dependences:
 * - Nil
 */
+function ($) { "use strict";
    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype;

    var FormWidget = function (element, options) {
        this.$el = $(element);
        this.options = options || {};
        this.fieldElementCache = null;

        /*
         * Throttle dependency updating
         */
        this.dependantUpdateInterval = 300;
        this.dependantUpdateTimers = {};

        $.oc.foundation.controlUtils.markDisposable(element);
        Base.call(this);
        this.init();
    }

    FormWidget.prototype = Object.create(BaseProto)
    FormWidget.prototype.constructor = FormWidget

    FormWidget.prototype.init = function() {
        this.$form = this.$el.closest('form');

        $('[data-change-handler]', this.$el).on('change.oc.formwidget', this.proxy(this.onRefreshChangeField));
        $('.nav-tabs', this.$el).on('shown.bs.tab shownLinkable.oc.tab', 'li.tab-lazy > a', this.proxy(this.showLazyTab));
        $('.field-checkboxlist', this.$el).on('oc.triggerOn.afterUpdate', this.proxy(this.toggleCheckboxlist));
        this.$el.on('oc.triggerOn.afterUpdate', this.proxy(this.toggleEmptyTabs));
        this.$el.one('dispose-control', this.proxy(this.dispose));

        this.bindDependents();
        this.bindCheckboxlist();
        this.bindCollapsibleSections();
        this.toggleEmptyTabs();
    }

    FormWidget.prototype.dispose = function() {
        $('[data-change-handler]', this.$el).off('change.oc.formwidget', this.proxy(this.onRefreshChangeField));
        $('.nav-tabs', this.$el).off('shown.bs.tab shownLinkable.oc.tab', 'li.tab-lazy > a', this.proxy(this.showLazyTab));
        $('.field-checkboxlist', this.$el).off('oc.triggerOn.afterUpdate', this.proxy(this.toggleCheckboxlist));
        this.$el.off('oc.triggerOn.afterUpdate', this.proxy(this.toggleEmptyTabs));
        this.$el.off('click', '[data-field-checkboxlist-all]');
        this.$el.off('click', '[data-field-checkboxlist-none]');
        $('.section-field[data-field-collapsible]', this.$form).off('click');

        this.$el.off('dispose-control', this.proxy(this.dispose));
        this.$el.removeData('oc.formwidget');

        this.$el = null;
        this.$form = null;
        this.options = null;
        this.fieldElementCache = null;

        BaseProto.dispose.call(this);
    }

    /*
     * Logic for checkboxlist
     */
    FormWidget.prototype.toggleCheckboxlist = function() {
        var $field = $('.field-checkboxlist', this.$el),
            isDisabled = $field.hasClass('control-disabled');

        $('input[type=checkbox]', $field).prop('disabled', isDisabled);
    }

    FormWidget.prototype.bindCheckboxlist = function() {
        var checkAllBoxes = function($field, flag) {
            $('input[type=checkbox]', $field)
                .prop('checked', flag)
                .first()
                .trigger('change');
        }

        this.$el.on('click', '[data-field-checkboxlist-all]', function() {
            checkAllBoxes($(this).closest('.field-checkboxlist'), true);
        });

        this.$el.on('click', '[data-field-checkboxlist-none]', function() {
            checkAllBoxes($(this).closest('.field-checkboxlist'), false);
        });

        this.toggleCheckboxlist();
    }

    /*
     * Get all fields elements that belong to this form, nested form
     * fields are removed from this collection.
     */
    FormWidget.prototype.getFieldElements = function() {
        if (this.fieldElementCache !== null) {
            return this.fieldElementCache;
        }

        var form = this.$el,
            nestedFields = form.find('[data-control="formwidget"] [data-field-name]');

        return this.fieldElementCache = form.find('[data-field-name]').not(nestedFields);
    }

    /*
     * Bind dependant fields
     */
    FormWidget.prototype.bindDependents = function() {
        if (!$('[data-field-depends]', this.$el).length) {
            return;
        }

        var self = this,
            fieldMap = {},
            fieldElements = this.getFieldElements();

        /*
         * Map master and slave fields
         */
        fieldElements.filter('[data-field-depends]').each(function() {
            var name = $(this).data('field-name'),
                depends = $(this).data('field-depends');

            $.each(depends, function(index, depend){
                if (!fieldMap[depend]) {
                    fieldMap[depend] = { fields: [] };
                }

                fieldMap[depend].fields.push(name);
            })
        })

        /*
         * When a master is updated, refresh its slaves
         */
        $.each(fieldMap, function(fieldName, toRefresh){
            fieldElements.filter('[data-field-name="'+fieldName+'"]')
                .on('change.oc.formwidget', $.proxy(self.onRefreshDependents, self, fieldName, toRefresh));

            // Set up disposal
            self.$el.one('dispose-control', function() {
                fieldElements.filter('[data-field-name="'+fieldName+'"]')
                    .off('change.oc.formwidget');
            });
        })
    }

    /*
     * Refresh a dependancy field
     * Uses a throttle to prevent duplicate calls and click spamming
     */
    FormWidget.prototype.onRefreshDependents = function(fieldName, toRefresh) {
        var self = this,
            formEl = this.$form,
            fieldElements = this.getFieldElements();

        if (this.dependantUpdateTimers[fieldName] !== undefined) {
            window.clearTimeout(this.dependantUpdateTimers[fieldName]);
        }

        this.dependantUpdateTimers[fieldName] = window.setTimeout(function() {
            var refreshData = $.extend({},
                toRefresh,
                paramToObj('data-refresh-data', self.options.refreshData)
            );

            formEl.request(self.options.refreshHandler, {
                data: refreshData
            }).done(function() {
                self.toggleEmptyTabs();

                $.each(toRefresh.fields, function(key, field) {
                    $('[data-field-name="' + field + '"]').trigger('change');
                });
            });
        }, this.dependantUpdateInterval);

        $.each(toRefresh.fields, function(index, field) {
            fieldElements.filter('[data-field-name="'+field+'"]:visible')
                .addClass('loading-indicator-container size-form-field')
                .loadIndicator();
        });
    }

    /*
     * Calls an AJAX handler when the field updates.
     */
    FormWidget.prototype.onRefreshChangeField = function(ev) {
        var $group = $(ev.target).closest('[data-change-handler]'),
            handler = $group.data('change-handler'),
            self = this;

        $group.request(handler).done(function() {
            self.toggleEmptyTabs();
        });
    }

    /*
     * Hides tabs that have no content, it is possible this can be
     * called multiple times in a single cycle due to input.trigger.
     */
    FormWidget.prototype.toggleEmptyTabs = function() {
        var self = this,
            form = this.$el;

        if (this.toggleEmptyTabsTimer !== undefined) {
            window.clearTimeout(this.toggleEmptyTabsTimer);
        }

        this.toggleEmptyTabsTimer = window.setTimeout(function() {
            var tabControl = $('[data-control=tab]', self.$el),
                tabContainer = $('.nav-tabs', tabControl);

            if (!tabControl.length || !$.contains(form.get(0), tabControl.get(0))) {
                return;
            }

            /*
             * Check each tab pane for form field groups
             */
            $('.tab-pane:not(.is-lazy):not(.nohide)', tabControl).each(function() {
                var hasControls = $('.form-group:not(:empty):not(.hide)', $(this)).length;

                $('[data-target="#' + $(this).attr('id') + '"]', tabControl)
                    .closest('li')
                    .toggle(!!hasControls);
            });

            /*
             * If a hidden tab was selected, select the first visible tab
             */
            if (!$('> li.active:visible', tabContainer).length) {
                $('> li:visible:first', tabContainer)
                    .find('> a:first')
                    .tab('show');
            }

        }, 1);
    }

    /*
     * Render tab form fields once a lazy tab is selected.
     */
    FormWidget.prototype.showLazyTab = function(ev) {
        var $el = $(ev.target),
            handlerName = $el.data('tab-lazy-handler');

        $el.request(handlerName, {
            data: {
                target: $el.data('target'),
                name: $el.data('tab-name'),
                section: $el.data('tab-section')
            },
            success: function(data) {
                this.success(data);
                $el.parent().removeClass('tab-lazy');

                // Trigger all input presets to populate new fields.
                setTimeout(function() {
                    $('[data-input-preset]').each(function() {
                        var preset = $(this).data('oc.inputPreset')
                        if (preset && preset.$src) {
                            preset.$src.trigger('input')
                        }
                    })
                }, 0);
            }
        });
    }

    /*
     * Makes sections collapsible by targeting every field after
     * up until the next section
     */
    FormWidget.prototype.bindCollapsibleSections = function() {
        $('.section-field[data-field-collapsible]', this.$form)
            .addClass('collapsed')
            .find('.field-section:first')
                .addClass('is-collapsible')
                .end()
            .on('click', function() {
                $(this)
                    .toggleClass('collapsed')
                    .nextUntil('.section-field').toggle();
            })
            .nextUntil('.section-field').hide();
    }

    FormWidget.DEFAULTS = {
        refreshHandler: null,
        refreshData: {}
    }

    // FORM WIDGET PLUGIN DEFINITION
    // ============================

    var old = $.fn.formWidget

    $.fn.formWidget = function (option) {
        var args = arguments,
            result;

        this.each(function () {
            var $this   = $(this);
            var data    = $this.data('oc.formwidget');
            var options = $.extend({}, FormWidget.DEFAULTS, $this.data(), typeof option == 'object' && option);
            if (!data) $this.data('oc.formwidget', (data = new FormWidget(this, options)));
            if (typeof option == 'string') result = data[option].call($this);
            if (typeof result != 'undefined') return false;
        })

        return result ? result : this;
      }

    $.fn.formWidget.Constructor = FormWidget;

    // FORM WIDGET NO CONFLICT
    // =================

    $.fn.formWidget.noConflict = function () {
        $.fn.formWidget = old;
        return this;
    }

    // FORM WIDGET DATA-API
    // ==============

    function paramToObj(name, value) {
        if (value === undefined) value = '';
        if (typeof value == 'object') return value;

        try {
            return ocJSON("{" + value + "}");
        }
        catch (e) {
            throw new Error('Error parsing the '+name+' attribute value. '+e);
        }
    }

    $(document).render(function() {
        $('[data-control="formwidget"]').formWidget();
    });

}(window.jQuery);
