<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'email' => 'seller@example.com',
            'full_name' => 'seller One',
            'password' => 'password',
            'role' => 'seller',
            'is_active' => true,
            ]);

        User::create([
            'email' => 'seller2@example.com',            
            'full_name' => 'seller Two',
            'password' => 'password123',
            'role' => 'seller',
            'is_active' => true,
            ]);

        User::create([
            'email' => 'user@example.com',
            'full_name' => 'user bidder',
            'password' => 'passworduser',
            'role' => 'bidder',
            'is_active' => true,
            ]);
        User::create([
            'email' => 'admin@example.com',
            'full_name' => 'admin',
            'password' => 'passwordadmin',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
