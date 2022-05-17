/*
 * Responsive extension for the main menu
 */

+function ($) { "use strict";
    function ResponsiveMenu(closeCallback) {
        var $mainMenuElement = $('#layout-mainmenu .navbar ul.mainmenu-items'),
            $menuContainer = $mainMenuElement.closest('.layout-row'),
            $responsiveMenuContainer = $('#layout-mainmenu-responsive-container'),
            $responsiveMainMenuPane = $responsiveMenuContainer.find('.mainmenu-pane'),
            $responsiveSubMenuPane = $responsiveMenuContainer.find('.submenu-pane'),
            $responsiveMainMenu = $responsiveMainMenuPane.find('ul.mainmenu-items'),
            $responsiveSubMenu = $responsiveSubMenuPane.find('ul.mainmenu-items'),
            $subMenuHeader = $responsiveSubMenuPane.find('.menu-header'),
            $subMenuHeaderMenuItem = $subMenuHeader.find('.mainmenu-item')

        function init() {
            if (!$mainMenuElement.length) {
                return
            }

            setupResponsiveMenu()

            initListeners()
        }

        function updateScrollIndicator(el) {
            $(el.parentElement).toggleClass('scrolled', el.scrollTop > 0);
        }

        function initScrollablePanel(el) {
            el.addEventListener('scroll', function(evt) {
                updateScrollIndicator(evt.target)
            }, {
                capture: true,
                passive: true
            })
        }

        function initListeners() {
            initScrollablePanel($responsiveMainMenu.get(0))
            initScrollablePanel($responsiveSubMenu.get(0))

            $responsiveMainMenuPane.on('click', '.mainmenu-item', onMainMenuItemClick)
            $responsiveSubMenuPane.on('click', 'a.go-back-link', onCloseSubmenuClick)
            $responsiveMainMenuPane.on('click', 'a.close-link', onMenuMenuCloseClick)
        }

        function initMenuScroll($menu) {
            $menu.dragScroll({
                vertical: true,
                useNative: false,
                useDrag: true
            })
        }

        function setupResponsiveMenu() {
            initMenuScroll($responsiveMainMenu)
            initMenuScroll($responsiveSubMenu)

            $(document.body).on('click', '.mainmenu-items', function() {
                // Do not handle menu item clicks while dragging
                if ($(document.body).hasClass('drag')) {
                    return false
                }
            })
        }

        function onMainMenuItemClick(ev) {
            var $menuItem = $(ev.currentTarget),
                $item = $menuItem.closest('.mainmenu-item')

            if (!$item.hasClass('has-subitems')) {
                return
            }

            var submenuIndex = $item.data('submenuIndex'),
                $submenu = $menuContainer.find('.mainmenu-submenu-dropdown[data-submenu-index='+submenuIndex+']')

            $responsiveSubMenu.html($submenu.html())
            $subMenuHeaderMenuItem.html($menuItem.html())
            $subMenuHeader.attr('data-submenu-index', submenuIndex)

            $(document.body).addClass('responsive-submenu-displayed')

            ev.preventDefault()
        }

        function onCloseSubmenuClick(ev) {
            $(document.body).removeClass('responsive-submenu-displayed')

            ev.preventDefault()
        }

        function onMenuMenuCloseClick() {
            closeCallback()
        }

        this.show = function() {
            $responsiveMainMenu.dragScroll('goToStart')
            $responsiveSubMenu.dragScroll('goToStart')

            $(document.body).addClass('responsive-menu-displayed')
        }

        this.hide = function() {
            $(document.body).removeClass('responsive-menu-displayed');

            window.setTimeout(function() {
                $(document.body).removeClass('responsive-submenu-displayed')
            }, 250)
        }

        init()
    }

    $.oc.responsiveMenu = ResponsiveMenu
}(window.jQuery);