$.oc.module.register('backend.component.dropdownmenubutton', function () {
    Vue.component('backend-component-dropdownmenubutton', {
        props: {
            menuitems: {
                type: Array,
                required: true
            },
            disabled: Boolean,
            cssClass: String,
            label: String,
            preferableMenuPosition: String,
            currentLabelCommand: String
        },
        data: function data() {
            return {
                menuId: null,
                buttonId: null,
                menuButtonId: null
            };
        },
        computed: {
            buttonText: function computeButtonText() {
                var _this = this;

                if (this.currentLabelCommand === null) {
                    return this.label;
                }

                if (!this.menuitems) {
                    return null;
                }

                var item = this.menuitems.find(function (item) {
                    return item.command == _this.currentLabelCommand;
                });
                if (!item) {
                    return null;
                }

                return item.label;
            }
        },
        methods: {
            onClick: function onClick(ev, isHotkey, isMenuButton) {
                if (this.disabled || $(document.body).hasClass('drag')) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    return false;
                }

                ev.preventDefault();
                ev.stopPropagation();

                this.$refs.menu.showMenu(this.$el);
                return false;
            },

            onMenuShown: function onMenuShown() {
                $(this.$refs.button).attr('aria-expanded', 'true');
            },

            onMenuHidden: function onMenuHidden() {
                $(this.$refs.button).removeAttr('aria-expanded');
            },

            onMenuItemCommand: function onMenuItemCommand(command) {
                this.$emit('command', command, false, null, this.$el);
            }
        },
        mounted: function onMounted() {
            this.menuId = $.oc.domIdManager.generate('dropdown-menu');
            this.buttonId = $.oc.domIdManager.generate('dropdownmenubutton-button');
            this.menuButtonId = $.oc.domIdManager.generate('dropdownmenubutton-menu');
        },
        template: '#backend_vuecomponents_dropdownmenubutton'
    });
});
