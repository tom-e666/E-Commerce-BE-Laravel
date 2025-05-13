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
            // Change total_price to DECIMAL with appropriate precision and scale
            // Example: DECIMAL(15, 2) allows for numbers up to 99,999,999,999,999.99
            $table->decimal('total_price', 15, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert to the previous type if known, e.g., float or a smaller decimal
            // This depends on what it was before. For safety, you might just leave it
            // or revert to a generic float if unsure.
            // $table->float('total_price')->change(); // Example if it was float
        });
    }
};