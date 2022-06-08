<?php namespace Cms;

use Event;
use Backend;
use Cms\Models\ThemeLog;
use Cms\Models\ThemeData;
use Cms\Classes\CmsObject;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\ThemeManager;
use Cms\Classes\CmsObjectCache;
use Backend\Models\UserRole;
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

        $this->registerThemeLogging();
        $this->registerCombinerEvents();
        $this->registerThemeTranslations();
        $this->registerConsole();

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
        $this->registerConsoleCommand('theme.seed', \Cms\Console\ThemeSeed::class);
        $this->registerConsoleCommand('theme.clear', \Cms\Console\ThemeClear::class);
        $this->registerConsoleCommand('theme.cache', \Cms\Console\ThemeCache::class);
    }

    /**
     * registerComponents
     */
    public function registerComponents()
    {
        return [
           \Cms\Components\ViewBag::class => 'viewBag',
           \Cms\Components\Resources::class => 'resources'
        ];
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
     * registerCombinerEvents for the asset combiner.
     */
    protected function registerCombinerEvents()
    {
        if ($this->app->runningInBackend() || $this->app->runningInConsole()) {
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

    /**
     * registerThemeTranslations localization from an active theme.
     */
    protected function registerThemeTranslations()
    {
        $this->app->afterResolving('translator', function() {
            if ($this->app->runningInBackend()) {
                ThemeManager::instance()->bootAllBackend();
            }
            else {
                ThemeManager::instance()->bootAllFrontend();
            }
        });
    }

    /**
     * registerReportWidgets
     */
    public function registerReportWidgets()
    {
        return [
            \Cms\ReportWidgets\ActiveTheme::class => [
                'label' => 'cms::lang.dashboard.active_theme.widget_title_default',
                'context' => 'dashboard'
            ],
        ];
    }

    /**
     * registerPermissions
     */
    public function registerPermissions()
    {
        return [
            // General
            'general.view_offline' => [
                'label' => 'View Website During Maintenance',
                'tab' => 'General',
                'order' => 100
            ],

            // Editor
            'editor.cms_content' => [
                'label' => 'Manage Content',
                'comment' => 'cms::lang.permissions.manage_content',
                'tab' => 'Editor',
                'roles' => UserRole::CODE_DEVELOPER,
                'order' => 200
            ],
            'editor.cms_assets' => [
                'label' => 'Manage Asset Files',
                'comment' => 'cms::lang.permissions.manage_assets',
                'tab' => 'Editor',
                'roles' => UserRole::CODE_DEVELOPER,
                'order' => 300
            ],
            'editor.cms_pages' => [
                'label' => 'Manage Pages',
                'comment' => 'cms::lang.permissions.manage_pages',
                'tab' => 'Editor',
                'roles' => UserRole::CODE_DEVELOPER,
                'order' => 400
            ],
            'editor.cms_partials' => [
                'label' => 'Manage Partials',
                'comment' => 'cms::lang.permissions.manage_partials',
                'tab' => 'Editor',
                'roles' => UserRole::CODE_DEVELOPER,
                'order' => 500
            ],
            'editor.cms_layouts' => [
                'label' => 'Manage Layouts',
                'comment' => 'cms::lang.permissions.manage_layouts',
                'tab' => 'Editor',
                'roles' => UserRole::CODE_DEVELOPER,
                'order' => 600
            ],

            // Themes
            'cms.themes' => [
                'label' => 'Manage Themes',
                'comment' => 'cms::lang.permissions.manage_themes',
                'tab' => 'Themes',
                'roles' => UserRole::CODE_DEVELOPER,
                'order' => 300
            ],
            'cms.themes.create' => [
                'label' => 'Create Theme',
                'tab' => 'Themes',
                'roles' => UserRole::CODE_DEVELOPER,
                'order' => 400
            ],
            'cms.themes.activate' => [
                'label' => 'Activate Theme',
                'tab' => 'Themes',
                'roles' => UserRole::CODE_DEVELOPER,
                'order' => 600
            ],
            'cms.themes.delete' => [
                'label' => 'Delete Theme',
                'tab' => 'Themes',
                'roles' => UserRole::CODE_DEVELOPER,
                'order' => 600
            ],
            'cms.maintenance_mode' => [
                'label' => 'Manage Maintenance Mode',
                'tab' => 'Themes',
                'order' => 900
            ],
            'cms.theme_customize' => [
                'label' => 'Customize Theme',
                'comment' => 'cms::lang.permissions.manage_theme_options',
                'tab' => 'Themes',
                'order' => 400
            ],
        ];
    }

    /**
     * registerSettings
     */
    public function registerSettings()
    {
        return [
            'theme' => [
                'label' => 'cms::lang.theme.settings_menu',
                'description' => 'cms::lang.theme.settings_menu_description',
                'category' => SettingsManager::CATEGORY_CMS,
                'icon' => 'octo-icon-text-image',
                'url' => Backend::url('cms/themes'),
                'permissions' => ['cms.themes', 'cms.theme_customize'],
                'order' => 200
            ],
            'maintenance_settings' => [
                'label' => 'cms::lang.maintenance.settings_menu',
                'description' => 'cms::lang.maintenance.settings_menu_description',
                'category' => SettingsManager::CATEGORY_CMS,
                'icon' => 'octo-icon-power',
                'class' => \Cms\Models\MaintenanceSetting::class,
                'permissions' => ['cms.maintenance_mode'],
                'order' => 300
            ],
            'theme_logs' => [
                'label' => 'cms::lang.theme_log.menu_label',
                'description' => 'cms::lang.theme_log.menu_description',
                'category' => SettingsManager::CATEGORY_LOGS,
                'icon' => 'icon-magic',
                'url' => Backend::url('cms/themelogs'),
                'permissions' => ['utilities.logs'],
                'order' => 910,
                'keywords' => 'theme change log'
            ]
        ];
    }

    /**
     * bootMenuItemEvents for menu items.
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
     * bootRichEditorEvents for rich editor page links.
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
     * bootEditorEvents handles editor events
     */
    protected function bootEditorEvents()
    {
        Event::listen('editor.extension.register', function () {
            return \Cms\Classes\EditorExtension::class;
        });
    }
}
