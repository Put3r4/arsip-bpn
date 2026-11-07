<?php

namespace App\Actions\ArchiveDocument;

use App\Models\ArchiveDocument;
use DomainException;

class DeleteArchiveDocument
{
    /**
     * Hapus dokumen menggunakan soft delete.
     * Sesuai aturan A5 butir 6:
     * - Ditolak jika dokumen memiliki peminjaman aktif.
     */
    public function execute(ArchiveDocument $document): void
    {
        if ($document->hasActiveLoan()) {
            throw new DomainException('Dokumen tidak dapat dihapus karena sedang dalam peminjaman aktif.');
        }

        $document->delete();
    }
}
