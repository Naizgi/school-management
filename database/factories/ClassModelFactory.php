<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ClassModel;

class ClassModelFactory extends Factory
{
    protected $model = ClassModel::class;

    public function definition()
    {
        return [
            'class_name' => $this->faker->word,
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
