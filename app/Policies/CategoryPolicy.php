<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

/**
 * Category management is admin-only. Routes are also guarded by the
 * `role:admin` middleware; this policy is the defence-in-depth layer.
 */
class CategoryPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : false;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }
}
