<?php

namespace App\Policies;

use App\Models\LandRightType;
use App\Models\User;

class LandRightTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageArchives();
    }

    public function view(User $user, LandRightType $landRightType): bool
    {
        return $user->canManageArchives();
    }

    public function create(User $user): bool
    {
        return $user->canManageArchives();
    }

    public function update(User $user, LandRightType $landRightType): bool
    {
        return $user->canManageArchives();
    }

    public function delete(User $user, LandRightType $landRightType): bool
    {
        if (! $user->canManageArchives()) {
            return false;
        }

        if ($landRightType->archiveDocuments()->exists()) {
            return false;
        }

        return true;
    }
}
