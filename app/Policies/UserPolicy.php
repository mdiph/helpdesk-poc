<?php

namespace App\Policies;

use App\Models\User;

/**
 * User management is an admin-only area. The only self-service action is
 * changing your own password (handled in ProfileController, not here).
 */
class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        // Let toggleActive() run so it can block self-deactivation.
        return $ability === 'toggleActive' ? null : true;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, User $model): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $model): bool
    {
        return false;
    }

    public function delete(User $user, User $model): bool
    {
        // Admins may disable accounts but never delete them (audit trail).
        return false;
    }

    /** An admin cannot disable or demote their own account. */
    public function toggleActive(User $user, User $model): bool
    {
        return $user->id !== $model->id;
    }
}
