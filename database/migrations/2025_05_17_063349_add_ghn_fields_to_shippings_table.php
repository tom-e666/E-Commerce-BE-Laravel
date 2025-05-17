<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shippings', function (Blueprint $table) {
            $table->string('ghn_order_code')->nullable();
            $table->integer('district_id')->nullable();
            $table->string('ward_code')->nullable();
            $table->decimal('shipping_fee', 10, 2)->nullable();
            $table->timestamp('expected_delivery_time')->nullable();
        });
    }

    public function down()
    {
        Schema::table('shippings', function (Blueprint $table) {
            $table->dropColumn([
                'ghn_order_code',
                'district_id',
                'ward_code',
                'shipping_fee',
                'expected_delivery_time'
            ]);
        });
    }
};