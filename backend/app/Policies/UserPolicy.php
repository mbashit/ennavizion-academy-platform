<?php

namespace App\Policies;

use App\Models\User;

/**
 * Client admins manage their own staff logins; developer accounts are off-limits to them.
 * UserResource also filters developers out of the list for non-developers, so they never
 * even see one — these checks back that up for direct URLs and actions.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, User $model): bool
    {
        return $user->is_developer || ! $model->is_developer;
    }

    public function update(User $user, User $model): bool
    {
        return $this->view($user, $model);
    }

    // Nobody deletes their own account from inside the panel (avoids locking yourself out).
    public function delete(User $user, User $model): bool
    {
        return $this->view($user, $model) && $user->isNot($model);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
