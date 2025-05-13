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
        Schema::table('order_items', function (Blueprint $table) {
            // Change price column to DECIMAL with precision 15 and scale 2
            // This allows numbers up to 9,999,999,999,999.99
            $table->decimal('price', 15, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // If you need to revert, change back to whatever type it was before
            // For example, if it was a regular DECIMAL(8,2) before:
            // $table->decimal('price', 8, 2)->change();
        });
    }
};