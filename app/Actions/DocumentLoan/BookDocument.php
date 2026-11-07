<?php

namespace App\Actions\DocumentLoan;

use App\Enums\DocumentStatus;
use App\Enums\LoanStatus;
use App\Models\ArchiveDocument;
use App\Models\DocumentLoan;
use DomainException;
use Illuminate\Support\Facades\DB;

class BookDocument
{
    /**
     * Mendaftarkan pengajuan booking dokumen arsip.
     * Sesuai A5 butir 1: DB::transaction dengan lockForUpdate pada baris dokumen,
     * status dicek ulang di dalam transaksi untuk mencegah double booking.
     *
     * @param array<string, mixed> $data
     * @throws DomainException
     */
    public function execute(array $data): DocumentLoan
    {
        return DB::transaction(function () use ($data) {
            /** @var ArchiveDocument $document */
            $document = ArchiveDocument::where('id', $data['archive_document_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Pengecekan status ketersediaan dokumen
            if ($document->status !== DocumentStatus::AVAILABLE) {
                throw new DomainException(
                    "Dokumen tidak tersedia untuk dipinjam (status: {$document->status->label()})."
                );
            }

            // Pengecekan penjagaan pinjaman aktif
            if ($document->hasActiveLoan()) {
                throw new DomainException('Dokumen sedang memiliki transaksi peminjaman aktif.');
            }

            // Perbarui status dokumen menjadi BOOKED
            $document->update([
                'status' => DocumentStatus::BOOKED,
            ]);

            // Buat transaksi peminjaman berstatus PENDING
            return DocumentLoan::create([
                'archive_document_id' => $document->id,
                'borrower_id' => $data['borrower_id'],
                'service_activity_id' => $data['service_activity_id'],
                'loan_date' => now()->toDateString(),
                'return_date' => null,
                'status' => LoanStatus::PENDING,
                'notes' => isset($data['notes']) && trim((string) $data['notes']) !== ''
                    ? trim((string) $data['notes'])
                    : null,
                'approved_by' => null,
                'approved_at' => null,
                'received_by' => null,
                'cancelled_at' => null,
            ]);
        });
    }
}
