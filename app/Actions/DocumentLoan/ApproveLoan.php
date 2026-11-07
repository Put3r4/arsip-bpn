<?php

namespace App\Actions\DocumentLoan;

use App\Enums\DocumentStatus;
use App\Enums\LoanStatus;
use App\Models\ArchiveDocument;
use App\Models\DocumentLoan;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ApproveLoan
{
    /**
     * Menyetujui peminjaman berkas (ACC) dan penyerahan fisik warkah.
     * Sesuai A5 butir 2: pending -> borrowed, mengisi approved_by dan approved_at,
     * status dokumen menjadi borrowed, dan return_date TETAP NULL (perbaikan bug CI3).
     *
     * @throws DomainException
     */
    public function execute(DocumentLoan $loan, User $approver): DocumentLoan
    {
        return DB::transaction(function () use ($loan, $approver) {
            /** @var DocumentLoan $lockedLoan */
            $lockedLoan = DocumentLoan::where('id', $loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLoan->status !== LoanStatus::PENDING) {
                throw new DomainException(
                    "Hanya permohonan peminjaman berstatus pending yang dapat disetujui."
                );
            }

            /** @var ArchiveDocument $lockedDoc */
            $lockedDoc = ArchiveDocument::where('id', $lockedLoan->archive_document_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Dokumen menjadi BORROWED
            $lockedDoc->update([
                'status' => DocumentStatus::BORROWED,
            ]);

            // Loan menjadi BORROWED, return_date TETAP NULL
            $lockedLoan->update([
                'status' => LoanStatus::BORROWED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'return_date' => null,
            ]);

            return $lockedLoan->fresh(['archiveDocument', 'borrower', 'serviceActivity', 'approver']);
        });
    }
}
