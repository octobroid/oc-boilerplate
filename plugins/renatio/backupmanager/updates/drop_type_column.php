<?php

namespace Renatio\BackupManager\Updates;

use October\Rain\Database\Updates\Migration;
use October\Rain\Support\Facades\Schema;
use Renatio\BackupManager\Models\Settings;

/**
 * Class DropTypeColumn
 * @package Renatio\BackupManager\Updates
 */
class DropTypeColumn extends Migration
{

    /**
     * @return void
     */
    public function up()
    {
        Schema::table('renatio_backupmanager_backups', function ($table) {
            $table->dropColumn('type');
        });

        $settings = Settings::instance();

        $settings->resetDefault();
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('renatio_backupmanager_backups', function ($table) {
            $table->enum('type', ['db', 'app']);
        });
    }

}