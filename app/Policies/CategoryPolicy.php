<?php

namespace App\Policies;

use App\Models\User;

/**
 * Category management is an admin-only area.
 */
class CategoryPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : false;
    }
}
