<?php

namespace Database\Factories;

use App\Models\StorageRack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StorageRack>
 */
class StorageRackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Rak ' . fake()->unique()->numerify('###'),
        ];
    }
}
