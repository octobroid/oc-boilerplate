<?php namespace Cms\Classes;

use App;
use Url;
use File;
use Lang;
use Yaml;
use Cache;
use Event;
use Config;
use Exception;
use BackendAuth;
use SystemException;
use DirectoryIterator;
use ApplicationException;
use Cms\Models\ThemeData;
use System\Models\Parameter;
use Backend\Models\UserPreference;
use October\Rain\Halcyon\Datasource\DbDatasource;
use October\Rain\Halcyon\Datasource\AutoDatasource;
use October\Rain\Halcyon\Datasource\FileDatasource;
use October\Rain\Halcyon\Datasource\DatasourceInterface;

/**
 * Theme class represents the CMS theme
 * CMS theme is a directory that contains all CMS objects - pages, layouts, partials and asset files..
 * The theme parameters are specified in the theme.ini file in the theme root directory.
 *
 * @package october\cms
 * @author Alexey Bobkov, Samuel Georges
 */
class Theme
{
    /**
     * @var string dirName specifies the theme directory name
     */
    protected $dirName;

    /**
     * @var mixed configCache keeps the cached configuration file values
     */
    protected $configCache;

    /**
     * @var mixed activeThemeCache in memory
     */
    protected static $activeThemeCache = false;

    /**
     * @var mixed editThemeCache in memory
     */
    protected static $editThemeCache = false;

    const ACTIVE_KEY = 'cms::theme.active';
    const EDIT_KEY = 'cms::theme.edit';

    /**
     * load the theme
     */
    public static function load($dirName): Theme
    {
        $theme = new static;

        $theme->setDirName((string) $dirName);

        $theme->registerHalyconDatasource();

        return $theme;
    }

    /**
     * getPath returns the absolute theme path.
     */
    public function getPath(string $dirName = null): string
    {
        if (!$dirName) {
            $dirName = $this->getDirName();
        }

        return themes_path().'/'.$dirName;
    }

    /**
     * setDirName sets the theme directory name
     */
    public function setDirName(string $dirName): void
    {
        $this->dirName = $dirName;
    }

    /**
     * getDirName returns the theme directory name
     */
    public function getDirName(): string
    {
        return (string) $this->dirName;
    }

    /**
     * getId is a helper for {{ theme.id }} twig vars that returns a unique
     * string for this theme
     */
    public function getId(): string
    {
        return snake_case(str_replace('/', '-', $this->getDirName()));
    }

    /**
     * exists determines if a theme with given directory name exists
     */
    public static function exists(string $dirName): bool
    {
        if (strlen(trim($dirName)) === 0) {
            return false;
        }

        $theme = static::load($dirName);

        $path = $theme->getPath();

        return File::isDirectory($path);
    }

    /**
     * listPages returns a list of pages in the theme
     * This method is used internally in the routing process and in the backend UI.
     * Skipping cache indicates if the pages should be reloaded from the disk bypassing the cache.
     */
    public function listPages(bool $skipCache = false)
    {
        return Page::listInTheme($this, $skipCache);
    }

    /**
     * isActiveTheme returns true if this theme is the chosen active theme
     */
    public function isActiveTheme(): bool
    {
        if ($activeThemeCode = self::getActiveThemeCode()) {
            return $activeThemeCode === $this->getDirName();
        }

        return false;
    }

