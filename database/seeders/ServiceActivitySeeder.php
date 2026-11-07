<?php

namespace Database\Seeders;

use App\Models\ServiceActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ServiceActivitySeeder extends Seeder
{
    public function run(): void
    {
        $json = File::get(__DIR__ . '/data/service_activities.json');
        $activities = json_decode($json, true);

        foreach ($activities as $item) {
            ServiceActivity::updateOrCreate(
                ['id' => $item['id']],
                ['name' => $item['name']]
            );
        }
    }
}
