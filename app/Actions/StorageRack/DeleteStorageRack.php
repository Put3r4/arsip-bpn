<?php

namespace App\Actions\StorageRack;

use App\Models\StorageRack;
use DomainException;

class DeleteStorageRack
{
    public function execute(StorageRack $storageRack): void
    {
        if ($storageRack->archiveDocuments()->exists()) {
            throw new DomainException('Rak penyimpanan tidak dapat dihapus karena masih digunakan oleh dokumen arsip.');
        }

        $storageRack->delete();
    }
}
