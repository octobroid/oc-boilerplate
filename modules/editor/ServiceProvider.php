<?php namespace Editor;

use App;
use Backend;
use BackendMenu;
use BackendAuth;
use Backend\Models\UserRole;
use October\Rain\Support\ModuleServiceProvider;

/**
 * ServiceProvider for Editor module
 */
class ServiceProvider extends ModuleServiceProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        parent::register('editor');

        /*
         * Backend specific
         */
        if (App::runningInBackend()) {
            $this->registerBackendNavigation();
            $this->registerBackendPermissions();
        }
    }

    /**
     * boot the module events.
     */
    public function boot()
    {
        parent::boot('editor');
    }

    /*
     * Register navigation
     */
    protected function registerBackendNavigation()
    {
        BackendMenu::registerCallback(function ($manager) {
            $manager->registerMenuItems('October.Editor', [
                'editor' => [
                    'label' => 'editor::lang.editor.menu_label',
                    'icon' => 'icon-pencil',
                    'iconSvg' => 'modules/editor/assets/images/editor-icon.svg',
                    'url' => Backend::url('editor'),
                    'order' => 90,
                    'permissions' => [
                        'editor.access_editor'
                    ]
                ]
            ]);
        });
    }

    /*
     * Register permissions
     */
    protected function registerBackendPermissions()
    {
        BackendAuth::registerCallback(function ($manager) {
            $manager->registerPermissions('October.Editor', [
                'editor.access_editor' => [
                    'label' => 'editor::lang.permissions.access_editor',
                    'tab' => 'editor::lang.permissions.name',
                    'roles' => UserRole::CODE_DEVELOPER,
                    'order' => 100
                ]
            ]);
        });
    }
}