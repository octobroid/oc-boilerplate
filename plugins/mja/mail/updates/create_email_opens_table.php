<?php namespace Mja\Mail\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateEmailOpensTable extends Migration
{

    public function up()
    {
        Schema::create('mja_mail_email_opens', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('email_id');
            $table->string('ip_address');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mja_mail_email_opens');
    }

}
