<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'user_name' => $this->faker->userName(),
            'phone_number' => $this->faker->phoneNumber(),
            'password' => bcrypt('password'),
            'role' => 'Parent', // Default role for testing
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'profile_picture' => 'default.png',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
