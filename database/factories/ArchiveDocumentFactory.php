<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\ArchiveDocument;
use App\Models\District;
use App\Models\DocumentType;
use App\Models\LandRightType;
use App\Models\StorageRack;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArchiveDocument>
 */
class ArchiveDocumentFactory extends Factory
{
    public function definition(): array
    {
        $district = District::first() ?? District::factory()->create();
        $village = Village::where('district_id', $district->id)->first() ?? Village::factory()->create(['district_id' => $district->id]);

        return [
            'document_number' => fake()->unique()->numerify('#####'),
            'year' => (string) fake()->numberBetween(2000, 2026),
            'document_type_id' => DocumentType::factory(),
            'land_right_type_id' => LandRightType::factory(),
            'storage_rack_id' => StorageRack::factory(),
            'range_number' => fake()->numerify('##-##'),
            'district_id' => $district->id,
            'village_id' => $village->id,
            'status' => DocumentStatus::AVAILABLE,
            'file_path' => null,
            'original_filename' => null,
            'file_size' => null,
            'mime_type' => null,
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::AVAILABLE,
        ]);
    }

    public function booked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::BOOKED,
        ]);
    }

    public function borrowed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::BORROWED,
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::LOST,
        ]);
    }

    public function invalid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::INVALID,
        ]);
    }
}
