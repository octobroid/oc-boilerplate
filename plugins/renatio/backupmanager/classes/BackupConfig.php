<?php

namespace Renatio\BackupManager\Classes;

use Renatio\BackupManager\Models\Settings;

/**
 * Class BackupConfig
 * @package Renatio\BackupManager\Classes
 */
class BackupConfig
{

    /**
     * @var
     */
    protected $settings;

    public function __construct()
    {
        $this->settings = Settings::instance();
    }

    /**
     * Initialize backup config with backend settings
     *
     * @return void
     */
    public function init()
    {
        $this->setSource();

        $this->setDestination();

        $this->setCleanupStrategy();

        $this->setPasswordProtection();

        $this->disableNotifications();
    }

    /**
     * @return void
     */
    protected function setSource()
    {
        config([
            'backup.backup.source.files.include' => $this->settings->getIncludePaths(),
            'backup.backup.source.files.exclude' => $this->settings->getExcludePaths(),
            'backup.backup.source.files.followLinks' => $this->settings->follow_links,
            'backup.backup.source.databases' => $this->settings->databases ?: [config('database.default')],
            'backup.backup.gzip_database_dump' => $this->settings->gzip_database_dump,
        ]);
    }

    /**
     * @return void
     */
    protected function setDestination()
    {
        config([
            'backup.backup.name' => $this->settings->backup_name,
            'backup.backup.destination' => [
                'filename_prefix' => $this->settings->filename_prefix,
                'disks' => $this->settings->disks,
            ],
        ]);
    }

    /**
     * Setup custom strategy to delete backups from database
     *
     * @return void
     */
    protected function setCleanupStrategy()
    {
        config([
            'backup.cleanup' => [
                'strategy' => CleanupStrategy::class,
                'defaultStrategy' => [
                    'keepAllBackupsForDays' => $this->settings->keep_all,
                    'keepDailyBackupsForDays' => $this->settings->keep_daily,
                    'keepWeeklyBackupsForWeeks' => $this->settings->keep_weekly,
                    'keepMonthlyBackupsForMonths' => $this->settings->keep_monthly,
                    'keepYearlyBackupsForYears' => $this->settings->keep_yearly,
                    'deleteOldestBackupsWhenUsingMoreMegabytesThan' => $this->settings->delete_oldest_when_mb,
                ],
            ],
        ]);
    }

    /**
     * @return void
     */
    protected function setPasswordProtection()
    {
        $encryption = $this->settings->zip_password_encryption ?: 'ENCRYPTION_DEFAULT';

        config([
            'backup-shield.password' => $this->settings->zip_password,
            'backup-shield.encryption' => constant('\Olssonm\BackupShield\Encryption::' . $encryption),
        ]);
    }

    /**
     * October does not support Laravel Notifications
     *
     * @return void
     */
    protected function disableNotifications()
    {
        config([
            'backup.notifications.notifications' => [
                \Spatie\Backup\Notifications\Notifications\BackupHasFailed::class => [],
                \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFound::class => [],
                \Spatie\Backup\Notifications\Notifications\CleanupHasFailed::class => [],
                \Spatie\Backup\Notifications\Notifications\BackupWasSuccessful::class => [],
                \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFound::class => [],
                \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessful::class => [],
            ],
        ]);
    }

}