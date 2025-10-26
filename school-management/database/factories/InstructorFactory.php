<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Instructor;
use App\Models\User;
use App\Models\ClassModel;

class InstructorFactory extends Factory
{
    protected $model = Instructor::class;

    public function definition()
    {
        return [
            'user_id' => User::factory()->create(['role' => 'Instructor'])->id,
            'name' => $this->faker->name,
            'class_id' => rand(0, 1) ? ClassModel::factory() : null, // Nullable
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
