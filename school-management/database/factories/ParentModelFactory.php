<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ParentModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParentModel>
 */
class ParentModelFactory extends Factory
{
    protected $model = ParentModel::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
