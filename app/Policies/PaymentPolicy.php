<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->canAccessAllMembers()) {
            return true;
        }
        return $user->member && $user->member->id === $payment->member_id;
    }

    public function create(User $user): bool
    {
        return $user->canAccessAllMembers();
    }

    public function update(User $user, Payment $payment): bool
    {
        if ($user->canAccessAllMembers()) {
            return true;
        }
        return $user->member && $user->member->id === $payment->member_id;
    }

    public function delete(User $user, Payment $payment): bool
    {
        if ($user->canAccessAllMembers()) {
            return true;
        }
        return $user->member && $user->member->id === $payment->member_id;
    }
}
