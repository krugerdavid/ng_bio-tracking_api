<?php

namespace App\Actions;

use App\Enums\UserStatus;
use App\Models\Member;
use Illuminate\Validation\ValidationException;

class ApproveMemberAction implements Action
{
    public function execute(...$args): Member
    {
        [$member] = $args;

        $user = $member->user;

        if (! $user) {
            throw ValidationException::withMessages([
                'member' => ['Este miembro no tiene una cuenta de usuario vinculada.'],
            ]);
        }

        if ($user->status !== UserStatus::Pending) {
            throw ValidationException::withMessages([
                'member' => ['Este registro no está pendiente de aprobación.'],
            ]);
        }

        $user->update(['status' => UserStatus::Active->value]);

        return $member->fresh(['user']);
    }
}
