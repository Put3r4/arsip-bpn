<?php

namespace App\Actions\DocumentLoan;

use App\Enums\DocumentStatus;
use App\Enums\LoanStatus;
use App\Models\ArchiveDocument;
use App\Models\DocumentLoan;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReturnLoan
{
    /**
     * Memproses pengembalian berkas fisik ke rak arsip.
     * Sesuai A5 butir 3: borrowed -> returned, mengisi return_date hari ini dan received_by,
     * status dokumen kembali menjadi available.
     *
     * @throws DomainException
     */
    public function execute(DocumentLoan $loan, User $receiver): DocumentLoan
    {
        return DB::transaction(function () use ($loan, $receiver) {
            /** @var DocumentLoan $lockedLoan */
            $lockedLoan = DocumentLoan::where('id', $loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLoan->status !== LoanStatus::BORROWED) {
                throw new DomainException(
                    "Hanya peminjaman berstatus dipinjam (borrowed) yang dapat diproses pengembaliannya."
                );
            }

            /** @var ArchiveDocument $lockedDoc */
            $lockedDoc = ArchiveDocument::where('id', $lockedLoan->archive_document_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Dokumen kembali AVAILABLE di rak
            $lockedDoc->update([
                'status' => DocumentStatus::AVAILABLE,
            ]);

            // Loan menjadi RETURNED
            $lockedLoan->update([
                'status' => LoanStatus::RETURNED,
                'received_by' => $receiver->id,
                'return_date' => now()->toDateString(),
            ]);

            return $lockedLoan->fresh(['archiveDocument', 'borrower', 'serviceActivity', 'receiver']);
        });
    }
}
