<?php

namespace Renatio\BackupManager\Behaviors;

use Illuminate\Support\Facades\Storage;
use October\Rain\Database\ModelBehavior;

/**
 * Class BackupModel
 * @package Renatio\BackupManager\Behaviors
 */
class BackupModel extends ModelBehavior
{

    /**
     * @return void
     */
    public function deleteFile()
    {
        foreach ($this->disks() as $disk) {
            $this->deleteFromDisk($disk);
        }
    }

    /**
     * @return array
     */
    public function disks()
    {
        return explode(', ', $this->model->filesystems);
    }

    /**
     * @param $disk
     * @return mixed
     */
    public function exists($disk)
    {
        return Storage::disk($disk)->exists($this->model->file_path);
    }

    /**
     * @param $disk
     */
    protected function deleteFromDisk($disk)
    {
        if ($this->exists($disk)) {
            Storage::disk($disk)->delete($this->model->file_path);
        }
    }

}