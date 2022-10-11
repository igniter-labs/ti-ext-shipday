<?php

namespace IgniterLabs\Shipday\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveriesTable extends Migration
{
    public function up()
    {
        Schema::create('igniterlabs_shipday_deliveries', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('shipday_order_id')->unqiue();
            $table->unsignedBigInteger('order_id')->nullable()->unique()->index();
            $table->bigInteger('fee');
            $table->string('status');
            $table->string('tracking_url')->index()->nullable();
            $table->text('response_data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('igniterlabs_shipday_deliveries');
    }
}
