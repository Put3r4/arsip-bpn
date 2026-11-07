<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $json = File::get(__DIR__ . '/data/document_types.json');
        $types = json_decode($json, true);

        foreach ($types as $item) {
            DocumentType::updateOrCreate(
                ['id' => $item['id']],
                ['name' => $item['name']]
            );
        }
    }
}
