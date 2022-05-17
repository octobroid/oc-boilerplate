<?php namespace Cms\VueComponents;

use Backend\Classes\VueComponentBase;

/**
 * CMS asset editor Vue component
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class AssetEditor extends VueComponentBase
{
    protected $require = [
        \Backend\VueComponents\MonacoEditor::class
    ];
}
