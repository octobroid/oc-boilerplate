/*
 * Checkbox cell processor for the table control.
 */
+function ($) { "use strict";

    // NAMESPACE CHECK
    // ============================

    if ($.oc.table === undefined)
        throw new Error("The $.oc.table namespace is not defined. Make sure that the table.js script is loaded.");

    if ($.oc.table.processor === undefined)
        throw new Error("The $.oc.table.processor namespace is not defined. Make sure that the table.processor.base.js script is loaded.");

    // CLASS DEFINITION
    // ============================

    var Base = $.oc.table.processor.base,
        BaseProto = Base.prototype

    var CheckboxProcessor = function(tableObj, columnName, columnConfiguration) {
        //
        // Parent constructor
        //

        Base.call(this, tableObj, columnName, columnConfiguration)
    }

    CheckboxProcessor.prototype = Object.create(BaseProto)
    CheckboxProcessor.prototype.constructor = CheckboxProcessor

    CheckboxProcessor.prototype.dispose = function() {
        BaseProto.dispose.call(this)
    }

    /*
     * Determines if the processor's cell is focusable.
     */
    CheckboxProcessor.prototype.isCellFocusable = function() {
        return false
    }

    /*
     * Renders the cell in the normal (no edit) mode
     */
    CheckboxProcessor.prototype.renderCell = function(value, cellContentContainer) {
        var checkbox = document.createElement('input')
        checkbox.setAttribute('data-checkbox-element', 'true');
        checkbox.setAttribute('tabindex', '0');
        checkbox.setAttribute('type', 'checkbox');
        checkbox.setAttribute('class', 'form-check-input');

        if (value && value != 0 && value != "false") {
            checkbox.checked = true;
        }

        cellContentContainer.appendChild(checkbox)
    }

    /*
     * This method is called when the cell managed by the processor
     * is focused (clicked or navigated with the keyboard).
     */
    CheckboxProcessor.prototype.onFocus = function(cellElement, isClick) {
        cellElement.querySelector('input[data-checkbox-element]').focus();
    }

    /*
     * Event handler for the click event. The table class calls this method
     * for all processors.
     */
    CheckboxProcessor.prototype.onClick = function(ev) {
        var chkElement = ev.target;

        if (chkElement.getAttribute('data-checkbox-element')) {
            // The method is called for all processors, but we should
            // update only the checkbox in the clicked column.
            var container = this.getCheckboxContainerNode(chkElement);
            if (container.getAttribute('data-column') !== this.columnName) {
                return;
            }

            this.changeState(chkElement);
            $(chkElement).trigger('change');
        }
    }

    CheckboxProcessor.prototype.changeState = function(chkElement) {
        var cell = this.getCheckboxContainerNode(chkElement);

        this.tableObj.setCellValue(cell, chkElement.checked ? 1 : 0);
    }

    CheckboxProcessor.prototype.getCheckboxContainerNode = function(checkbox) {
        return checkbox.parentNode.parentNode;
    }

    /*
     * This method is called when a cell value in the row changes.
     */
    CheckboxProcessor.prototype.onRowValueChanged = function(columnName, cellElement) {
        if (columnName !== this.columnName) {
            return;
        }

        var checkbox = cellElement.querySelector('input[data-checkbox-element]'),
            value = this.tableObj.getCellValue(cellElement)

        if (value && value != 0 && value != "false") {
            checkbox.checked = true;
        }
        else {
            checkbox.checked = false;
        }
    }

    $.oc.table.processor.checkbox = CheckboxProcessor;
}(window.jQuery);
