<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_products', function (Blueprint $table) {
            
            $table->id();
            $table->integer('business_id');
            $table->integer('product_id');
            $table->string('qty');
            $table->string('remaining_qty');
            $table->integer('banch');
            $table->string('status');
            $table->integer('recorded_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_products');
    }
}
