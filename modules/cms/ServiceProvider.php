<?php namespace Cms;

use App;
use Event;
use Backend;
use BackendAuth;
use Cms\Models\ThemeLog;
use Cms\Models\ThemeData;
use Cms\Classes\CmsObject;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\ThemeManager;
use Cms\Classes\CmsObjectCache;
use Cms\Classes\ComponentManager;
use Backend\Models\UserRole;
use Backend\Classes\WidgetManager;
use System\Classes\SettingsManager;
use October\Rain\Support\ModuleServiceProvider;

/**
 * ServiceProvider for CMS module
 */
class ServiceProvider extends ModuleServiceProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        parent::register('cms');

        $this->registerConsole();
        $this->registerComponents();
        $this->registerThemeLogging();
        $this->registerCombinerEvents();
        $this->registerHalcyonModels();

        /*
         * Backend specific
         */
        if (App::runningInBackend()) {
            $this->registerBackendReportWidgets();
            $this->registerBackendPermissions();
            $this->registerBackendWidgets();
            $this->registerBackendSettings();
        }

        CmsObjectCache::flush();
    }

    /**
     * boot the module events.
     */
    public function boot()
    {
        parent::boot('cms');

        $this->bootEditorEvents();
        $this->bootMenuItemEvents();
        $this->bootRichEditorEvents();

        if (App::runningInBackend()) {
            $this->bootThemesForBackend();
        }
    }

    /**
     * registerConsole for command line specifics
     */
    protected function registerConsole()
    {
        $this->registerConsoleCommand('theme.install', \Cms\Console\ThemeInstall::class);
        $this->registerConsoleCommand('theme.remove', \Cms\Console\ThemeRemove::class);
        $this->registerConsoleCommand('theme.list', \Cms\Console\ThemeList::class);
        $this->registerConsoleCommand('theme.use', \Cms\Console\ThemeUse::class);
        $this->registerConsoleCommand('theme.copy', \Cms\Console\ThemeCopy::class);
        $this->registerConsoleCommand('theme.check', \Cms\Console\ThemeCheck::class);
    }

    /**
     * registerComponents
     */
    protected function registerComponents()
    {
        ComponentManager::instance()->registerComponents(function ($manager) {
            $manager->registerComponent(\Cms\Components\ViewBag::class, 'viewBag');
            $manager->registerComponent(\Cms\Components\Resources::class, 'resources');
        });
    }

    /**
     * registerThemeLogging on templates
     */
    protected function registerThemeLogging()
    {
        CmsObject::extend(function ($model) {
            ThemeLog::bindEventsToModel($model);
        });
    }

    /**
     * Registers events for the asset combiner.
     */
    protected function registerCombinerEvents()
    {
        if (App::runningInBackend() || App::runningInConsole()) {
            return;
        }

        Event::listen('cms.combiner.beforePrepare', function ($combiner, $assets) {
            $filters = array_flatten($combiner->getFilters());
            ThemeData::applyAssetVariablesToCombinerFilters($filters);
        });

        Event::listen('cms.combiner.getCacheKey', function ($combiner, $holder) {
            $holder->key = $holder->key . ThemeData::getCombinerCacheKey();
        });
    }

    /*
     * Register report widgets
     */
    protected function registerBackendReportWidgets()
    {
        WidgetManager::instance()->registerReportWidgets(function ($manager) {
            $manager->registerReportWidget(\Cms\ReportWidgets\ActiveTheme::class, [
                'label' => 'cms::lang.dashboard.active_theme.widget_title_default',
                'context' => 'dashboard'
            ]);
        });
    }

    /*
     * Register permissions
     */
    protected function registerBackendPermissions()
    {
        BackendAuth::registerCallback(function ($manager) {
            $manager->registerPermissions('October.Cms', [
                'cms.manage_content' => [
                    'label' => 'cms::lang.permissions.manage_content',
                    'tab' => 'cms::lang.permissions.name',
                    'roles' => UserRole::CODE_DEVELOPER,
                    'order' => 100
                ],
                'cms.manage_assets' => [
                    'label' => 'cms::lang.permissions.manage_assets',
                    'tab' => 'cms::lang.permissions.name',
                    'roles' => UserRole::CODE_DEVELOPER,
                    'order' => 100
                ],
                'cms.manage_pages' => [
                    'label' => 'cms::lang.permissions.manage_pages',
                    'tab' => 'cms::lang.permissions.name',
                    'roles' => UserRole::CODE_DEVELOPER,
                    'order' => 100
                ],
                'cms.manage_layouts' => [
                    'label' => 'cms::lang.permissions.manage_layouts',
                    'tab' => 'cms::lang.permissions.name',
                    'roles' => UserRole::CODE_DEVELOPER,
                    'order' => 100
                ],
                'cms.manage_partials' => [
                    'label' => 'cms::lang.permissions.manage_partials',
                    'tab' => 'cms::lang.permissions.name',
                    'roles' => UserRole::CODE_DEVELOPER,
                    'order' => 100
                ],
                'cms.manage_themes' => [
                    'label' => 'cms::lang.permissions.manage_themes',
                    'tab' => 'cms::lang.permissions.name',
                    'roles' => UserRole::CODE_DEVELOPER,
                    'order' => 100
                ],
                'cms.manage_theme_options' => [
                    'label' => 'cms::lang.permissions.manage_theme_options',
                    'tab' => 'cms::lang.permissions.name',
                    'order' => 100
                ],
            ]);
        });
    }

    /**
     * registerBackendWidgets
     */
    protected function registerBackendWidgets()
    {
        // @deprecated
        WidgetManager::instance()->registerFormWidgets(function ($manager) {
            $manager->registerFormWidget(\Cms\FormWidgets\Components::class);
        });
    }

    /*
     * Register settings
     */
    protected function registerBackendSettings()
    {
        SettingsManager::instance()->registerCallback(function ($manager) {
            $manager->registerSettingItems('October.Cms', [
                'theme' => [
                    'label' => 'cms::lang.theme.settings_menu',
                    'description' => 'cms::lang.theme.settings_menu_description',
                    'category' => SettingsManager::CATEGORY_CMS,
                    'icon' => 'octo-icon-text-image',
                    'url' => Backend::url('cms/themes'),
                    'permissions' => ['cms.manage_themes', 'cms.manage_theme_options'],
                    'order' => 200
                ],
                'maintenance_settings' => [
                    'label' => 'cms::lang.maintenance.settings_menu',
                    'description' => 'cms::lang.maintenance.settings_menu_description',
                    'category' => SettingsManager::CATEGORY_CMS,
                    'icon' => 'octo-icon-power',
                    'class' => \Cms\Models\MaintenanceSetting::class,
                    'permissions' => ['cms.manage_themes'],
                    'order' => 300
                ],
                'theme_logs' => [
                    'label' => 'cms::lang.theme_log.menu_label',
                    'description' => 'cms::lang.theme_log.menu_description',
                    'category' => SettingsManager::CATEGORY_LOGS,
                    'icon' => 'icon-magic',
                    'url' => Backend::url('cms/themelogs'),
                    'permissions' => ['system.access_logs'],
                    'order' => 910,
                    'keywords' => 'theme change log'
                ]
            ]);
        });
    }

    /**
     * Registers events for menu items.
     */
    protected function bootMenuItemEvents()
    {
        Event::listen('pages.menuitem.listTypes', function () {
            return [
                'cms-page' => 'cms::lang.page.cms_page'
            ];
        });

        Event::listen('pages.menuitem.getTypeInfo', function ($type) {
            if ($type === 'cms-page') {
                return CmsPage::getMenuTypeInfo($type);
            }
        });

        Event::listen('pages.menuitem.resolveItem', function ($type, $item, $url, $theme) {
            if ($type === 'cms-page') {
                return CmsPage::resolveMenuItem($item, $url, $theme);
            }
        });
    }

    /**
     * Registers events for rich editor page links.
     */
    protected function bootRichEditorEvents()
    {
        Event::listen('backend.richeditor.listTypes', function () {
            return [
                'cms-page' => 'cms::lang.page.cms_page'
            ];
        });

        Event::listen('backend.richeditor.getTypeInfo', function ($type) {
            if ($type === 'cms-page') {
                return CmsPage::getRichEditorTypeInfo($type);
            }
        });
    }

    /**
     * bootThemesForBackend localization from an active theme for backend items.
     */
    protected function bootThemesForBackend()
    {
        ThemeManager::instance()->bootAllBackend();
    }

    /**
     * Registers the models to be made available to the theme database layer
     */
    protected function registerHalcyonModels()
    {
        Event::listen('system.console.theme.sync.getAvailableModelClasses', function () {
            return [
                \Cms\Classes\Meta::class,
                \Cms\Classes\Page::class,
                \Cms\Classes\Layout::class,
                \Cms\Classes\Content::class,
                \Cms\Classes\Partial::class
            ];
        });
    }

    /**
     * Handle Editor events
     */
    protected function bootEditorEvents()
    {
        Event::listen('editor.extension.register', function () {
            return \Cms\Classes\EditorExtension::class;
        });
    }
}
