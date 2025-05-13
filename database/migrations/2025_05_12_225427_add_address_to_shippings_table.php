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
            // Add the new address column
            // You can choose TEXT for longer addresses or STRING if they are relatively short.
            // Make it nullable if an address is not always required.
            $table->text('address')->nullable()->after('status'); // Or ->string('address')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shippings', function (Blueprint $table) {
            // Remove the address column if the migration is rolled back
            $table->dropColumn('address');
        });
    }
};