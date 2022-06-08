<?php namespace System\Classes;

use Backend;
use October\Contracts\Support\OctoberPackage;
use Illuminate\Support\ServiceProvider as ServiceProviderBase;
use ReflectionClass;
use SystemException;
use Yaml;

/**
 * PluginBase class
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class PluginBase extends ServiceProviderBase implements OctoberPackage
{
    /**
     * @var array require plugin dependencies.
     */
    public $require = [];

    /**
     * @var boolean disabled determines if this plugin should be loaded (false) or not (true).
     */
    public $disabled = false;

    /**
     * @var bool loadedYamlConfiguration
     */
    protected $loadedYamlConfiguration = false;

    /**
     * pluginDetails returns information about this plugin, including plugin name and developer name.
     *
     * @return array
     * @throws SystemException
     */
    public function pluginDetails()
    {
        $thisClass = get_class($this);

        $configuration = $this->getConfigurationFromYaml(sprintf(
            'Plugin configuration file plugin.yaml is not '.
            'found for the plugin class %s. Create the file or override pluginDetails() '.
            'method in the plugin class.',
            $thisClass
        ));

        if (array_key_exists('plugin', $configuration)) {
            return $configuration['plugin'];
        }

        throw new SystemException(sprintf(
            'The plugin configuration file plugin.yaml should contain the "plugin" section: %s.',
            $thisClass
        ));
    }

    /**
     * register method, called when the plugin is first registered.
     */
    public function register()
    {
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
    }

    /**
     * @inheritDoc
     */
    public function registerMarkupTags()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerComponents()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerContentFields()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerNavigation()
    {
        $configuration = $this->getConfigurationFromYaml();

        if (!array_key_exists('navigation', $configuration)) {
            return [];
        }

        $navigation = $configuration['navigation'];

        if (!is_array($navigation)) {
            return [];
        }

        array_walk_recursive($navigation, static function (&$item, $key) {
            if ($key === 'url') {
                $item = Backend::url($item);
            }
        });

        return $navigation;
    }

    /**
     * @inheritDoc
     */
    public function registerPermissions()
    {
        $configuration = $this->getConfigurationFromYaml();

        if (!array_key_exists('permissions', $configuration)) {
            return [];
        }

        return $configuration['permissions'];
    }

    /**
     * @inheritDoc
     */
    public function registerSettings()
    {
        $configuration = $this->getConfigurationFromYaml();

        if (!array_key_exists('settings', $configuration)) {
            return [];
        }

        $settings = $configuration['settings'];

        if (!is_array($settings)) {
            return [];
        }

        array_walk_recursive($settings, function (&$item, $key) {
            if ($key === 'url') {
                $item = Backend::url($item);
            }
        });

        return $settings;
    }

    /**
     * @inheritDoc
     */
    public function registerSchedule($schedule)
    {
    }

    /**
     * @inheritDoc
     */
    public function registerReportWidgets()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerFormWidgets()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerFilterWidgets()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerListColumnTypes()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerMailLayouts()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerMailTemplates()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerMailPartials()
    {
        return [];
    }

    /**
     * registerConsoleCommand registers a new console (artisan) command.
     * @param string $key The command name
     * @param string $class The command class
     * @return void
     */
    public function registerConsoleCommand($key, $class)
    {
        $key = 'command.'.$key;

        $this->app->singleton($key, $class);

        $this->commands($key);
    }

    /**
     * getConfigurationFromYaml reads configuration from YAML file.
     * @param string|null $exceptionMessage
     * @return array|bool
     * @throws SystemException
     */
    protected function getConfigurationFromYaml($exceptionMessage = null)
    {
        if ($this->loadedYamlConfiguration !== false) {
            return $this->loadedYamlConfiguration;
        }

        $reflection = new ReflectionClass(get_class($this));
        $yamlFilePath = dirname($reflection->getFileName()).'/plugin.yaml';

        if (file_exists($yamlFilePath)) {
            $this->loadedYamlConfiguration = Yaml::parse(file_get_contents($yamlFilePath));

            if (!is_array($this->loadedYamlConfiguration)) {
                throw new SystemException(sprintf(
                    'Invalid format of the plugin configuration file: %s. The file should define an array.',
                    $yamlFilePath
                ));
            }
        }
        else {
            if ($exceptionMessage !== null) {
                throw new SystemException($exceptionMessage);
            }

            $this->loadedYamlConfiguration = [];
        }

        return $this->loadedYamlConfiguration;
    }
}
