<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $json = File::get(__DIR__ . '/data/sections.json');
        $sections = json_decode($json, true);

        foreach ($sections as $item) {
            Section::updateOrCreate(
                ['id' => $item['id']],
                ['name' => $item['name']]
            );
        }
    }
}
