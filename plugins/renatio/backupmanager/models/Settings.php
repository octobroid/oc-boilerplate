<?php

namespace Renatio\BackupManager\Models;

use October\Rain\Database\Model;
use October\Rain\Database\Traits\Encryptable;

/**
 * Class Settings
 * @package Renatio\BackupManager\Models
 */
class Settings extends Model
{

    use Encryptable;

    /**
     * @var array
     */
    public $implement = ['System.Behaviors.SettingsModel'];

    /**
     * @var string
     */
    public $settingsCode = 'renatio_backupmanager_settings';

    /**
     * @var string
     */
    public $settingsFields = 'fields.yaml';

    /**
     * @var array
     */
    protected $encryptable = ['zip_password'];

    /**
     * @return void
     */
    public function initSettingsData()
    {
        foreach ($this->getDefaultSettings() as $key => $setting) {
            $this->{$key} = $setting;
        }
    }

    /**
     * @return array
     */
    public function getDefaultSettings()
    {
        return [
            'databases' => [
                config('database.default'),
            ],
            'gzip_database_dump' => false,
            'follow_links' => false,
            'include' => [
            ],
            'exclude' => [
                ['path' => 'vendor'],
                ['path' => 'node_modules']
            ],
            'backup_name' => config('app.name', 'backups'),
            'filename_prefix' => '',
            'disks' => ['local'],
            'keep_all' => 7,
            'keep_daily' => 16,
            'keep_weekly' => 8,
            'keep_monthly' => 4,
            'keep_yearly' => 2,
            'delete_oldest_when_mb' => 5000,
        ];
    }

    /**
     * @return array
     */
    public function getIncludePaths()
    {
        if (empty($this->include)) {
            return [base_path()];
        }

        return collect($this->include)
            ->flatten()
            ->map(function ($path) {
                return base_path($path);
            })
            ->toArray();
    }

    /**
     * @return array
     */
    public function getExcludePaths()
    {
        if (empty($this->exclude)) {
            return [];
        }

        return collect($this->exclude)
            ->flatten()
            ->map(function ($path) {
                return base_path($path);
            })
            ->toArray();
    }

    /**
     * @return array
     */
    public function getDatabasesOptions()
    {
        $keys = array_keys(config('database.connections'));

        return array_combine($keys, $keys);
    }

    /**
     * @return array
     */
    public function getDisksOptions()
    {
        $keys = array_keys(config('filesystems.disks'));

        return array_combine($keys, $keys);
    }

    /**
     * @return array
     */
    public function getSchedulerOptions()
    {
        return [
            '' => trans('renatio.backupmanager::lang.schedule.choose'),
            'everyFiveMinutes' => trans('renatio.backupmanager::lang.schedule.every_five_minutes'),
            'everyTenMinutes' => trans('renatio.backupmanager::lang.schedule.every_ten_minutes'),
            'everyThirtyMinutes' => trans('renatio.backupmanager::lang.schedule.every_thirty_minutes'),
            'hourly' => trans('renatio.backupmanager::lang.schedule.hourly'),
            'daily' => trans('renatio.backupmanager::lang.schedule.daily'),
            'weekly' => trans('renatio.backupmanager::lang.schedule.weekly'),
            'monthly' => trans('renatio.backupmanager::lang.schedule.monthly'),
            'yearly' => trans('renatio.backupmanager::lang.schedule.yearly'),
        ];
    }

    /**
     * @return array
     */
    public function getEncryptionOptions()
    {
        return [
            '' => trans('renatio.backupmanager::lang.schedule.choose'),
            'ENCRYPTION_DEFAULT' => 'PKWARE/ZipCrypto',
            'ENCRYPTION_WINZIP_AES_128' => 'AES 128',
            'ENCRYPTION_WINZIP_AES_192' => 'AES 192',
            'ENCRYPTION_WINZIP_AES_256' => 'AES 256',
        ];
    }

}