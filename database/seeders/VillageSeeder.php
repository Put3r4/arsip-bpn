<?php

namespace Database\Seeders;

use App\Models\Village;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class VillageSeeder extends Seeder
{
    public function run(): void
    {
        $json = File::get(__DIR__ . '/data/villages.json');
        $villages = json_decode($json, true);

        foreach ($villages as $item) {
            Village::updateOrCreate(
                ['id' => $item['id']],
                [
                    'district_id' => $item['district_id'],
                    'name' => $item['name'],
                ]
            );
        }
    }
}
