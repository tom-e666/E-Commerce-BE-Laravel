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
        Schema::table('orders', function (Blueprint $table) {
            // Add status field if it doesn't exist
            if (!Schema::hasColumn('orders', 'status')) {
                $table->string('status')->default('pending')
                    ->comment('pending, confirmed, shipped, delivered, cancelled, returned');
            }
            
            // Make sure there's a shipping relationship support via order_id
            if (!Schema::hasColumn('orders', 'shipping_id')) {
                $table->foreignId('shipping_id')->nullable()
                    ->after('status')
                    ->constrained('shippings')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Only drop columns we added
            if (Schema::hasColumn('orders', 'shipping_id')) {
                $table->dropForeign(['shipping_id']);
                $table->dropColumn('shipping_id');
            }
            
            // We're not dropping status in down() as it might be used by other parts of the app
        });
    }
};