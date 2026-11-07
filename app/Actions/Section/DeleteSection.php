<?php

namespace App\Actions\Section;

use App\Models\Section;
use DomainException;

class DeleteSection
{
    public function execute(Section $section): void
    {
        if ($section->users()->exists()) {
            throw new DomainException('Seksi tidak dapat dihapus karena masih digunakan oleh pengguna.');
        }

        $section->delete();
    }
}
