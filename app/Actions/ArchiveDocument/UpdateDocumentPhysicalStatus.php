<?php

namespace App\Actions\ArchiveDocument;

use App\Enums\DocumentStatus;
use App\Models\ArchiveDocument;
use DomainException;

class UpdateDocumentPhysicalStatus
{
    /**
     * Ubah status fisik dokumen arsip secara manual.
     * Sesuai aturan A5 butir 5:
     * - Hanya ke available, lost, atau invalid.
     * - Ditolak jika dokumen memiliki peminjaman aktif (pending atau borrowed).
     */
    public function execute(ArchiveDocument $document, DocumentStatus|string $status): ArchiveDocument
    {
        $targetStatus = $status instanceof DocumentStatus ? $status : DocumentStatus::from($status);

        $allowedStatuses = [
            DocumentStatus::AVAILABLE,
            DocumentStatus::LOST,
            DocumentStatus::INVALID,
        ];

        if (! in_array($targetStatus, $allowedStatuses, true)) {
            throw new DomainException('Status fisik dokumen hanya dapat diubah ke status: tersedia (available), hilang (lost), atau rusak/tidak sah (invalid).');
        }

        if ($document->hasActiveLoan()) {
            throw new DomainException('Status fisik dokumen tidak dapat diubah karena sedang dalam peminjaman aktif.');
        }

        $document->update([
            'status' => $targetStatus,
        ]);

        return $document;
    }
}
