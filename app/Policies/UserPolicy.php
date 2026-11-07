<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view a specific user.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can reset the password of the user.
     */
    public function resetPassword(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete the user.
     * Cannot delete self, and cannot delete if user has any loan history.
     */
    public function delete(User $user, User $model): bool
    {
        if (! $user->isSuperAdmin()) {
            return false;
        }

        // Cannot delete self
        if ($user->id === $model->id) {
            return false;
        }

        // Cannot delete the last active super admin
        if ($model->isSuperAdmin() && $model->is_active) {
            $activeSuperAdminCount = User::where('role', \App\Enums\UserRole::SUPER_ADMIN)
                ->where('is_active', true)
                ->count();

            if ($activeSuperAdminCount <= 1) {
                return false;
            }
        }

        // Cannot delete user with loan history (borrowed, approved, or received)
        if ($model->loans()->exists() || $model->approvedLoans()->exists() || $model->receivedLoans()->exists()) {
            return false;
        }

        return true;
    }
}
