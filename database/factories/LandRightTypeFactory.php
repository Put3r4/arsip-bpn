<?php

namespace Database\Factories;

use App\Models\LandRightType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LandRightType>
 */
class LandRightTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->lexify('H??'),
        ];
    }
}
