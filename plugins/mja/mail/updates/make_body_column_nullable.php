<?php namespace Mja\Mail\Updates;

use DB;
use October\Rain\Database\Updates\Migration;

class MakeBodyColumnNullable extends Migration
{

    public function up()
    {
        DB::statement('ALTER TABLE `mja_mail_email_log` MODIFY `body` text COLLATE utf8_unicode_ci NULL;');
    }

    public function down()
    {
        DB::statement('ALTER TABLE `mja_mail_email_log` MODIFY `body` text COLLATE utf8_unicode_ci NOT NULL;');
    }

}
