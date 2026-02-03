<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Only root can list users.
     */
    public function viewAny(User $user): bool
    {
        return $user->isRoot();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isRoot();
    }

    /**
     * Only root can create users; role must be admin or member.
     */
    public function create(User $user): bool
    {
        return $user->isRoot();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isRoot();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isRoot();
    }
}
