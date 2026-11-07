<?php

namespace App\Actions\User;

use App\Enums\UserRole;
use App\Models\User;
use DomainException;

class UpdateUser
{
    /**
     * @param array<string, mixed> $data
     */
    public function execute(User $user, array $data): User
    {
        $isCurrentlyActiveSuperAdmin = $user->isSuperAdmin() && $user->is_active;

        $newRole = $data['role'] instanceof UserRole ? $data['role'] : UserRole::tryFrom($data['role']);
        $newIsActive = (bool) $data['is_active'];
        $willRemainActiveSuperAdmin = $newRole === UserRole::SUPER_ADMIN && $newIsActive;

        if ($isCurrentlyActiveSuperAdmin && ! $willRemainActiveSuperAdmin) {
            $activeSuperAdminCount = User::where('role', UserRole::SUPER_ADMIN)
                ->where('is_active', true)
                ->count();

            if ($activeSuperAdminCount <= 1) {
                throw new DomainException('Tidak dapat menonaktifkan atau mengubah peran satu-satunya Super Admin aktif yang tersisa.');
            }
        }

        $attributes = [
            'name' => $data['name'],
            'username' => $data['username'],
            'nip' => $data['nip'] ?? null,
            'email' => $data['email'] ?? null,
            'role' => $data['role'],
            'section_id' => $data['section_id'] ?? null,
            'is_active' => (bool) $data['is_active'],
        ];

        $user->update($attributes);

        return $user;
    }
}
