<?php namespace Backend\VueComponents;

use Backend\Classes\VueComponentBase;

/**
 * Read-only information table Vue component
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class InfoTable extends VueComponentBase
{
    protected function registerSubcomponents()
    {
        $this->registerSubcomponent('item');
    }
}