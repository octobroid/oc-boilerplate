<?php namespace Backend\VueComponents;

use Backend\Classes\VueComponentBase;

/**
 * Treeview Vue component.
 *
 * See NodeDefinition for node options.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class TreeView extends VueComponentBase
{
    protected $require = [
        \Backend\VueComponents\ScrollablePanel::class,
        \Backend\VueComponents\DropdownMenu::class,
        \Backend\VueComponents\Modal::class
    ];

    /**
     * Adds component specific asset files. Use $this->addJs() and $this->addCss()
     * to register new assets to include on the page.
     * The default component script and CSS file are loaded automatically.
     * @return void
     */
    protected function loadAssets()
    {
        $this->addJsBundle('js/treeview-navigation.js', 'core');
        $this->addJsBundle('js/treeview-utils.js', 'core');
        $this->addJsBundle('js/treeview-draganddrop.js', 'core');
        $this->addJsBundle('js/treeview-selection.js', 'core');
    }

    protected function registerSubcomponents()
    {
        $this->registerSubcomponent('node');
        $this->registerSubcomponent('section');
        $this->registerSubcomponent('quickaccess');
    }
}
