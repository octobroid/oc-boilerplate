<?php namespace Backend\Classes;

use System\Classes\PluginManager;

/**
 * WidgetManager
 *
 * @method static WidgetManager instance()
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class WidgetManager
{
    use \October\Rain\Support\Traits\Singleton;
    use \Backend\Classes\WidgetManager\HasFormWidgets;
    use \Backend\Classes\WidgetManager\HasFilterWidgets;
    use \Backend\Classes\WidgetManager\HasReportWidgets;

    /**
     * @var System\Classes\PluginManager pluginManager
     */
    protected $pluginManager;

    /**
     * init initializes this singleton.
     */
    protected function init()
    {
        $this->pluginManager = PluginManager::instance();
    }
}
