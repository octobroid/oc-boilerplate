<?php

namespace Renatio\BackupManager\Behaviors;

use Backend\Classes\ControllerBehavior;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use October\Rain\Support\Facades\Flash;
use Renatio\BackupManager\Models\Backup;

/**
 * Class BackupController
 * @package Renatio\BackupManager\Behaviors
 */
class BackupController extends ControllerBehavior
{

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function onCreate()
    {
        $params = post('only_db') ? ['--only-db' => true] : [];

        $result = Artisan::call('backup:run', $params);

        $this->saveConsoleOutputToLog();

        $result === 0
            ? Flash::success(e(trans('renatio.backupmanager::lang.backup.success')))
            : Flash::error(e(trans('renatio.backupmanager::lang.backup.failed')));

        return redirect()->refresh();
    }

    /**
     * @return mixed
     */
    public function onClean()
    {
        $result = Artisan::call('backup:clean');

        $this->saveConsoleOutputToLog();

        $result === 0
            ? Flash::success(e(trans('renatio.backupmanager::lang.clean.success')))
            : Flash::error(e(trans('renatio.backupmanager::lang.clean.failed')));

        return $this->controller->listRefresh();
    }

    /**
     * @return mixed
     */
    public function onPreviewLog()
    {
        $log = Storage::exists('backup.log')
            ? Storage::get('backup.log')
            : e(trans('renatio.backupmanager::lang.log.empty'));

        return $this->makePartial('$/renatio/backupmanager/controllers/backups/_log.htm', compact('log'));
    }

    /**
     * @param $id
     * @return mixed
     */
    public function download($id)
    {
        $backup = Backup::findOrFail($id);

        return $backup->download();
    }

    /**
     * @return void
     */
    protected function saveConsoleOutputToLog()
    {
        Storage::put('backup.log', Artisan::output());
    }

}