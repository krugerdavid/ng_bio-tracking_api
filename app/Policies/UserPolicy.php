<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Root and admin can list users.
     */
    public function viewAny(User $user): bool
    {
        return $user->isRoot() || $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isRoot() || $user->isAdmin();
    }

    /**
     * Root and admin can create users. Only root can create admin role (checked in controller).
     */
    public function create(User $user): bool
    {
        return $user->isRoot() || $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isRoot() || $user->isAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isRoot() || $user->isAdmin();
    }
}
