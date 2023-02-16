<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceProvidersServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_providers_services', function (Blueprint $table) {
            //
            $table->id();
            $table->unsignedBigInteger('service_provider_id');
            $table->unsignedBigInteger('service_id');

            $table->foreign('service_provider_id')->references('id')->on('service_providers')
                ->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_providers_services');
    }
}
