<?php namespace Editor\VueComponents;

use Backend\Classes\VueComponentBase;

/**
 * Editor Application component
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class Application extends VueComponentBase
{
    protected $require = [
        \Editor\VueComponents\Navigator::class,
        \Editor\VueComponents\DocumentInfoPopup::class,
        \Backend\VueComponents\LoadingIndicator::class
    ];
}
