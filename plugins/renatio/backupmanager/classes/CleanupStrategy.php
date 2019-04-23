<?php

namespace Renatio\BackupManager\Classes;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Renatio\BackupManager\Models\Backup as BackupModel;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupCollection;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;

/**
 * Class CleanupStrategy
 * @package Renatio\BackupManager\Classes
 */
class CleanupStrategy extends DefaultStrategy
{

    /**
     * @param Collection $backupsPerPeriod
     */
    protected function removeBackupsForAllPeriodsExceptOne(Collection $backupsPerPeriod)
    {
        $backupsPerPeriod->each(function (Collection $groupedBackupsByDateProperty, string $periodName) {
            $groupedBackupsByDateProperty->each(function (Collection $group) {
                $group->shift();

                $group->each(function (Backup $backup) {
                    $backup->delete();

                    $this->deleteModelByPath($backup->path());
                });
            });
        });
    }

    /**
     * @param Carbon $endDate
     * @param BackupCollection $backups
     */
    protected function removeBackupsOlderThan(Carbon $endDate, BackupCollection $backups)
    {
        $backups->filter(function (Backup $backup) use ($endDate) {
            return $backup->exists() && $backup->date()->lt($endDate);
        })->each(function (Backup $backup) {
            $backup->delete();

            $this->deleteModelByPath($backup->path());
        });
    }

    /**
     * @param BackupCollection $backups
     */
    protected function removeOldBackupsUntilUsingLessThanMaximumStorage(BackupCollection $backups)
    {
        if ( ! $oldest = $backups->oldest()) {
            return;
        }

        $maximumSize = $this->config->get('backup.cleanup.defaultStrategy.deleteOldestBackupsWhenUsingMoreMegabytesThan')
            * 1024 * 1024;

        if (($backups->size() + $this->newestBackup->size()) <= $maximumSize) {
            return;
        }

        $oldest->delete();

        $this->deleteModelByPath($oldest->path());

        $backups = $backups->filter->exists();

        $this->removeOldBackupsUntilUsingLessThanMaximumStorage($backups);
    }

    /**
     * @param $path
     */
    protected function deleteModelByPath($path)
    {
        $model = BackupModel::where('file_path', $path)->first();

        if ( ! empty($model)) {
            $model->delete();
        }
    }

}