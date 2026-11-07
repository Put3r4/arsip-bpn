<?php

namespace App\Policies;

use App\Models\DocumentType;
use App\Models\User;

class DocumentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageArchives();
    }

    public function view(User $user, DocumentType $documentType): bool
    {
        return $user->canManageArchives();
    }

    public function create(User $user): bool
    {
        return $user->canManageArchives();
    }

    public function update(User $user, DocumentType $documentType): bool
    {
        return $user->canManageArchives();
    }

    public function delete(User $user, DocumentType $documentType): bool
    {
        if (! $user->canManageArchives()) {
            return false;
        }

        if ($documentType->archiveDocuments()->exists()) {
            return false;
        }

        return true;
    }
}
