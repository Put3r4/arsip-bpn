<?php

namespace Database\Factories;

use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->numerify('5204###'),
            'regency_id' => '5204',
            'name' => fake()->city(),
        ];
    }
}
