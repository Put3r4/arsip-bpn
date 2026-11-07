<?php

namespace Database\Seeders;

use App\Models\LandRightType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class LandRightTypeSeeder extends Seeder
{
    public function run(): void
    {
        $json = File::get(__DIR__ . '/data/land_right_types.json');
        $types = json_decode($json, true);

        foreach ($types as $item) {
            LandRightType::updateOrCreate(
                ['id' => $item['id']],
                ['name' => $item['name']]
            );
        }
    }
}
