<?php

namespace App\Actions\LandRightType;

use App\Models\LandRightType;
use DomainException;

class DeleteLandRightType
{
    public function execute(LandRightType $landRightType): void
    {
        if ($landRightType->archiveDocuments()->exists()) {
            throw new DomainException('Jenis hak tanah tidak dapat dihapus karena masih digunakan oleh dokumen arsip.');
        }

        $landRightType->delete();
    }
}
