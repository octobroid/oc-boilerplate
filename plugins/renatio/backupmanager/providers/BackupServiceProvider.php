<?php

namespace Renatio\BackupManager\Providers;

use Illuminate\Notifications\NotificationServiceProvider;
use Illuminate\Support\ServiceProvider;
use Olssonm\BackupShield\BackupShieldServiceProvider;
use Spatie\Backup\BackupServiceProvider as LaravelBackupServiceProvider;

/**
 * Class BackupServiceProvider
 * @package Renatio\BackupManager\Providers
 */
class BackupServiceProvider extends ServiceProvider
{

    /**
     * @return void
     */
    public function register()
    {
        $this->app->register(LaravelBackupServiceProvider::class);

        $this->app->register(BackupShieldServiceProvider::class);

        /*
         * Required to fire BackupWasSuccessful event
         */
        $this->app->register(NotificationServiceProvider::class);
    }

}