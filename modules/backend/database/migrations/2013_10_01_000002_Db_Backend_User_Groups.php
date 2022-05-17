<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class DbBackendUserGroups extends Migration
{
    public function up()
    {
        Schema::create('backend_user_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique('name_unique');
            $table->string('code')->nullable()->index('code_index');
            $table->text('description')->nullable();
            $table->boolean('is_new_user_default')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('backend_user_groups');
    }
}
