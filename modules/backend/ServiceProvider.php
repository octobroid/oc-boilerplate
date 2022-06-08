<?php namespace Backend;

use Backend;
use System\Classes\MailManager;
use System\Classes\CombineAssets;
use System\Classes\SettingsManager;
use Backend\Models\UserRole;
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
    }

    /**
     * boot the module events.
     */
    public function boot()
    {
        parent::boot('backend');

        AuthException::setDefaultErrorMessage('backend::lang.auth.invalid_login');
    }

    /**
     * registerMailer templates
     */
    protected function registerMailer()
    {
        MailManager::registerCallback(function ($manager) {
            $manager->registerMailTemplates([
                'backend::mail.invite',
                'backend::mail.restore',
            ]);
        });
    }

    /**
     * registerAssetBundles
     */
    protected function registerAssetBundles()
    {
        // Rich Editor is protected by DRM
        CombineAssets::registerCallback(function ($combiner) {
            if (file_exists(base_path('modules/backend/formwidgets/richeditor/assets/vendor/froala_drm'))) {
                $combiner->registerBundle('~/modules/backend/formwidgets/richeditor/assets/js/build-plugins.js');
                $combiner->registerBundle('~/modules/backend/formwidgets/richeditor/assets/less/richeditor.less');
                $combiner->registerBundle('~/modules/backend/formwidgets/richeditor/assets/js/build.js');
            }
        });
    }

    /**
     * registerNavigation
     */
    public function registerNavigation()
    {
        return [
            'dashboard' => [
                'label' => 'backend::lang.dashboard.menu_label',
                'icon' => 'icon-dashboard',
                'iconSvg' => 'modules/backend/assets/images/dashboard-icon.svg',
                'url' => Backend::url('backend'),
                'permissions' => ['dashboard'],
                'order' => 10
            ]
        ];
    }

    /**
     * registerReportWidgets
     */
    public function registerReportWidgets()
    {
        return [
            \Backend\ReportWidgets\Welcome::class => [
                'label' => 'backend::lang.dashboard.welcome.widget_title_default',
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
            'general.backend' => [
                'label' => 'Access the Backend Panel',
                'tab' => 'General',
                'order' => 200
            ],
            'general.backend.view_offline' => [
                'label' => 'View Backend During Maintenance',
                'tab' => 'General',
                'order' => 300
            ],
            'general.backend.perform_updates' => [
                'label' => 'Perform Software Updates',
                'tab' => 'General',
                'roles' => UserRole::CODE_DEVELOPER,
                'order' => 300
            ],

            // Dashboard
            'dashboard' => [
                'label' => 'system::lang.permissions.view_the_dashboard',
                'tab' => 'Dashboard',
                'order' => 200
            ],
            'dashboard.defaults' => [
                'label' => 'system::lang.permissions.manage_default_dashboard',
                'tab' => 'Dashboard',
                'order' => 300,
                'roles' => UserRole::CODE_DEVELOPER,
            ],

            // Administrators
            'admins.manage' => [
                'label' => 'Manage Admins',
                'tab' => 'Administrators',
                'order' => 200
            ],
            'admins.manage.create' => [
                'label' => 'Create Admins',
                'tab' => 'Administrators',
                'order' => 300
            ],
            // 'admins.manage.moderate' => [
            //     'label' => 'Moderate Admins',
            //     'comment' => 'Manage account suspension and ban admin accounts',
            //     'tab' => 'Administrators',
            //     'order' => 400
            // ],
            'admins.manage.roles' => [
                'label' => 'Manage Roles',
                'comment' => 'Allow users to create new roles and manage roles lower than their highest role.',
                'tab' => 'Administrators',
                'order' => 500
            ],
            'admins.manage.groups' => [
                'label' => 'Manage Groups',
                'tab' => 'Administrators',
                'order' => 600
            ],
            'admins.manage.other_admins' => [
                'label' => 'Manage Other Admins',
                'comment' => 'Allow users to reset passwords and update emails.',
                'tab' => 'Administrators',
                'order' => 700
            ],
            'admins.manage.delete' => [
                'label' => 'Delete Admins',
                'tab' => 'Administrators',
                'order' => 800
            ],

            // Preferences
            'preferences' => [
                'label' => 'system::lang.permissions.manage_preferences',
                'tab' => 'Preferences',
                'order' => 400
            ],
            'preferences.code_editor' => [
                'label' => 'system::lang.permissions.manage_editor',
                'tab' => 'Preferences',
                'order' => 500
            ],

            // Settings
            'settings.customize_backend' => [
                'label' => 'system::lang.permissions.manage_branding',
                'tab' => 'Settings',
                'order' => 400
            ],
            'settings.editor_settings' => [
                'label' => 'Global Editor Settings',
                'comment' => 'backend::lang.editor.menu_description',
                'tab' => 'Settings',
                'order' => 500
            ]
        ];
    }

    /**
     * registerFormWidgets
     */
    public function registerFormWidgets()
    {
        return [
            \Backend\FormWidgets\CodeEditor::class => 'codeeditor',
            \Backend\FormWidgets\RichEditor::class => 'richeditor',
            \Backend\FormWidgets\MarkdownEditor::class => 'markdown',
            \Backend\FormWidgets\FileUpload::class => 'fileupload',
            \Backend\FormWidgets\Relation::class => 'relation',
            \Backend\FormWidgets\DatePicker::class => 'datepicker',
            \Backend\FormWidgets\TimePicker::class => 'timepicker',
            \Backend\FormWidgets\ColorPicker::class => 'colorpicker',
            \Backend\FormWidgets\DataTable::class => 'datatable',
            \Backend\FormWidgets\RecordFinder::class => 'recordfinder',
            \Backend\FormWidgets\Repeater::class => 'repeater',
            \Backend\FormWidgets\TagList::class => 'taglist',
            \Backend\FormWidgets\NestedForm::class => 'nestedform',
            \Backend\FormWidgets\Sensitive::class => 'sensitive',
        ];
    }

    /**
     * registerFilterWidgets
     */
    public function registerFilterWidgets()
    {
        return [
            \Backend\FilterWidgets\Group::class => 'group',
            \Backend\FilterWidgets\Date::class => 'date',
            \Backend\FilterWidgets\Text::class => 'text',
            \Backend\FilterWidgets\Number::class => 'number',
        ];
    }

    /**
     * registerSettings
     */
    public function registerSettings()
    {
        return [
            'administrators' => [
                'label' => 'backend::lang.user.menu_label',
                'description' => 'backend::lang.user.menu_description',
                'category' => SettingsManager::CATEGORY_TEAM,
                'icon' => 'octo-icon-users',
                'url' => Backend::url('backend/users'),
                'permissions' => ['admins.manage'],
                'order' => 400
            ],
            'adminroles' => [
                'label' => 'backend::lang.user.role.menu_label',
                'description' => 'backend::lang.user.role.menu_description',
                'category' => SettingsManager::CATEGORY_TEAM,
                'icon' => 'octo-icon-id-card-1',
                'url' => Backend::url('backend/userroles'),
                'permissions' => ['admins.manage.roles'],
                'order' => 410
            ],
            'admingroups' => [
                'label' => 'backend::lang.user.group.menu_label',
                'description' => 'backend::lang.user.group.menu_description',
                'category' => SettingsManager::CATEGORY_TEAM,
                'icon' => 'octo-icon-user-group',
                'url' => Backend::url('backend/usergroups'),
                'permissions' => ['admins.manage.groups'],
                'order' => 420
            ],
            'branding' => [
                'label' => 'backend::lang.branding.menu_label',
                'description' => 'backend::lang.branding.menu_description',
                'category' => SettingsManager::CATEGORY_SYSTEM,
                'icon' => 'octo-icon-paint-brush-1',
                'class' => 'Backend\Models\BrandSetting',
                'permissions' => ['settings.customize_backend'],
                'order' => 500,
                'keywords' => 'brand style'
            ],
            'editor' => [
                'label' => 'backend::lang.editor.menu_label',
                'description' => 'backend::lang.editor.menu_description',
                'category' => SettingsManager::CATEGORY_SYSTEM,
                'icon' => 'icon-code',
                'class' => 'Backend\Models\EditorSetting',
                'permissions' => ['settings.editor_settings'],
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
                'permissions' => ['preferences'],
                'order' => 510,
                'context' => 'mysettings'
            ],
            'access_logs' => [
                'label' => 'backend::lang.access_log.menu_label',
                'description' => 'backend::lang.access_log.menu_description',
                'category' => SettingsManager::CATEGORY_LOGS,
                'icon' => 'octo-icon-lock',
                'url' => Backend::url('backend/accesslogs'),
                'permissions' => ['utilities.logs'],
                'order' => 920
            ]
        ];
    }
}
