<?php namespace Backend\Classes;

use Lang;
use ApplicationException;
use October\Rain\Extension\ExtensionBase;
use System\Traits\ViewMaker;

/**
 * ControllerBehavior base class
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class ControllerBehavior extends ExtensionBase
{
    use \Backend\Traits\WidgetMaker;
    use \Backend\Traits\SessionMaker;
    use \System\Traits\AssetMaker;
    use \System\Traits\ConfigMaker;
    use \System\Traits\ViewMaker {
        ViewMaker::makeFileContents as localMakeFileContents;
    }

    /**
     * @var object Supplied configuration.
     */
    protected $config;

    /**
     * @var \Backend\Classes\Controller Reference to the back end controller.
     */
    protected $controller;

    /**
     * @var array Properties that must exist in the controller using this behavior.
     */
    protected $requiredProperties = [];

    /**
     * @var array Visible actions in context of the controller. Only takes effect if it is an array
     */
    protected $actions;

    /**
     * __construct the behavior
     */
    public function __construct($controller)
    {
        $this->controller = $controller;
        $this->viewPath = $this->configPath = $this->guessViewPath('/partials');
        $this->assetPath = $this->guessViewPath('/assets', true);

        /*
         * Validate controller properties
         */
        foreach ($this->requiredProperties as $property) {
            if (!isset($controller->{$property})) {
                throw new ApplicationException(Lang::get('system::lang.behavior.missing_property', [
                    'class' => get_class($controller),
                    'property' => $property,
                    'behavior' => get_called_class()
                ]));
            }
        }

        /*
         * Hide all methods that aren't explicitly listed as actions
         */
        if (is_array($this->actions)) {
            $this->hideAction(array_diff(get_class_methods(get_class($this)), $this->actions));
        }

        /**
         * Constructor logic that is protected by authentication
         */
        $controller->bindEvent('page.beforeDisplay', function() {
            $this->beforeDisplay();
        });
    }

    /**
     * beforeDisplay fires before the page is displayed and AJAX is executed.
     */
    public function beforeDisplay()
    {
    }

    /**
     * setConfig sets the configuration values
     * @param mixed $config   Config object or array
     * @param array $required Required config items
     */
    public function setConfig($config, $required = [])
    {
        $this->config = $this->makeConfig($config, $required);
    }

    /**
     * getConfig is a safe accessor for configuration values
     * @param string $name Config name, supports array names like "field[key]"
     * @param mixed $default Default value if nothing is found
     * @return string
     */
    public function getConfig($name = null, $default = null)
    {
        if (!$this->config) {
            return $default;
        }

        return $this->getConfigValueFrom($this->config, $name, $default);
    }

    /**
     * hideAction protects a public method from being available as an controller action.
     * These methods could be defined in a controller to override a behavior default action.
     * Such methods should be defined as public, to allow the behavior object to access it.
     * By default public methods of a controller are considered as actions.
     * To prevent this occurrence, methods should be hidden by using this method.
     * @param mixed $methodName Specifies a method name.
     */
    protected function hideAction($methodName)
    {
        if (!is_array($methodName)) {
            $methodName = [$methodName];
        }

        $this->controller->hiddenActions = array_merge($this->controller->hiddenActions, $methodName);
    }

    /**
     * makeFileContents makes all views in context of the controller, not the behavior.
     * @param string $filePath Absolute path to the view file.
     * @param array $extraParams Parameters that should be available to the view.
     * @return string
     */
    public function makeFileContents($filePath, $extraParams = [])
    {
        $this->controller->vars = array_merge($this->controller->vars, $this->vars);

        return $this->controller->makeFileContents($filePath, $extraParams);
    }

    /**
     * controllerMethodExists returns true in case if a specified method exists in the
     * extended controller.
     * @param string $methodName Specifies the method name
     * @return bool
     */
    protected function controllerMethodExists($methodName)
    {
        return method_exists($this->controller, $methodName);
    }
}
