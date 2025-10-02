<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            "full_name" => "fachri",
            "email" => "fachri@example.com",
            "phone_number" => "08123456789",
            "npwp" => "1234567890123456",
            "password" => "password123",
            "role" => "admin",
            "is_active" => true
        ]);
    }
}