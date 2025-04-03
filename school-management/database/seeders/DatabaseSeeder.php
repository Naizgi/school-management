<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'user_name' => 'test_user',
            'phone_number' => '1234567890',
            'password' => bcrypt('password'), // Hash the password
            'role' => 'admin',
            'profile_picture' => 'default.png',
        ]);
    }
}
