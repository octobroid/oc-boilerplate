<?php namespace Backend;

use App;
use Event;
use Backend;
use BackendMenu;
use BackendAuth;
use System\Classes\MailManager;
use System\Classes\CombineAssets;
use System\Classes\SettingsManager;
use Backend\Classes\WidgetManager;
use October\Rain\Auth\AuthException;
use October\Rain\Support\ModuleServiceProvider;

/**
 * ServiceProvider for Backend module
 */
class ServiceProvider extends ModuleServiceProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        parent::register('backend');

        $this->registerMailer();
        $this->registerAssetBundles();

        /*
         * Backend specific
         */
        if (App::runningInBackend()) {
            $this->registerBackendNavigation();
            $this->registerBackendReportWidgets();
            $this->registerBackendWidgets();
            $this->registerBackendPermissions();
            $this->registerBackendSettings();
        }
    }

    /**
     * boot the module events.
     */
    public function boot()
    {
        parent::boot('backend');

        $this->bootAuth();
    }

    /**
     * bootAuth boots authentication based logic.
     */
    protected function bootAuth(): void
    {
        AuthException::setDefaultErrorMessage(__('backend::lang.auth.invalid_login'));
    }

    /**
     * Register mail templates
     */
    protected function registerMailer()
    {
        MailManager::instance()->registerCallback(function ($manager) {
            $manager->registerMailTemplates([
                'backend::mail.invite',
                'backend::mail.restore',
            ]);
        });
    }

    /**
     * Register asset bundles
     */
    protected function registerAssetBundles()
    {
        CombineAssets::registerCallback(function ($combiner) {
            $combiner->registerBundle('~/modules/backend/widgets/table/assets/js/build.js');
            $combiner->registerBundle('~/modules/backend/formwidgets/codeeditor/assets/less/codeeditor.less');
            $combiner->registerBundle('~/modules/backend/formwidgets/codeeditor/assets/js/build.js');
            $combiner->registerBundle('~/modules/backend/formwidgets/nestedform/assets/less/nestedform.less');
            $combiner->registerBundle('~/modules/backend/formwidgets/richeditor/assets/js/build-plugins.js');
            $combiner->registerBundle('~/modules/backend/formwidgets/sensitive/assets/less/sensitive.less');

            /*
             * Rich Editor is protected by DRM
             */
            if (file_exists(base_path('modules/backend/formwidgets/richeditor/assets/vendor/froala_drm'))) {
                $combiner->registerBundle('~/modules/backend/formwidgets/richeditor/assets/less/richeditor.less');
                $combiner->registerBundle('~/modules/backend/formwidgets/richeditor/assets/js/build.js');
            }
        });
    }

    /*
     * Register navigation
     */
    protected function registerBackendNavigation()
    {
        BackendMenu::registerCallback(function ($manager) {
            $manager->registerMenuItems('October.Backend', [
                'dashboard' => [
                    'label' => 'backend::lang.dashboard.menu_label',
                    'icon' => 'icon-dashboard',
                    'iconSvg' => 'modules/backend/assets/images/dashboard-icon.svg',
                    'url' => Backend::url('backend'),
                    'permissions' => ['backend.access_dashboard'],
                    'order' => 10
                ]
            ]);
        });
    }

    /*
     * Register report widgets
     */
    protected function registerBackendReportWidgets()
    {
        WidgetManager::instance()->registerReportWidgets(function ($manager) {
            $manager->registerReportWidget(\Backend\ReportWidgets\Welcome::class, [
                'label'   => 'backend::lang.dashboard.welcome.widget_title_default',
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
            $manager->registerPermissions('October.Backend', [
                'backend.access_dashboard' => [
                    'label' => 'system::lang.permissions.view_the_dashboard',
                    'tab' => 'system::lang.permissions.name'
                ],
                'backend.manage_default_dashboard' => [
                    'label' => 'system::lang.permissions.manage_default_dashboard',
                    'tab' => 'system::lang.permissions.name',
                ],
                'backend.manage_users' => [
                    'label' => 'system::lang.permissions.manage_other_administrators',
                    'tab' => 'system::lang.permissions.name'
                ],
                'backend.manage_preferences' => [
                    'label' => 'system::lang.permissions.manage_preferences',
                    'tab' => 'system::lang.permissions.name'
                ],
                'backend.manage_editor' => [
                    'label' => 'system::lang.permissions.manage_editor',
                    'tab' => 'system::lang.permissions.name'
                ],
                'backend.manage_branding' => [
                    'label' => 'system::lang.permissions.manage_branding',
                    'tab' => 'system::lang.permissions.name'
                ]
            ]);
        });
    }

    /*
     * Register widgets
     */
    protected function registerBackendWidgets()
    {
        WidgetManager::instance()->registerFormWidgets(function ($manager) {
            $manager->registerFormWidget(\Backend\FormWidgets\CodeEditor::class, 'codeeditor');
            $manager->registerFormWidget(\Backend\FormWidgets\RichEditor::class, 'richeditor');
            $manager->registerFormWidget(\Backend\FormWidgets\MarkdownEditor::class, 'markdown');
            $manager->registerFormWidget(\Backend\FormWidgets\FileUpload::class, 'fileupload');
            $manager->registerFormWidget(\Backend\FormWidgets\Relation::class, 'relation');
            $manager->registerFormWidget(\Backend\FormWidgets\DatePicker::class, 'datepicker');
            $manager->registerFormWidget(\Backend\FormWidgets\TimePicker::class, 'timepicker');
            $manager->registerFormWidget(\Backend\FormWidgets\ColorPicker::class, 'colorpicker');
            $manager->registerFormWidget(\Backend\FormWidgets\DataTable::class, 'datatable');
            $manager->registerFormWidget(\Backend\FormWidgets\RecordFinder::class, 'recordfinder');
            $manager->registerFormWidget(\Backend\FormWidgets\Repeater::class, 'repeater');
            $manager->registerFormWidget(\Backend\FormWidgets\TagList::class, 'taglist');
            $manager->registerFormWidget(\Backend\FormWidgets\NestedForm::class, 'nestedform');
            $manager->registerFormWidget(\Backend\FormWidgets\Sensitive::class, 'sensitive');
        });
    }

    /*
     * Register settings
     */
    protected function registerBackendSettings()
    {
        Event::listen('system.settings.extendItems', function ($manager) {
            if ((!$user = BackendAuth::getUser()) || !$user->isSuperUser()) {
                $manager->removeSettingItem('October.Backend', 'adminroles');
            }
        });

        SettingsManager::instance()->registerCallback(function ($manager) {
            $manager->registerSettingItems('October.Backend', [
                'administrators' => [
                    'label' => 'backend::lang.user.menu_label',
                    'description' => 'backend::lang.user.menu_description',
                    'category' => SettingsManager::CATEGORY_TEAM,
                    'icon' => 'octo-icon-users',
                    'url' => Backend::url('backend/users'),
                    'permissions' => ['backend.manage_users'],
                    'order' => 400
                ],
                'adminroles' => [
                    'label' => 'backend::lang.user.role.menu_label',
                    'description' => 'backend::lang.user.role.menu_description',
                    'category' => SettingsManager::CATEGORY_TEAM,
                    'icon' => 'octo-icon-id-card-1',
                    'url' => Backend::url('backend/userroles'),
                    'permissions' => ['backend.manage_users'],
                    'order' => 410
                ],
                'admingroups' => [
                    'label' => 'backend::lang.user.group.menu_label',
                    'description' => 'backend::lang.user.group.menu_description',
                    'category' => SettingsManager::CATEGORY_TEAM,
                    'icon' => 'octo-icon-user-group',
                    'url' => Backend::url('backend/usergroups'),
                    'permissions' => ['backend.manage_users'],
                    'order' => 420
                ],
                'branding' => [
                    'label' => 'backend::lang.branding.menu_label',
                    'description' => 'backend::lang.branding.menu_description',
                    'category' => SettingsManager::CATEGORY_SYSTEM,
                    'icon' => 'octo-icon-paint-brush-1',
                    'class' => 'Backend\Models\BrandSetting',
                    'permissions' => ['backend.manage_branding'],
                    'order' => 500,
                    'keywords' => 'brand style'
                ],
                'editor' => [
                    'label' => 'backend::lang.editor.menu_label',
                    'description' => 'backend::lang.editor.menu_description',
                    'category' => SettingsManager::CATEGORY_SYSTEM,
                    'icon' => 'icon-code',
                    'class' => 'Backend\Models\EditorSetting',
                    'permissions' => ['backend.manage_editor'],
                    'order' => 500,
                    'keywords' => 'html code class style'
                ],
                'myaccount' => [
                    'label' => 'backend::lang.myaccount.menu_label',
                    'description' => 'backend::lang.myaccount.menu_description',
                    'category' => SettingsManager::CATEGORY_MYSETTINGS,
                    'icon' => 'octo-icon-user-account',
                    'url' => Backend::url('backend/users/myaccount'),
                    'order' => 500,
                    'context' => 'mysettings',
                    'keywords' => 'backend::lang.myaccount.menu_keywords'
                ],
                'preferences' => [
                    'label' => 'backend::lang.backend_preferences.menu_label',
                    'description' => 'backend::lang.backend_preferences.menu_description',
                    'category' => SettingsManager::CATEGORY_MYSETTINGS,
                    'icon' => 'octo-icon-app-window',
                    'url' => Backend::url('backend/preferences'),
                    'permissions' => ['backend.manage_preferences'],
                    'order' => 510,
                    'context' => 'mysettings'
                ],
                'access_logs' => [
                    'label' => 'backend::lang.access_log.menu_label',
                    'description' => 'backend::lang.access_log.menu_description',
                    'category' => SettingsManager::CATEGORY_LOGS,
                    'icon' => 'octo-icon-lock',
                    'url' => Backend::url('backend/accesslogs'),
                    'permissions' => ['system.access_logs'],
                    'order' => 920
                ]
            ]);
        });
    }
}
