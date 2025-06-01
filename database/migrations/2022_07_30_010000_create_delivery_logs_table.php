<?php

namespace IgniterLabs\Shipday\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryLogsTable extends Migration
{
    public function up()
    {
        Schema::create('igniterlabs_shipday_logs', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('shipday_id')->unqiue();
            $table->bigInteger('fee')->nullable();
            $table->string('status')->nullable();
            $table->bigInteger('carrier_id')->nullable();
            $table->string('tracking_url')->index()->nullable();
            $table->text('request_data')->nullable();
            $table->text('response_data')->nullable();
            $table->timestamps();
        });

        if (!Schema::hasColumn('orders', 'shipday_id')) {
            Schema::table('orders', function(Blueprint $table) {
                $table->bigInteger('shipday_id')->nullable();
            });
        }

        if (!Schema::hasColumn('admin_users', 'telephone')) {
            Schema::table('admin_users', function(Blueprint $table) {
                $table->string('telephone')->after('email')->nullable();
            });
        }

        if (!Schema::hasColumn('admin_users', 'shipday_id')) {
            Schema::table('admin_users', function(Blueprint $table) {
                $table->bigInteger('shipday_id')->nullable();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('igniterlabs_shipday_deliveries');
        Schema::dropIfExists('igniterlabs_shipday_logs');

        if (Schema::hasColumn('orders', 'shipday_id')) {
            Schema::dropColumns('orders', ['shipday_id']);
        }

        if (Schema::hasColumn('admin_users', 'shipday_id')) {
            Schema::dropColumns('admin_users', ['telephone', 'shipday_id']);
        }
    }
}
