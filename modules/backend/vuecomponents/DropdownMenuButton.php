<?php namespace Backend\VueComponents;

use Backend\Classes\VueComponentBase;

/**
 * A generic button with a drop-down menu Vue component
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class DropdownMenuButton extends VueComponentBase
{
    protected $require = [
        \Backend\VueComponents\DropdownMenu::class
    ];
}
