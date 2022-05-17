+function ($) { "use strict";
    var ModalSize = function() {
        var lastPoint = {
                x: 0,
                y: 0
            },
            $content = null,
            size = {},
            offset = {},
            mouseUpCallback = null,
            side = null,
            resizeMinWidth = 0,
            resizeMinHeight = 0,
            that = this;

        function clear() {
            $content = null;
            size = {};
            offset = {},
            mouseUpCallback = null;
        }

        function initSizeObj() {
            if (!size.width) {
                size.width = $content.outerWidth();
                size.height = $content.outerHeight();
            }
        }

        function getDragClassName() {
            if (side == 'bt' || side == 'tp') {
                return 'modal-dragging-ns';
            }

            if (side == 'lf' || side == 'rt') {
                return 'modal-dragging-ew';
            }

            if (side == 'bt-lf' || side == 'tp-rt') {
                return 'modal-dragging-nesw';
            }

            if (side == 'tp-lf' || side == 'bt-rt') {
                return 'modal-dragging-nwse';
            }
        }

        this.onMouseDown = function onMouseDown(ev, contentObj, sizeObj, offsetObj, mouseUp, minWidth, minHeight) {
            side = ev.target.getAttribute('data-side'),
            $content = $(contentObj);
            size = sizeObj;
            offset = offsetObj;
            mouseUpCallback = mouseUp;

            resizeMinWidth = minWidth;
            resizeMinHeight = minHeight;

            $(document.body).addClass('modal-dragging ' + getDragClassName());
            document.addEventListener('mousemove', onMouseMove, {passive: true});
            document.addEventListener('mouseup', onMouseUp);

            lastPoint.x = ev.pageX;
            lastPoint.y = ev.pageY;

            ev.preventDefault();
            ev.stopPropagation();
        };

        function onMouseUp(ev) {
            document.removeEventListener('mousemove', onMouseMove, {passive: true});
            document.removeEventListener('mouseup', onMouseUp);
            $(document.body).removeClass('modal-dragging ' + getDragClassName());

            $.oc.vueComponentHelpers.modalPosition.fixPosition($content, true, offset);

            Vue.nextTick(mouseUpCallback);

            clear();
        }

        function updateLf(deltaX) {
            var sizeDelta = size.width - Math.max(resizeMinWidth, size.width - deltaX);
            if (sizeDelta != 0) {
                offset.left += sizeDelta;
                size.width -= sizeDelta;
            }
        }

        function updateTp(deltaY) {
            var sizeDelta = size.height - Math.max(resizeMinHeight, size.height - deltaY);
            if (sizeDelta != 0) {
                offset.top += sizeDelta;
                size.height -= sizeDelta;
            }
        }

        function onMouseMove(ev) {
            if (ev.buttons != 1) {
                // Handle the case when the button was released
                // outside of the viewport. mouseup doesn't fire
                // in that case.
                //
                onMouseUp();
            }

            initSizeObj();

            var deltaX = ev.pageX - lastPoint.x,
                deltaY = ev.pageY - lastPoint.y,
                prevHeight = size.height,
                prevWidth = size.width,
                sizeDelta = 0;

            switch (side) {
                case 'bt' :
                    size.height += deltaY;
                break;
                case 'rt' :
                    size.width += deltaX;
                break;
                case 'bt-rt' :
                    size.height += deltaY;
                    size.width += deltaX;
                break;
                case 'tp-lf' :
                    updateTp(deltaY);
                    updateLf(deltaX);
                break;
                case 'bt-lf' :
                    size.height += deltaY;
                    updateLf(deltaX);
                break;
                case 'tp-rt' :
                    size.width += deltaX;
                    updateTp(deltaY);
                break;
                case 'tp':
                    updateTp(deltaY);
                break;
                case 'lf':
                    updateLf(deltaX);
                break;
            }

            size.height = Math.max(resizeMinHeight, size.height);
            size.width = Math.max(resizeMinWidth, size.width);

            if (prevHeight != size.height) {
                lastPoint.y = ev.pageY;
            }

            if (prevWidth != size.width) {
                lastPoint.x = ev.pageX;
            }
        }

        this.onMouseUp = onMouseUp;
    };

    $.oc.vueComponentHelpers.modalSize = new ModalSize();
}(window.jQuery);