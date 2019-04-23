<?php

use Renatio\BackupManager\Listeners\BackupSuccess;
use Spatie\Backup\Events\BackupWasSuccessful;

Event::listen(BackupWasSuccessful::class, BackupSuccess::class);