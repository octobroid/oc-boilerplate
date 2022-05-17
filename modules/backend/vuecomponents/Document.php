<?php namespace Backend\VueComponents;

use Backend\Classes\VueComponentBase;

/**
 * Document UI entity Vue component
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class Document extends VueComponentBase
{
    protected $require = [
        \Backend\VueComponents\DropdownMenu::class,
        \Backend\VueComponents\LoadingIndicator::class
    ];

    protected function registerSubcomponents()
    {
        $this->registerSubcomponent('toolbar');
        $this->registerSubcomponent('toolbarButton');
        $this->registerSubcomponent('header');
    }
}
