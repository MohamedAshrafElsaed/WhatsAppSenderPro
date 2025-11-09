<?php

namespace App\Policies;

use App\Models\ContactImport;
use App\Models\User;


class ContactImportPolicy
{
    /**
     * Determine if the user can view the import
     */
    public function view(User $user, ContactImport $import): bool
    {
        return $import->user_id === $user->id;
    }

    /**
     * Determine if the user can update the import
     */
    public function update(User $user, ContactImport $import): bool
    {
        return $import->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the import
     */
    public function delete(User $user, ContactImport $import): bool
    {
        return $import->user_id === $user->id;
    }
}
