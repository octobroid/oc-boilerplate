<?php

namespace Renatio\BackupManager\Updates;

use October\Rain\Database\Updates\Migration;
use October\Rain\Support\Facades\Schema;

/**
 * Class CreateBackupsTable
 * @package Renatio\BackupManager\Updates
 */
class CreateBackupsTable extends Migration
{

    /**
     * @return void
     */
    public function up()
    {
        Schema::create('renatio_backupmanager_backups', function ($table) {
            $table->increments('id');
            $table->string('disk_name');
            $table->string('file_path');
            $table->enum('type', ['db', 'app']);
            $table->string('filesystems');
            $table->unsignedInteger('file_size');
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('renatio_backupmanager_backups');
    }

}