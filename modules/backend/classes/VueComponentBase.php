<?php namespace Backend\Classes;

use File;
use stdClass;
use SystemException;
use October\Rain\Extension\Extendable;

/**
 * Vue component base class.
 *
 * Each component must include two files:
 *   vuecomponents/mycomponents
 *   - partials/mycomponents.htm
 *   - assets/js/mycomponents.js
 *
 * The optional CSS file is loaded automatically if presented:
 *   vuecomponents/mycomponents
 *   - assets/css/mycomponents.css
 *
 * Components can have subcomponents. Each subcomponent
 * must be presented with a JavaScript file and partial.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class VueComponentBase extends Extendable
{
    use \System\Traits\ViewMaker;
    use \System\Traits\AssetMaker;

    /**
     * @var \Backend\Classes\Controller Backend controller object.
     */
    protected $controller;

    /**
     * @var array A list of Vue component class names required for this component.
     */
    protected $require = [];

    /**
     * @var array A list of subcomponents this component provides
     */
    private $subcomponents = [];

    /**
     * Constructor
     * @param \Backend\Classes\Controller $controller
     */
    public function __construct($controller)
    {
        $this->controller = $controller;
        $this->viewPath = $this->guessViewPath('/partials');
        $this->assetPath = $this->guessViewPath('/assets', true);

        /*
         * Prepare assets used by this widget.
         */
        $this->loadDependencyAssets();
        $this->loadDefaultAssets();
        $this->loadAssets();
        $this->registerSubcomponents();
        $this->prepareVars();

        parent::__construct();
    }

    /**
     * Renders the default component partial.
     */
    public function render()
    {
        return $this->makePartial($this->getComponentBaseName());
    }

    public function renderSubcomponent($name)
    {
        if (!array_key_exists($name, $this->subcomponents)) {
            throw new SystemException(sprintf('Subcomponent not registered: %s', $name));
        }

        $name = str_replace('.', '-', $name);
        return $this->makePartial($name);
    }

    public function getDependencies()
    {
        return $this->require;
    }

    public function getSubcomponents()
    {
        return array_keys($this->subcomponents);
    }

    protected function loadDefaultAssets()
    {
        $baseName = $this->getComponentBaseName();

        $this->addJsBundle('js/'.$baseName.'.js', 'core');

        $cssPath = 'css/'.$baseName.'.css';
        if (File::exists(base_path($this->assetPath.'/'.$cssPath))) {
            $this->addCssBundle($cssPath, 'core');
        }
    }

    /**
     * Prepares variables required by the component's partials
     */
    protected function prepareVars()
    {
    }

    /**
     * Adds component specific asset files. Use $this->addJs() and $this->addCss()
     * to register new assets to include on the page.
     * The default component script and CSS file are loaded automatically.
     * @return void
     */
    protected function loadAssets()
    {
    }

    /**
     * Adds dependency assets required for the component.
     * This method is called before the component's default resources are loaded.
     * Use $this->addJs() and $this->addCss() to register new assets to include
     * on the page.
     * @return void
     */
    protected function loadDependencyAssets()
    {
    }

    protected function getComponentBaseName()
    {
        $classNameArray = explode('\\', get_class($this));
        return strtolower(end($classNameArray));
    }

    /**
     * Adds a subcomponent.
     * @param string $name The component name.
     * A JavaScript file and partial with the same name must exist.
     */
    protected function registerSubcomponent($name)
    {
        $name = strtolower($name);

        $this->subcomponents[$name] = true;
        $this->addJsBundle('js/'.$name.'.js', 'core');
    }

    protected function registerSubcomponents()
    {
    }
}