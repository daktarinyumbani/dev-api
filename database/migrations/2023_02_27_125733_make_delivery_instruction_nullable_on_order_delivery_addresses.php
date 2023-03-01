<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeDeliveryInstructionNullableOnOrderDeliveryAddresses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_delivery_addresses', function (Blueprint $table) {
            $table->string('delivery_instruction')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_delivery_addresses', function (Blueprint $table) {
            $table->string('delivery_instruction')->nullable()->change();
        });
    }
}
