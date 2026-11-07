<?php

namespace App\Actions\DocumentType;

use App\Models\DocumentType;
use DomainException;

class DeleteDocumentType
{
    public function execute(DocumentType $documentType): void
    {
        if ($documentType->archiveDocuments()->exists()) {
            throw new DomainException('Jenis berkas tidak dapat dihapus karena masih digunakan oleh dokumen arsip.');
        }

        $documentType->delete();
    }
}
