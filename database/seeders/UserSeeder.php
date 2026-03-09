<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Admin User',
                'email' => 'admin@payment.com',
                'password' => Hash::make('password'),
                'role' => 'ADMIN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@payment.com',
                'password' => Hash::make('password'),
                'role' => 'MANAGER',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Finance User',
                'email' => 'finance@payment.com',
                'password' => Hash::make('password'),
                'role' => 'FINANCE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Regular User',
                'email' => 'user@payment.com',
                'password' => Hash::make('password'),
                'role' => 'USER',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
