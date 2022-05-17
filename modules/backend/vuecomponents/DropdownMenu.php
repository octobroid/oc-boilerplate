<?php namespace Backend\VueComponents;

use Backend\Classes\VueComponentBase;

/**
 * Dropdown menu Vue component
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class DropdownMenu extends VueComponentBase
{
    /**
     * Adds component specific asset files. Use $this->addJs() and $this->addCss()
     * to register new assets to include on the page.
     * The default component script and CSS file are loaded automatically.
     * @return void
     */
    protected function loadAssets()
    {
        $this->addJsBundle('js/dropdownmenu-utils.js', 'core');
    }

    protected function registerSubcomponents()
    {
        $this->registerSubcomponent('sheet');
        $this->registerSubcomponent('menuitem');
    }
}
