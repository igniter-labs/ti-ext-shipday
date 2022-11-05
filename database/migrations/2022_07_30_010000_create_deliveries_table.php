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
            $table->unsignedBigInteger('order_id')->nullable()->unique()->index();
            $table->string('shipday_id')->unqiue();
            $table->bigInteger('fee');
            $table->string('status');
            $table->string('tracking_url')->index()->nullable();
            $table->text('request_data')->nullable();
            $table->text('response_data')->nullable();
            $table->timestamps();
        });

        if (!Schema::hasColumn('orders', 'shipday_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->bigInteger('shipday_id')->nullable();
            });
        }

        if (!Schema::hasColumn('staffs', 'shipday_id')) {
            Schema::table('staffs', function (Blueprint $table) {
                $table->string('telephone')->nullable();
                $table->bigInteger('shipday_id')->nullable();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('igniterlabs_shipday_deliveries');

        if (Schema::hasColumn('orders', 'shipday_id'))
            Schema::dropColumns('orders', ['shipday_id']);

        if (Schema::hasColumn('staffs', 'shipday_id'))
            Schema::dropColumns('staffs', ['telephone', 'shipday_id']);
    }
}
