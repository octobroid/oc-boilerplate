/*
 * Implements a new tooltips solution.
 *
 * The solution doesn't use Bootstrap tooltips and doesn't
 * require adding listeners to each control with a tooltip.
 * It can handle tooltips on dynamically added elements without
 * explicit initialization.
 */

+(function($) {
    'use strict';
    var Tooltips = function() {
        var $tooltipElement = null;
        var tooltipTimeout = null;

        function init() {
            addListeners();
        }

        function addListeners() {
            document.addEventListener('mouseenter', onMouseEnter, true);
            document.addEventListener('mouseleave', onMouseLeave, true);
            document.addEventListener('mousedown', onMouseDown);
            document.addEventListener('click', onMouseDown);
            document.addEventListener('keydown', onKeyDown);
        }

        function clearTooltipTimeout() {
            if (tooltipTimeout !== null) {
                clearTimeout(tooltipTimeout);
            }

            tooltipTimeout = null;
        }

        function createTooltip(element) {
            if (!$tooltipElement) {
                $tooltipElement = $(
                    '<div class="october-tooltip tooltip-hidden tooltip-invisible"><span class="tooltip-text"></span><span class="tooltip-hotkey"></span></div>'
                );
                $(document.body).append($tooltipElement);
            }

            $tooltipElement.find('.tooltip-text').text(element.getAttribute('data-tooltip-text'));
            var tooltipHotkey = element.getAttribute('data-tooltip-hotkey'),
                hotkeySpan = $tooltipElement.find('.tooltip-hotkey').html('');

            if (tooltipHotkey) {
                tooltipHotkey.split(',').forEach(function(hotkeys) {
                    hotkeySpan.append($('<i>').text(hotkeys.trim()));
                });
            }

            $tooltipElement.removeClass('tooltip-hidden');
            $tooltipElement.css('left', 0);

            var $element = $(element),
                elementOffset = $element.offset(),
                elementWidth = $element.outerWidth(),
                elementHeight = $element.height(),
                tooltipWidth = $tooltipElement.outerWidth(),
                bodyWidth = $(document.body).width(),
                left = Math.round(elementOffset.left + elementWidth / 2 - tooltipWidth / 2);

            if (left < 0) {
                left = 15;
            }

            var rightDiff = left + tooltipWidth - bodyWidth;
            if (rightDiff > 0) {
                left -= rightDiff + 15;
            }

            $tooltipElement.css({
                left: left,
                top: elementOffset.top + elementHeight + 5
            });

            $tooltipElement.removeClass('tooltip-invisible');
        }

        function destroyTooltip() {
            if (!$tooltipElement) {
                return;
            }

            $tooltipElement.addClass('tooltip-invisible');

            setTimeout(function() {
                $tooltipElement.addClass('tooltip-hidden');
            }, 150);
        }

        function onMouseEnter(ev) {
            if (!ev.target || !ev.target.getAttribute || !ev.target.dataset) {
                return;
            }

            if (!ev.target.getAttribute('data-tooltip-text')) {
                return;
            }

            clearTooltipTimeout();
            destroyTooltip();

            tooltipTimeout = setTimeout(function() {
                createTooltip(ev.target);
            }, 300);
        }

        function onMouseLeave(ev) {
            if (!ev.target || !ev.target.getAttribute || !ev.target.dataset) {
                return;
            }

            if (!ev.target.getAttribute('data-tooltip-text')) {
                return;
            }

            clearTooltipTimeout();
            destroyTooltip();
        }

        function onMouseDown(ev) {
            destroyTooltip();
        }

        function onKeyDown(ev) {
            destroyTooltip();
        }

        init();

        this.clear = function() {
            clearTooltipTimeout();
            destroyTooltip();
        };
    };

    var tooltips = new Tooltips();

    $.oc.octoberTooltips = {
        clear: function() {
            tooltips.clear();
        }
    };
})(window.jQuery);
