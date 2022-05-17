/*
 * Allows to scroll an element content in the horizontal or horizontal directions. This script doesn't use
 * absolute positioning and rely on the scrollLeft/scrollTop DHTML properties. The element width should be
 * fixed with the CSS or JavaScript.
 *
 * Events triggered on the element:
 * - start.oc.dragScroll
 * - drag.oc.dragScroll
 * - stop.oc.dragScroll
 *
 * Options:
 * - start - callback function to execute when the drag starts
 * - drag - callback function to execute when the element is dragged
 * - stop - callback function to execute when the drag ends
 * - vertical - determines if the scroll direction is vertical, true by default
 * - scrollClassContainer - if specified, specifies an element or element selector to apply the 'scroll-before' and 'scroll-after' CSS classes,
 *   depending on whether the scrollable area is in its start or end
 * - scrollMarkerContainer - if specified, specifies an element or element selector to inject scroll markers (span elements that con
 *   contain the ellipses icon, indicating whether scrolling is possible)
 * - useDrag - determines if dragging is allowed support, true by default
 * - useNative - if native CSS is enabled via "mobile" on the HTML tag, false by default
 * - useScroll - determines if the mouse wheel scrolling is allowed, true by default
 * - useComboScroll - determines if horizontal scroll should act as vertical, and vice versa, true by default
 * - dragSelector - restrict drag events to this selector
 * - scrollSelector - restrict scroll events to this selector
 *
 * Methods:
 * - isStart - determines if the scrollable area is in its start (left or top)
 * - isEnd - determines if the scrollable area is in its end (right or bottom)
 * - goToStart - moves the scrollable area to the start (left or top)
 * - goToElement - moves the scrollable area to an element
 *
 * Require:
 * - modernizr/modernizr
 * - mousewheel/mousewheel
 */
