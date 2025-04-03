<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Course;
use App\Models\ClassModel;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition()
    {
        return [
            'course_name' => $this->faker->word,
            'class_id' => ClassModel::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
