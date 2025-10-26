<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\TimeSlot;

class TimeSlotFactory extends Factory
{
    protected $model = TimeSlot::class;

    public function definition()
    {
        return [
            'start_time' => $this->faker->time,
            'end_time' => $this->faker->time,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
