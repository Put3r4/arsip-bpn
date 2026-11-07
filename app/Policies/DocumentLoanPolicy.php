<?php

namespace App\Policies;

use App\Models\DocumentLoan;
use App\Models\User;

class DocumentLoanPolicy
{
    /**
     * Seluruh pengguna terotentikasi berhak melihat halaman daftar peminjaman.
     * (Staf melihat seluruh data se-kantor, pegawai hanya miliknya via controller filter).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Staf dapat melihat detail peminjaman apa pun, sedangkan pegawai hanya miliknya.
     */
    public function view(User $user, DocumentLoan $loan): bool
    {
        if ($user->canManageArchives()) {
            return true;
        }

        return $user->id === $loan->borrower_id;
    }

    /**
     * Semua pengguna terotentikasi berhak melakukan booking (peminjaman).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Hanya staf arsip (super_admin dan archivist) yang boleh booking atas nama pegawai lain.
     */
    public function bookForOther(User $user): bool
    {
        return $user->canManageArchives();
    }

    /**
     * Menyetujui peminjaman (ACC) hanya boleh dilakukan oleh staf arsip.
     */
    public function approve(User $user, DocumentLoan $loan): bool
    {
        return $user->canManageArchives();
    }

    /**
     * Memproses pengembalian fisik warkah hanya boleh dilakukan oleh staf arsip.
     */
    public function return(User $user, DocumentLoan $loan): bool
    {
        return $user->canManageArchives();
    }

    /**
     * Membatalkan booking pending:
     * - Staf arsip dapat membatalkan semua booking pending.
     * - Pegawai hanya dapat membatalkan booking pending miliknya sendiri.
     */
    public function cancel(User $user, DocumentLoan $loan): bool
    {
        if ($user->canManageArchives()) {
            return true;
        }

        return $user->id === $loan->borrower_id;
    }

    /**
     * Melihat riwayat peminjam per dokumen (jejak audit) hanya boleh diakses staf arsip.
     */
    public function viewDocumentHistory(User $user): bool
    {
        return $user->canManageArchives();
    }
}
