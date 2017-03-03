<?php namespace Mja\Mail\Updates;

use DB;
use October\Rain\Database\Updates\Migration;

class MakeCodeColumnNullable extends Migration
{

    public function up()
    {
        DB::statement('ALTER TABLE `mja_mail_email_log` MODIFY `code` VARCHAR(255) NULL;');
    }

    public function down()
    {
        DB::statement('ALTER TABLE `mja_mail_email_log` MODIFY `code` VARCHAR(255);');
    }

}