    /**
     * getActiveThemeCode returns the active theme code.
     *
     * By default the active theme is loaded from the cms.active_theme config item.
     * If there's a back-end user session, loads the theme code from the CMS Editor
     * Edit Theme user preference.
     *
     * This behavior can be overridden by the cms.theme.getActiveTheme event listener.
     */
    public static function getActiveThemeCode(): ?string
    {
        /**
         * @event cms.theme.getActiveTheme
         * Overrides the active theme code.
         *
         * If a value is returned from this halting event, it will be used as the active
         * theme code. Example usage:
         *
         *     Event::listen('cms.theme.getActiveTheme', function () {
         *         return 'mytheme';
         *     });
         *
         */
        $apiResult = Event::fire('cms.theme.getActiveTheme', [], true);
        if ($apiResult !== null) {
            return $apiResult;
        }

        $activeTheme = $activeFromConfig = Config::get('cms.active_theme');

        // Backend override
        // @todo This needs performance review, use a session marker so
        // normal users are not constantly checking -sg
        if (App::hasDatabase() && BackendAuth::getUser()) {
            try {
                $prefTheme = UserPreference::forUser()->get(Theme::EDIT_KEY, null);
            }
            catch (Exception $ex) {
                $prefTheme = null;
            }

            if ($prefTheme !== null && static::exists($prefTheme)) {
                return $prefTheme;
            }
        }

        // Check cache
        try {
            $cached = Cache::get(self::ACTIVE_KEY, false);
            if ($cached !== false) {
                $cached = @json_decode($cached, true);
                if ($cached && $cached['config'] === $activeFromConfig) {
                    return $cached['active'];
                }
            }
        }
        catch (Exception $ex) {
            // Cache failed
        }

        // Proceed with expensive lookup
        if (App::hasDatabase()) {
            try {
                $dbResult = Parameter::applyKey(self::ACTIVE_KEY)->value('value');
            }
            catch (Exception $ex) {
                $dbResult = null;
            }

            if ($dbResult !== null && static::exists($dbResult)) {
                $activeTheme = $dbResult;
            }
        }

        if (!strlen($activeTheme)) {
            throw new SystemException(Lang::get('cms::lang.theme.active.not_set'));
        }

        // Cache outcome
        try {
            Cache::put(
                self::ACTIVE_KEY,
                json_encode([
                    'active' => $activeTheme,
                    'config' => $activeFromConfig
                ]),
                now()->addMinutes(1440)
            );
        }
        catch (Exception $ex) {
            // Cache failed
        }

        return $activeTheme;
    }

    /**
     * getActiveTheme returns the active theme object
     */
    public static function getActiveTheme(): ?Theme
    {
        if (self::$activeThemeCache !== false) {
            return self::$activeThemeCache;
        }

        $theme = static::load($themeCode = static::getActiveThemeCode());

        if ($theme->isLocked()) {
            throw new ApplicationException(Lang::get('cms::lang.theme.active.is_locked', ['theme' => $themeCode]));
        }

        if (!File::isDirectory($theme->getPath())) {
            return self::$activeThemeCache = null;
        }

        return self::$activeThemeCache = $theme;
    }

    /**
     * setActiveTheme sets the active theme
     *
     * The active theme code is stored in the database and overrides the
     * configuration cms.active_theme config item.
     */
    public static function setActiveTheme(string $code)
    {
        if (($theme = static::load($code)) && $theme->isLocked()) {
            throw new ApplicationException(Lang::get('cms::lang.theme.active.is_locked', ['theme' => $code]));
        }

        Parameter::set(self::ACTIVE_KEY, $code);

        self::resetCache();

        /**
         * @event cms.theme.setActiveTheme
         * Fires when the active theme has been changed.
         *
         * Example usage:
         *
         *     Event::listen('cms.theme.setActiveTheme', function ($code) {
         *         \Log::info("Theme has been changed to $code");
         *     });
         *
         */
        Event::fire('cms.theme.setActiveTheme', compact('code'));
    }

    /**
     * getEditThemeCode returns the edit theme code
     *
     * There are several ways the edit theme can be set. The code loads
     * the edit theme from the following sources:
     * → CMS Editor edit theme for the currently authenticated back-end user, if any.
     * → cms.edit_theme config item.
     * → CMS Active theme.
     * → cms.theme.getEditTheme event. The theme code returned by the
     * event handler has the highest priority.
     */
    public static function getEditThemeCode(): ?string
    {
        /**
         * @event cms.theme.getEditTheme
         * Overrides the edit theme code.
         *
         * If a value is returned from this halting event, it will be used as the edit
         * theme code. Example usage:
         *
         *     Event::listen('cms.theme.getEditTheme', function () {
         *         return "the-edit-theme-code";
         *     });
         *
         */
        $apiResult = Event::fire('cms.theme.getEditTheme', [], true);
        if ($apiResult !== null) {
            return $apiResult;
        }

        $editTheme = null;

        if (BackendAuth::getUser()) {
            $editTheme = UserPreference::forUser()->get(Theme::EDIT_KEY, null);
        }

        if (!$editTheme) {
            $editTheme = Config::get('cms.edit_theme');
        }

        if (!$editTheme) {
            $editTheme = static::getActiveThemeCode();
        }

        if (!strlen($editTheme)) {
            throw new SystemException(Lang::get('cms::lang.theme.edit.not_set'));
        }

        return $editTheme;
    }

