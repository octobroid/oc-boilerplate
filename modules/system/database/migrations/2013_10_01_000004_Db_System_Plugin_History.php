<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class DbSystemPluginHistory extends Migration
{
    public function up()
    {
        Schema::create('system_plugin_history', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->index();
            $table->string('type', 20)->index();
            $table->string('version', 50);
            $table->mediumText('detail')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_plugin_history');
    }
}
