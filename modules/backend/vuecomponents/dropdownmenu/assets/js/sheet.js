$.oc.module.register('backend.component.dropdownmenu.sheet', function () {
    var dropdownMenuUtils = $.oc.module.import('backend.component.dropdownmenu.utils');

    Vue.component('backend-component-dropdownmenu-sheet', {
        props: {
            items: Array,
            menuId: String,
            labeledById: String,
            isSubmenu: Boolean,
            preferablePosition: String
        },
        data: function() {
            return {
                visible: false,
                submenuVisible: false
            };
        },
        computed: {
            menuClassName: function computeMenuClassName() {
                var hasIcons = false,
                    hasSubmenus = false;
    
                for (var index = 0; index < this.items.length; index++) {
                    var hasState = ['radiobutton', 'checkbox'].indexOf(this.items[index].type) !== -1;
    
                    if (this.items[index].icon && hasState) {
                        throw new Error('Radio button and checkbox dropdown menu items cannot have icons');
                    }
    
                    if (this.items[index].icon || hasState) {
                        hasIcons = true;
                    }
    
                    if (this.items[index].items && this.items[index].items.length) {
                        hasSubmenus = true;
                    }
                }
    
                var result = [];
                if (hasIcons) {
                    result.push('has-icons');
                }
    
                if (hasSubmenus) {
                    result.push('has-submenus');
                }
    
                if (this.submenuVisible) {
                    result.push('submenu-visible');
                }
    
                return result.join(' ');
            }
        },
        methods: {
            show: function show(triggerElementOrEvent) {
                if (this.visible) {
                    return;
                }
    
                this.visible = true;
    
                var that = this;
                Vue.nextTick(function() {
                    $.oc.modalFocusManager.push(that.onFocusIn, 'dropdownmenu-sheet', that._uid);
    
                    if (!triggerElementOrEvent) {
                        var eventData = {
                            triggerElementOrEvent: null
                        };
                        that.$emit('aligntotrigger', eventData);
                        if (eventData.triggerElement !== null) {
                            that.onAlignToTrigger(eventData.triggerElement);
                        }
                    }
                    else {
                        that.onAlignToTrigger(triggerElementOrEvent);
                    }
                });
    
                return true;
            },
    
            hide: function hide() {
                if (!this.visible) {
                    return false;
                }
    
                this.visible = false;
                this.notifyMenuClosing();
    
                var that = this;
                Vue.nextTick(function() {
                    $.oc.modalFocusManager.pop();
                    that.$emit('hidden');
                });
    
                return true;
            },
    
            notifyMenuClosing: function notifyMenuClosing() {
                this.submenuVisible = false;
    
                for (var index = 0; index < this.$children.length; index++) {
                    var item = this.$children[index];
                    item.notifyMenuClosing();
                }
            },
    
            getFocusedItem: function getCurrentFocus() {
                return $(this.$el).find('li [data-menuitem]:focus').closest('li');
            },
    
            onFocusIn: function onFocusIn(ev) {
                if (!ev) {
                    $(this.$el).find('li:not([disabled]):not(.separator) [data-menuitem]').first().focus();
    
                    return;
                }
    
                if (document !== event.target && this.$el !== event.target && !this.$el.contains(event.target)) {
                    $(this.$el).find('li:not([disabled]):not(.separator) [data-menuitem]').first().focus();
                }
            },
    
            onKeyDown: function onKeyDown(ev) {
                if (!this.visible) {
                    return;
                }
    
                for (var index = 0; index < this.$children.length; index++) {
                    var item = this.$children[index];
                    item.onKeyDown(ev);
                }
    
                if (!$.oc.modalFocusManager.isUidTop(this._uid)) {
                    return;
                }
    
                // Up/down/tab arrow handling
                //
    
                if ([9, 37, 38, 39, 40].indexOf(ev.keyCode) === -1) {
                    return;
                }
    
                if (ev.keyCode == 37 && this.isSubmenu) {
                    this.hide();
    
                    ev.preventDefault();
                    ev.stopPropagation();
                    return;
                }
    
                var focused = this.getFocusedItem(),
                    newFocused = [];
    
                if (focused.length) {
                    if (ev.keyCode == 40 || ev.keyCode == 9) {
                        newFocused = focused.nextAll('li.item:not([disabled]):not(.separator)').first();
    
                        if (!newFocused.length && ev.keyCode == 9) {
                            // Allow Tab to cycle
                            newFocused = $(this.$el).find('li.item:not([disabled]):not(.separator)').first();
                        }
                    }
    
                    if (ev.keyCode == 38) {
                        newFocused = focused.prevAll('li.item:not([disabled]):not(.separator)').first();
                    }
    
                    if (ev.keyCode == 39) {
                        var item = focused.find('> [data-menuitem].has-submenu:not([disabled])');
                        if (item.length) {
                            item.trigger('click');
    
                            ev.preventDefault();
                            ev.stopPropagation();
                            return;
                        }
                    }
                }
                else {
                    newFocused = $(this.$el).find('li.item:not([disabled]):not(.separator)').first();
                }
    
                if (newFocused.length) {
                    newFocused.find('[data-menuitem]').focus();
                }
    
                ev.preventDefault();
                ev.stopPropagation();
                return false;
            },
    
            onAlignToTrigger: function(triggerElementOrEvent) {
                var positionContainer = $(this.$el).closest('[data-menu-position-container]').get(0);
                dropdownMenuUtils.alignToTriggerElement(
                    triggerElementOrEvent,
                    positionContainer,
                    this.isSubmenu,
                    this.preferablePosition
                );
            },
    
            onCommand: function onCommand(data) {
                this.hide();
                this.$emit('command', data);
            },
    
            onHide: function onHide() {
                this.hide();
            },
    
            onItemMouseEnter: function onItemMouseEnter(uid) {
                for (var index = 0; index < this.$children.length; index++) {
                    var item = this.$children[index];
    
                    if (item._uid != uid) {
                        item.hideSubmenu();
                    }
                }
            },
    
            onAfterLeave: function onAfterLeave() {
                if ($(document.documentElement).hasClass('user-touch')) {
                    Vue.nextTick(function() {
                        //  $('document.body').removeClass(''); // TODO for a root sheet
                    });
                }
            },
    
            onItemSubmenuShown: function onItemSubmenuShown() {
                this.submenuVisible = true;
            },
    
            onItemSubmenuHidden: function onItemSubmenuShown() {
                this.submenuVisible = false;
            },
    
            onCloseClick: function onCloseClick() {
                this.$emit('closemenu');
            },
    
            onGoBackClick: function onGoBackClick() {
                this.hide();
            }
        },
        template: '#backend_vuecomponents_dropdownmenu_sheet'
    });
});