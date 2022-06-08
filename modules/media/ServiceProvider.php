<?php namespace Media;

use Backend;
use BackendAuth;
use Media\Widgets\MediaManager;
use Backend\Classes\Controller as BackendController;
use October\Rain\Support\ModuleServiceProvider;

/**
 * ServiceProvider for Media module
 */
class ServiceProvider extends ModuleServiceProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        parent::register('media');

        // Backend specific
        if ($this->app->runningInBackend()) {
            $this->registerGlobalInstance();
        }
    }

    /**
     * boot the module events.
     */
    public function boot()
    {
        parent::boot('media');
    }

    /**
     * registerNavigation
     */
    public function registerNavigation()
    {
        return [
            'media' => [
                'label' => 'backend::lang.media.menu_label',
                'icon' => 'icon-image',
                'iconSvg' => 'modules/media/assets/images/media-icon.svg',
                'url' => Backend::url('media'),
                'permissions' => ['media.library'],
                'order' => 200
            ]
        ];
    }

    /**
     * registerPermissions
     */
    public function registerPermissions()
    {
        return [
            'media.library' => [
                'label' => 'Access the Media Manager',
                'tab' => 'Media',
                'order' => 300
            ],
            'media.library.create' => [
                'label' => 'Upload Media',
                'comment' => 'backend::lang.permissions.manage_media',
                'tab' => 'Media',
                'order' => 400
            ],
            // 'media.library.update' => [
            //     'label' => 'Modify Media',
            //     'comment' => 'Change meta data and other information',
            //     'tab' => 'Media',
            //     'order' => 500
            // ],
            'media.library.delete' => [
                'label' => 'Delete Media',
                'tab' => 'Media',
                'order' => 600
            ]
        ];
    }

    /**
     * registerFormWidgets
     */
    public function registerFormWidgets()
    {
        return [
            \Media\FormWidgets\MediaFinder::class => 'mediafinder'
        ];
    }

    /**
     * registerMarkupTags
     */
    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'media'  => [\Media\Classes\MediaLibrary::class, 'url'],
            ]
        ];
    }

    /**
     * registerGlobalInstance ensures media Manager widget is available on all backend pages
     */
    protected function registerGlobalInstance()
    {
        BackendController::extend(function($controller) {
            if (!BackendAuth::userHasAccess('media.library')) {
                return;
            }

            $manager = new MediaManager($controller, 'ocmediamanager');
            $manager->bindToController();
        });
    }
}
