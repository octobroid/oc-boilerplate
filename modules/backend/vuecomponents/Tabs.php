<?php namespace Backend\VueComponents;

use Backend\Classes\VueComponentBase;

/**
 * Tabs Vue component
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class Tabs extends VueComponentBase
{
    protected $require = [
        \Backend\VueComponents\DropdownMenu::class
    ];
}
