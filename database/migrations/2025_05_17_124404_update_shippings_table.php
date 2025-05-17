<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shippings', function (Blueprint $table) {
            // Add fields for GHN integration
            $table->string('shipping_method')->default('SHOP')->after('expected_delivery_time');
            $table->integer('weight')->nullable()->after('shipping_method');
            
            // Modify status field to include all possible values
            // This doesn't change existing data but documents the values
            $table->string('status')->default('pending')
                ->comment('pending, packed, shipped, delivered, cancelled')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shippings', function (Blueprint $table) {
            $table->dropColumn([
                'ghn_order_code',
                'province_name', 
                'district_name',
                'ward_name',
                'shipping_fee',
                'expected_delivery_time',
                'shipping_method',
                'weight'
            ]);
            
            // Remove the comment from status
            $table->string('status')->default('pending')->change();
        });
    }
};