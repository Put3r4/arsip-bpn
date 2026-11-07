<?php

namespace App\Actions\User;

use App\Models\User;
use DomainException;

class DeleteUser
{
    public function execute(User $actor, User $target): void
    {
        if ($actor->id === $target->id) {
            throw new DomainException('Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($target->isSuperAdmin() && $target->is_active) {
            $activeSuperAdminCount = User::where('role', \App\Enums\UserRole::SUPER_ADMIN)
                ->where('is_active', true)
                ->count();

            if ($activeSuperAdminCount <= 1) {
                throw new DomainException('Tidak dapat menghapus satu-satunya Super Admin aktif yang tersisa.');
            }
        }

        if ($target->loans()->exists() || $target->approvedLoans()->exists() || $target->receivedLoans()->exists()) {
            throw new DomainException('Pengguna yang memiliki riwayat peminjaman tidak dapat dihapus. Silakan nonaktifkan akun.');
        }

        $target->delete();
    }
}
