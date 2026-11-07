<?php

namespace App\Policies;

use App\Models\StorageRack;
use App\Models\User;

class StorageRackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageArchives();
    }

    public function view(User $user, StorageRack $storageRack): bool
    {
        return $user->canManageArchives();
    }

    public function create(User $user): bool
    {
        return $user->canManageArchives();
    }

    public function update(User $user, StorageRack $storageRack): bool
    {
        return $user->canManageArchives();
    }

    public function delete(User $user, StorageRack $storageRack): bool
    {
        if (! $user->canManageArchives()) {
            return false;
        }

        if ($storageRack->archiveDocuments()->exists()) {
            return false;
        }

        return true;
    }
}
