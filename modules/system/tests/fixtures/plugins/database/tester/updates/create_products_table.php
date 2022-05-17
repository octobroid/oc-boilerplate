<?php namespace Database\Tester\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('database_tester_products', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('code')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('database_tester_authors_products', function ($table) {
            $table->string('author_code');
            $table->string('product_code');
            $table->primary(['author_code', 'product_code']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('database_tester_products');
        Schema::dropIfExists('database_tester_authors_products');
    }
}
