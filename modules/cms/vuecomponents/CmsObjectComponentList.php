<?php namespace Cms\VueComponents;

use Backend\Classes\VueComponentBase;

/**
 * CMS object component list - Vue component
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class CmsObjectComponentList extends VueComponentBase
{
    protected function registerSubcomponents()
    {
        $this->registerSubcomponent('component');
    }
}
