<?php

namespace App\Policies;

use App\Models\ServiceActivity;
use App\Models\User;

class ServiceActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageArchives();
    }

    public function view(User $user, ServiceActivity $serviceActivity): bool
    {
        return $user->canManageArchives();
    }

    public function create(User $user): bool
    {
        return $user->canManageArchives();
    }

    public function update(User $user, ServiceActivity $serviceActivity): bool
    {
        return $user->canManageArchives();
    }

    public function delete(User $user, ServiceActivity $serviceActivity): bool
    {
        if (! $user->canManageArchives()) {
            return false;
        }

        if ($serviceActivity->loans()->exists()) {
            return false;
        }

        return true;
    }
}
