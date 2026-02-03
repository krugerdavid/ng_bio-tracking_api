<?php

namespace App\Policies;

use App\Models\Bioimpedance;
use App\Models\User;

class BioimpedancePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Bioimpedance $bioimpedance): bool
    {
        if ($user->canAccessAllMembers()) {
            return true;
        }
        return $user->member && $user->member->id === $bioimpedance->member_id;
    }

    public function create(User $user): bool
    {
        return $user->canAccessAllMembers();
    }

    public function update(User $user, Bioimpedance $bioimpedance): bool
    {
        if ($user->canAccessAllMembers()) {
            return true;
        }
        return $user->member && $user->member->id === $bioimpedance->member_id;
    }

    public function delete(User $user, Bioimpedance $bioimpedance): bool
    {
        if ($user->canAccessAllMembers()) {
            return true;
        }
        return $user->member && $user->member->id === $bioimpedance->member_id;
    }
}
