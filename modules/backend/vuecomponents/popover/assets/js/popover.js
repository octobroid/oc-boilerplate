/*
 * Vue popover implementation
 */
$.oc.module.register('backend.component.popover', function () {
    Vue.component('backend-component-popover', {
        props: {
            closeByEsc: {
                type: Boolean,
                default: true
            },
            containerCssClass: {
                type: String,
                default: ''
            },
            closeByOverlayClick: {
                type: Boolean,
                default: true
            },
            baseZIndex: {
                type: Number,
                default: 600
            },
            position: {
                type: String,
                default: 'bottom-right',
                validator: function validator(value) {
                    return ['bottom-right'].indexOf(value) !== -1;
                }
            },
            alwaysVisible: {
                type: Boolean,
                default: false
            }
        },
        data: function data() {
            return {
                basePopoverZindex: 600,
                visible: false,
                in: false,
                zIndex: 600,
                offset: {
                    left: 0,
                    top: 0
                }
            };
        },
        computed: {
            containerStyle: function containerStyle() {
                var result = {
                    'z-index': this.zIndex
                };

                if (this.alwaysVisible && !this.visible) {
                    result.display = 'none';
                }

                return result;
            },
            popoverStyle: function popoverStyle() {
                var result = {
                    top: this.offset.top + 'px',
                    left: this.offset.left + 'px'
                };

                return result;
            },
            containerCssClassFull: function containerCssClassFull() {
                var result = this.containerCssClass;

                if (this.in) {
                    result += ' in ';
                }

                if (this.position === 'bottom-right') {
                    result += ' placement-bottom placement-bottom-right';
                }

                return result;
            },
            isVisible: function isVisible() {
                return this.visible;
            }
        },
        methods: {
            show: function show(triggerElement) {
                var _this = this;

                if (!triggerElement) {
                    throw new Error('Popover trigger element is not provided');
                }

                this.offset.top = 0;
                this.offset.left = 0;

                this.visible = true;
                Vue.nextTick(function (_) {
                    _this.in = true;
                    _this.loadPosition(triggerElement);
                });

                $(document.body).on('keydown', this.onKeyDown);
            },
            hide: function hide(byEscape) {
                var _this2 = this;

                $(document.body).off('keydown', this.onKeyDown);
                this.in = false;

                setTimeout(function (_) {
                    _this2.visible = false;
                    $.oc.modalFocusManager.pop();
                    Vue.nextTick(function (_) {
                        return _this2.$emit('hidden', byEscape);
                    });
                }, 300);
            },
            loadPosition: function loadPosition(triggerElement) {
                var _this3 = this;

                Vue.nextTick(function (_) {
                    _this3.calculatePosition(triggerElement);
                    $.oc.modalFocusManager.push(_this3.onFocusIn, 'popover', _this3._uid, true);
                    _this3.zIndex = _this3.basePopoverZindex + $.oc.modalFocusManager.getNumberOfType('popover');
                    _this3.$emit('shown');
                });
            },
            calculatePosition: function calculatePosition(triggerElement) {
                var relativeOffset = $.oc.vueUtils.getRelativeOffset(triggerElement, this.$refs.popover);
                var triggerHeight = $(triggerElement).outerHeight();
                var triggerWidth = $(triggerElement).outerWidth();
                var containerWidth = $(this.$refs.popover).width();

                this.offset.top = relativeOffset.top + triggerHeight + 10;
                this.offset.left = relativeOffset.left + triggerWidth - containerWidth;
            },
            focusDefaultControl: function focusDefaultControl() {
                var defaultFocus = $(this.$refs.content).find('[data-default-focus]').first();
                setTimeout(function (_) {
                    return defaultFocus.focus();
                }, 100);
            },
            onKeyDown: function onKeyDown(ev) {
                if (!$.oc.modalFocusManager.isUidTop(this._uid)) {
                    return;
                }

                if (ev.keyCode == 27) {
                    if (!this.closeByEsc) {
                        return;
                    }

                    var event = $.Event('escapepressed');

                    this.$emit('escapepressed', event);
                    if (!event.isDefaultPrevented()) {
                        this.hide(true);

                        ev.stopPropagation();
                        ev.preventDefault();
                    }
                }

                if (ev.keyCode == 13) {
                    this.$emit('enterkey', ev);
                }
            },
            onFocusIn: function onFocusIn(ev) {
                if (!ev) {
                    var event = $.Event('setdefaultfocus');

                    this.$emit('setdefaultfocus', event);
                    if (event.isDefaultPrevented()) {
                        return;
                    }

                    this.focusDefaultControl();

                    return;
                }

                if (!this.isModal) {
                    return;
                }

                if (document !== ev.target && this.$refs.content !== ev.target && this.$el !== ev.target && !this.$refs.content.contains(ev.target)) {
                    this.focusDefaultControl();

                    return false;
                }
            },
            onOverlayClick: function onOverlayClick(ev) {
                if (!this.visible) {
                    return;
                }

                var $popover = $(this.$refs.popover);
                if ($popover.has(ev.target).length) {
                    return;
                }

                if (this.$refs.popover == ev.target) {
                    return;
                }

                if (this.closeByOverlayClick) {
                    this.hide(true);
                    return;
                }
            }
        },
        mounted: function mounted() {
            this.basePopoverZindex = this.baseZIndex;
        },

        template: '#backend_vuecomponents_popover'
    });
});