    /**
     * getEditTheme returns the edit theme
     */
    public static function getEditTheme(): ?Theme
    {
        if (self::$editThemeCache !== false) {
            return self::$editThemeCache;
        }

        $theme = static::load(static::getEditThemeCode());

        if (!File::isDirectory($theme->getPath())) {
            return self::$editThemeCache = null;
        }

        return self::$editThemeCache = $theme;
    }

    /**
     * setEditTheme sets the editing theme
     */
    public static function setEditTheme(string $code)
    {
        UserPreference::forUser()->set(Theme::EDIT_KEY, $code);

        self::resetCache();

        /**
         * @event cms.theme.setEditTheme
         * Fires when the edit theme has been changed.
         *
         * Example usage:
         *
         *     Event::listen('cms.theme.setActiveTheme', function ($code) {
         *         \Log::info("Theme has been changed to $code");
         *     });
         *
         */
        Event::fire('cms.theme.setEditTheme', compact('code'));
    }

    /**
     * all returns all themes on disk
     */
    public static function all(): array
    {
        $it = new DirectoryIterator(themes_path());
        $it->rewind();

        $result = [];
        foreach ($it as $fileinfo) {
            if (!$fileinfo->isDir() || $fileinfo->isDot()) {
                continue;
            }

            $theme = static::load($fileinfo->getFilename());

            $result[] = $theme;
        }

        return $result;
    }

    /**
     * allAvailable returns all available themes, those that are not locked
     */
    public static function allAvailable(): array
    {
        $themes = [];

        foreach (self::all() as $theme) {
            if ($theme->isLocked()) {
                continue;
            }
            $themes[] = $theme;
        }

        return $themes;
    }

    /**
     * getConfig reads the theme.yaml file and returns the theme configuration values
     */
    public function getConfig(): array
    {
        if ($this->configCache !== null) {
            return $this->configCache;
        }

        $path = $this->getPath().'/theme.yaml';
        if (!File::exists($path)) {
            $config = [];
        }
        else {
            $config = (array) Yaml::parseFileCached($path);
        }

        /**
         * @event cms.theme.extendConfig
         * Extend basic theme configuration supplied by the theme by returning an array.
         *
         * Note if planning on extending form fields, use the `cms.theme.extendFormConfig`
         * event instead.
         *
         * Example usage:
         *
         *     Event::listen('cms.theme.extendConfig', function ($themeCode, &$config) {
         *          $config['name'] = 'October Theme';
         *          $config['description'] = 'Another great theme from October CMS';
         *     });
         *
         */
        Event::fire('cms.theme.extendConfig', [$this->getDirName(), &$config]);

        return $this->configCache = $config;
    }

    /**
     * getFormConfig returns the dedicated `form` option that provide form fields
     * for customization, this is an immutable accessor for that and also an
     * solid anchor point for extension
     */
    public function getFormConfig(): array
    {
        if ($this->hasParentTheme()) {
            $parentTheme = $this->getParentTheme();

            try {
                $config = $this->getConfigArray('form') ?: $parentTheme->getFormConfig();
            }
            catch (Exception $ex) {
                $config = $parentTheme->getFormConfig();
            }
        }
        else {
            $config = $this->getConfigArray('form');
        }

        /**
         * @event cms.theme.extendFormConfig
         * Extend form field configuration supplied by the theme by returning an array.
         *
         * Example usage:
         *
         *     Event::listen('cms.theme.extendFormConfig', function ($themeCode, &$config) {
         *          array_set($config, 'tabs.fields.header_color', [
         *              'label'           => 'Header Colour',
         *              'type'            => 'colorpicker',
         *              'availableColors' => [#34495e, #708598, #3498db],
         *              'assetVar'        => 'header-bg',
         *              'tab'             => 'Global'
         *          ]);
         *     });
         *
         */
        Event::fire('cms.theme.extendFormConfig', [$this->getDirName(), &$config]);

        return $config;
    }

