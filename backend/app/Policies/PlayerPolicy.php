<?php

namespace App\Policies;

use App\Models\Player;
use App\Models\User;

/** Parents manage only their own children; staff can view players (read-only). */
class PlayerPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // lists are scoped to the parent's own players in the portal resource
    }

    public function view(User $user, Player $player): bool
    {
        return $user->isStaff() || $player->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isParent() && $user->hasVerifiedEmail();
    }

    public function update(User $user, Player $player): bool
    {
        return $user->isParent() && $player->user_id === $user->id;
    }

    public function delete(User $user, Player $player): bool
    {
        // A child with an application stays on record; the parent can withdraw the application.
        return $user->isParent() && $player->user_id === $user->id && ! $player->applications()->exists();
    }
}
