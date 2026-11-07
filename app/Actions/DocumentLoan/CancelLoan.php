<?php

namespace App\Actions\DocumentLoan;

use App\Enums\DocumentStatus;
use App\Enums\LoanStatus;
use App\Models\ArchiveDocument;
use App\Models\DocumentLoan;
use DomainException;
use Illuminate\Support\Facades\DB;

class CancelLoan
{
    /**
     * Membatalkan permohonan booking peminjaman.
     * Sesuai A5 butir 4: hanya untuk pending, status menjadi cancelled, cancelled_at terisi,
     * status dokumen kembali available. Peminjaman borrowed tidak bisa dibatalkan (harus return).
     * Tidak ada hard delete.
     *
     * @throws DomainException
     */
    public function execute(DocumentLoan $loan): DocumentLoan
    {
        return DB::transaction(function () use ($loan) {
            /** @var DocumentLoan $lockedLoan */
            $lockedLoan = DocumentLoan::where('id', $loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLoan->status === LoanStatus::BORROWED) {
                throw new DomainException(
                    "Peminjaman yang sedang berjalan (dipinjam) tidak dapat dibatalkan, berkas harus melalui proses pengembalian."
                );
            }

            if ($lockedLoan->status !== LoanStatus::PENDING) {
                throw new DomainException(
                    "Hanya permohonan peminjaman berstatus booking (pending) yang dapat dibatalkan."
                );
            }

            /** @var ArchiveDocument $lockedDoc */
            $lockedDoc = ArchiveDocument::where('id', $lockedLoan->archive_document_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Dokumen kembali AVAILABLE
            $lockedDoc->update([
                'status' => DocumentStatus::AVAILABLE,
            ]);

            // Loan menjadi CANCELLED
            $lockedLoan->update([
                'status' => LoanStatus::CANCELLED,
                'cancelled_at' => now(),
            ]);

            return $lockedLoan->fresh(['archiveDocument', 'borrower', 'serviceActivity']);
        });
    }
}
