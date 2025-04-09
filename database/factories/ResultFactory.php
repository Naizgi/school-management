<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Result;
use App\Models\Student;
use App\Models\Course;

class ResultFactory extends Factory
{
    protected $model = Result::class;

    public function definition()
    {
        return [
            'student_id' => Student::factory(),
            'course_id' => Course::factory(),
            'semester' => 'Spring 2024',
            'activity_type' => $this->faker->randomElement(['Daily Activity', 'Quiz', 'Test']),
            'title' => $this->faker->sentence,
            'date' => $this->faker->date,
            'score' => $this->faker->randomFloat(2, 50, 100),
            'amount' => $this->faker->randomFloat(2, 0, 50),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
