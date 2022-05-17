<?php namespace Backend\VueComponents;

use Backend\Classes\VueComponentBase;

/**
 * Autocomplete Vue component
 *
 * @link https://github.com/trevoreyre/autocomplete
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class Autocomplete extends VueComponentBase
{
    protected function loadDependencyAssets()
    {
        $this->addJs('vendor/vue-autocomplete/vue-autocomplete.min.js', 'core');
        $this->addCss('vendor/vue-autocomplete/vue-autocomplete.min.css', 'core');
    }
}
