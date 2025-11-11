<?php

namespace App\Policies;

use App\Models\Template;
use App\Models\User;

class TemplatePolicy
{
    /**
     * Determine if the user can view any templates
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the template
     */
    public function view(User $user, Template $template): bool
    {
        return $template->user_id === $user->id;
    }

    /**
     * Determine if the user can create templates
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can update the template
     */
    public function update(User $user, Template $template): bool
    {
        return $template->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the template
     */
    public function delete(User $user, Template $template): bool
    {
        return $template->user_id === $user->id;
    }
}
