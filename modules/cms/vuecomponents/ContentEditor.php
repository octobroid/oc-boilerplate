<?php namespace Cms\VueComponents;

use Backend\Classes\VueComponentBase;

/**
 * CMS content block editor Vue component
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class ContentEditor extends VueComponentBase
{
    protected $require = [
        \Backend\VueComponents\MonacoEditor::class,
        \Cms\VueComponents\CmsObjectComponentList::class,
        \Backend\VueComponents\RichEditorDocumentConnector::class,
        \Backend\VueComponents\DocumentMarkdownEditor::class
    ];
}
