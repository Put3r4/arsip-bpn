<?php

namespace Database\Factories;

use App\Models\District;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Village>
 */
class VillageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->numerify('5204######'),
            'district_id' => District::factory(),
            'name' => fake()->streetName(),
        ];
    }
}
