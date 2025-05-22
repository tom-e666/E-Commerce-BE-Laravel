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
        // Update user_credentials table with any missing fields
        Schema::table('user_credentials', function (Blueprint $table) {
            // Add email_verification fields if they don't exist
            if (!Schema::hasColumn('user_credentials', 'email_verified')) {
                $table->boolean('email_verified')->default(false)->after('role');
            }
            
            if (!Schema::hasColumn('user_credentials', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email_verified');
            }
            
            if (!Schema::hasColumn('user_credentials', 'email_verification_token')) {
                $table->string('email_verification_token')->nullable()->after('email_verified_at');
            }
            
            if (!Schema::hasColumn('user_credentials', 'email_verification_sent_at')) {
                $table->timestamp('email_verification_sent_at')->nullable()->after('email_verification_token');
            }
        });

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove added columns only (we don't want to drop the whole table in down())
        Schema::table('user_credentials', function (Blueprint $table) {
            $table->dropColumn([
                'email_verified',
                'email_verified_at', 
                'email_verification_token',
                'email_verification_sent_at'
            ]);
        });

        Schema::dropIfExists('refresh_tokens');
    }
};
