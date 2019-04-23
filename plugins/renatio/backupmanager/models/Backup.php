<?php

namespace Renatio\BackupManager\Models;

use Illuminate\Support\Facades\File;
use October\Rain\Database\Model;
use Renatio\BackupManager\Behaviors\BackupModel;
use Renatio\BackupManager\Classes\DownloadResponse;
use Spatie\Backup\BackupDestination\BackupDestination;

/**
 * Class Backup
 * @package Renatio\BackupManager\Models
 */
class Backup extends Model
{

    /**
     * @var string
     */
    public $table = 'renatio_backupmanager_backups';

    /**
     * @var array
     */
    public $implement = [
        BackupModel::class,
    ];

    /**
     * @var array
     */
    protected $fillable = ['disk_name', 'file_path', 'type', 'filesystems', 'file_size'];

    /**
     * @param BackupDestination $backupDestination
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public static function saveRecord(BackupDestination $backupDestination)
    {
        $backup = $backupDestination->newestBackup();

        return static::create([
            'disk_name' => basename($backup->path()),
            'file_path' => $backup->path(),
            'file_size' => $backup->size(),
            'filesystems' => $backupDestination->diskName(),
        ]);
    }

    /**
     * @param $val
     * @return mixed
     */
    public function getFileSizeAttribute($val)
    {
        return File::sizeToString($val);
    }

    /**
     * @return void
     */
    public function afterDelete()
    {
        $this->deleteFile();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download()
    {
        return (new DownloadResponse($this))->create();
    }

}