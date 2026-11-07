<?php

namespace App\Policies;

use App\Models\ArchiveDocument;
use App\Models\User;

class ArchiveDocumentPolicy
{
    /**
     * Seluruh pengguna terotentikasi dapat melihat katalog berkas.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Seluruh pengguna terotentikasi dapat melihat detail berkas.
     */
    public function view(User $user, ArchiveDocument $archiveDocument): bool
    {
        return true;
    }

    /**
     * Seluruh pengguna terotentikasi berhak melihat pratinjau scan file.
     */
    public function viewFile(User $user, ArchiveDocument $archiveDocument): bool
    {
        return true;
    }

    /**
     * Hanya staf arsip (super_admin dan archivist) yang berhak menambah berkas.
     */
    public function create(User $user): bool
    {
        return $user->canManageArchives();
    }

    /**
     * Hanya staf arsip yang berhak mengubah berkas.
     */
    public function update(User $user, ArchiveDocument $archiveDocument): bool
    {
        return $user->canManageArchives();
    }

    /**
     * Mengubah status fisik hanya oleh staf arsip dan dokumen tidak sedang dalam peminjaman aktif.
     */
    public function updateStatus(User $user, ArchiveDocument $archiveDocument): bool
    {
        if (! $user->canManageArchives()) {
            return false;
        }

        if ($archiveDocument->hasActiveLoan()) {
            return false;
        }

        return true;
    }

    /**
     * Menghapus dokumen hanya oleh staf arsip dan dokumen tidak sedang dalam peminjaman aktif.
     */
    public function delete(User $user, ArchiveDocument $archiveDocument): bool
    {
        if (! $user->canManageArchives()) {
            return false;
        }

        if ($archiveDocument->hasActiveLoan()) {
            return false;
        }

        return true;
    }
}
