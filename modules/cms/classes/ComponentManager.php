<?php namespace Cms\Classes;

use App;
use Str;
use Config;
use System\Classes\PluginManager;
use SystemException;

/**
 * ComponentManager
 *
 * @method static ComponentManager instance()
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class ComponentManager
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * @var array callbacks for registration.
     */
    protected $callbacks = [];

    /**
     * @var array codeMap where keys are codes and values are class names.
     */
    protected $codeMap;

    /**
     * @var array classMap where keys are class names and values are codes.
     */
    protected $classMap;

    /**
     * @var array ownerDetailsMap with owner information about a component.
     */
    protected $ownerDetailsMap;

    /**
     * @var array ownerMap where keys are class name and values are owner class.
     */
    protected $ownerMap;

    /**
     * @var array detailsCache array of component details.
     */
    protected $detailsCache;

    /**
     * loadComponents scans each plugin an loads it's components.
     * @return void
     */
    protected function loadComponents()
    {
        // Load module components
        foreach ($this->callbacks as $callback) {
            $callback($this);
        }

        // Load plugin components
        $pluginManager = PluginManager::instance();
        $plugins = $pluginManager->getPlugins();

        foreach ($plugins as $plugin) {
            $components = $plugin->registerComponents();
            if (!is_array($components)) {
                continue;
            }

            foreach ($components as $className => $code) {
                $this->registerComponent($className, $code, $plugin);
            }
        }
    }

    /**
     * registerComponents manually registers a component for consideration. Usage:
     *
     *     ComponentManager::registerComponents(function ($manager) {
     *         $manager->registerComponent(\October\Demo\Components\Test::class, 'testComponent');
     *     });
     *
     * @return array Array values are class names.
     */
    public function registerComponents(callable $definitions)
    {
        $this->callbacks[] = $definitions;
    }

    /**
     * registerComponent registers a single component.
     */
    public function registerComponent($className, $code = null, $owner = null)
    {
        if (!$this->classMap) {
            $this->classMap = [];
        }

        if (!$this->codeMap) {
            $this->codeMap = [];
        }

        if (!$code) {
            $code = Str::getClassId($className);
        }

        if ($code === 'viewBag' && $className !== \Cms\Components\ViewBag::class) {
            throw new SystemException(sprintf(
                'The component code viewBag is reserved. Please use another code for the component class %s.',
                $className
            ));
        }

        $className = Str::normalizeClassName($className);

        $this->codeMap[$code] = $className;

        $this->classMap[$className] = $code;

        if ($owner !== null) {
            if ($owner instanceof \System\Classes\PluginBase) {
                $this->setComponentOwnerAsPlugin($code, $className, $owner);
            }
            else {
                $this->setComponentOwnerAsModule($code, $className, $owner);
            }
        }
    }

    /**
     * setComponentOwnerAsPlugin
     */
    protected function setComponentOwnerAsPlugin(string $code, string $className, $pluginObj): void
    {
        $ownerClass = get_class($pluginObj);

        if (!isset($this->ownerDetailsMap[$ownerClass])) {
            $this->ownerDetailsMap[$ownerClass] = [
                'details' => $pluginObj->pluginDetails(),
                'components' => []
            ];
        }

        $this->ownerMap[$className] = $ownerClass;
        $this->ownerDetailsMap[$ownerClass]['components'][$code] = $className;
    }

    /**
     * setComponentOwnerAsModule
     */
    protected function setComponentOwnerAsModule(string $code, string $className, $moduleObj): void
    {
        $ownerClass = get_class($moduleObj);

        if (!isset($this->ownerDetailsMap[$ownerClass])) {
            $moduleName = substr($ownerClass, 0, strrpos($ownerClass, '\\'));
            $this->ownerDetailsMap[$ownerClass] = [
                'details' => [
                    'name' => class_basename($moduleName),
                    'icon' => 'icon-puzzle-piece'
                ],
                'components' => []
            ];
        }

        $this->ownerMap[$className] = $ownerClass;
        $this->ownerDetailsMap[$ownerClass]['components'][$code] = $className;
    }

    /**
     * listComponents returns a list of registered components.
     * @return array Array keys are codes, values are class names.
     */
    public function listComponents()
    {
        if ($this->codeMap === null) {
            $this->loadComponents();
        }

        return $this->codeMap;
    }

    /**
     * listComponentDetails returns an array of all component detail definitions.
     * @return array Array keys are component codes, values are the details defined in the component.
     */
    public function listComponentDetails()
    {
        if ($this->detailsCache !== null) {
            return $this->detailsCache;
        }

        $details = [];
        foreach ($this->listComponents() as $componentAlias => $componentClass) {
            $details[$componentAlias] = $this->makeComponent($componentClass)->componentDetails();
        }

        return $this->detailsCache = $details;
    }

    /**
     * listComponentOwnerDetails returns the components grouped by owner and injects the owner details.
     */
    public function listComponentOwnerDetails()
    {
        $details = $this->listComponentDetails();
        if (!$this->ownerDetailsMap) {
            return [];
        }

        $owners = $this->ownerDetailsMap;
        foreach ($this->ownerDetailsMap as $ownerClass => $ownerArr) {
            $components = $ownerArr['components'] ?? [];
            foreach ($components as $code => $className) {
                $detailsArr = $details[$code] ?? [];
                $owners[$ownerClass]['components'][$code] = ['className' => $className] + $detailsArr;
            }
        }

        return $owners;
    }

    /**
     * resolve returns a class name from a component code
     * Normalizes a class name or converts an code to it's class name.
     * @return string The class name resolved, or null.
     */
    public function resolve($name)
    {
        $codes = $this->listComponents();

        if (isset($codes[$name])) {
            return $codes[$name];
        }

        $name = Str::normalizeClassName($name);
        if (isset($this->classMap[$name])) {
            return $name;
        }

        return null;
    }

    /**
     * hasComponent checks to see if a component has been registered.
     * @param string $name A component class name or code.
     * @return bool Returns true if the component is registered, otherwise false.
     */
    public function hasComponent($name)
    {
        $className = $this->resolve($name);
        if (!$className) {
            return false;
        }

        return isset($this->classMap[$className]);
    }

    /**
     * makeComponent object with properties set.
     * @param string $name A component class name or code.
     * @param CmsObject $cmsObject The Cms object that spawned this component.
     * @param array $properties The properties set by the Page or Layout.
     * @return ComponentBase|null The component object.
     */
    public function makeComponent($name, $cmsObject = null, $properties = [])
    {
        $className = $this->resolve($name);
        if (!$className) {
            $strictMode = Config::get('cms.strict_components', false);
            if ($strictMode) {
                throw new SystemException(sprintf(
                    'Class name is not registered for the component "%s". Check the component plugin.',
                    $name
                ));
            }
            else {
                return null;
            }
        }

        if (!class_exists($className)) {
            throw new SystemException(sprintf(
                'Component class not found "%s". Check the component plugin.',
                $className
            ));
        }

        $component = App::make($className, [$cmsObject, $properties]);
        $component->name = $name;

        return $component;
    }

    /**
     * findComponentOwnerDetails returns details about the component owner as an array.
     */
    public function findComponentOwnerDetails($component): array
    {
        $className = Str::normalizeClassName(get_class($component));

        if (isset($this->ownerMap[$className])) {
            $ownerClass = $this->ownerMap[$className];
            return $this->ownerDetailsMap[$ownerClass]['details'] ?? [];
        }

        return [];
    }

    /**
     * findComponentPlugin returns a parent plugin for a specific component object.
     * @param mixed $component A component to find the plugin for.
     * @return mixed Returns the plugin object or null.
     * @deprecated use findComponentOwnerDetails instead
     */
    public function findComponentPlugin($component)
    {
        $className = Str::normalizeClassName(get_class($component));
        if (isset($this->ownerMap[$className])) {
            return PluginManager::instance()->findByNamespace($this->ownerMap[$className]);
        }

        return null;
    }
}
