<?php namespace System;

use Db;
use Event;
use Config;
use Schema;
use Backend;
use BackendMenu;
use System as SystemHelper;
use System\Models\EventLog;
use System\Models\MailSetting;
use System\Classes\MailManager;
use System\Classes\ErrorHandler;
use System\Classes\UpdateManager;
use System\Classes\PluginManager;
use System\Classes\SettingsManager;
use System\Twig\Engine as TwigEngine;
use System\Twig\Loader as TwigLoader;
use System\Twig\Extension as TwigExtension;
use October\Rain\Support\ModuleServiceProvider;
use Illuminate\Pagination\Paginator;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\SandboxExtension;

/**
 * ServiceProvider for System module
 */
class ServiceProvider extends ModuleServiceProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        parent::register('system');

        $this->forgetSingletons();
        $this->registerSingletons();

        // Register all plugins
        PluginManager::instance()->registerAll();

        $this->registerErrorHandler();
        $this->registerLogging();
        $this->registerTwigParser();
        $this->registerValidator();
        $this->registerManifest();
        $this->registerConsole();
        $this->extendViewService();
        $this->extendMailerService();

        // Register other module providers
        foreach (SystemHelper::listModules() as $module) {
            if ($module !== 'System') {
                $this->app->register('\\' . $module . '\ServiceProvider');
            }
        }

        // Register app service provider
        if (class_exists(\App\Provider::class)) {
            $this->app->register(\App\Provider::class);
        }

        // Backend specific
        if ($this->app->runningInBackend()) {
            $this->extendBackendNavigation();
            $this->extendBackendSettings();
        }
    }

    /**
     * boot the module events.
     */
    public function boot()
    {
        // Fix UTF8MB4 support for MariaDB < 10.2 and MySQL < 5.7
        $this->applyDatabaseDefaultStringLength();

        // Fix use of Storage::url() for local disks that haven't been configured correctly
        foreach (Config::get('filesystems.disks') as $key => $config) {
            if ($config['driver'] === 'local' && ends_with($config['root'], '/storage/app') && empty($config['url'])) {
                Config::set("filesystems.disks.$key.url", '/storage/app');
            }
        }

        // Set pagination views
        Paginator::defaultView('system::pagination.default');
        Paginator::defaultSimpleView('system::pagination.simple-default');

        // Boot plugins
        PluginManager::instance()->bootAll();

        parent::boot('system');
    }

    /**
     * forgetSingletons that may linger from previous instances,
     * useful for testing and booting secondary instances
     */
    protected function forgetSingletons()
    {
        PluginManager::forgetInstance();
        UpdateManager::forgetInstance();
    }

    /**
     * registerSingletons
     */
    protected function registerSingletons()
    {
        $this->app->singleton('cms.helper', \Cms\Helpers\Cms::class);
        $this->app->singleton('system.helper', \System\Helpers\System::class);
        $this->app->singleton('system.manifest', \System\Classes\ManifestCache::class);
        $this->app->singleton('backend.ui', \Backend\Helpers\BackendUi::class);
        $this->app->singleton('backend.helper', \Backend\Helpers\Backend::class);
        $this->app->singleton('backend.menu', function () { return \Backend\Classes\NavigationManager::instance(); });
        $this->app->singleton('backend.auth', function () { return \Backend\Classes\AuthManager::instance(); });
    }

    /**
     * registerMarkupTags
     */
    public function registerMarkupTags()
    {
        return [
            'functions' => [
                // Escaped Functions
                'input' => ['input', true],
                'post' => ['post', true],
                'get' => ['get', true],
                'form_value' => [\Form::class, 'value', true],

                // Raw Functions
                'link_to' => 'link_to',
                'link_to_asset' => 'link_to_asset',
                'link_to_route' => 'link_to_route',
                'link_to_action' => 'link_to_action',
                'asset' => 'asset',
                'action' => 'action',
                'url' => 'url',
                'route' => 'route',
                'secure_url' => 'secure_url',
                'secure_asset' => 'secure_asset',
                'html_email' => [\Html::class, 'email'],

                // Escaped Classes
                'str_*' => [\Str::class, '*', true],
                'html_*' => [\Html::class, '*', true],

                // Raw Classes
                'url_*' => [\Url::class, '*'],
                'form_*' => [\Form::class, '*'],
                'form_macro' => [\Form::class, '__call']
            ],
            'filters' => [
                // Escaped Classes
                'str_*' => [\Str::class, '*', true],
                'html_*' => [\Html::class, '*', true],
                'trans' => [\Lang::class, 'get', true],
                'transchoice' => [\Lang::class, 'choice', true],

                // Raw Classes
                'url_*' => [\Url::class, '*'],
                'slug' => [\Str::class, 'slug'],
                'plural' => [\Str::class, 'plural'],
                'singular' => [\Str::class, 'singular'],
                'finish' => [\Str::class, 'finish'],
                'snake' => [\Str::class, 'snake'],
                'camel' => [\Str::class, 'camel'],
                'studly' => [\Str::class, 'studly'],
                'md' => [\Markdown::class, 'parse'],
                'md_safe' => [\Markdown::class, 'parseSafe'],
                'md_clean' => [\Markdown::class, 'parseClean'],
                'md_indent' => [\Markdown::class, 'parseIndent'],
                'time_since' => [\System\Helpers\DateTime::class, 'timeSince'],
                'time_tense' => [\System\Helpers\DateTime::class, 'timeTense'],
            ]
        ];
    }

    /**
     * registerManifest
     */
    protected function registerManifest()
    {
        $this->app->after(function() {
            $this->app->make('system.manifest')->build();
        });
    }

    /**
     * registerConsole command line specifics
     */
    protected function registerConsole()
    {
        // Allow app and plugins to use the scheduler
        Event::listen('console.schedule', function ($schedule) {
            foreach (PluginManager::instance()->getPlugins() as $plugin) {
                $plugin->registerSchedule($schedule);
            }

            if ($app = $this->app->getProvider(\App\Provider::class)) {
                $app->registerSchedule($schedule);
            }
        });

        // Add CMS based cache clearing to native command
        Event::listen('cache:cleared', function () {
            \System\Helpers\Cache::clearInternal();
        });

        // Register console commands
        $this->registerConsoleCommand('october.up', \System\Console\OctoberUp::class);
        $this->registerConsoleCommand('october.down', \System\Console\OctoberDown::class);
        $this->registerConsoleCommand('october.migrate', \System\Console\OctoberMigrate::class);
        $this->registerConsoleCommand('october.update', \System\Console\OctoberUpdate::class);
        $this->registerConsoleCommand('october.util', \System\Console\OctoberUtil::class);
        $this->registerConsoleCommand('october.mirror', \System\Console\OctoberMirror::class);
        $this->registerConsoleCommand('october.fresh', \System\Console\OctoberFresh::class);
        $this->registerConsoleCommand('october.passwd', \System\Console\OctoberPasswd::class);
        $this->registerConsoleCommand('october.optimize', \System\Console\OctoberOptimize::class);

        $this->registerConsoleCommand('plugin.install', \System\Console\PluginInstall::class);
        $this->registerConsoleCommand('plugin.remove', \System\Console\PluginRemove::class);
        $this->registerConsoleCommand('plugin.disable', \System\Console\PluginDisable::class);
        $this->registerConsoleCommand('plugin.enable', \System\Console\PluginEnable::class);
        $this->registerConsoleCommand('plugin.refresh', \System\Console\PluginRefresh::class);
        $this->registerConsoleCommand('plugin.list', \System\Console\PluginList::class);
        $this->registerConsoleCommand('plugin.check', \System\Console\PluginCheck::class);

        $this->registerConsoleCommand('project.sync', \System\Console\ProjectSync::class);
    }

    /**
     * registerErrorHandler for uncaught Exceptions
     */
    protected function registerErrorHandler()
    {
        Event::listen('exception.beforeReport', function (&$exception) {
            $exception = (new ErrorHandler)->beforeReport($exception);
        });

        Event::listen('exception.beforeRender', function ($exception, $httpCode, $request) {
            return (new ErrorHandler)->handleException($exception);
        });
    }

    /**
     * registerLogging writes all log events to the database
     */
    protected function registerLogging()
    {
        Event::listen(\Illuminate\Log\Events\MessageLogged::class, function ($event) {
            if (EventLog::useLogging()) {
                EventLog::add($event->message, $event->level, $event->context);
            }
        });
    }

    /**
     * registerTwigParser
     */
    protected function registerTwigParser()
    {
        // Register system Twig environment
        $this->app->singleton('twig.environment', function ($app) {
            $twig = new TwigEnvironment(new TwigLoader, ['auto_reload' => true]);
            $twig->addExtension(new TwigExtension);

            // @deprecated always use the main policy here
            if (env('CMS_SECURITY_POLICY_V1', false)) {
                $twig->addExtension(new SandboxExtension(new \System\Twig\SecurityPolicyLegacy, true));
            }
            else {
                $twig->addExtension(new SandboxExtension(new \System\Twig\SecurityPolicy, true));
            }

            // Desired logic
            // $twig->addExtension(new SandboxExtension(new \System\Twig\SecurityPolicy, true));

            return $twig;
        });

        // Register Twig for mailer
        $this->app->singleton('twig.environment.mailer', function ($app) {
            $twig = new TwigEnvironment(new TwigLoader, ['auto_reload' => true]);
            $twig->addExtension(new TwigExtension);

            // @deprecated always use the main policy here
            if (env('CMS_SECURITY_POLICY_V1', false)) {
                $twig->addExtension(new SandboxExtension(new \System\Twig\SecurityPolicyLegacy, true));
            }
            else {
                $twig->addExtension(new SandboxExtension(new \System\Twig\SecurityPolicy, true));
            }

            // Desired logic
            // $twig->addExtension(new SandboxExtension(new \System\Twig\SecurityPolicy, true));

            $twig->addTokenParser(new \System\Twig\MailPartialTokenParser);
            return $twig;
        });
    }

    /**
     * registerMailLayouts
     */
    public function registerMailLayouts()
    {
        return [
            'default' => 'system::mail.layout-default',
            'system' => 'system::mail.layout-system',
        ];
    }

    /**
     * registerMailPartials
     */
    public function registerMailPartials()
    {
        return [
            'header' => 'system::mail.partial-header',
            'footer' => 'system::mail.partial-footer',
            'button' => 'system::mail.partial-button',
            'panel' => 'system::mail.partial-panel',
            'table' => 'system::mail.partial-table',
            'subcopy' => 'system::mail.partial-subcopy',
            'promotion' => 'system::mail.partial-promotion',
        ];
    }

    /**
     * registerNavigation
     */
    public function registerNavigation()
    {
        return [
            'system' => [
                'label' => 'system::lang.settings.menu_label',
                'icon' => 'icon-cog',
                'iconSvg' => 'modules/system/assets/images/cog-icon.svg',
                'url' => Backend::url('system/settings'),
                'permissions' => [],
                'order' => 1000
            ]
        ];
    }

    /**
     * extendBackendNavigation
     */
    protected function extendBackendNavigation()
    {
        // Register the sidebar for the System main menu
        BackendMenu::registerContextSidenavPartial(
            'October.System',
            'system',
            '~/modules/system/partials/_system_sidebar.php'
        );

        // Remove the October.System.system main menu item if there is no subpages to display
        Event::listen('backend.menu.extendItems', function ($manager) {
            $systemSettingItems = SettingsManager::instance()->listItems('system');
            $systemMenuItems = $manager->listSideMenuItems('October.System', 'system');

            if (empty($systemSettingItems) && empty($systemMenuItems)) {
                $manager->removeMainMenuItem('October.System', 'system');
            }
        }, -9999);
    }

    /**
     * registerReportWidgets
     */
    public function registerReportWidgets()
    {
        return [
            \System\ReportWidgets\Status::class => [
                'label' => 'backend::lang.dashboard.status.widget_title_default',
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
            // Mail
            'mail.templates' => [
                'label' => 'system::lang.permissions.manage_mail_templates',
                'tab' => 'Mail',
                'order' => 300
            ],
            'mail.settings' => [
                'label' => 'system::lang.permissions.manage_mail_settings',
                'tab' => 'Mail',
                'order' => 900
            ],

            // Utilities
            'utilities.logs' => [
                'label' => 'system::lang.permissions.access_logs',
                'tab' => 'Utilities',
                'order' => 400
            ]
        ];
    }

    /**
     * registerSettings
     */
    public function registerSettings()
    {
        return [
            'updates' => [
                'label' => 'System Updates',
                'description' => 'Update the system modules and plugins.',
                'category' => SettingsManager::CATEGORY_SYSTEM,
                'icon' => 'octo-icon-download',
                'url' => Backend::url('system/updates'),
                'permissions' => ['general.backend.perform_updates'],
                'order' => 300
            ],
            'my_updates' => [
                'label' => 'System Updates',
                'description' => 'Update the system modules and plugins.',
                'category' => SettingsManager::CATEGORY_MYSETTINGS,
                'icon' => 'octo-icon-components',
                'url' => Backend::url('system/updates'),
                'permissions' => ['general.backend.perform_updates'],
                'order' => 520,
                'context' => 'mysettings'
            ],
            'mail_templates' => [
                'label' => 'system::lang.mail_templates.menu_label',
                'description' => 'system::lang.mail_templates.menu_description',
                'category' => SettingsManager::CATEGORY_MAIL,
                'icon' => 'octo-icon-mail-messages',
                'url' => Backend::url('system/mailtemplates'),
                'permissions' => ['mail.templates'],
                'order' => 610
            ],
            'mail_settings' => [
                'label' => 'system::lang.mail.menu_label',
                'description' => 'system::lang.mail.menu_description',
                'category' => SettingsManager::CATEGORY_MAIL,
                'icon' => 'octo-icon-mail-settings',
                'class' => 'System\Models\MailSetting',
                'permissions' => ['mail.settings'],
                'order' => 620
            ],
            'mail_brand_settings' => [
                'label' => 'system::lang.mail_brand.menu_label',
                'description' => 'system::lang.mail_brand.menu_description',
                'category' => SettingsManager::CATEGORY_MAIL,
                'icon' => 'octo-icon-mail-branding',
                'url' => Backend::url('system/mailbrandsettings'),
                'permissions' => ['mail.templates'],
                'order' => 630
            ],
            'event_logs' => [
                'label' => 'system::lang.event_log.menu_label',
                'description' => 'system::lang.event_log.menu_description',
                'category' => SettingsManager::CATEGORY_LOGS,
                'icon' => 'octo-icon-text-format-ul',
                'url' => Backend::url('system/eventlogs'),
                'permissions' => ['utilities.logs'],
                'order' => 900,
                'keywords' => 'error exception'
            ],
            'request_logs' => [
                'label' => 'system::lang.request_log.menu_label',
                'description' => 'system::lang.request_log.menu_description',
                'category' => SettingsManager::CATEGORY_LOGS,
                'icon' => 'icon-file-o',
                'url' => Backend::url('system/requestlogs'),
                'permissions' => ['utilities.logs'],
                'order' => 910,
                'keywords' => '404 error'
            ],
            'log_settings' => [
                'label' => 'system::lang.log.menu_label',
                'description' => 'system::lang.log.menu_description',
                'category' => SettingsManager::CATEGORY_LOGS,
                'icon' => 'octo-icon-log-settings',
                'class' => 'System\Models\LogSetting',
                'permissions' => ['system.manage_logs'],
                'order' => 990
            ],
        ];
    }

    /**
     * extendBackendSettings
     */
    protected function extendBackendSettings()
    {
        Event::listen('system.settings.extendItems', function ($manager) {
            \System\Models\LogSetting::filterSettingItems($manager);
        });
    }

    /**
     * registerValidator extends the validator with custom rules
     */
    protected function registerValidator()
    {
        $this->app->resolving('validator', function ($validator) {
            // Allowed file extensions, as opposed to mime types.
            // - extensions: png,jpg,txt
            $validator->extend('extensions', function ($attribute, $value, $parameters) {
                $extension = strtolower($value->getClientOriginalExtension());
                return in_array($extension, $parameters);
            });

            $validator->replacer('extensions', function ($message, $attribute, $rule, $parameters) {
                return strtr($message, [':values' => implode(', ', $parameters)]);
            });
        });
    }

    /**
     * applyDatabaseDefaultStringLength allows the database config to specify a max length
     * for VARCHAR. Primarily used by MariaDB (<10.2) and MySQL (<5.7)
     * @todo This should be moved to the core library
     */
    protected function applyDatabaseDefaultStringLength()
    {
        if (Db::getDriverName() !== 'mysql') {
            return;
        }

        $defaultStrLen = Db::getConfig('varcharmax');
        if ($defaultStrLen === null) {
            return;
        }

        Schema::defaultStringLength((int) $defaultStrLen);
    }

    /**
     * extendViewService
     */
    protected function extendViewService()
    {
        $this->app->resolving('view', function($view) {
            // Register .htm extension for Twig views
            $view->addExtension('htm', 'twig', function () {
                return new TwigEngine($this->app->make('twig.environment'));
            });

            // Share app name
            $view->share('appName', Config::get('app.name'));
        });
    }

    /**
     * extendMailerService templating and settings override.
     */
    protected function extendMailerService()
    {
        // Override system mailer with mail settings
        Event::listen('mailer.beforeRegister', function () {
            if (MailSetting::isConfigured()) {
                MailSetting::applyConfigValues();
            }
        });

        // Override standard Mailer content with template
        Event::listen('mailer.beforeAddContent', function ($mailer, $message, $view, $data, $raw, $plain) {
            return !MailManager::instance()->addContentFromEvent($message, $view, $plain, $raw, $data);
        });
    }
}
