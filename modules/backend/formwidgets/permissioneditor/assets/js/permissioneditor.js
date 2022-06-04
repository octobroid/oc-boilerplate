+function ($) { "use strict";

    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype

    var PermissionEditor = function() {
        Base.call(this);

        this.init();
    }

    PermissionEditor.prototype = Object.create(BaseProto);
    PermissionEditor.prototype.constructor = PermissionEditor;

    PermissionEditor.prototype.init = function() {
        $(document).on('click', '.permissioneditor [data-field-permission-all]', this.proxy(this.onPermissionAllClick));
        $(document).on('click', '.permissioneditor [data-field-permission-none]', this.proxy(this.onPermissionNoneClick));
        $(document).on('click', '.permissioneditor [data-field-permission-toggle]', this.proxy(this.onPermissionToggleClick));
        $(document).on('click', '.permissioneditor li.permission-item label.item-name', this.proxy(this.onPermissionNameClick));
        $(document).on('click', '.permissioneditor li.mode-checkbox input[type=checkbox]', this.proxy(this.renderViewState));
        $(document).on('click', '.permissioneditor li.mode-radio input[type=radio]', this.proxy(this.renderViewState));

        this.renderViewState();
    }

    // EVENT HANDLERS
    // ============================

    PermissionEditor.prototype.renderViewState = function() {
        this.evalDisabledStateForAll();
        this.evalNestingOptions();
        this.evalSelectionStateForAll();
    }

    PermissionEditor.prototype.evalDisabledStateForAll = function() {
        var self = this;
        $('.permissioneditor li').each(function() {
            self.evalDisabledState($(this));
        });
    }

    PermissionEditor.prototype.evalDisabledState = function($item) {
        var isDisabled;

        if ($item.hasClass('mode-checkbox')) {
            isDisabled = !$('> .item-content > .item-value > input[type=checkbox]', $item).is(':checked');
        }
        else {
            isDisabled = $('> .item-content > .item-value > input[type=radio][value=-1]', $item).is(':checked');
        }

        $item.toggleClass('disabled', isDisabled);
    }

    PermissionEditor.prototype.evalNestingOptions = function($items) {
        if (!$items) {
            $items = $('.permissioneditor > ul > li');
        }

        var self = this;
        $items.each(function() {
            var $item = $(this);

            if (!$item.hasClass('disabled')) {
                $('> ul > li > .item-content > .item-value > input', $item)
                    .prop('readonly', false);
            }
            else if ($item.hasClass('mode-checkbox')) {
                $('> ul > li > .item-content > .item-value > input', $item)
                    .prop('readonly', true)
                    .prop('checked', false)
                    .closest('li').addClass('disabled');
            }
            else if ($item.hasClass('mode-radio')) {
                $('> ul > li > .item-content > .item-value > input', $item)
                    .prop('readonly', true)
                    .prop('checked', false);

                $('> ul > li > .item-content > .item-value > input[value=-1]', $item)
                    .prop('checked', true)
                    .closest('li').addClass('disabled');
            }

            self.evalNestingOptions($('> ul > li', $item));
        });
    }

    PermissionEditor.prototype.evalSelectionStateForAll = function(ev) {
        var self = this;
        $('.permissioneditor li.permission-section').each(function() {
            self.evalSelectionState(this);
        });
    }

    PermissionEditor.prototype.evalSelectionState = function(el) {
        var $header = $(el),
            $checkNone = $('[data-field-permission-none]', $header),
            $checkAll = $('[data-field-permission-all]', $header);

        if (!$checkAll.length || !$checkNone.length) {
            return;
        }

        $checkAll.show();
        $checkNone.hide();

        $header.nextUntil('li.permission-section').each(function() {
            var $row = $(this);
            if (!$row.hasClass('disabled')) {
                $checkNone.show();
                $checkAll.hide();
                return false;
            }
        });
    }

    PermissionEditor.prototype.onPermissionAllClick = function(ev) {
        var self = this,
            $header = $(ev.target).closest('li');

        $header.nextUntil('li.permission-section').each(function() {
            var $row = $(this);
            self.onPermissionNameClick({ target: $row.get(0) }, true);

            // Children
            $('li', this).each(function() {
                self.onPermissionNameClick({ target: this }, true);
            });
        });
    }

    PermissionEditor.prototype.onPermissionNoneClick = function(ev) {
        var self = this,
            $header = $(ev.target).closest('li');

        $header.nextUntil('li.permission-section').each(function() {
            var $row = $(this);
            self.onPermissionNameClick({ target: $row.get(0) }, false);

            // Children
            $('li', this).each(function() {
                self.onPermissionNameClick({ target: this }, false);
            });
        });
    }

    PermissionEditor.prototype.onPermissionToggleClick = function(ev) {
        var self = this,
            $header = $(ev.target).closest('li'),
            $radios = $header.next().find('> .item-content > .item-value > input[type=radio]'),
            nextIndex = this.findNextIndexFromRadio($radios);

        $header.nextUntil('li.permission-section').each(function() {
            var $row = $(this);
            self.onPermissionNameClick({ target: $row.get(0) }, nextIndex);

            // Children
            $('li', this).each(function() {
                self.onPermissionNameClick({ target: this }, nextIndex);
            });
        });
    }

    PermissionEditor.prototype.findNextIndexFromRadio = function($radios) {
        var nextIndex = 0;

        for (var i=2; i>=0; i--) {
            if ($radios.get(i).checked) {
                nextIndex = i + 1;
                break;
            }
        }

        if (nextIndex > 2) {
            nextIndex = 0;
        }

        return nextIndex;
    }

    PermissionEditor.prototype.onPermissionNameClick = function(ev, isChecked) {
        var $row = $(ev.target).closest('li'),
            $checkbox = $row.find('> .item-content > .item-value > input[type=checkbox]');

        if ($checkbox.length) {
            if (isChecked !== undefined) {
                $checkbox.prop('checked', isChecked);
                this.renderViewState();
            }
            else {
                $checkbox.trigger('click');
            }
        }
        else {
            var $radios = $row.find('> .item-content > .item-value > input[type=radio]');
            if ($radios.length != 3) {
                return;
            }

            if (isChecked !== undefined) {
                $($radios.get(isChecked)).prop('checked', true);
                this.renderViewState();
            }
            else {
                var nextIndex = this.findNextIndexFromRadio($radios);
                $($radios.get(nextIndex)).trigger('click');
            }
        }
    }

    // INITIALIZATION
    // ============================

    $(document).ready(function(){
        new PermissionEditor();
    });

}(window.jQuery);
