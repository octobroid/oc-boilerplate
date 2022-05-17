<?php namespace Cms\VueComponents;

use Backend\Classes\VueComponentBase;

/**
 * CMS layout editor Vue component
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class LayoutEditor extends VueComponentBase
{
    protected $require = [
        \Backend\VueComponents\MonacoEditor::class,
        \Cms\VueComponents\CmsObjectComponentList::class
    ];
}
