<?php

namespace Database\Seeders;

use App\Models\StorageRack;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class StorageRackSeeder extends Seeder
{
    public function run(): void
    {
        $json = File::get(__DIR__ . '/data/storage_racks.json');
        $storageRacks = json_decode($json, true);

        foreach ($storageRacks as $item) {
            StorageRack::updateOrCreate(
                ['id' => $item['id']],
                ['name' => $item['name']]
            );
        }
    }
}
