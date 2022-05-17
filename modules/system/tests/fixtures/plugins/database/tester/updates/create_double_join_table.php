<?php namespace Database\Tester\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateDoubleJoinTable extends Migration
{
    public function up()
    {
        Schema::create('database_tester_double_joins', function ($table) {
            $table->integer('host_id')->nullable();
            $table->string('host_type')->nullable();
            $table->integer('entity_id')->nullable();
            $table->string('entity_type')->nullable();

            $table->index(['host_id', 'host_type'], 'dtdj_host_master_index');
            $table->index(['entity_id', 'entity_type'], 'dtdj_entity_master_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('database_tester_double_joins');
    }
}
