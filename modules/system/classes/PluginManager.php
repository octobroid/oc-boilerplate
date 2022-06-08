<?php namespace System\Classes;

use Db;
use App;
use Str;
use File;
use Log;
use Config;
use Schema;
use System;
use Manifest;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use SystemException;
use Throwable;

/**
 * PluginManager
 *
 * @method static PluginManager instance()
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class PluginManager
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * @var const keys for manifest storage
     */
    const MANIFEST_PLUGINS = 'plugins.all';
    const MANIFEST_DISABLED = 'plugins.disabled';

    /**
     * @var App app instance, since Plugins are an extension of a Service Provider
     */
    protected $app;

    /**
     * @var array plugins container object used for storing plugin information objects.
     */
    protected $plugins;

    /**
     * @var array pathMap of plugins and their directory paths.
     */
    protected $pathMap = [];

    /**
     * @var bool registered check if all plugins have had the register() method called.
     */
    protected $registered = false;

    /**
     * @var bool booted check if all plugins have had the boot() method called.
     */
    protected $booted = false;

    /**
     * @var array disabledPlugins collection of disabled plugins
     */
    protected $disabledPlugins = [];

    /**
     * @var array registrationMethodCache cache of registration method results.
     */
    protected $registrationMethodCache = [];

    /**
     * init initializes the plugin manager
     */
    protected function init()
    {
        $this->bindContainerObjects();
        $this->loadDisabled();
        $this->loadPlugins();

        if ($this->app->runningInBackend()) {
            $this->loadDependencies();
        }
    }

    /**
     * bindContainerObjects rebinds to the container because these objects are
     * "soft singletons" and may be lost when the IoC container reboots.
     * This provides a way to rebuild for the purposes of unit testing.
     */
    public function bindContainerObjects()
    {
        $this->app = App::make('app');
    }

    /**
     * loadPlugins finds all available plugins and loads them in to the $plugins array
     */
    public function loadPlugins(): array
    {
        $this->plugins = [];

        // Locate all plugins and binds them to the container
        foreach ($this->getPluginNamespaces() as $namespace => $path) {
            $this->loadPlugin($namespace, plugins_path($path));
        }

        $this->sortDependencies();

        return $this->plugins;
    }

    /**
     * loadPlugin loads a single plugin in to the manager where a namespace is Acme\Blog
     * and the path is somewhere on the disk
     */
    public function loadPlugin(string $namespace, string $path)
    {
        $className = $namespace.'\Plugin';
        $classPath = $path.'/Plugin.php';

        try {
            // Autoloader failed?
            if (!class_exists($className)) {
                include_once $classPath;
            }

            // Not a valid plugin!
            if (!class_exists($className)) {
                return;
            }

            $classObj = new $className($this->app);
        }
        catch (Throwable $e) {
            Log::error('Plugin ' . $className . ' could not be instantiated.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return;
        }

        $classId = $this->getIdentifier($classObj);

        // Check for disabled plugins
        if ($this->isDisabled($classId)) {
            $classObj->disabled = true;
        }

        $this->plugins[$classId] = $classObj;
        $this->pathMap[$classId] = $path;

        return $classObj;
    }

    /**
     * registerAll runs the register() method on all plugins and can only be called once
     */
    public function registerAll($force = false)
    {
        if ($this->registered && !$force) {
            return;
        }

        foreach ($this->plugins as $pluginId => $plugin) {
            $this->registerPlugin($plugin, $pluginId);
        }

        $this->registered = true;
    }

    /**
     * unregisterAll unregisters all plugins: the negative of registerAll()
     */
    public function unregisterAll()
    {
        $this->registered = false;
        $this->plugins = [];
    }

    /**
     * registerPlugin for a single object
     * @param PluginBase $plugin
     * @param string $pluginId
     * @return void
     */
    public function registerPlugin($plugin, $pluginId = null)
    {
        if (!$pluginId) {
            $pluginId = $this->getIdentifier($plugin);
        }

        if (!$plugin || $plugin->disabled) {
            return;
        }

        $pluginPath = $this->getPluginPath($plugin);
        $pluginNamespace = strtolower($pluginId);

        // Register plugin class autoloaders
        $autoloadPath = $pluginPath . '/vendor/autoload.php';
        if (is_file($autoloadPath)) {
            ComposerManager::instance()->autoload($pluginPath . '/vendor');
        }

        $plugin->register();

        // Register configuration path
        $configPath = $pluginPath . '/config';
        if (is_dir($configPath)) {
            Config::package($pluginNamespace, $configPath, $pluginNamespace);
        }

        // Register views path
        $viewsPath = $pluginPath . '/views';
        if (is_dir($viewsPath)) {
            $this->callAfterResolving('view', function ($view) use ($pluginNamespace, $viewsPath) {
                $view->addNamespace($pluginNamespace, $viewsPath);
            });
        }

        // Register language namespaces
        $langPath = $pluginPath . '/lang';
        if (is_dir($langPath)) {
            $this->callAfterResolving('translator', function ($translator) use ($pluginNamespace, $langPath) {
                $translator->addNamespace($pluginNamespace, $langPath);
                if (App::runningInBackend()) {
                    $translator->addJsonPath($langPath);
                }
            });
        }

        // Add init, if available
        $initFile = $pluginPath . '/init.php';
        if (file_exists($initFile)) {
            require $initFile;
        }

        // Add routes, if available
        $routesFile = $pluginPath . '/routes.php';
        if (!$this->app->routesAreCached() && file_exists($routesFile)) {
            require $routesFile;
        }
    }

    /**
     * bootAll runs the boot() method on all plugins. Can only be called once.
     */
    public function bootAll($force = false)
    {
        if ($this->booted && !$force) {
            return;
        }

        foreach ($this->plugins as $pluginId => $plugin) {
            $this->bootPlugin($plugin, $pluginId);
        }

        $this->booted = true;
    }

    /**
     * bootPlugin registers a single plugin object.
     * @param PluginBase $plugin
     * @return void
     */
    public function bootPlugin($plugin, $pluginId = null)
    {
        if (!$pluginId) {
            $pluginId = $this->getIdentifier($plugin);
        }

        if (!$plugin || $plugin->disabled) {
            return;
        }

        $plugin->boot();
    }

    /**
     * callAfterResolving sets up an after resolving listener, or fire immediately
     * if already resolved.
     */
    protected function callAfterResolving($name, $callback)
    {
        $this->app->afterResolving($name, $callback);

        if ($this->app->resolved($name)) {
            $callback($this->app->make($name), $this->app);
        }
    }

    /**
     * getPluginPath returns the directory path to a plugin
     */
    public function getPluginPath($id)
    {
        $classId = $this->getIdentifier($id);

        if (!isset($this->pathMap[$classId])) {
            return null;
        }

        return File::normalizePath($this->pathMap[$classId]);
    }

    /**
     * getComposerCode finds the composer code for a plugin
     */
    public function getComposerCode($id)
    {
        $path = $this->getPluginPath($id);
        $file = $path . '/composer.json';

        if (!$path || !file_exists($file)) {
            return null;
        }

        $info = json_decode(File::get($file), true);

        return $info['name'] ?? null;
    }

    /**
     * exists checks if a plugin exists and is enabled
     * @param   string $id Plugin identifier, eg: Namespace.PluginName
     * @return  boolean
     */
    public function exists($id)
    {
        return !(!$this->findByIdentifier($id) || $this->isDisabled($id));
    }

    /**
     * getPlugins an array with all registered plugins
     * The index is the plugin namespace, the value is the plugin information object.
     */
    public function getPlugins()
    {
        return array_diff_key((array) $this->plugins, $this->disabledPlugins);
    }

    /**
     * getAllPlugins regardless of enabled state
     */
    public function getAllPlugins(): ?array
    {
        return $this->plugins;
    }

    /**
     * findByNamespace returns a plugin registration class based on
     * its namespace (Author\Plugin)
     */
    public function findByNamespace($namespace)
    {
        if (!$this->hasPlugin($namespace)) {
            return null;
        }

        $classId = $this->getIdentifier($namespace);

        return $this->plugins[$classId];
    }

    /**
     * findByIdentifier returns a plugin registration class based on
     * its identifier (Author.Plugin)
     */
    public function findByIdentifier($identifier)
    {
        if (!isset($this->plugins[$identifier])) {
            $identifier = $this->normalizeIdentifier($identifier);
        }

        if (!isset($this->plugins[$identifier])) {
            return null;
        }

        return $this->plugins[$identifier];
    }

    /**
     * hasPlugin checks to see if a plugin has been registered
     */
    public function hasPlugin($namespace)
    {
        $classId = $this->getIdentifier($namespace);

        $normalized = $this->normalizeIdentifier($classId);

        return isset($this->plugins[$normalized]);
    }

    /**
     * getPluginNamespaces returns a flat array of vendor plugin namespaces and their paths
     */
    public function getPluginNamespaces()
    {
        if (Manifest::has(self::MANIFEST_PLUGINS)) {
            return (array) Manifest::get(self::MANIFEST_PLUGINS);
        }

        $classNames = [];

        foreach ($this->getVendorAndPluginNames() as $vendorName => $vendorList) {
            foreach ($vendorList as $pluginName => $pluginPath) {
                $namespace = ucfirst($vendorName).'\\'.ucfirst($pluginName);
                $classNames[$namespace] = $pluginPath;
            }
        }

        Manifest::put(self::MANIFEST_PLUGINS, $classNames);

        return $classNames;
    }

    /**
     * getVendorAndPluginNames returns a 2 dimensional array of vendors and their plugins.
     */
    public function getVendorAndPluginNames()
    {
        $plugins = [];

        $dirPath = plugins_path();
        if (!is_dir($dirPath)) {
            return $plugins;
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::FOLLOW_SYMLINKS)
        );
        $it->setMaxDepth(2);
        $it->rewind();

        while ($it->valid()) {
            if (($it->getDepth() > 1) && $it->isFile() && (strtolower($it->getFilename()) === "plugin.php")) {
                $filePath = dirname($it->getPathname());
                $pluginName = basename($filePath);
                $vendorName = basename(dirname($filePath));
                $plugins[$vendorName][$pluginName] = "$vendorName/$pluginName";
            }

            $it->next();
        }

        return $plugins;
    }

    /**
     * getIdentifier resolves a plugin identifier from a plugin class name or object.
     * @param mixed Plugin class name or object
     * @return string Identifier in format of Vendor.Plugin
     */
    public function getIdentifier($namespace)
    {
        $namespace = Str::normalizeClassName($namespace);
        if (strpos($namespace, '\\') === null) {
            return $namespace;
        }

        $parts = explode('\\', $namespace);
        $slice = array_slice($parts, 0, 2);
        $namespace = implode('.', $slice);

        return $namespace;
    }

    /**
     * normalizeIdentifier takes a human plugin code (acme.blog) and makes it authentic (Acme.Blog)
     * @param  string $id
     * @return string
     */
    public function normalizeIdentifier($identifier)
    {
        foreach ($this->plugins as $id => $object) {
            if (strtolower($id) === strtolower($identifier)) {
                return $id;
            }
        }

        return $identifier;
    }

    /**
     * getRegistrationMethodValues spins over every plugin object and collects the results
     * of a method call.
     * @param  string $methodName
     * @return array
     */
    public function getRegistrationMethodValues($methodName)
    {
        if (isset($this->registrationMethodCache[$methodName])) {
            return $this->registrationMethodCache[$methodName];
        }

        $results = [];

        // Load module items
        foreach (System::listModules() as $module) {
            if ($provider = App::getProvider($module . '\\ServiceProvider')) {
                if (method_exists($provider, $methodName)) {
                    $results['October.'.$module] = $provider->{$methodName}();
                }
            }
        }

        // Load plugin items
        foreach ($this->getPlugins() as $id => $plugin) {
            if (method_exists($plugin, $methodName)) {
                $results[$id] = $plugin->{$methodName}();
            }
        }

        // Load app items
        if ($app = App::getProvider(\App\Provider::class)) {
            if (method_exists($app, $methodName)) {
                $results['October.App'] = $app->{$methodName}();
            }
        }

        return $this->registrationMethodCache[$methodName] = $results;
    }

    //
    // Disability
    //

    /**
     * listDisabledByConfig
     */
    public function listDisabledByConfig(): array
    {
        $disablePlugins = Config::get('system.disable_plugins');

        if (!$disablePlugins) {
            return [];
        }
        elseif (is_array($disablePlugins)) {
            return $disablePlugins;
        }
        else {
            return array_map('trim', explode(',', (string) $disablePlugins));
        }
    }

    /**
     * clearDisabledCache
     */
    public function clearDisabledCache()
    {
        Manifest::forget(self::MANIFEST_DISABLED);
        $this->disabledPlugins = [];
    }

    /**
     * loadDisabled loads all disables plugins from the meta file.
     */
    protected function loadDisabled()
    {
        foreach ($this->listDisabledByConfig() as $disabled) {
            $this->disabledPlugins[$disabled] = true;
        }

        if (Manifest::has(self::MANIFEST_DISABLED)) {
            $this->disabledPlugins = (array) Manifest::get(self::MANIFEST_DISABLED);
        }
        else {
            $this->populateDisabledPluginsFromDb();
            $this->writeDisabled();
        }
    }

    /**
     * isDisabled determines if a plugin is disabled by looking at the meta information
     * or the application configuration.
     * @return boolean
     */
    public function isDisabled($id)
    {
        $code = $this->getIdentifier($id);

        if (array_key_exists($code, $this->disabledPlugins)) {
            return true;
        }

        return false;
    }

    /**
     * writeDisabled writes the disabled plugins to a meta file.
     */
    protected function writeDisabled()
    {
        Manifest::put(self::MANIFEST_DISABLED, $this->disabledPlugins);
    }

    /**
     * populateDisabledPluginsFromDb populates information about disabled plugins from database
     * @return void
     */
    protected function populateDisabledPluginsFromDb()
    {
        if (!$this->app->hasDatabase()) {
            return;
        }

        if (!Schema::hasTable('system_plugin_versions')) {
            return;
        }

        $disabled = Db::table('system_plugin_versions')->where('is_disabled', 1)->pluck('code')->all();

        foreach ($disabled as $code) {
            $this->disabledPlugins[$code] = true;
        }
    }

    /**
     * disablePlugin disables a single plugin in the system.
     * @param string $id Plugin code/namespace
     * @param bool $isUser Set to true if disabled by the user
     * @return bool
     */
    public function disablePlugin($id, $isUser = false)
    {
        $code = $this->getIdentifier($id);
        if (array_key_exists($code, $this->disabledPlugins)) {
            return false;
        }

        $this->disabledPlugins[$code] = $isUser;
        $this->writeDisabled();

        if ($pluginObj = $this->findByIdentifier($code)) {
            $pluginObj->disabled = true;
        }

        return true;
    }

    /**
     * enablePlugin enables a single plugin in the system.
     * @param string $id Plugin code/namespace
     * @param bool $isUser Set to true if enabled by the user
     * @return bool
     */
    public function enablePlugin($id, $isUser = false)
    {
        $code = $this->getIdentifier($id);
        if (!array_key_exists($code, $this->disabledPlugins)) {
            return false;
        }

        // Prevent system from enabling plugins disabled by the user
        if (!$isUser && $this->disabledPlugins[$code] === true) {
            return false;
        }

        unset($this->disabledPlugins[$code]);
        $this->writeDisabled();

        if ($pluginObj = $this->findByIdentifier($code)) {
            $pluginObj->disabled = false;
        }

        return true;
    }

    //
    // Dependencies
    //

    /**
     * findMissingDependencies scans the system plugins to locate any dependencies that are not
     * currently installed. Returns an array of plugin codes that are needed.
     *
     *     PluginManager::instance()->findMissingDependencies();
     *
     * @return array
     */
    public function findMissingDependencies()
    {
        $missing = [];

        foreach ($this->plugins as $id => $plugin) {
            $required = $this->getDependencies($plugin);
            if (!$required) {
                continue;
            }

            foreach ($required as $require) {
                if ($this->hasPlugin($require)) {
                    continue;
                }

                if (!in_array($require, $missing)) {
                    $missing[] = $require;
                }
            }
        }

        return $missing;
    }

    /**
     * loadDependencies cross checks all plugins and their dependancies, if not met plugins
     * are disabled and vice versa.
     * @return void
     */
    protected function loadDependencies()
    {
        foreach ($this->plugins as $id => $plugin) {
            $required = $this->getDependencies($plugin);
            if (!$required) {
                continue;
            }

            $disable = false;

            foreach ($required as $require) {
                if (!$pluginObj = $this->findByIdentifier($require)) {
                    $disable = true;
                }
                elseif ($pluginObj->disabled) {
                    $disable = true;
                }
            }

            if ($disable) {
                $this->disablePlugin($id);
            }
            else {
                $this->enablePlugin($id);
            }
        }
    }

    /**
     * sortDependencies sorts a collection of plugins, in the order that they should be actioned,
     * according to their given dependencies. Least dependent come first.
     * @return array Collection of sorted plugin identifiers
     */
    protected function sortDependencies()
    {
        ksort($this->plugins);

        // Canvas the dependency tree
        $checklist = $this->plugins;
        $result = [];

        $loopCount = 0;
        while (count($checklist)) {
            if (++$loopCount > 2048) {
                throw new SystemException('Too much recursion! Check for circular dependencies in your plugins.');
            }

            foreach ($checklist as $code => $plugin) {

                // Get dependencies and remove any aliens
                $depends = $this->getDependencies($plugin) ?: [];
                $depends = array_filter($depends, function ($pluginCode) {
                    return isset($this->plugins[$pluginCode]);
                });

                // No dependencies
                if (!$depends) {
                    array_push($result, $code);
                    unset($checklist[$code]);
                    continue;
                }

                // Find dependencies that have not been checked
                $depends = array_diff($depends, $result);
                if (count($depends) > 0) {
                    continue;
                }

                // All dependencies are checked
                array_push($result, $code);
                unset($checklist[$code]);
            }
        }

        // Reassemble plugin map
        $sortedPlugins = [];

        foreach ($result as $code) {
            $sortedPlugins[$code] = $this->plugins[$code];
        }

        return $this->plugins = $sortedPlugins;
    }

    /**
     * getDependencies returns the plugin identifiers that are required by the supplied plugin.
     * @param  string $plugin Plugin identifier, object or class
     * @return array
     */
    public function getDependencies($plugin)
    {
        if (is_string($plugin) && (!$plugin = $this->findByIdentifier($plugin))) {
            return false;
        }

        if (!isset($plugin->require) || !$plugin->require) {
            return null;
        }

        return is_array($plugin->require) ? $plugin->require : [$plugin->require];
    }

    //
    // Management
    //

    /**
     * deletePlugin completely roll back and delete a plugin from the system.
     * @param string $id Plugin code/namespace
     * @return void
     */
    public function deletePlugin($id)
    {
        // Rollback plugin
        UpdateManager::instance()->rollbackPlugin($id);

        // Delete from file system
        if ($pluginPath = self::instance()->getPluginPath($id)) {
            File::deleteDirectory($pluginPath);
        }
    }

    /**
     * refreshPlugin tears down a plugin's database tables and rebuilds them.
     * @param string $id Plugin code/namespace
     * @return void
     */
    public function refreshPlugin($id)
    {
        $manager = UpdateManager::instance();
        $manager->rollbackPlugin($id);
        $manager->updatePlugin($id);
    }
}
