<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $json = File::get(__DIR__ . '/data/districts.json');
        $districts = json_decode($json, true);

        foreach ($districts as $item) {
            District::updateOrCreate(
                ['id' => $item['id']],
                [
                    'regency_id' => $item['regency_id'] ?? '5204',
                    'name' => $item['name'],
                ]
            );
        }
    }
}
