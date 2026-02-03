<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    /**
     * Root and admin see all; member sees only their own (via scope in controller).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Member $member): bool
    {
        if ($user->canAccessAllMembers()) {
            return true;
        }
        return $user->member?->id === $member->id;
    }

    /**
     * Only root and admin can create members.
     */
    public function create(User $user): bool
    {
        return $user->canAccessAllMembers();
    }

    public function update(User $user, Member $member): bool
    {
        if ($user->canAccessAllMembers()) {
            return true;
        }
        return $user->member?->id === $member->id;
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->canAccessAllMembers();
    }
}
