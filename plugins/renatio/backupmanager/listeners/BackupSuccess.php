<?php

namespace Renatio\BackupManager\Listeners;

use Renatio\BackupManager\Models\Backup;
use Spatie\Backup\Events\BackupWasSuccessful;

/**
 * Class BackupSuccess
 * @package Renatio\BackupManager\Listeners
 */
class BackupSuccess
{

    /**
     * @param BackupWasSuccessful $event
     */
    public function handle(BackupWasSuccessful $event)
    {
        Backup::saveRecord($event->backupDestination);
    }

}