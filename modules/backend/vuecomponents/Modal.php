<?php namespace Backend\VueComponents;

use Backend\Classes\VueComponentBase;

/**
 * Modal dialog Vue component.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class Modal extends VueComponentBase
{
    /**
     * Adds component specific asset files. Use $this->addJs() and $this->addCss()
     * to register new assets to include on the page.
     * The default component script and CSS file are loaded automatically.
     * @return void
     */
    protected function loadAssets()
    {
        $this->addJsBundle('js/modal-position.js', 'core');
        $this->addJsBundle('js/modal-size.js', 'core');
        $this->addJsBundle('js/modal-utils.js', 'core');
    }

    protected function registerSubcomponents()
    {
        $this->registerSubcomponent('alert');
        $this->registerSubcomponent('confirm');
    }
}
