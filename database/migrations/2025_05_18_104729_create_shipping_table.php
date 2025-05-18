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
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->timestamp('estimated_date')->nullable();
            $table->string('status')->default('pending')
                ->comment('pending, shipped, delivered, cancelled');
            $table->string('address');
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->text('note')->nullable();
            $table->string('ghn_order_code')->nullable();
            $table->string('province_name')->nullable();
            $table->string('district_name')->nullable();
            $table->string('ward_name')->nullable();
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->timestamp('expected_delivery_time')->nullable();
            $table->string('shipping_method')->default('standard')
                ->comment('standard, express, economy');
            $table->timestamps();
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