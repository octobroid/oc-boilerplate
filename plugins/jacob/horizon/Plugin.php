<?php namespace Jacob\Horizon;

use Backend;
use Backend\Facades\BackendAuth;
use Backend\Models\User;
use Illuminate\Foundation\AliasLoader;
use Laravel\Horizon\Horizon;
use System\Classes\PluginBase;

/**
 * OmsFaker Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails(): array
    {
        return [
            'name'        => 'Horizon',
            'description' => 'Horizon provides a beautiful dashboard and code-driven configuration for your October powered Redis queues. Horizon allows you to easily monitor key metrics of your queue system such as job throughput, runtime, and job failures.',
            'author'      => 'Jacob',
            'iconSvg'     => Backend::url('jacob/horizon/horizon/icon')
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->register('Laravel\Horizon\HorizonServiceProvider');

        AliasLoader::getInstance()->alias('Horizon', 'Laravel\Horizon\Horizon');

        Horizon::auth(function ($request) {
            if (!BackendAuth::check()) {
                return false;
            }

            /** @var User $user */
            $user = BackendAuth::getUser();

            return $user->isSuperUser() || $user->hasPermission('jacob.horizon.access');
        });
    }

    /**
     * Registers scheduled tasks that are executed on a regular basis.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function registerSchedule($schedule)
    {
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'jacob.horizon.access' => [
                'tab'   => 'Horizon',
                'label' => 'Access to the Horizon dashboard',
                'roles' => ['developer'],
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return [
            'horizon' => [
                'label' => 'Horizon',
                'url' => Backend::url('jacob/horizon/horizon'),
                'iconSvg' => Backend::url('jacob/horizon/horizon/icon'),
                'order' => 500,
                'permissions' => ['jacob.horizon.access'],
            ]
        ];
    }
}
