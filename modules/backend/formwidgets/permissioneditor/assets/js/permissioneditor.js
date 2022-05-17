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
        $(document).on('click', '.permissioneditor table [data-field-permission-all]', this.proxy(this.onPermissionAllClick));
        $(document).on('click', '.permissioneditor table [data-field-permission-none]', this.proxy(this.onPermissionNoneClick));
        $(document).on('click', '.permissioneditor table [data-field-permission-toggle]', this.proxy(this.onPermissionToggleClick));
        $(document).on('click', '.permissioneditor table td.permission-name', this.proxy(this.onPermissionNameClick));
        $(document).on('click', '.permissioneditor table tr.mode-checkbox input[type=checkbox]', this.proxy(this.onPermissionCheckboxClick));
        $(document).on('click', '.permissioneditor table tr.mode-radio input[type=radio]', this.proxy(this.onPermissionRadioClick));

        this.toggleSelectionStateForAll();
    }

    // EVENT HANDLERS
    // ============================

    PermissionEditor.prototype.toggleSelectionStateForAll = function(ev) {
        var self = this;
        $('.permissioneditor table tr.section').each(function() {
            self.toggleSelectionState(this);
        });
    }

    PermissionEditor.prototype.toggleSelectionState = function(el) {
        var $header = $(el),
            $checkNone = $('[data-field-permission-none]', $header),
            $checkAll = $('[data-field-permission-all]', $header);

        if (!$checkAll.length || !$checkNone.length) {
            return;
        }

        $checkAll.show();
        $checkNone.hide();

        $header.nextUntil('tr.section').each(function() {
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
            $header = $(ev.target).closest('tr');

        $header.nextUntil('tr.section').each(function() {
            var $row = $(this);
            self.onPermissionNameClick({ target: $row.get(0) }, true);
        });

        this.toggleSelectionState($header);
    }

    PermissionEditor.prototype.onPermissionNoneClick = function(ev) {
        var self = this,
            $header = $(ev.target).closest('tr');

        $header.nextUntil('tr.section').each(function() {
            var $row = $(this);
            self.onPermissionNameClick({ target: $row.get(0) }, false);
        });

        this.toggleSelectionState($header);
    }

    PermissionEditor.prototype.onPermissionToggleClick = function(ev) {
        var self = this,
            $header = $(ev.target).closest('tr'),
            $radios = $header.next().find('input[type=radio]'),
            nextIndex = this.findNextIndexFromRadio($radios);

        $header.nextUntil('tr.section').each(function() {
            var $row = $(this);
            self.onPermissionNameClick({ target: $row.get(0) }, nextIndex);
        });
    }

    PermissionEditor.prototype.findNextIndexFromRadio = function($radios) {
        var nextIndex = 0
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
        var $row = $(ev.target).closest('tr'),
            $checkbox = $row.find('input[type=checkbox]');

        if ($checkbox.length) {
            if (isChecked !== undefined) {
                $checkbox.prop('checked', isChecked);
                this.onPermissionCheckboxClick(ev, isChecked);
            }
            else {
                $checkbox.trigger('click');
            }
        }
        else {
            var $radios = $row.find('input[type=radio]');

            if ($radios.length != 3) {
                return;
            }

            if (isChecked !== undefined) {
                $($radios.get(isChecked)).prop('checked', true);
                this.onPermissionRadioClick(ev, isChecked !== 2);
            }
            else {
                var nextIndex = this.findNextIndexFromRadio($radios);
                $($radios.get(nextIndex)).trigger('click');
            }
        }
    }

    PermissionEditor.prototype.onPermissionCheckboxClick = function(ev, isChecked) {
        var $row = $(ev.target).closest('tr');

        if (isChecked === undefined) {
            isChecked = ev.target.checked;
        }

        $row.toggleClass('disabled', !isChecked);

        this.toggleSelectionState($row.prevAll('tr.section:first'));
    }

    PermissionEditor.prototype.onPermissionRadioClick = function(ev, isChecked) {
        var $row = $(ev.target).closest('tr');

        if (isChecked === undefined) {
            isChecked = ev.target.value != -1;
        }

        $row.toggleClass('disabled', !isChecked);

        this.toggleSelectionState($row.prevAll('tr.section:first'));
    }

    // INITIALIZATION
    // ============================

    $(document).ready(function(){
        new PermissionEditor();
    });

}(window.jQuery);