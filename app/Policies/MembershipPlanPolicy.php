<?php

namespace App\Policies;

use App\Models\MembershipPlan;
use App\Models\User;

class MembershipPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MembershipPlan $plan): bool
    {
        if ($user->canAccessAllMembers()) {
            return true;
        }
        return $user->member && $user->member->id === $plan->member_id;
    }

    public function create(User $user): bool
    {
        return $user->canAccessAllMembers();
    }

    public function update(User $user, MembershipPlan $plan): bool
    {
        if ($user->canAccessAllMembers()) {
            return true;
        }
        return $user->member && $user->member->id === $plan->member_id;
    }

    public function delete(User $user, MembershipPlan $plan): bool
    {
        return $user->canAccessAllMembers();
    }
}