+(function($) {
    'use strict';

    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype;

    var DragScroll = function(element, options) {
        this.options = $.extend({}, DragScroll.DEFAULTS, options);
        this.touchDragStarted = false;
        this.onTouchMove = onTouchMove;

        var $el = $(element),
            el = $el.get(0),
            dragStart = 0,
            startOffset = 0,
            self = this,
            dragging = false,
            eventElementName = this.options.vertical ? 'pageY' : 'pageX',
            isNative = this.options.useNative && $('html').hasClass('mobile');

        this.el = $el;
        this.scrollClassContainer = this.options.scrollClassContainer ? $(this.options.scrollClassContainer) : $el;
        this.isScrollable = true;

        Base.call(this);

        /*
         * Inject scroll markers
         */
        if (this.options.scrollMarkerContainer) {
            $(this.options.scrollMarkerContainer).append(
                $('<span class="before scroll-marker"></span><span class="after scroll-marker"></span>')
            );
        }

        /*
         * Bind events
         */
        var $scrollSelect = this.options.scrollSelector ? $(this.options.scrollSelector, $el) : $el;

        $scrollSelect.mousewheel(function(event) {
            if (!self.options.useScroll || self.paused) {
                return;
            }

            var offset,
                offsetX = event.deltaFactor * event.deltaX,
                offsetY = event.deltaFactor * event.deltaY;

            if (!offsetX && self.options.useComboScroll) {
                offset = offsetY * -1;
            }
            else if (!offsetY && self.options.useComboScroll) {
                offset = offsetX;
            }
            else {
                offset = self.options.vertical ? offsetY * -1 : offsetX;
            }

            if (!self.options.vertical) {
                event.preventDefault();
                event.stopPropagation();
            }

            var scrolled = scrollWheel(offset);
            if (!scrolled && self.options.noOverScroll) {
                event.preventDefault();
                event.stopPropagation();
            }

            return !scrolled
        });

        if (this.options.useDrag) {
            $el.on('mousedown.dragScroll', this.options.dragSelector, function(event) {
                if (self.paused) {
                    return;
                }

                if (event.target && event.target.tagName === 'INPUT') {
                    return; // Don't prevent clicking inputs in the toolbar
                }

                if (!self.isScrollable) {
                    return;
                }

                startDrag(event);
                return false;
            });
        }

        if (Modernizr.touchevents) {
            $el.on('touchstart.dragScroll', this.options.dragSelector, function (event) {
                if (self.paused) {
                    return;
                }

                var touchEvent = event.originalEvent;

                if (touchEvent.touches.length == 1) {
                    startDrag(touchEvent.touches[0]);
                    self.touchDragStarted = true;

                    event.stopPropagation();
                }
            });

            window.addEventListener('touchmove', self.onTouchMove, { passive: false })
        }

        $el.on('click.dragScroll', function() {
            // Do not handle item clicks while dragging
            if ($(document.body).hasClass(self.options.dragClass)) {
                return false;
            }
        });

        if (!this.options.noScrollClasses) {
            $(document).on('ready', this.proxy(this.fixScrollClasses));
            $(window).on('resize', this.proxy(this.fixScrollClasses));
        }

        /*
         * Internal event, drag has started
         */
        function startDrag(event) {
            if (self.paused) {
                return;
            }

            dragStart = event[eventElementName];
            startOffset = self.options.vertical ? $el.scrollTop() : $el.scrollLeft();

            if (Modernizr.touchevents) {
                $(window).on('touchend.dragScroll', function(event) {
                    stopDrag();
                });
            }

            $(window).on('mousemove.dragScroll', function(event) {
                moveDrag(event);
                return false;
            });

            $(window).on('mouseup.dragScroll', function(mouseUpEvent) {
                var isClick = event.pageX == mouseUpEvent.pageX && event.pageY == mouseUpEvent.pageY;
                stopDrag(isClick);
                return false;
            });
        }

        function onTouchMove(event) {
            if (!self.touchDragStarted) {
                return;
            }

            var touchEvent = event
            moveDrag(touchEvent.touches[0])
            if (!isNative) {
                event.preventDefault()
            }
        }

        /*
         * Internal event, drag is active
         */
        function moveDrag(event) {
            var current = event[eventElementName],
                offset = dragStart - current;

            if (Math.abs(offset) > 3) {
                if (!dragging) {
                    dragging = true;
                    $el.trigger('start.oc.dragScroll');
                    self.options.start();
                    $(document.body).addClass(self.options.dragClass);
                }

                if (!isNative) {
                    self.options.vertical ? $el.scrollTop(startOffset + offset) : $el.scrollLeft(startOffset + offset);
                }

                $el.trigger('drag.oc.dragScroll');
                self.options.drag();
            }
        }

        /*
         * Internal event, drag has ended
         */
        function stopDrag(click) {
            $(window).off('.dragScroll');
            self.touchDragStarted = false;

            dragging = false;

            if (click) {
                $(document.body).removeClass(self.options.dragClass);
            }
            else {
                self.fixScrollClasses();
            }

            window.setTimeout(function() {
                if (!click) {
                    $(document.body).removeClass(self.options.dragClass);
                    $el.trigger('stop.oc.dragScroll');
                    self.options.stop();
                    self.fixScrollClasses();
                }
            }, 100);
        }

        /*
         * Scroll wheel has moved by supplied offset
         */
        function scrollWheel(offset) {
            if (self.paused) {
                return;
            }

            startOffset = self.options.vertical ? el.scrollTop : el.scrollLeft;

            self.options.vertical ? $el.scrollTop(startOffset + offset) : $el.scrollLeft(startOffset + offset);

            var scrolled = self.options.vertical ? el.scrollTop != startOffset : el.scrollLeft != startOffset;

            $el.trigger('drag.oc.dragScroll');
            self.options.drag();

            if (scrolled) {
                if (self.wheelUpdateTimer !== undefined && self.wheelUpdateTimer !== false)
                    window.clearInterval(self.wheelUpdateTimer);

                self.wheelUpdateTimer = window.setTimeout(function() {
                    self.wheelUpdateTimer = false;
                    self.fixScrollClasses();
                }, 100);
            }

            return scrolled;
        }

        this.fixScrollClasses();
    };

    DragScroll.prototype = Object.create(BaseProto);
    DragScroll.prototype.constructor = DragScroll;

    DragScroll.DEFAULTS = {
        vertical: false,
        useDrag: true,
        useScroll: true,
        useNative: false,
        useComboScroll: true,
        scrollClassContainer: false,
        scrollMarkerContainer: false,
        scrollSelector: null,
        dragSelector: null,
        noOverScroll: false,
        dragClass: 'drag',
        start: function() {},
        drag: function() {},
        stop: function() {}
    };

    DragScroll.prototype.fixScrollClasses = function() {
        if (this.options.noScrollClasses) {
            return;
        }

        if (this.fixScrollClassesIntervalId) {
            window.clearTimeout(this.fixScrollClassesIntervalId);
            this.fixScrollClassesIntervalId = null;
        }

        var that = this;
        this.fixScrollClassesIntervalId = window.setTimeout(function() {
            that.fixScrollClassesIntervalId = null;

            var isStart = that.isStart(),
                isEnd = that.isEnd();

            that.scrollClassContainer.toggleClass('scroll-before', !isStart);
            that.scrollClassContainer.toggleClass('scroll-after', !isEnd);

            that.scrollClassContainer.toggleClass('scroll-active-before', that.isActiveBefore());
            that.scrollClassContainer.toggleClass('scroll-active-after', that.isActiveAfter());
            that.isScrollable = !isStart || !isEnd;
        }, 30);
    };

    DragScroll.prototype.isStart = function() {
        if (!this.options.vertical) {
            return this.el.scrollLeft() <= 0;
        }
        else {
            return this.el.scrollTop() <= 0;
        }
    };

    DragScroll.prototype.isEnd = function() {
        // Fudge factor for retina displays
        var offset = 1;

        if (!this.options.vertical) {
            return this.el[0].scrollWidth - (this.el.scrollLeft() + this.el.width()) - offset <= 0;
        }
        else {
            return this.el[0].scrollHeight - (this.el.scrollTop() + this.el.height()) - offset <= 0;
        }
    };

    DragScroll.prototype.goToStart = function() {
        if (!this.options.vertical) {
            return this.el.scrollLeft(0);
        }
        else {
            return this.el.scrollTop(0);
        }
    };

    /*
     * Determines if the element with the class 'active' is hidden before the viewport -
     * on the left or on the top, depending on whether the scrollbar is horizontal or vertical.
     */
    DragScroll.prototype.isActiveAfter = function() {
        var activeElement = $('.active', this.el);
        if (activeElement.length == 0) {
            return false;
        }

        if (!this.options.vertical) {
            return activeElement.get(0).offsetLeft > this.el.scrollLeft() + this.el.width();
        }
        else {
            return activeElement.get(0).offsetTop > this.el.scrollTop() + this.el.height();
        }
    };

    /*
     * Determines if the element with the class 'active' is hidden after the viewport -
     * on the right or on the bottom, depending on whether the scrollbar is horizontal or vertical.
     */
    DragScroll.prototype.isActiveBefore = function() {
        var activeElement = $('.active', this.el);
        if (activeElement.length == 0) {
            return false;
        }

        if (!this.options.vertical) {
            return activeElement.get(0).offsetLeft + activeElement.width() < this.el.scrollLeft();
        }
        else {
            return activeElement.get(0).offsetTop + activeElement.height() < this.el.scrollTop();
        }
    };

    DragScroll.prototype.goToElement = function(element, callback, options) {
        var $el = $(element);
        if (!$el.length) return;

        var self = this,
            params = {
                duration: 300,
                queue: false,
                complete: function() {
                    self.fixScrollClasses();
                    if (callback !== undefined) callback();
                }
            };

        params = $.extend(params, options || {});

        var offset = 0,
            animated = false;

        if (!this.options.vertical) {
            offset = $el.get(0).offsetLeft - this.el.scrollLeft();

            if (offset < 0) {
                this.el.animate({ scrollLeft: $el.get(0).offsetLeft }, params);
                animated = true;
            }
            else {
                offset = $el.get(0).offsetLeft + $el.width() - (this.el.scrollLeft() + this.el.width());
                if (offset > 0) {
                    this.el.animate({ scrollLeft: $el.get(0).offsetLeft + $el.width() - this.el.width() }, params);
                    animated = true;
                }
            }
        }
        else {
            offset = $el.get(0).offsetTop - this.el.scrollTop();

            if (offset < 0) {
                this.el.animate({ scrollTop: $el.get(0).offsetTop }, params);
                animated = true;
            }
            else {
                var heightOffset = 0;
                if (params.alignBottom) {
                    heightOffset = $el.height();
                }

                offset = $el.get(0).offsetTop + heightOffset - (this.el.scrollTop() + this.el.height());
                if (offset > 0) {
                    this.el.animate(
                        { scrollTop: $el.get(0).offsetTop + $el.height() - this.el.height() + heightOffset },
                        params
                    );
                    animated = true;
                }
            }
        }

        if (!animated && callback !== undefined) {
            callback();
        }
    };

    DragScroll.prototype.pause = function() {
        this.paused = true;
    };

    DragScroll.prototype.resume = function() {
        this.paused = false;
    };

    DragScroll.prototype.dispose = function() {
        this.scrollClassContainer = null;

        if (!this.options.noScrollClasses) {
            $(document).off('ready', this.proxy(this.fixScrollClasses));
            $(window).off('resize', this.proxy(this.fixScrollClasses));
        }
        this.el.off('.dragScroll');

        this.el.removeData('oc.dragScroll');
        window.removeEventListener('touchmove', this.onTouchMove, {passive: false})

        this.el = null;
        BaseProto.dispose.call(this);
    };

    // DRAGSCROLL PLUGIN DEFINITION
    // ============================

    var old = $.fn.dragScroll;

    $.fn.dragScroll = function(option) {
        var args = arguments;

        return this.each(function() {
            var $this = $(this);
            var data = $this.data('oc.dragScroll');
            var options = typeof option == 'object' && option;

            if (!data) $this.data('oc.dragScroll', (data = new DragScroll(this, options)));
            if (typeof option == 'string') {
                var methodArgs = [];
                for (var i = 1; i < args.length; i++) methodArgs.push(args[i]);

                data[option].apply(data, methodArgs);
            }
        });
    };

    $.fn.dragScroll.Constructor = DragScroll;

    // DRAGSCROLL NO CONFLICT
    // =================

    $.fn.dragScroll.noConflict = function() {
        $.fn.dragScroll = old;
        return this;
    };
})(window.jQuery);