    /**
     * getConfigValue returns a value from the theme configuration file by its name
     */
    public function getConfigValue(string $name, $default = null)
    {
        return array_get($this->getConfig(), $name, $default);
    }

    /**
     * getConfigArray returns an array value from the theme configuration file by its name
     *
     * If the value is a string, it is treated as a YAML file and loaded.
     */
    public function getConfigArray(string $name): array
    {
        $result = array_get($this->getConfig(), $name, []);

        if (is_string($result)) {
            $fileName = File::symbolizePath($result);

            if (File::isLocalPath($fileName)) {
                $path = $fileName;
            }
            else {
                $path = $this->getPath().'/'.$result;
            }

            if (!File::exists($path)) {
                throw new ApplicationException('Path does not exist: '.$path);
            }

            $result = Yaml::parseFileCached($path);
        }

        return (array) $result;
    }

    /**
     * writeConfig to the theme.yaml file with the supplied array values
     */
    public function writeConfig(array $values = [], bool $overwrite = false)
    {
        if (!$overwrite) {
            $values = $values + (array) $this->getConfig();
        }

        $path = $this->getPath().'/theme.yaml';

        if (!File::exists($path)) {
            throw new ApplicationException('Path does not exist: '.$path);
        }

        $contents = Yaml::render($values);

        File::put($path, $contents);

        $this->writeComposerFile($values);

        $this->configCache = $values;
    }

    /**
     * writeComposerFile writes to a composer file for a theme
     */
    protected function writeComposerFile(array $data)
    {
        $author = strtolower(trim(array_get($data, 'authorCode')));
        $code = strtolower(trim(array_get($data, 'code')));
        $description = array_get($data, 'description');
        $path = $this->getPath();

        if (!$description) {
            $description = array_get($data, 'name');
        }

        // Abort
        if (!$path || !$author || !$code) {
            return;
        }

        $composerArr = [
            'name' => $author.'/'.$code.'-theme',
            'type' => 'october-theme',
            'description' => $description,
            'require' => [
                'composer/installers' => '~1.0'
            ]
        ];

        File::put(
            $path.'/composer.json',
            json_encode($composerArr, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)
        );
    }

    /**
     * getPreviewImageUrl returns the theme preview image URL
     *
     * If the image file doesn't exist returns the placeholder image URL.
     */
    public function getPreviewImageUrl(): string
    {
        $previewPath = $this->getConfigValue('previewImage', 'assets/images/theme-preview.png');

        if (File::exists($this->getPath().'/'.$previewPath)) {
            return Url::asset('themes/'.$this->getDirName().'/'.$previewPath);
        }

        if ($this->hasParentTheme()) {
            return $this->getParentTheme()->getPreviewImageUrl();
        }

        return Url::asset('modules/cms/assets/images/default-theme-preview.png');
    }

    /**
     * isLocked returns true if the theme cannot be used
     */
    public function isLocked(): bool
    {
        return File::isFile($this->getPath().'/.themelock');
    }

    /**
     * resetCache resets any memory or cache involved with the active or edit theme
     */
    public static function resetCache()
    {
        self::$activeThemeCache = false;
        self::$editThemeCache = false;

        Cache::forget(self::ACTIVE_KEY);
        Cache::forget(self::EDIT_KEY);
    }

    /**
     * hasCustomData returns true if this theme has form fields that supply customization data
     */
    public function hasCustomData(): bool
    {
        $form = $this->getConfigValue('form', false);

        if (!$form && $this->hasParentTheme()) {
            $form = $this->getParentTheme()->hasCustomData();
        }

        return (bool) $form;
    }

