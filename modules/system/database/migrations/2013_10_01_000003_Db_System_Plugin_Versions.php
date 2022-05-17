<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class DbSystemPluginVersions extends Migration
{
    public function up()
    {
        Schema::create('system_plugin_versions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->index();
            $table->string('version', 50);
            $table->boolean('is_frozen')->default(0);
            $table->boolean('is_disabled')->default(0);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_plugin_versions');
    }
}
