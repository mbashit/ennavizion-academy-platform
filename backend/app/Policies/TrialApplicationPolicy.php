<?php

namespace App\Policies;

use App\Models\TrialApplication;
use App\Models\User;

/** Parents see and withdraw only their own applications; staff review and update all of them. */
class TrialApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // the portal resource scopes the list to the parent's own applications
    }

    public function view(User $user, TrialApplication $application): bool
    {
        return $user->isStaff() || $application->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isParent() && $user->hasVerifiedEmail();
    }

    public function update(User $user, TrialApplication $application): bool
    {
        return $user->isStaff();
    }

    public function withdraw(User $user, TrialApplication $application): bool
    {
        return $user->isParent() && $application->user_id === $user->id && $application->isOpen();
    }

    public function delete(User $user, TrialApplication $application): bool
    {
        return $user->isStaff();
    }
}
