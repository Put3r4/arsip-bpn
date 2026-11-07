<?php

namespace Database\Factories;

use App\Models\ServiceActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceActivity>
 */
class ServiceActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(2),
        ];
    }
}
