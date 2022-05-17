<?php namespace Backend\VueComponents;

use Backend\Classes\VueComponentBase;

/**
 * Universal file uploader Vue component.
 *
 * JavaScript dependencies:
 *
 * - modules/editor/assets/vendor/bluebirdjs/bluebird.min.js
 * - modules/editor/assets/vendor/promise-queue/promise-queue.js
 *
 * Promise cancellation must be enabled.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class Uploader extends VueComponentBase
{
    protected $require = [
        \Backend\VueComponents\ScrollablePanel::class,
        \Backend\VueComponents\LoadingIndicator::class
    ];

    /**
     * Adds dependency assets required for the component.
     * This method is called before the component's default resources are loaded.
     * Use $this->addJs() and $this->addCss() to register new assets to include
     * on the page.
     * @return void
     */
    protected function loadDependencyAssets()
    {
        $this->addJsBundle('js/utils.js', 'core');
        $this->addJsBundle('js/queue.js', 'core');
        $this->addJsBundle('js/file.js', 'core');
    }

    protected function registerSubcomponents()
    {
        $this->registerSubcomponent('item');
    }
}
