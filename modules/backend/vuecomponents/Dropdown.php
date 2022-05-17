<?php namespace Backend\VueComponents;

use Backend\Classes\VueComponentBase;

/**
 * Document dropdown Vue component
 *
 * @link https://github.com/shentao/vue-multiselect
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class Dropdown extends VueComponentBase
{
    protected function loadDependencyAssets()
    {
        $this->addJs('vendor/vue-multiselect/vue-multiselect.min.js', 'core');
        $this->addCss('vendor/vue-multiselect/vue-multiselect.min.css', 'core');
    }
}
