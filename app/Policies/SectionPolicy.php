<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

class SectionPolicy
{
    /**
     * Determine whether the user can view any sections.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can view the section.
     */
    public function view(User $user, Section $section): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create sections.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the section.
     */
    public function update(User $user, Section $section): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete the section.
     * Cannot delete section that still has users attached.
     */
    public function delete(User $user, Section $section): bool
    {
        if (! $user->isSuperAdmin()) {
            return false;
        }

        if ($section->users()->exists()) {
            return false;
        }

        return true;
    }
}
