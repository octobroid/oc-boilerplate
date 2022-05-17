+(function($) {
    'use strict';
    var ModalPosition = function() {
        var lastPoint = {
                x: 0,
                y: 0
            },
            $content = null,
            offset = {},
            mouseUpCallback = null,
            that = this;

        function clear() {
            $content = null;
            offset = {};
            mouseUpCallback = null;
        }

        function onMouseUp(ev) {
            document.removeEventListener('mousemove', onMouseMove, { passive: true });
            document.removeEventListener('mouseup', onMouseUp);

            $(document.body).removeClass('modal-dragging');

            that.fixPosition($content, true, offset);

            mouseUpCallback();

            clear();
        }

        function onMouseMove(ev) {
            if (ev.buttons != 1) {
                // Handle the case when the button was released
                // outside of the viewport. mouseup doesn't fire
                // in that case.
                //
                onMouseUp();
            }

            var deltaX = ev.pageX - lastPoint.x,
                deltaY = ev.pageY - lastPoint.y;

            lastPoint.x = ev.pageX;
            lastPoint.y = ev.pageY;

            offset.left += deltaX;
            offset.top += deltaY;
        }

        this.fixPosition = function fixPosition($contentElement, animate, offsetObj) {
            var contentOffset = $contentElement.offset();

            contentOffset.top -= window.scrollY;

            var contentWidth = $contentElement.width(),
                contentHeight = $contentElement.height(),
                contentRight = contentOffset.left + contentWidth,
                contentBottom = contentOffset.top + contentHeight,
                documentWidth = $(document).width(),
                documentHeight = $(document).height();

            if (
                animate &&
                (contentOffset.left < 10 ||
                    contentOffset.top < 10 ||
                    contentRight > documentWidth - 10 ||
                    contentBottom > documentHeight - 10)
            ) {
                $contentElement.addClass('animate-position');
            }

            if (contentOffset.left < 10) {
                offsetObj.left -= contentOffset.left - 10;
            }

            if (contentOffset.top < 10) {
                offsetObj.top -= contentOffset.top - 10;
            }

            if (contentRight > documentWidth - 10) {
                offsetObj.left -= contentRight - documentWidth + 10;
            }

            if (contentBottom > documentHeight - 10) {
                var delta = contentBottom - documentHeight + 10;
                offsetObj.top -= delta;

                contentOffset.top -= delta;
                if (contentOffset.top < 10) {
                    offsetObj.top -= contentOffset.top - 10;
                }
            }
        };

        this.getInitialOffset = function(isModal) {
            var count = $.oc.modalFocusManager.getNumberOfType('modal');

            if (isModal) {
                return count * 20;
            }

            return 150 + count * 20;
        };

        this.applyConstraint = function(value, min, max) {
            value = Math.max(value, min);
            value = Math.min(value, max);

            return value;
        };

        this.onMouseDown = function onMouseDown(ev, content, offsetObj, mouseUp) {
            if (ev.target.tagName === 'BUTTON' || ev.target.tagName === 'A') {
                return;
            }

            $content = $(content);
            offset = offsetObj;
            mouseUpCallback = mouseUp;

            $(document.body).addClass('modal-dragging');
            document.addEventListener('mousemove', onMouseMove, { passive: true });
            document.addEventListener('mouseup', onMouseUp);

            lastPoint.x = ev.pageX;
            lastPoint.y = ev.pageY;

            ev.preventDefault();
            ev.stopPropagation();
        };

        this.onMouseUp = onMouseUp;
    };

    if ($.oc.vueComponentHelpers === undefined) {
        $.oc.vueComponentHelpers = {};
    }

    $.oc.vueComponentHelpers.modalPosition = new ModalPosition();
})(window.jQuery);
