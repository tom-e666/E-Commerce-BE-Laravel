<?php

namespace Database\Seeders;

use App\Models\UserCredential;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        UserCredential::create([
            'email' => 'admin@example.com',
            'phone' => '1234567890',
            'password' => Hash::make('password'),
            'full_name' => 'Admin User',
            'role' => UserCredential::ROLE_ADMIN,
            'email_verified' => true
        ]);
        
        // Create staff user
        UserCredential::create([
            'email' => 'staff@example.com',
            'phone' => '2345678901',
            'password' => Hash::make('password'),
            'full_name' => 'Staff User',
            'role' => UserCredential::ROLE_STAFF,
            'email_verified' => true
        ]);
        
        // Create regular users
        UserCredential::create([
            'email' => 'user@example.com',
            'phone' => '3456789012',
            'password' => Hash::make('password'),
            'full_name' => 'Regular User',
            'role' => UserCredential::ROLE_USER,
            'email_verified' => true
        ]);
    }
}