<?php

use Backend\Models\UserRole;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class DbBackendUserRoles extends Migration
{
    public function up()
    {
        Schema::create('backend_user_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique('role_unique');
            $table->string('code')->nullable()->index('role_code_index');
            $table->text('description')->nullable();
            $table->text('permissions')->nullable();
            $table->boolean('is_system')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('backend_user_roles');
    }
}