    /**
     * getCustomData returns data specific to this theme
     */
    public function getCustomData(): ThemeData
    {
        return ThemeData::forTheme($this);
    }

    /**
     * removeCustomData removes data specific to this theme
     */
    public function removeCustomData(): bool
    {
        if ($this->hasCustomData()) {
            return $this->getCustomData()->delete();
        }

        return true;
    }

    /**
     * databaseLayerEnabled checks global and local config
     */
    public function databaseLayerEnabled(): bool
    {
        // Globally
        $enableDbLayer = Config::get('cms.database_templates', false);

        // @deprecated
        if ($enableDbLayer === null) {
            $enableDbLayer = !Config::get('app.debug', false);
        }

        // Locally
        if (!$enableDbLayer) {
            $enableDbLayer = $this->getConfigValue('database', false);
        }

        return $enableDbLayer && App::hasDatabase();
    }

    /**
     * hasParentTheme checks if a parent theme is defined
     */
    public function hasParentTheme(): bool
    {
        return (bool) $this->getConfigValue('parent');
    }

    /**
     * getParentTheme returns a parent theme, if enabled
     */
    public function getParentTheme(): ?Theme
    {
        if (!$this->hasParentTheme()) {
            return null;
        }

        return static::load((string) $this->getConfigValue('parent'));
    }

    /**
     * secondLayerEnabled is true if two or more datasources exist
     */
    public function secondLayerEnabled(): bool
    {
        // All changes going to the database
        if ($this->databaseLayerEnabled()) {
            return true;
        }

        // Has an unlocked parent
        if (($parent = $this->getParentTheme()) && !$parent->isLocked()) {
            return true;
        }

        return false;
    }

    /**
     * useParentAsset determines if a parent asset should be used
     */
    public function useParentAsset($relativePath): bool
    {
        return $this->hasParentTheme() && !File::exists($this->getPath().'/'.$relativePath);
    }

    /**
     * registerHalyconDatasource ensures this theme is registered as a Halcyon datasource
     */
    public function registerHalyconDatasource()
    {
        $resolver = App::make('halcyon');

        // Already registered
        if ($resolver->hasDatasource($this->dirName)) {
            return;
        }

        $datasources = [];

        // Database layer
        if ($this->databaseLayerEnabled()) {
            $datasources[] = new DbDatasource($this->dirName, 'cms_theme_templates');
        }

        // Current / child theme
        $datasources[] = new FileDatasource($this->getPath(), App::make('files'));

        // Parent theme
        if ($parentTheme = $this->getParentTheme()) {
            $datasources[] = new FileDatasource($parentTheme->getPath(), App::make('files'));
        }

        $resolver->addDatasource($this->dirName, new AutoDatasource($datasources));
    }

    /**
     * getDatasource returns the theme's datasource
     */
    public function getDatasource(): DatasourceInterface
    {
        $resolver = App::make('halcyon');

        return $resolver->datasource($this->getDirName());
    }

    /**
     * getParentOptions returns dropdown options for a parent theme
     */
    public function getParentOptions(): array
    {
        $result = [
            '' => Lang::get('cms::lang.theme.no_parent'),
        ];

        foreach (static::all() as $theme) {
            if ($theme->getDirName() === $this->getDirName()) {
                continue;
            }

            if ($theme->isLocked()) {
                $label = $theme->getConfigValue('name').' ('.$theme->getDirName().'*)';
            }
            else {
                $label = $theme->getConfigValue('name').' ('.$theme->getDirName().')';
            }

            $result[$theme->getDirName()] = $label;
        }

        return $result;
    }

    /**
     * __get magic
     */
    public function __get($name)
    {
        if ($this->hasCustomData()) {
            return $this->getCustomData()->{$name};
        }

        return null;
    }

    /**
     * __isset magic
     */
    public function __isset($key)
    {
        if ($this->hasCustomData()) {
            $theme = $this->getCustomData();
            return $theme->offsetExists($key);
        }

        return false;
    }
}
