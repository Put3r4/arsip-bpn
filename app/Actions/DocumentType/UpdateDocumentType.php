<?php

namespace App\Actions\DocumentType;

use App\Models\DocumentType;

class UpdateDocumentType
{
    /**
     * @param array{name: string} $data
     */
    public function execute(DocumentType $documentType, array $data): DocumentType
    {
        $documentType->update([
            'name' => trim($data['name']),
        ]);

        return $documentType;
    }
}
