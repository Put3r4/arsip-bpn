<?php

namespace App\Actions\DocumentType;

use App\Models\DocumentType;

class CreateDocumentType
{
    /**
     * @param array{name: string} $data
     */
    public function execute(array $data): DocumentType
    {
        return DocumentType::create([
            'name' => trim($data['name']),
        ]);
    }
}
