<?php

namespace Renatio\BackupManager\Classes;

use Renatio\BackupManager\Models\Settings;

/**
 * Class Schedule
 * @package Renatio\BackupManager\Classes
 */
class Schedule
{

    /**
     * @var
     */
    protected $settings;

    /**
     * @var
     */
    protected $schedule;

    public function __construct($schedule)
    {
        $this->schedule = $schedule;
        $this->settings = Settings::instance();
    }

    /**
     * @return $this
     */
    public function databaseBackup()
    {
        $this->scheduleCommand('backup:run', $this->settings->db_scheduler, ['--only-db']);

        return $this;
    }

    /**
     * @return $this
     */
    public function appBackup()
    {
        $this->scheduleCommand('backup:run', $this->settings->app_scheduler);

        return $this;
    }

    /**
     * @return $this
     */
    public function cleanOldBackups()
    {
        $this->scheduleCommand('backup:clean', $this->settings->clean_scheduler);

        return $this;
    }

    /**
     * @param $command
     * @param $when
     * @param array $options
     * @return mixed
     */
    public function scheduleCommand($command, $when, $options = [])
    {
        if ( ! empty($when)) {
            return $this->schedule->command($command, $options)
                ->$when()
                ->sendOutputTo(storage_path('app/backup.log'));
        }
    }

}