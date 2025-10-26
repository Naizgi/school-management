<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Student;
use App\Models\ParentModel;
use App\Models\ClassModel;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition()
    {
        return [
            'parent_id'  => \App\Models\ParentModel::factory(),
            'name' => $this->faker->name,
            'class_id' => ClassModel::factory(),
            'roll_number' => $this->faker->unique()->numberBetween(1000, 9999),
            'academic_year' => now()->year,
            'date_of_admission' => $this->faker->date,
            'father_name' => $this->faker->name('male'),
            'mother_name' => $this->faker->name('female'),
            'date_of_birth' => $this->faker->date,
            'age' => $this->faker->numberBetween(6, 18),
            'address' => $this->faker->address,
            'profile_picture' => $this->faker->imageUrl(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
