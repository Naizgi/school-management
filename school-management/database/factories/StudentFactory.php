<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Student;
use App\Models\ParentModel;
use App\Models\SchoolClass;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition()
    {
        return [
            'parent_id' => ParentModel::factory(),
            'name' => $this->faker->name(),
            'class_id' => SchoolClass::factory(),
            'roll_number' => $this->faker->randomNumber(5),
            'academic_year' => '2024',
            'date_of_admission' => now(),
            'father_name' => $this->faker->name('male'),
            'mother_name' => $this->faker->name('female'),
            'date_of_birth' => $this->faker->date(),
            'age' => rand(5, 18),
            'address' => $this->faker->address(),
            'profile_picture' => 'default.png',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
