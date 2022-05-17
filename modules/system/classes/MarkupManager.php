<?php namespace System\Classes;

use Str;
use Twig\TokenParser\AbstractTokenParser as TwigTokenParser;
use Twig\TwigFilter as TwigSimpleFilter;
use Twig\TwigFunction as TwigSimpleFunction;
use ApplicationException;

/**
 * MarkupManager class manages Twig functions, token parsers and filters.
 *
 * @method static MarkupManager instance()
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class MarkupManager
{
    use \October\Rain\Support\Traits\Singleton;

    const EXTENSION_FILTER = 'filters';
    const EXTENSION_FUNCTION = 'functions';
    const EXTENSION_TOKEN_PARSER = 'tokens';

    /**
     * @var array Cache of registration callbacks.
     */
    protected $callbacks = [];

    /**
     * @var array[MarkupExtentionItem[]] Globally registered extension items
     */
    protected $items;

    /**
     * @var \System\Classes\PluginManager
     */
    protected $pluginManager;

    /**
     * Initialize this singleton.
     */
    protected function init()
    {
        $this->pluginManager = PluginManager::instance();
    }

    /**
     * loadExtensions parses all registrations and adds them to this class
     */
    protected function loadExtensions(): void
    {
        // Load module items
        foreach ($this->callbacks as $callback) {
            $callback($this);
        }

        // Load plugin items
        $plugins = $this->pluginManager->getPlugins();

        foreach ($plugins as $id => $plugin) {
            $items = $plugin->registerMarkupTags();
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $type => $definitions) {
                if (!is_array($definitions)) {
                    continue;
                }

                $this->registerExtensions($type, $definitions);
            }
        }
    }

    /**
     * Registers a callback function that defines simple Twig extensions.
     * The callback function should register menu items by calling the manager's
     * `registerFunctions`, `registerFilters`, `registerTokenParsers` function.
     * The manager instance is passed to the callback function as an argument. Usage:
     *
     *     MarkupManager::registerCallback(function ($manager) {
     *         $manager->registerFilters([...]);
     *         $manager->registerFunctions([...]);
     *         $manager->registerTokenParsers([...]);
     *     });
     *
     * @param callable $callback A callable function.
     */
    public function registerCallback(callable $callback)
    {
        $this->callbacks[] = $callback;
    }

    /**
     * Registers the CMS Twig extension items.
     * The argument is an array of the extension definitions. The array keys represent the
     * function/filter name, specific for the plugin/module. Each element in the
     * array should be an associative array.
     * @param string $type The extension type: filters, functions, tokens
     * @param array $definitions An array of the extension definitions.
     */
    public function registerExtensions($type, array $definitions)
    {
        if ($this->items === null) {
            $this->items = [];
        }

        if (!array_key_exists($type, $this->items)) {
            $this->items[$type] = [];
        }

        foreach ($definitions as $name => $definition) {
            $item = $this->defineMarkupExtensionItem([
                'name' => $name,
                'type' => $type,
                'definition' => $definition,
            ]);

            switch ($type) {
                case self::EXTENSION_TOKEN_PARSER:
                    $this->items[$type][] = $item;
                    break;
                case self::EXTENSION_FILTER:
                case self::EXTENSION_FUNCTION:
                    $this->items[$type][$name] = $item;
                    break;
            }
        }
    }

    /**
     * defineMarkupExtensionItem
     */
    protected function defineMarkupExtensionItem(array $config): MarkupExtensionItem
    {
        return (new MarkupExtensionItem)->useConfig($config);
    }

    /**
     * Registers a CMS Twig Filter
     * @param array $definitions An array of the extension definitions.
     */
    public function registerFilters(array $definitions)
    {
        $this->registerExtensions(self::EXTENSION_FILTER, $definitions);
    }

    /**
     * Registers a CMS Twig Function
     * @param array $definitions An array of the extension definitions.
     */
    public function registerFunctions(array $definitions)
    {
        $this->registerExtensions(self::EXTENSION_FUNCTION, $definitions);
    }

    /**
     * Registers a CMS Twig Token Parser
     * @param array $definitions An array of the extension definitions.
     */
    public function registerTokenParsers(array $definitions)
    {
        $this->registerExtensions(self::EXTENSION_TOKEN_PARSER, $definitions);
    }

    /**
     * Returns a list of the registered Twig extensions of a type.
     * @param $type string The Twig extension type
     * @return array
     */
    public function listExtensions($type)
    {
        $results = [];

        if ($this->items === null) {
            $this->loadExtensions();
        }

        if (isset($this->items[$type]) && is_array($this->items[$type])) {
            $results = $this->items[$type];
        }

        return $results;
    }

    /**
     * Returns a list of the registered Twig filters.
     * @return array
     */
    public function listFilters()
    {
        return $this->listExtensions(self::EXTENSION_FILTER);
    }

    /**
     * Returns a list of the registered Twig functions.
     * @return array
     */
    public function listFunctions()
    {
        return $this->listExtensions(self::EXTENSION_FUNCTION);
    }

    /**
     * Returns a list of the registered Twig token parsers.
     * @return array
     */
    public function listTokenParsers()
    {
        return $this->listExtensions(self::EXTENSION_TOKEN_PARSER);
    }

    /**
     * makeTwigFunctions makes a set of Twig functions for use in a twig extension.
     * @param  array $functions Current collection
     * @return array
     */
    public function makeTwigFunctions($functions = [])
    {
        if (!is_array($functions)) {
            $functions = [];
        }

        foreach ($this->listFunctions() as $item) {
            $functions[] = new TwigSimpleFunction(
                $item->name,
                $this->makeItemCallback($item),
                $item->getTwigOptions()
            );
        }

        return $functions;
    }

    /**
     * makeTwigFilters makes a set of Twig filters for use in a twig extension.
     * @param  array $filters Current collection
     * @return array
     */
    public function makeTwigFilters($filters = [])
    {
        if (!is_array($filters)) {
            $filters = [];
        }

        foreach ($this->listFilters() as $item) {
            $filters[] = new TwigSimpleFilter(
                $item->name,
                $this->makeItemCallback($item),
                $item->getTwigOptions()
            );
        }

        return $filters;
    }

    /**
     * makeItemCallback handles wildcard call
     */
    protected function makeItemCallback($item)
    {
        // Handle a wildcard function
        if (strpos($item->name, '*') !== false && $item->isWildCallable()) {
            return function ($name) use ($item) {
                $arguments = array_slice(func_get_args(), 1);
                $method = $item->getWildCallback(Str::camel($name));
                return call_user_func_array($method, $arguments);
            };
        }

        // Cannot call item method
        $callable = $item->callback;
        if (!is_callable($callable)) {
            throw new ApplicationException("The markup filter/function for '{$item->name}' is not callable.");
        }

        // Wrap in a closure to prevent Twig from reflecting facades
        // when applying its named closure support
        return function(...$args) use ($callable) {
            return $callable(...$args);
        };
    }

    /**
     * Makes a set of Twig token parsers for use in a twig extension.
     * @param  array $parsers Current collection
     * @return array
     */
    public function makeTwigTokenParsers($parsers = [])
    {
        if (!is_array($parsers)) {
            $parsers = [];
        }

        foreach ($this->listTokenParsers() as $item) {
            if (!$item->callback instanceof TwigTokenParser) {
                continue;
            }

            $parsers[] = $item->callback;
        }

        return $parsers;
    }
}
