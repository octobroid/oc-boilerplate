<?php namespace Editor;

use Backend;
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
    }

    /**
     * boot the module events.
     */
    public function boot()
    {
        parent::boot('editor');
    }

    /**
     * registerNavigation
     */
    public function registerNavigation()
    {
        return [
            'editor' => [
                'label' => 'editor::lang.editor.menu_label',
                'icon' => 'icon-pencil',
                'iconSvg' => 'modules/editor/assets/images/editor-icon.svg',
                'url' => Backend::url('editor'),
                'order' => 90,
                'permissions' => [
                    'editor'
                ]
            ]
        ];
    }

    /**
     * registerPermissions
     */
    public function registerPermissions()
    {
        return [
            'editor' => [
                'label' => 'Access the Editor Tool',
                'comment' => 'editor::lang.permissions.access_editor',
                'tab' => 'Editor',
                'roles' => UserRole::CODE_DEVELOPER,
                'order' => 100
            ],
        ];
    }
}
