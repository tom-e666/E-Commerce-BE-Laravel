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
        Schema::create('shippings', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('order_id')
                  ->constrained('orders') // Assumes your orders table is named 'orders'
                  ->onDelete('cascade'); // Optional: if an order is deleted, its shipping record is also deleted
            $table->string('tracking_code')->nullable();
            $table->string('carrier')->nullable();
            $table->date('estimated_date')->nullable(); // Or $table->dateTime('estimated_date')->nullable();
            $table->string('status')->default('pending'); // Default status, e.g., 'pending', 'packed', 'shipped', 'delivered', 'cancelled'
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shippings');
    }
};